<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Identifie le fabricant et la catégorie d'un appareil depuis son adresse MAC (OUI).
 *
 * Stratégie en 3 niveaux :
 *   1. Liste locale curatée (>80 fabricants communs) — instantané, hors-ligne
 *   2. API macvendors.com (gratuite) — couvre tous les OUI IEEE
 *   3. Dégradation gracieuse — retourne 'unknown' si tout échoue
 *
 * Catégories retournées :
 *   mobile        — smartphones/tablettes (Xiaomi, OnePlus, Oppo, Realme, Samsung Mobile…)
 *   apple_device  — Apple Inc. (iPhone, iPad, Mac — indistingables par OUI seul)
 *   computer      — PC, laptop (Intel NIC, Dell, HP, Lenovo, Realtek…)
 *   router        — équipements réseau (TP-Link, Cisco, Ubiquiti, D-Link…)
 *   iot           — embarqué / domotique (Raspberry Pi, Espressif…)
 *   printer       — imprimantes (HP, Canon, Epson, Brother…)
 *   tv            — TV connectées (LG Electronics, Samsung TV, Sony TV…)
 *   unknown       — non identifié
 */
class MacVendorService
{
    private const CACHE_TTL_DAYS = 7;

    // ── Liste locale OUI → [fabricant, catégorie] ─────────────────────────────
    // Clé : 6 premiers caractères hex en majuscules SANS séparateurs
    // Priorisé sur l'API pour les fabricants communs (hors-ligne friendly)
    private const LOCAL_OUI = [
        // ─── APPLE (iPhones, iPads, MacBooks — catégorie commune "apple_device") ──
        '000393' => ['Apple, Inc.', 'apple_device'],
        '0050E4' => ['Apple, Inc.', 'apple_device'],
        '001124' => ['Apple, Inc.', 'apple_device'],
        '001451' => ['Apple, Inc.', 'apple_device'],
        '0023DF' => ['Apple, Inc.', 'apple_device'],
        '002500' => ['Apple, Inc.', 'apple_device'],
        '0C4DE9' => ['Apple, Inc.', 'apple_device'],
        '3C0754' => ['Apple, Inc.', 'apple_device'],
        '4C1D96' => ['Apple, Inc.', 'apple_device'],
        '4C3275' => ['Apple, Inc.', 'apple_device'],
        '4C8D79' => ['Apple, Inc.', 'apple_device'],
        '8C8EF2' => ['Apple, Inc.', 'apple_device'],
        '9C84BF' => ['Apple, Inc.', 'apple_device'],
        'A45E60' => ['Apple, Inc.', 'apple_device'],
        'A8862D' => ['Apple, Inc.', 'apple_device'],
        'AC29FA' => ['Apple, Inc.', 'apple_device'],
        'B4F0AB' => ['Apple, Inc.', 'apple_device'],
        'C82A14' => ['Apple, Inc.', 'apple_device'],
        'D0034B' => ['Apple, Inc.', 'apple_device'],
        'D49A20' => ['Apple, Inc.', 'apple_device'],
        'DC9B9C' => ['Apple, Inc.', 'apple_device'],
        'E45F01' => ['Apple, Inc.', 'apple_device'],   // aussi Raspberry Pi
        'F07960' => ['Apple, Inc.', 'apple_device'],
        'F4F15A' => ['Apple, Inc.', 'apple_device'],
        'F81EDF' => ['Apple, Inc.', 'apple_device'],

        // ─── SAMSUNG (phones, tablettes, TV) ──────────────────────────────────
        '002195' => ['Samsung Electronics', 'mobile'],
        '0018AF' => ['Samsung Electronics', 'mobile'],
        '001632' => ['Samsung Electronics', 'mobile'],
        '00218B' => ['Samsung Electronics', 'mobile'],
        '00E064' => ['Samsung Electronics', 'mobile'],
        '08D40C' => ['Samsung Electronics', 'mobile'],
        '0C8910' => ['Samsung Electronics', 'mobile'],
        '1C5A3E' => ['Samsung Electronics', 'mobile'],
        '2C0E3D' => ['Samsung Electronics', 'mobile'],
        '3001C7' => ['Samsung Electronics', 'mobile'],
        '44F459' => ['Samsung Electronics', 'mobile'],
        '4844F7' => ['Samsung Electronics', 'mobile'],
        '5C0A5B' => ['Samsung Electronics', 'mobile'],
        '600147' => ['Samsung Electronics', 'mobile'],
        '6C2F2C' => ['Samsung Electronics', 'mobile'],
        '70F927' => ['Samsung Electronics', 'mobile'],
        '8450AD' => ['Samsung Electronics', 'mobile'],
        '8CB5AD' => ['Samsung Electronics', 'mobile'],
        '8C71F8' => ['Samsung Electronics', 'mobile'],
        'B047BF' => ['Samsung Electronics', 'mobile'],
        'B8C111' => ['Samsung Electronics', 'mobile'],
        'C44201' => ['Samsung Electronics', 'mobile'],
        'CC07AB' => ['Samsung Electronics', 'mobile'],
        'D4880A' => ['Samsung Electronics', 'mobile'],
        'E47CF9' => ['Samsung Electronics', 'mobile'],

        // ─── XIAOMI ─────────────────────────────────────────────────────────────
        '286C07' => ['Xiaomi Communications', 'mobile'],
        '00EC0A' => ['Xiaomi Communications', 'mobile'],
        '50EC50' => ['Xiaomi Communications', 'mobile'],
        '640980' => ['Xiaomi Communications', 'mobile'],
        '7451BA' => ['Xiaomi Communications', 'mobile'],
        '7811DC' => ['Xiaomi Communications', 'mobile'],
        '98FAE3' => ['Xiaomi Communications', 'mobile'],
        'A45046' => ['Xiaomi Communications', 'mobile'],
        'AC2260' => ['Xiaomi Communications', 'mobile'],
        'B0E235' => ['Xiaomi Communications', 'mobile'],
        'F4606F' => ['Xiaomi Communications', 'mobile'],
        'F8A45F' => ['Xiaomi Communications', 'mobile'],
        '28E31F' => ['Xiaomi Communications', 'mobile'],

        // ─── HUAWEI ─────────────────────────────────────────────────────────────
        '00259E' => ['Huawei Technologies', 'mobile'],
        '00464B' => ['Huawei Technologies', 'mobile'],
        '0402CF' => ['Huawei Technologies', 'mobile'],
        '009ACD' => ['Huawei Technologies', 'mobile'],
        '00E0FC' => ['Huawei Technologies', 'mobile'],
        '086075' => ['Huawei Technologies', 'mobile'],
        '1C1DE0' => ['Huawei Technologies', 'mobile'],
        '3CB39D' => ['Huawei Technologies', 'mobile'],
        '40CB A0' => ['Huawei Technologies', 'mobile'],
        '4CBE0B' => ['Huawei Technologies', 'mobile'],
        '54514E' => ['Huawei Technologies', 'mobile'],
        '68A0F6' => ['Huawei Technologies', 'mobile'],
        '9017AC' => ['Huawei Technologies', 'mobile'],
        'BC764E' => ['Huawei Technologies', 'mobile'],
        'C8D15E' => ['Huawei Technologies', 'mobile'],
        'D05AB5' => ['Huawei Technologies', 'mobile'],
        'F4CB52' => ['Huawei Technologies', 'mobile'],

        // ─── OPPO ───────────────────────────────────────────────────────────────
        '001E42' => ['OPPO Electronics', 'mobile'],
        '044E06' => ['OPPO Electronics', 'mobile'],
        '9C28EF' => ['OPPO Electronics', 'mobile'],
        'A45BAB' => ['OPPO Electronics', 'mobile'],
        'B4B812' => ['OPPO Electronics', 'mobile'],
        'D83CE6' => ['OPPO Electronics', 'mobile'],

        // ─── VIVO ───────────────────────────────────────────────────────────────
        '00165B' => ['Vivo Mobile', 'mobile'],
        '1C7725' => ['Vivo Mobile', 'mobile'],
        'E44E2D' => ['Vivo Mobile', 'mobile'],
        'FC4765' => ['Vivo Mobile', 'mobile'],

        // ─── OnePlus ────────────────────────────────────────────────────────────
        '1C4D70' => ['OnePlus Technology', 'mobile'],
        '8CBEBE' => ['OnePlus Technology', 'mobile'],
        'AC4530' => ['OnePlus Technology', 'mobile'],

        // ─── REALME / BBK Electronics ────────────────────────────────────────
        '546CF5' => ['Realme (BBK)', 'mobile'],
        'E03603' => ['Realme (BBK)', 'mobile'],

        // ─── GOOGLE (Pixel, Chromecast) ─────────────────────────────────────
        '3C5AB4' => ['Google LLC', 'mobile'],
        '54605A' => ['Google LLC', 'mobile'],
        'A47DA1' => ['Google LLC', 'mobile'],
        'F88FCA' => ['Google LLC', 'mobile'],

        // ─── MOTOROLA ────────────────────────────────────────────────────────
        '000CE5' => ['Motorola (Lenovo)', 'mobile'],
        '9C93E4' => ['Motorola (Lenovo)', 'mobile'],
        'C8D3A3' => ['Motorola (Lenovo)', 'mobile'],

        // ─── LG Electronics (phones) ─────────────────────────────────────────
        '001295' => ['LG Electronics', 'mobile'],
        '3C8093' => ['LG Electronics', 'mobile'],
        'B47849' => ['LG Electronics', 'mobile'],
        'CC2D8C' => ['LG Electronics', 'mobile'],

        // ─── SONY MOBILE ─────────────────────────────────────────────────────
        '00EB2D' => ['Sony Mobile', 'mobile'],
        '40B8D0' => ['Sony Mobile', 'mobile'],
        '5C4CA9' => ['Sony Mobile', 'mobile'],
        'A8E0A0' => ['Sony Mobile', 'mobile'],

        // ─── NOKIA ───────────────────────────────────────────────────────────
        '000E3E' => ['Nokia', 'mobile'],
        '0025D1' => ['Nokia', 'mobile'],
        '8C1AB9' => ['Nokia', 'mobile'],

        // ─── NOTHING TECHNOLOGY ──────────────────────────────────────────────
        '5C3A45' => ['Nothing Technology', 'mobile'],

        // ─── TP-LINK ─────────────────────────────────────────────────────────
        '002722' => ['TP-Link Technologies', 'router'],
        '14CC20' => ['TP-Link Technologies', 'router'],
        '1C3BF3' => ['TP-Link Technologies', 'router'],
        '404A03' => ['TP-Link Technologies', 'router'],
        '503EAA' => ['TP-Link Technologies', 'router'],
        '54C80F' => ['TP-Link Technologies', 'router'],
        '647002' => ['TP-Link Technologies', 'router'],
        '98DAC4' => ['TP-Link Technologies', 'router'],
        'B04E26' => ['TP-Link Technologies', 'router'],
        'F81A67' => ['TP-Link Technologies', 'router'],

        // ─── CISCO ───────────────────────────────────────────────────────────
        '00000C' => ['Cisco Systems', 'router'],
        '000142' => ['Cisco Systems', 'router'],
        '001A6C' => ['Cisco Systems', 'router'],
        '001BB1' => ['Cisco Systems', 'router'],
        '0026CB' => ['Cisco Systems', 'router'],
        '2CBE08' => ['Cisco Systems', 'router'],

        // ─── UBIQUITI ────────────────────────────────────────────────────────
        '00156D' => ['Ubiquiti Networks', 'router'],
        '0418D6' => ['Ubiquiti Networks', 'router'],
        '24A43C' => ['Ubiquiti Networks', 'router'],
        '687251' => ['Ubiquiti Networks', 'router'],
        '788A20' => ['Ubiquiti Networks', 'router'],
        '802AA8' => ['Ubiquiti Networks', 'router'],
        'DC9FDB' => ['Ubiquiti Networks', 'router'],
        'F09FC2' => ['Ubiquiti Networks', 'router'],

        // ─── D-LINK ──────────────────────────────────────────────────────────
        '00055D' => ['D-Link Corporation', 'router'],
        '000D88' => ['D-Link Corporation', 'router'],
        '001195' => ['D-Link Corporation', 'router'],
        '1CABCD' => ['D-Link Corporation', 'router'],

        // ─── NETGEAR ─────────────────────────────────────────────────────────
        '001E2A' => ['Netgear', 'router'],
        '20E52A' => ['Netgear', 'router'],
        '28C68E' => ['Netgear', 'router'],
        '6031C3' => ['Netgear', 'router'],
        'A021B7' => ['Netgear', 'router'],

        // ─── ASUS (routeurs) ─────────────────────────────────────────────────
        '002354' => ['ASUSTek Computer', 'router'],
        '04921F' => ['ASUSTek Computer', 'router'],
        '107B44' => ['ASUSTek Computer', 'router'],
        '48EE0C' => ['ASUSTek Computer', 'router'],

        // ─── MIKROTIK ────────────────────────────────────────────────────────
        '2CC8B7' => ['MikroTik', 'router'],
        '4C5E0C' => ['MikroTik', 'router'],
        '6C3B6B' => ['MikroTik', 'router'],
        'DC2C6E' => ['MikroTik', 'router'],
        'E48D8C' => ['MikroTik', 'router'],

        // ─── RASPBERRY PI ────────────────────────────────────────────────────
        'B827EB' => ['Raspberry Pi Foundation', 'iot'],
        'DCA632' => ['Raspberry Pi Foundation', 'iot'],
        'E45F01' => ['Raspberry Pi Foundation', 'iot'],   // partagé avec Apple

        // ─── ESPRESSIF (ESP8266, ESP32) ──────────────────────────────────────
        '240AC4' => ['Espressif Inc.', 'iot'],
        '3C71BF' => ['Espressif Inc.', 'iot'],
        '8CAAB5' => ['Espressif Inc.', 'iot'],
        'A02050' => ['Espressif Inc.', 'iot'],
        'E09806' => ['Espressif Inc.', 'iot'],

        // ─── INTEL NIC (laptops) ─────────────────────────────────────────────
        '000086' => ['Intel Corporate', 'computer'],
        '000C29' => ['VMware', 'computer'],
        '000569' => ['VMware', 'computer'],
        '001BFC' => ['Intel Corporate', 'computer'],
        '1C69A5' => ['Intel Corporate', 'computer'],
        '3CCE42' => ['Intel Corporate', 'computer'],
        '8C8D28' => ['Intel Corporate', 'computer'],
        'A4C3F0' => ['Intel Corporate', 'computer'],

        // ─── REALTEK (PC) ────────────────────────────────────────────────────
        '00E04C' => ['Realtek Semiconductor', 'computer'],
        'E0D55E' => ['Realtek Semiconductor', 'computer'],

        // ─── DELL ────────────────────────────────────────────────────────────
        '001422' => ['Dell Inc.', 'computer'],
        '001AA0' => ['Dell Inc.', 'computer'],
        '001E4F' => ['Dell Inc.', 'computer'],
        '0025B5' => ['Dell Inc.', 'computer'],
        'F0761C' => ['Dell Inc.', 'computer'],

        // ─── HP / HEWLETT-PACKARD ────────────────────────────────────────────
        '001708' => ['HP Inc.', 'computer'],
        '001E0B' => ['HP Inc.', 'computer'],
        '001E8C' => ['HP Inc.', 'computer'],
        '3C4A92' => ['HP Inc.', 'computer'],
        'B499BA' => ['HP Inc.', 'computer'],

        // ─── LENOVO ──────────────────────────────────────────────────────────
        '00238D' => ['Lenovo', 'computer'],
        '285AEB' => ['Lenovo', 'computer'],
        '3C46D8' => ['Lenovo', 'computer'],
        '4CEF91' => ['Lenovo', 'computer'],
        '70720D' => ['Lenovo', 'computer'],

        // ─── HP (imprimantes) ─────────────────────────────────────────────────
        '00805F' => ['HP (Printer)', 'printer'],
        '0021B7' => ['HP (Printer)', 'printer'],
        '283737' => ['HP (Printer)', 'printer'],
        '3C2AF4' => ['HP (Printer)', 'printer'],

        // ─── CANON ───────────────────────────────────────────────────────────
        '000085' => ['Canon', 'printer'],
        '001E8F' => ['Canon', 'printer'],

        // ─── EPSON ───────────────────────────────────────────────────────────
        '00266B' => ['Seiko Epson', 'printer'],
        '44D240' => ['Seiko Epson', 'printer'],
    ];

