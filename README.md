# refatbd/laravel-free-fire

Laravel integration package for the Free Fire player information & official ASTC media rendering engine.

> **Generated distribution repository:** development happens in `refatbd/free-fire-php-monorepo`. Do not edit the split repository directly.

## Installation

```bash
composer require refatbd/laravel-free-fire
php artisan vendor:publish --tag=freefire-config
php artisan freefire:media-check
```

## Basic Usage

```php
use Refatbd\LaravelFreeFire\Facades\FreeFire;

// Retrieve player information (JSON array)
$player = FreeFire::player('4422076728', 'BD');

// Access basic info and ranks
echo $player['basicInfo']['nickname']; // ᴛᴀʙᴀꜱꜱᴜᴍ♡ʀ
echo $player['basicInfo']['level'];    // 67
```

## Diagnostic & Media Capability Checker

Run the built-in Artisan diagnostic command to verify ASTC decoding and WebP media capability on your server:

```bash
php artisan freefire:media-check
```

### Server Requirements & Media Engine Guidance

| Platform / Server Type | Recommended Setup |
|---|---|
| **Ubuntu / Debian / VPS** | `sudo apt update && sudo apt install astc-encoder` |
| **cPanel / Shared Hosting** | Leave `FREEFIRE_ASTCENC_BINARY=astcenc`; the core package auto-detects the bundled Linux decoder when `proc_open` is enabled |
| **Windows Server / Local** | Leave `FREEFIRE_ASTCENC_BINARY=astcenc`; the core package auto-detects the bundled Windows decoder |

> **Graceful Fallback Note:** If `proc_open` is disabled or `astcenc` is missing on cheap shared hosting, player statistics and JSON API endpoints **continue to work 100% reliably**. Media rendering automatically degrades to PHP GD gradient graphics without crashing.

## Protocol & Configuration

```dotenv
FREEFIRE_ENABLED=true
FREEFIRE_DEFAULT_REGION=BD
FREEFIRE_PROTOCOL=OB54
FREEFIRE_ASTCENC_BINARY=astcenc
```

## API Routes

- `GET /api/free-fire/v1/health`
- `GET /api/free-fire/v1/players/{uid}?region=BD`
- `GET /api/free-fire/v1/players/{uid}/avatar?region=BD`
- `GET /api/free-fire/v1/players/{uid}/banner?region=BD&raw=1` (Clean background mode)
- `GET /api/free-fire/v1/players/{uid}/banner?region=BD` (Composited banner graphic mode)

### Legacy Compatibility Routes
- `GET /player-info?uid={uid}&region={region}`
- `GET /api/avatar/avatar_{uid}.webp`
- `GET /api/banner/banner_{uid}.webp`
