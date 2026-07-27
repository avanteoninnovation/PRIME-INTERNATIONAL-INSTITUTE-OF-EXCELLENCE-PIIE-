<?php

namespace App\Support\Audit;

use Illuminate\Http\Request;

class ClientInfo
{
    /**
     * Derive device type, browser, and OS/platform from the request's
     * User-Agent header. Lightweight regex matching — no external
     * dependency — with safe "Unknown" fallbacks for anything unmatched.
     *
     * @return array{device_type: string, browser: string, platform: string}
     */
    public static function fromRequest(Request $request): array
    {
        $ua = (string) $request->userAgent();

        return [
            'device_type' => static::deviceType($ua),
            'browser'     => static::browser($ua),
            'platform'    => static::platform($ua),
        ];
    }

    private static function deviceType(string $ua): string
    {
        if ($ua === '') {
            return 'Unknown';
        }

        if (preg_match('/iPad|Android(?!.*Mobile)|Tablet|Kindle|Silk/i', $ua)) {
            return 'Tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini|Windows Phone/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private static function browser(string $ua): string
    {
        if ($ua === '') {
            return 'Unknown';
        }

        $map = [
            '/SamsungBrowser/i'        => 'Samsung Browser',
            '/EdgA|EdgiOS|Edge|Edg\//i' => 'Edge',
            '/OPR\/|Opera Mini|OPiOS/i' => 'Opera',
            '/CriOS/i'                 => 'Chrome Mobile',
            '/FxiOS/i'                 => 'Firefox Mobile',
            '/Firefox/i'               => 'Firefox',
            '/Chrome\//i'              => 'Chrome',
            '/CFNetwork|Version\/.*Mobile.*Safari/i' => 'Mobile Safari',
            '/Version\/.*Safari/i'     => 'Safari',
            '/MSIE|Trident/i'          => 'Internet Explorer',
        ];

        foreach ($map as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    private static function platform(string $ua): string
    {
        if ($ua === '') {
            return 'Unknown';
        }

        $map = [
            '/iPhone|iPad|iPod/i' => 'iOS',
            '/Android/i'          => 'Android',
            '/Windows Phone/i'    => 'Windows Phone',
            '/Windows NT 10/i'    => 'Windows 10/11',
            '/Windows NT 6\.3/i'  => 'Windows 8.1',
            '/Windows NT 6\.2/i'  => 'Windows 8',
            '/Windows NT 6\.1/i'  => 'Windows 7',
            '/Windows/i'          => 'Windows',
            '/Mac OS X/i'         => 'macOS',
            '/CrOS/i'             => 'Chrome OS',
            '/Linux/i'            => 'Linux',
        ];

        foreach ($map as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }

        return 'Unknown';
    }
}
