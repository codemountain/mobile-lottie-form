<?php

namespace CodeMountain\LottieForm;

use CodeMountain\LottieForm\Commands\CopyAssetsCommand;
use Illuminate\Support\ServiceProvider;

class LottieFormServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LottieForm::class, function () {
            return new LottieForm;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
