<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Refatbd\FreeFire\Exception\ConfigurationException;
use Refatbd\FreeFire\Exception\FreeFireException;
use Refatbd\FreeFire\Exception\InvalidInputException;
use Refatbd\FreeFire\FreeFireClient;
use Refatbd\FreeFire\Media\MediaVersion;

final class PlayerController
{
    public function show(Request $request, string $uid, FreeFireClient $client): JsonResponse
    {
        return $this->lookup($request, $uid, $client);
    }

    public function legacy(Request $request, FreeFireClient $client): JsonResponse
    {
        $uid = (string) $request->query('uid', '');
        if ($uid === '') {
            return response()->json(['error' => 'The uid query parameter is required.'], 422);
        }

        return $this->lookup($request, $uid, $client);
    }

    private function lookup(Request $request, string $uid, FreeFireClient $client): JsonResponse
    {
        if (!config('freefire.enabled', true)) {
            return response()->json(['error' => 'Free Fire lookup is disabled.'], 503);
        }

        $regionInput = $request->query('region') ? (string) $request->query('region') : null;
        try {
            $data = $client->player($uid, $regionInput);
            $region = (string) ($data['basicInfo']['region'] ?? $regionInput ?? 'BD');
            if (config('freefire.media.enabled', true)) {
                $avatarVersion = MediaVersion::avatar($data);
                $bannerVersion = MediaVersion::banner($data);
                $data['mediaInfo'] = array_merge($data['mediaInfo'] ?? [], [
                    'avatarUrl' => route('freefire.avatar', [
                        'uid' => $uid,
                        'region' => $region,
                        'v' => $avatarVersion,
                    ]),
                    'bannerUrl' => route('freefire.banner', [
                        'uid' => $uid,
                        'region' => $region,
                        'v' => $bannerVersion,
                    ]),
                ]);
            }

            return response()->json($data);
        } catch (InvalidInputException|\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (ConfigurationException $e) {
            report($e);
            return response()->json(['error' => 'Free Fire integration is not configured correctly.'], 503);
        } catch (FreeFireException $e) {
            report($e);
            return response()->json(['error' => 'Player information is temporarily unavailable.'], 502);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Player information is temporarily unavailable.'], 502);
        }
    }
}
