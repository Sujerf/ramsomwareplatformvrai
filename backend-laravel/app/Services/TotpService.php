<?php

namespace App\Services;

use RuntimeException;

class TotpService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const DIGITS       = 6;
    private const PERIOD       = 30;

    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    public function qrCodeUri(string $issuer, string $accountName, string $secret): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Vérifie un code TOTP avec une fenêtre de ±1 période (tolérance horloge 30s).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $key = $this->base32Decode($secret);
        $t   = (int) floor(time() / self::PERIOD);

        for ($i = $t - $window; $i <= $t + $window; $i++) {
            if (hash_equals($this->hotp($key, $i), $code)) {
                return true;
            }
        }
        return false;
    }

    // ── private helpers ────────────────────────────────────────────────────────

    private function hotp(string $key, int $counter): string
    {
        $msg    = pack('NN', 0, $counter);   // 8 bytes big-endian
        $hash   = hash_hmac('sha1', $msg, $key, true);   // 20 bytes
        $offset = ord($hash[19]) & 0x0f;

        $code = (
            ((ord($hash[$offset])     & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8)  |
             (ord($hash[$offset + 3]) & 0xff)
        ) % 1_000_000;

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $input  = strtoupper(str_replace('=', '', $input));
        $output = '';
        $buffer = 0;
        $bits   = 0;

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos(self::BASE32_CHARS, $input[$i]);
            if ($val === false) {
                throw new RuntimeException("Invalid base32 character: {$input[$i]}");
            }
            $buffer = ($buffer << 5) | $val;
            $bits  += 5;
            if ($bits >= 8) {
                $bits   -= 8;
                $output .= chr(($buffer >> $bits) & 0xff);
            }
        }
        return $output;
    }
}
