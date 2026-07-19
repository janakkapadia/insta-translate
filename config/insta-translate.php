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
    | API Keys
    |--------------------------------------------------------------------------
    */
    'claude_key' => env('INSTA_TRANSLATE_CLAUDE_KEY'),
    'gemini_key' => env('INSTA_TRANSLATE_GEMINI_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Language File Path
    |--------------------------------------------------------------------------
    |
    | The path where the JSON translation files are stored.
    |
    */
    'lang_path' => env('INSTA_TRANSLATE_LANG_PATH', base_path('lang')),
];
