<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Console;

use Illuminate\Console\Command;
use Refatbd\FreeFire\Exception\InvalidInputException;
use Refatbd\FreeFire\Exception\RateLimitedException;
use Refatbd\FreeFire\Exception\TransportException;
use Refatbd\FreeFire\FreeFireClient;

final class PlayerLookupCommand extends Command
{
    protected $signature = 'freefire:player 
                            {uid : Free Fire numeric account UID} 
                            {--region= : Specific region code (e.g. BD, SG, IND, BR, VN, ID, TH, TW)} 
                            {--raw : Display raw JSON payload}';

    protected $description = 'Lookup a Free Fire player profile by account UID.';

    public function handle(FreeFireClient $client): int
    {
        $uid = trim((string) $this->argument('uid'));
        $region = $this->option('region') ? strtoupper(trim((string) $this->option('region'))) : null;
        $raw = (bool) $this->option('raw');

        if ($uid === '' || !preg_match('/^\d{5,20}$/', $uid)) {
            $this->error("Invalid Free Fire UID '{$uid}'. Please provide a valid 5-20 digit numeric UID.");
            return self::FAILURE;
        }

        $this->info("Looking up Free Fire player '{$uid}'" . ($region ? " in region '{$region}'..." : " across global gateways..."));
        $started = microtime(true);

        try {
            $player = $client->player($uid, $region);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $basic = $player['basicInfo'] ?? [];
            $nickname = (string) ($basic['nickname'] ?? 'N/A');
            $level = (string) ($basic['level'] ?? 'N/A');
            $resolvedRegion = (string) ($basic['region'] ?? $region ?? 'Auto-resolved');

            $this->newLine();
            $this->table(['Field', 'Value'], [
                ['UID', $uid],
                ['Nickname', $nickname],
                ['Level', $level],
                ['Region', $resolvedRegion],
                ['Lookup Time', "{$durationMs} ms"],
            ]);
            $this->newLine();

            if ($raw) {
                $this->line(json_encode($player, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }

            return self::SUCCESS;
        } catch (RateLimitedException $e) {
            $this->error("Rate limit reached (HTTP 429): Garena upstream is temporarily rate-limiting requests. Please wait a moment before retrying.");
            return self::FAILURE;
        } catch (InvalidInputException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (TransportException $e) {
            $this->error("Upstream transport error: " . $e->getMessage());
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Lookup failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
