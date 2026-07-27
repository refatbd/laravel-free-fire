<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Refatbd\FreeFire\Media\AstcencProcessDecoder;
use Refatbd\FreeFire\Media\MediaCapability;
use Refatbd\FreeFire\Protocol\ProtocolProfileInterface;

final class HealthController
{
    public function __invoke(ProtocolProfileInterface $profile): JsonResponse
    {
        $media = MediaCapability::detect(
            new AstcencProcessDecoder((string) config('freefire.media.astcenc_binary', 'astcenc'))
        );

        return response()->json([
            'ok' => true,
            'protocol' => $profile->obVersion(),
            'media' => $media,
            'credentials' => 'configured-server-side',
        ]);
    }
}
