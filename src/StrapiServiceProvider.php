<?php

namespace Combindma\Strapi;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class StrapiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-strapi')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Strapi::class, function ($app): Strapi {
            /** @var ConfigRepository $config */
            $config = $app['config'];

            return new Strapi(
                Strapi::makeClient(
                    (string) $config->get('strapi.graphql_url', ''),
                    (string) $config->get('strapi.token', ''),
                    (int) $config->get('strapi.timeout', 30),
                ),
                $app->make(Repository::class),
            );
        });
    }
}
