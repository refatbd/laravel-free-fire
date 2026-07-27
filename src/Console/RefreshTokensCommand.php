<?php
declare(strict_types=1);
namespace Refatbd\LaravelFreeFire\Console;
use Illuminate\Console\Command;
use Refatbd\FreeFire\Token\TokenManager;
use Refatbd\FreeFire\Region\RegionRegistry;
final class RefreshTokensCommand extends Command
{
    protected $signature='freefire:tokens-refresh {--region=* : Regions to refresh}';protected $description='Refresh cached Free Fire access tokens.';
    public function handle(TokenManager $tokens): int{$regions=$this->option('region')?:RegionRegistry::SUPPORTED;$failed=[];foreach($regions as $r){try{$tokens->get((string)$r,true);$this->info("Refreshed {$r}");}catch(\Throwable $e){$failed[]=$r;$this->error("{$r}: {$e->getMessage()}");}}return $failed?self::FAILURE:self::SUCCESS;}
}
