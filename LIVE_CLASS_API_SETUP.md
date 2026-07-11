# Live Class API Setup (Zoom + Google Meet)

This project can auto-create meeting links for:
- Jitsi (already works without external API)
- Zoom (via Zoom Server-to-Server OAuth)
- Google Meet (via Google Calendar API + OAuth refresh token)

## 1) Put credentials in `.env`

Add these keys in your real `.env` file (same folder as `artisan`):

```env
# Zoom Server-to-Server OAuth
ZOOM_ACCOUNT_ID=your_zoom_account_id
ZOOM_CLIENT_ID=your_zoom_client_id
ZOOM_CLIENT_SECRET=your_zoom_client_secret

# Google Meet via Google Calendar API OAuth
GOOGLE_CLIENT_ID=your_google_oauth_client_id
GOOGLE_CLIENT_SECRET=your_google_oauth_client_secret
GOOGLE_REFRESH_TOKEN=your_google_oauth_refresh_token
GOOGLE_CALENDAR_ID=primary
```

## 2) Reload config cache

Run:

```bash
php artisan optimize:clear
```

## 3) How the system behaves

- If platform is `jitsi` and meeting URL is empty: system auto-generates Jitsi room URL.
- If platform is `zoom` and meeting URL is empty: system calls Zoom API and stores `join_url`.
- If platform is `google_meet` and meeting URL is empty: system creates Google Calendar event with Meet and stores `hangoutLink`.
- If Zoom/Google credentials are missing or invalid: form returns validation error telling which credentials are required.

## 4) Required provider setup details

### Zoom
Use **Server-to-Server OAuth** app in Zoom Marketplace:
- Copy Account ID, Client ID, Client Secret into `.env`.
- Ensure app has permission to create meetings for the account.

### Google Meet
Use Google Cloud OAuth credentials for Calendar API:
- Enable Calendar API.
- Create OAuth Client ID + Client Secret.
- Obtain refresh token with Calendar scope.
- The target Google account calendar must allow event creation.

Recommended scope:
- `https://www.googleapis.com/auth/calendar.events`

## 5) Quick verification

1. Create live class with `platform=zoom` and leave meeting URL empty.
2. Save and check `meeting_url` in class details.
3. Repeat for `platform=google_meet`.
4. If error appears, verify `.env` values and run `php artisan optimize:clear` again.
