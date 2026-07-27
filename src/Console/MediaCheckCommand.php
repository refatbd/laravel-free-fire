<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Console;

use Illuminate\Console\Command;
use Refatbd\FreeFire\Media\AstcencProcessDecoder;
use Refatbd\FreeFire\Media\MediaCapability;

final class MediaCheckCommand extends Command
{
    protected $signature = 'freefire:media-check';
    protected $description = 'Check ASTC decoding and active WebP rendering capabilities.';

    public function handle(): int
    {
        $capability = MediaCapability::detect(
            new AstcencProcessDecoder((string) config('freefire.media.astcenc_binary', 'astcenc'))
        );

        $this->table(['Capability', 'Status'], [
            ['ASTC decoder', $capability->astcDecoder ? 'available' : 'unavailable'],
            ['GD', $capability->gd ? 'available' : 'unavailable'],
            ['Imagick (diagnostic only)', $capability->imagick ? 'available' : 'unavailable'],
            ['Active WebP renderer', $capability->webp ? 'available' : 'unavailable'],
            ['Decoder driver', $capability->decoderDriver],
            ['Renderer driver', $capability->rendererDriver],
        ]);

        if (!$capability->astcDecoder) {
            $this->newLine();
            $this->warn('! ASTC Media Decoder is currently UNAVAILABLE.');
            $this->warn('  Official Garena textures will fall back to PHP GD gradient graphics.');
            $this->newLine();

            if (!function_exists('proc_open')) {
                $this->error('  Reason: PHP function "proc_open" is disabled on this server.');
                $this->info('  Fix: Remove "proc_open" from "disable_functions" in your php.ini or cPanel PHP settings.');
            } else {
                $this->error('  Reason: "astcenc" binary could not be located.');
                $this->info('  Fix for Linux (Ubuntu/Debian): sudo apt update && sudo apt install astc-encoder');
                $this->info('  Fix for cPanel/Shared Hosting: keep FREEFIRE_ASTCENC_BINARY=astcenc; bundled auto-detection requires proc_open and executable permission.');
                $this->info('  Fix for Windows: keep FREEFIRE_ASTCENC_BINARY=astcenc; the bundled Windows decoder is auto-detected.');
            }
            $this->newLine();
        } else {
            $this->newLine();
            $this->info('✓ ASTC Media Decoder is operational (Official Garena textures active).');
            $this->newLine();
        }

        return $capability->astcDecoder && $capability->rendererDriver === 'gd-webp'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
