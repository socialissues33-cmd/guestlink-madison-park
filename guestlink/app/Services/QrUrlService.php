<?php

namespace App\Services;

use App\Models\Room;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrUrlService
{
    /**
     * Get current hotel-wide Wi-Fi configuration & auto-connect QR SVGs.
     * Supports periodic Wi-Fi reset/cleanup while keeping guest experience 100% scan-only (no manual voucher typing).
     */
    public static function getWifiConfig(): array
    {
        $configPath = storage_path('app/hotel_wifi.json');
        $saved = [];
        if (file_exists($configPath)) {
            $decoded = json_decode(@file_get_contents($configPath), true);
            if (is_array($decoded)) {
                $saved = $decoded;
            }
        }

        $wifiSsid = $saved['ssid'] ?? env('HOTEL_WIFI_SSID', 'MadisonPark_Guest');
        $wifiPassword = array_key_exists('password', $saved)
            ? (string) $saved['password']
            : (string) env('HOTEL_WIFI_PASSWORD', 'luxurywelcome');
        $rawMode = $saved['qr_mode'] ?? 'local';
        $qrMode = in_array($rawMode, ['local', 'public'], true) ? $rawMode : 'local';
        $customLanIp = trim((string) ($saved['custom_lan_ip'] ?? ''));
        $lastResetAt = $saved['last_reset_at'] ?? now()->startOfDay()->toDateTimeString();

        $wifiPayload = $wifiPassword !== ''
            ? "WIFI:T:WPA;S:{$wifiSsid};P:{$wifiPassword};;"
            : "WIFI:T:nopass;S:{$wifiSsid};;";

        $wifiQr = QrCode::format('svg')
            ->size(320)->margin(2)->errorCorrection('M')->generate($wifiPayload);

        $wifiQrMini = QrCode::format('svg')
            ->size(110)->margin(1)->errorCorrection('M')->generate($wifiPayload);

        return [
            'wifiSsid'      => $wifiSsid,
            'wifiPassword'  => $wifiPassword,
            'wifiPayload'   => $wifiPayload,
            'wifiQr'        => $wifiQr,
            'wifiQrMini'    => $wifiQrMini,
            'wifiLastReset' => $lastResetAt,
            'qrMode'        => $qrMode,
            'customLanIp'   => $customLanIp,
        ];
    }

    /**
     * Save or reset hotel-wide Wi-Fi & QR mode configuration.
     */
    public static function updateWifiConfig(string $ssid, ?string $password, ?string $qrMode = null, ?string $customLanIp = null): array
    {
        $current = self::getWifiConfig();

        $data = [
            'ssid'          => trim($ssid) ?: 'MadisonPark_Guest',
            'password'      => trim((string) $password),
            'qr_mode'       => in_array($qrMode, ['local', 'public'], true) ? $qrMode : ($current['qrMode'] ?? 'local'),
            'custom_lan_ip' => $customLanIp !== null ? trim($customLanIp) : ($current['customLanIp'] ?? ''),
            'last_reset_at' => now()->toDateTimeString(),
        ];

        @file_put_contents(storage_path('app/hotel_wifi.json'), json_encode($data, JSON_PRETTY_PRINT));

        return self::getWifiConfig();
    }

    /**
     * Quick toggle between 'local' (Local Wi-Fi Network QR) and 'public' (Cloudflare Tunnel QR).
     */
    public static function setQrMode(string $mode, ?string $customLanIp = null): array
    {
        $current = self::getWifiConfig();
        return self::updateWifiConfig(
            $current['wifiSsid'],
            $current['wifiPassword'],
            in_array($mode, ['local', 'public'], true) ? $mode : 'local',
            $customLanIp
        );
    }

    /**
     * Resolve public and local URLs + QR SVGs for a room.
     */
    public static function resolveRoomUrls(Room $room): array
    {
        $currentHost = rtrim(request()->getSchemeAndHttpHost(), '/');
        $reqHostName = strtolower((string) request()->getHost());

        $isPrivateOrLocalHost = in_array($reqHostName, ['', 'localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($reqHostName, '.trycloudflare.com')
            || (bool) preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $reqHostName);

        $isCloudDomain = !$isPrivateOrLocalHost;

        if ($isCloudDomain) {
            // Deployed on a real 24/7 cloud host (Render, Railway, Fly.io, Hostinger, etc.)
            $tunnelHost = $currentHost;
            $hasTunnel = true;
        } else {
            $configuredTunnel = env('CLOUDFLARE_TUNNEL_URL');

            // Auto-detect live Cloudflare URL from possible log paths
            $tunnelLogCandidates = [
                storage_path('tunnel.log'),
                base_path('../tools/tunnel.log'),
                base_path('storage/tunnel.log'),
                storage_path('logs/tunnel.log'),
            ];
            foreach ($tunnelLogCandidates as $logFile) {
                if (file_exists($logFile)) {
                    $logData = @file_get_contents($logFile);
                    if ($logData && preg_match('/https:\/\/[a-zA-Z0-9-]+\.trycloudflare\.com/', $logData, $tMatches)) {
                        $configuredTunnel = $tMatches[0];
                        break;
                    }
                }
            }

            // Determine Public Tunnel Host
            if (!empty($configuredTunnel)) {
                $tunnelHost = rtrim($configuredTunnel, '/');
                $hasTunnel = true;
            } elseif (!in_array($reqHostName, ['', 'localhost', '127.0.0.1', '::1'], true)) {
                $tunnelHost = $currentHost;
                $hasTunnel = true;
            } else {
                $tunnelHost = null;
                $hasTunnel = false;
            }
        }

        $wifiData = self::getWifiConfig();

        // Determine Local Wi-Fi / Hotspot IP (prefer customLanIp or real Wi-Fi/Hotspot subnet over VirtualBox 192.168.56.1 or loopback)
        $lanHost = !empty($wifiData['customLanIp']) ? $wifiData['customLanIp'] : env('LOCAL_LAN_IP');
        if (empty($lanHost)) {
            $reqHost = request()->getHost();
            if ($reqHost && !in_array($reqHost, ['localhost', '127.0.0.1']) && !str_ends_with($reqHost, '.trycloudflare.com')) {
                $lanHost = $reqHost;
            } else {
                $candidates = @gethostbynamel(gethostname()) ?: [];
                foreach ($candidates as $ip) {
                    if ($ip !== '127.0.0.1' && !str_starts_with($ip, '169.254.') && $ip !== '192.168.56.1') {
                        $lanHost = $ip;
                        break;
                    }
                }
                if (empty($lanHost)) {
                    $fallbackIp = gethostbyname(gethostname());
                    $lanHost = ($fallbackIp && $fallbackIp !== '127.0.0.1' && !str_starts_with($fallbackIp, '169.254.'))
                        ? $fallbackIp
                        : '192.168.254.147';
                }
            }
        }
        $port = request()->getPort();
        $portSuffix = ($port && !in_array($port, [80, 443])) ? ":{$port}" : ':8000';

        $localUrl = "http://{$lanHost}{$portSuffix}/guest/{$room->qr_token}";
        $publicUrl = $tunnelHost ? "{$tunnelHost}/guest/{$room->qr_token}" : $localUrl;

        $publicQr = QrCode::format('svg')
            ->size(320)->margin(2)->errorCorrection('H')->generate($publicUrl);

        $localQr = QrCode::format('svg')
            ->size(320)->margin(2)->errorCorrection('H')->generate($localUrl);

        $preferredMode = ($isCloudDomain || (($wifiData['qrMode'] ?? 'local') === 'public' && $hasTunnel))
            ? 'public'
            : 'local';

        return array_merge([
            'room'          => $room,
            'publicUrl'     => $publicUrl,
            'localUrl'      => $localUrl,
            'publicQr'      => $publicQr,
            'localQr'       => $localQr,
            'lanHost'       => $lanHost,
            'portSuffix'    => $portSuffix,
            'hasTunnel'     => $hasTunnel,
            'preferredMode' => $preferredMode,
        ], $wifiData);
    }
}
