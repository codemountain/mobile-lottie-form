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

        $this->copyAnimations($animationsDir);
        $this->copyFonts($animationsDir);

        return self::SUCCESS;
    }

    protected function copyAnimations(string $animationsDir): void
    {
        $files = glob($animationsDir.'/*.lottie');

        if (empty($files)) {
            $this->warn("LottieForm: No .lottie files found in {$animationsDir}");

            return;
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
                $this->patchFontAscent($dest);
            }
        }

        $this->info('LottieForm: Copied '.count($files).' animation(s) from '.$animationsDir);
    }

    protected function copyFonts(string $animationsDir): void
    {
        $fontsDir = $animationsDir.'/fonts';

        if (! is_dir($fontsDir)) {
            return;
        }

        $fonts = glob($fontsDir.'/*.{ttf,otf}', GLOB_BRACE);

        if (empty($fonts)) {
            return;
        }

        foreach ($fonts as $font) {
            $filename = basename($font);

            if ($this->isAndroid()) {
                $dest = $this->buildPath().'/app/src/main/assets/fonts/'.$filename;
                $this->copyFile($font, $dest);
            }

            if ($this->isIos()) {
                $dest = $this->buildPath().'/NativePHP/Resources/'.$filename;
                $this->copyFile($font, $dest);
            }
        }

        $this->info('LottieForm: Copied '.count($fonts).' font(s) from '.$fontsDir);
    }

    /**
     * Lottie iOS requires an 'ascent' field on every font entry.
     * Many .lottie files exported from the wild omit it, causing
     * invalidInput errors at parse time. This patches them at build time.
     */
    protected function patchFontAscent(string $lottiePath): void
    {
        $zip = new \ZipArchive;

        if ($zip->open($lottiePath) !== true) {
            return;
        }

        $patched = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (! str_ends_with($name, '.json') || $name === 'manifest.json') {
                continue;
            }

            $json = json_decode($zip->getFromIndex($i), true);

            if (! isset($json['fonts']['list']) || ! is_array($json['fonts']['list'])) {
                continue;
            }

            $fontPatched = false;

            foreach ($json['fonts']['list'] as &$font) {
                if (! isset($font['ascent'])) {
                    $font['ascent'] = 75.0;
                    $fontPatched = true;
                }
            }

            unset($font);

            if ($fontPatched) {
                $zip->deleteName($name);
                $zip->addFromString($name, json_encode($json, JSON_UNESCAPED_SLASHES));
                $patched = true;
            }
        }

        $zip->close();

        if ($patched) {
            $this->info('LottieForm: Patched missing font ascent in '.basename($lottiePath));
        }
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
