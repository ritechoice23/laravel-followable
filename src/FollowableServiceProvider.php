<?php

namespace Ritechoice23\Followable;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FollowableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-followable')
            ->hasConfigFile()
            ->hasMigration('2025_1_01_0000001_create_follows_table');
    }
}
