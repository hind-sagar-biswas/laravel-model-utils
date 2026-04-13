<?php

namespace HindBiswas\ModelUtils;

use HindBiswas\ModelUtils\Commands\ModelUtilsCommand;
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
        $package
            ->name('laravel-model-utils')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_model_utils_table')
            ->hasCommand(ModelUtilsCommand::class);
    }
}
