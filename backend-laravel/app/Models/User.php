<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'password'                   => 'hashed',
            'two_factor_confirmed_at'    => 'datetime',
            'two_factor_recovery_codes'  => 'encrypted:array',
            'last_login_at'              => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    // ── Codes de secours ─────────────────────────────────────────────────────

    public function generateRecoveryCodes(): array
    {
        $plain = [];
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $code   = strtoupper(bin2hex(random_bytes(3))).'-'.strtoupper(bin2hex(random_bytes(3)));
            $plain[] = $code;
            $codes[] = ['code' => $code, 'used' => false];
        }

        $this->update(['two_factor_recovery_codes' => $codes]);

        return $plain;
    }

    public function recoveryCodesRemaining(): int
    {
        $codes = $this->two_factor_recovery_codes ?? [];

        return count(array_filter($codes, fn ($c) => ! $c['used']));
    }

    public function consumeRecoveryCode(string $input): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $input = strtoupper(trim($input));

        foreach ($codes as &$entry) {
            if (! $entry['used'] && strtoupper($entry['code']) === $input) {
                $entry['used'] = true;
                $this->update(['two_factor_recovery_codes' => $codes]);

                return true;
            }
        }

        return false;
    }
}
