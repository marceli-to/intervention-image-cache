<?php

namespace MarceliTo\InterventionImageCache\Tests;

use MarceliTo\InterventionImageCache\ImageCacheServiceProvider;
use MarceliTo\InterventionImageCache\Facades\ImageCache;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ImageCacheServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'ImageCache' => ImageCache::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set up image cache config
        $app['config']->set('image-cache.cache_path', 'app/public/cache/images');
        $app['config']->set('image-cache.lifetime', 60); // 1 hour for tests
        $app['config']->set('image-cache.paths', [
            __DIR__ . '/fixtures/images',
        ]);
        $app['config']->set('image-cache.templates', [
            'large' => \MarceliTo\InterventionImageCache\Templates\Large::class,
            'small' => \MarceliTo\InterventionImageCache\Templates\Small::class,
            'thumbnail' => \MarceliTo\InterventionImageCache\Templates\Thumbnail::class,
            'crop' => \MarceliTo\InterventionImageCache\Templates\Crop::class,
        ]);
    }
}
