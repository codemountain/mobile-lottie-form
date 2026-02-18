<?php

namespace CodeMountain\LottieForm\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

class CopyAssetsCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:mobile-lottie-form:copy-assets';

    protected $description = 'Copy Lottie animation files for LottieForm plugin';

    public function handle(): int
    {
        $animationsDir = $this->resolveAnimationsDir();

        if (! is_dir($animationsDir)) {
            $this->warn("LottieForm: Animations directory not found: {$animationsDir}");

            return self::SUCCESS;
        }

        $files = glob($animationsDir.'/*.lottie');

        if (empty($files)) {
            $this->warn("LottieForm: No .lottie files found in {$animationsDir}");

            return self::SUCCESS;
        }

        foreach ($files as $file) {
            $filename = basename($file);

            if ($this->isAndroid()) {
                $dest = $this->buildPath().'/app/src/main/assets/animations/'.$filename;
                $this->copyFile($file, $dest);
            }

            if ($this->isIos()) {
                $dest = $this->buildPath().'/NativePHP/Resources/animations/'.$filename;
                $this->copyFile($file, $dest);
            }
        }

        $this->info('LottieForm: Copied '.count($files).' animation(s) from '.$animationsDir);

        return self::SUCCESS;
    }

    /**
     * Resolve the source directory for .lottie files.
     *
     * Checks LOTTIE_FORM_ANIMATIONS env var first, falls back to resources/animations.
     */
    protected function resolveAnimationsDir(): string
    {
        $configured = env('LOTTIE_FORM_ANIMATIONS');

        if ($configured) {
            $path = base_path($configured);

            if (is_dir($path)) {
                return $path;
            }

            $this->warn("LottieForm: Configured path '{$configured}' not found, falling back to resources/animations");
        }

        return base_path('resources/animations');
    }
}
