<?php

/*
|--------------------------------------------------------------------------
| Services configuration
|--------------------------------------------------------------------------
|
| This file was absent from the installation (like config/auth.php and
| config/cache.php before it — /config/*.php is gitignored in this repo, so
| none of these ship with the checkout and must exist on every environment
| that runs the app). Its absence meant every config('services.*.*') call
| silently returned null, which is why Live Class scheduling could select
| "Zoom" or "Google Meet" as a platform but never actually create a
| meeting — App\Http\Controllers\LiveClassController::createZoomMeetingUrl()
| and createGoogleMeetUrl() both treat empty credentials as "not configured"
| and fail gracefully, so this wasn't a crash, just silently inert.
|
| The zoom/google_meet blocks below are this app's own addition, read by
| LiveClassController; everything else is Laravel 9's stock content.
|
*/

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
     * Zoom Server-to-Server OAuth — see LIVE_CLASS_API_SETUP.md.
     * Empty values are treated as "Zoom not configured" by
     * LiveClassController::createZoomMeetingUrl(), which fails the request
     * with a clear validation message rather than a silent/broken meeting.
     */
    'zoom' => [
        'account_id' => env('ZOOM_ACCOUNT_ID', ''),
        'client_id' => env('ZOOM_CLIENT_ID', ''),
        'client_secret' => env('ZOOM_CLIENT_SECRET', ''),
    ],

    /*
     * Google Meet via the Google Calendar API — see LIVE_CLASS_API_SETUP.md.
     * Same "not configured" fallback as Zoom above when any value is empty.
     */
    'google_meet' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'refresh_token' => env('GOOGLE_REFRESH_TOKEN', ''),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

    /*
     * Jitsi moderator JWT — see LIVE_CLASS_JITSI_JWT_SETUP.md.
     *
     * Without this, every Jitsi meeting embed is anonymous, so Jitsi's
     * server never learns who "the host" is and shows "waiting for a
     * moderator" to everyone, including the person who started the class.
     * App\Support\LiveClasses\JitsiTokenService treats missing credentials
     * as "not configured" and returns null (no JWT) rather than failing —
     * the embed still works, just without moderator rights, exactly like
     * today, until this is filled in.
     *
     * Two supported algorithms, picked by JITSI_JWT_ALGORITHM:
     *   - RS256 (default): 8x8 JaaS (jaas.8x8.vc) — no server to run,
     *     works on ordinary shared hosting. Needs app_id, kid, private_key.
     *   - HS256: a self-hosted Jitsi with the jitsi-meet-tokens prosody
     *     plugin. Needs app_id, app_secret.
     */
    'jitsi' => [
        'algorithm' => env('JITSI_JWT_ALGORITHM', 'RS256'),
        'app_id' => env('JITSI_APP_ID', ''),
        'kid' => env('JITSI_KID', ''),
        'private_key' => env('JITSI_PRIVATE_KEY', ''),
        'app_secret' => env('JITSI_APP_SECRET', ''),
    ],

];
