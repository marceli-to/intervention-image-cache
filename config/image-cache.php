<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Image Cache Configuration
	|--------------------------------------------------------------------------
	|
	| This file contains the configuration for the image cache package.
	|
	*/

	// Cache path relative to storage_path()
	'cache_path' => 'app/public/cache/images',

	// Cache lifetime in minutes (default: 30 days)
	'lifetime' => 43200,

	// Paths to search for original images
	'paths' => [
		public_path('images'),
		storage_path('app/public/images'),
	],

	// Available templates
	'templates' => [
		'large' => \MarceliTo\InterventionImageCache\Templates\Large::class,
		'small' => \MarceliTo\InterventionImageCache\Templates\Small::class,
		'thumbnail' => \MarceliTo\InterventionImageCache\Templates\Thumbnail::class,
	],
	
	// Route configuration
	'register_routes' => true,
	
	// Middleware for the image routes
	'middleware' => ['web'],
];
