<?php

beforeEach(function () {
    $this->pluginPath = dirname(__DIR__);
    $this->manifestPath = $this->pluginPath.'/nativephp.json';
});

describe('Plugin Manifest', function () {
    it('has a valid nativephp.json file', function () {
        expect(file_exists($this->manifestPath))->toBeTrue();

        $content = file_get_contents($this->manifestPath);
        $manifest = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    it('has required fields', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest)->toHaveKeys(['namespace', 'bridge_functions']);
    });

    it('has valid bridge functions', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['bridge_functions'])->toBeArray()->toHaveCount(2);

        foreach ($manifest['bridge_functions'] as $function) {
            expect($function)->toHaveKeys(['name', 'android', 'ios']);
        }
    });

    it('registers ShowAnimation and Dismiss bridge functions', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $names = array_column($manifest['bridge_functions'], 'name');

        expect($names)->toContain('LottieForm.ShowAnimation');
        expect($names)->toContain('LottieForm.Dismiss');
    });

    it('declares Lottie dependencies for both platforms', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['android']['dependencies']['implementation'])
            ->toContain('com.airbnb.android:lottie-compose:6.7.1');

        expect($manifest['ios']['dependencies']['swift_packages'])
            ->toHaveCount(1)
            ->and($manifest['ios']['dependencies']['swift_packages'][0]['url'])
            ->toContain('lottie-spm');
    });

    it('registers both events', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['events'])->toContain('CodeMountain\\LottieForm\\Events\\AnimationStarted');
        expect($manifest['events'])->toContain('CodeMountain\\LottieForm\\Events\\AnimationCompleted');
    });
});

describe('Native Code', function () {
    it('has Android Kotlin file', function () {
        $kotlinFile = $this->pluginPath.'/resources/android/LottieFormFunctions.kt';
        expect(file_exists($kotlinFile))->toBeTrue();
    });

    it('has iOS Swift file', function () {
        $swiftFile = $this->pluginPath.'/resources/ios/LottieFormFunctions.swift';
        expect(file_exists($swiftFile))->toBeTrue();
    });
});

describe('PHP Classes', function () {
    it('has service provider', function () {
        expect(file_exists($this->pluginPath.'/src/LottieFormServiceProvider.php'))->toBeTrue();
    });

    it('has facade', function () {
        expect(file_exists($this->pluginPath.'/src/Facades/LottieForm.php'))->toBeTrue();
    });

    it('has main implementation class', function () {
        expect(file_exists($this->pluginPath.'/src/LottieForm.php'))->toBeTrue();
    });

    it('has AnimationStarted event', function () {
        expect(file_exists($this->pluginPath.'/src/Events/AnimationStarted.php'))->toBeTrue();
    });

    it('has AnimationCompleted event', function () {
        expect(file_exists($this->pluginPath.'/src/Events/AnimationCompleted.php'))->toBeTrue();
    });
});

describe('Composer Configuration', function () {
    it('has valid composer.json', function () {
        $composerPath = $this->pluginPath.'/composer.json';
        expect(file_exists($composerPath))->toBeTrue();

        $content = file_get_contents($composerPath);
        $composer = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($composer['type'])->toBe('nativephp-plugin');
        expect($composer['name'])->toBe('codemountain/mobile-lottie-form');
    });
});
