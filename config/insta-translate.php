<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Model
    |--------------------------------------------------------------------------
    |
    | Supported models: "claude", "gemini"
    |
    */
    'default_model' => env('INSTA_TRANSLATE_MODEL', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language code to use as the base for translations.
    |
    */
    'default_language' => env('INSTA_TRANSLATE_DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Translation Mode
    |--------------------------------------------------------------------------
    |
    | Set to 'json' to process JSON files, or 'php' for PHP array files.
    |
    */
    'mode' => env('INSTA_TRANSLATE_MODE', 'json'),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | API keys are managed directly by Laravel AI using standard env variables:
    | ANTHROPIC_API_KEY, GEMINI_API_KEY, etc.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Language File Path
    |--------------------------------------------------------------------------
    |
    | The path where the JSON translation files are stored.
    |
    */
    'lang_path' => env('INSTA_TRANSLATE_LANG_PATH', base_path('lang')),

    /*
    |--------------------------------------------------------------------------
    | InstaTranslate Dashboard Route Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where the InstaTranslate dashboard will be accessible from.
    | If null, the dashboard will be available on all domains or the default app domain.
    |
    */
    'domain' => env('INSTA_TRANSLATE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | InstaTranslate Dashboard Route Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where the InstaTranslate dashboard will be accessible from.
    | You can change this to any path you like (e.g., 'translations-ui').
    |
    */
    'path' => env('INSTA_TRANSLATE_PATH', 'insta-translate'),

    /*
    |--------------------------------------------------------------------------
    | InstaTranslate Dashboard Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each InstaTranslate dashboard route,
    | giving you the chance to add your own middleware to this list or change
    | any of the existing middleware.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Glossary Path
    |--------------------------------------------------------------------------
    |
    | Path to a glossary.json file that defines brand terms to never translate
    | and locale-specific overrides for certain terms.
    |
    */
    'glossary_path' => env('INSTA_TRANSLATE_GLOSSARY_PATH', base_path('lang/glossary.json')),

];
