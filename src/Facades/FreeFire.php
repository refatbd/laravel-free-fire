<?php
declare(strict_types=1);
namespace Refatbd\LaravelFreeFire\Facades;
use Illuminate\Support\Facades\Facade;
/** @method static array player(string|int $uid,string $region) */
final class FreeFire extends Facade { protected static function getFacadeAccessor(): string{return 'freefire';} }
