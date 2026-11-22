<?php

namespace HindBiswas\ModelUtils;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelUtilsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name('laravel-model-utils')->hasConfigFile('lmu');
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lmu.php' => config_path('lmu.php'),
            ], 'lmu-config');

            $this->publishes([
                __DIR__.'/../resources/types/listing.ts' => resource_path('js/types/listing.ts'),
            ], 'lmu-types');
        }
    }
}