    // ── Icônes par catégorie (pour la vue) ────────────────────────────────────
    public const CATEGORY_ICON = [
        'mobile'       => '📱',
        'apple_device' => '🍎',
        'computer'     => '💻',
        'router'       => '📡',
        'iot'          => '🔌',
        'printer'      => '🖨️',
        'tv'           => '📺',
        'unknown'      => '❓',
    ];

    public const CATEGORY_LABEL = [
        'mobile'       => 'Mobile / Tablette',
        'apple_device' => 'Appareil Apple',
        'computer'     => 'Ordinateur',
        'router'       => 'Équipement réseau',
        'iot'          => 'IoT / Embarqué',
        'printer'      => 'Imprimante',
        'tv'           => 'TV connectée',
        'unknown'      => 'Inconnu',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    //  API publique
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{vendor: string|null, category: string, icon: string, label: string}
     */
    public function lookup(?string $mac): array
    {
        if (! $mac) {
            return $this->unknown();
        }

        $oui = $this->normalizeOui($mac);

        if (! $oui) {
            return $this->unknown();
        }

        // Niveau 1 : liste locale
        if (isset(self::LOCAL_OUI[$oui])) {
            [$vendor, $category] = self::LOCAL_OUI[$oui];

            return $this->result($vendor, $category);
        }

        // Niveau 2 : API externe avec cache
        $cacheKey = 'oui:' . $oui;

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($mac) {
            return $this->apiLookup($mac);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Internals
    // ─────────────────────────────────────────────────────────────────────────

    private function normalizeOui(?string $mac): ?string
    {
        if (! $mac) {
            return null;
        }

        // Supprimer séparateurs et prendre les 6 premiers hex chars en majuscules
        $clean = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));

        if (strlen($clean) < 6) {
            return null;
        }

        return substr($clean, 0, 6);
    }

    private function apiLookup(string $mac): array
    {
        try {
            $response = Http::timeout(3)
                ->withHeaders(['User-Agent' => 'RansomShield-SOC/1.0'])
                ->get('https://api.macvendors.com/' . urlencode($mac));

            if ($response->ok()) {
                $vendor   = trim($response->body());
                $category = $this->categorizeByVendorName($vendor);

                return $this->result($vendor, $category);
            }
        } catch (\Throwable $e) {
            Log::debug('[MacVendor] API unavailable for ' . $mac . ': ' . $e->getMessage());
        }

        return $this->unknown();
    }

    private function categorizeByVendorName(string $vendor): string
    {
        $v = strtolower($vendor);

        // Mobile-only marques
        $mobileKeywords = ['xiaomi', 'oneplus', 'oppo', 'vivo', 'realme', 'motorola solutions',
                           'blackberry', 'nothing technology', 'fairphone', 'zte', 'tcl'];
        foreach ($mobileKeywords as $kw) {
            if (str_contains($v, $kw)) {
                return 'mobile';
            }
        }

        // Samsung peut être TV/phone/laptop — on laisse "mobile" (le plus commun)
        if (str_contains($v, 'samsung')) {
            return 'mobile';
        }

        // LG = TV ou phone — on laisse "mobile" car téléphones plus courants sur un LAN
        if (str_contains($v, 'lg electronics')) {
            return 'mobile';
        }

        // Huawei = phone ou routeur — téléphone plus courant
        if (str_contains($v, 'huawei')) {
            return 'mobile';
        }

        // Apple = Mac ou iPhone — catégorie distincte
        if (str_contains($v, 'apple')) {
            return 'apple_device';
        }

        // Google = Pixel ou Chromecast
        if (str_contains($v, 'google')) {
            return 'mobile';
        }

        // Réseau
        $routerKeywords = ['cisco', 'tp-link', 'd-link', 'netgear', 'ubiquiti', 'mikrotik',
                           'juniper', 'aruba', 'ruckus', 'meraki', 'fortinet', 'pfsense',
                           'zyxel', 'linksys', 'belkin', 'buffalo', 'synology', 'qnap'];
        foreach ($routerKeywords as $kw) {
            if (str_contains($v, $kw)) {
                return 'router';
            }
        }

        // IoT
        $iotKeywords = ['raspberry', 'espressif', 'arduino', 'wemos', 'shelly', 'tuya',
                        'texas instruments', 'nordic semiconductor'];
        foreach ($iotKeywords as $kw) {
            if (str_contains($v, $kw)) {
                return 'iot';
            }
        }

        // Imprimante
        $printerKeywords = ['epson', 'canon', 'brother', 'xerox', 'lexmark', 'ricoh', 'kyocera'];
        foreach ($printerKeywords as $kw) {
            if (str_contains($v, $kw)) {
                return 'printer';
            }
        }

        // TV
        $tvKeywords = ['philips', 'vizio', 'hisense', 'tcl industries', 'roku'];
        foreach ($tvKeywords as $kw) {
            if (str_contains($v, $kw)) {
                return 'tv';
            }
        }

        // Ordinateur par défaut
        $computerKeywords = ['intel', 'dell', 'hewlett', 'lenovo', 'realtek', 'acer', 'asus',
                             'gigabyte', 'msi', 'vmware', 'parallels', 'virtualbox'];
        foreach ($computerKeywords as $kw) {
            if (str_contains($v, $kw)) {
                return 'computer';
            }
        }

        return 'unknown';
    }

    private function result(string $vendor, string $category): array
    {
        return [
            'vendor'   => $vendor,
            'category' => $category,
            'icon'     => self::CATEGORY_ICON[$category] ?? '❓',
            'label'    => self::CATEGORY_LABEL[$category] ?? 'Inconnu',
        ];
    }

    private function unknown(): array
    {
        return [
            'vendor'   => null,
            'category' => 'unknown',
            'icon'     => '❓',
            'label'    => 'Inconnu',
        ];
    }
}
