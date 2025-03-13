<?php

namespace MarceliTo\InterventionImageCache;

use Illuminate\Support\ServiceProvider;

class ImageCacheServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
		$this->mergeConfigFrom(
			__DIR__ . '/../config/image-cache.php', 'image-cache'
		);

		$this->app->singleton('image-cache', function ($app) {
			return new ImageCache();
		});
	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		// Publish configuration
		$this->publishes([
			__DIR__ . '/../config/image-cache.php' => config_path('image-cache.php'),
		], 'image-cache-config');

		// Register commands
		if ($this->app->runningInConsole()) {
			$this->commands([
				Commands\ClearImageCacheCommand::class,
			]);
		}
	}
}
