<?php

namespace MarceliTo\InterventionImageCache;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
		
		// Register routes
		if (config('image-cache.register_routes', true)) {
			$this->registerRoutes();
		}
	}
	
	/**
	 * Register the package routes.
	 */
	protected function registerRoutes()
	{
		Route::group($this->routeConfiguration(), function () {
			Route::get('/img/{template}/{filename}/{maxW?}/{maxH?}/{coords?}', 
				[\MarceliTo\InterventionImageCache\Http\Controllers\ImageController::class, 'getResponse'])
				->name('image-cache.image');
		});
	}
	
	/**
	 * Get the route group configuration.
	 */
	protected function routeConfiguration()
	{
		return [
			'middleware' => config('image-cache.middleware', ['web']),
		];
	}
}
