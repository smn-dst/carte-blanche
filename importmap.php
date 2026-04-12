<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'map' => [
        'path' => './assets/js/map/map.js',
        'entrypoint' => true,
    ],
    'map-helpers' => [
        'path' => './assets/js/map/map-helpers.js',
    ],
    'registration-register' => [
        'path' => './assets/js/registration/register-page.js',
        'entrypoint' => true,
    ],
    'registration-preferences' => [
        'path' => './assets/js/registration/user-preferences-wizard.js',
        'entrypoint' => true,
    ],
    'restaurant-form' => [
        'path' => './assets/js/restaurant/restaurant-form-page.js',
        'entrypoint' => true,
    ],
    'encheres-detail' => [
        'path' => './assets/js/encheres/auction-ticket-stepper.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'mapbox-gl' => [
        'version' => '3.19.0',
    ],
    'mapbox-gl/dist/mapbox-gl.min.css' => [
        'version' => '3.19.0',
        'type' => 'css',
    ],
];
