<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Http\Controllers;

use Illuminate\Http\Request;
use Refatbd\FreeFire\Exception\ConfigurationException;
use Refatbd\FreeFire\Exception\FreeFireException;
use Refatbd\FreeFire\Exception\InvalidInputException;
use Refatbd\FreeFire\Exception\MediaException;
use Refatbd\FreeFire\FreeFireClient;
use Refatbd\FreeFire\Media\MediaService;
use Refatbd\FreeFire\Media\RenderedMedia;

final class MediaController
{
    public function avatar(Request $request, string $uid, FreeFireClient $client, MediaService $media)
    {
        return $this->render(function () use ($request, $uid, $client, $media) {
            $region = $request->query('region') ? (string) $request->query('region') : null;
            $size = max(128, min((int) $request->query('size', 512), 1024));
            return $media->avatar($client->player($uid, $region), $size);
        });
    }

    public function banner(Request $request, string $uid, FreeFireClient $client, MediaService $media)
    {
        return $this->render(function () use ($request, $uid, $client, $media) {
            $region = $request->query('region') ? (string) $request->query('region') : null;
            $width = max(800, min((int) $request->query('width', 1000), 1600));
            $height = max(200, min((int) $request->query('height', 250), 400));
            $raw = $request->boolean('raw') || $request->query('raw') === '1' || $request->query('mode') === 'clean';
            return $media->banner($client->player($uid, $region), $width, $height, $raw);
        });
    }

    private function render(callable $callback)
    {
        if (!config('freefire.enabled', true) || !config('freefire.media.enabled', true)) {
            return response()->json(['error' => 'Free Fire media rendering is disabled.'], 503);
        }

        try {
            return $this->respond($callback());
        } catch (InvalidInputException|\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (ConfigurationException $e) {
            report($e);
            return response()->json(['error' => 'Free Fire media is not configured correctly.'], 503);
        } catch (MediaException $e) {
            report($e);
            return response()->json(['error' => 'Free Fire media is temporarily unavailable.'], 503);
        } catch (FreeFireException $e) {
            report($e);
            return response()->json(['error' => 'Player information is temporarily unavailable.'], 502);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Free Fire media is temporarily unavailable.'], 503);
        }
    }

    private function respond(RenderedMedia $rendered)
    {
        $maxAge = max(60, (int) config('freefire.media.http_cache_ttl', 300));

        return response($rendered->data, 200, [
            'Content-Type' => $rendered->contentType,
            'Content-Length' => (string) strlen($rendered->data),
            'Cache-Control' => "public, max-age={$maxAge}, stale-while-revalidate=600",
            'X-Content-Type-Options' => 'nosniff',
            'X-Free-Fire-Media-Source' => $rendered->source,
            'X-Free-Fire-Official-Banner' => $rendered->officialBanner ? '1' : '0',
            'X-Free-Fire-Official-Avatar' => $rendered->officialAvatar ? '1' : '0',
        ]);
    }
}
