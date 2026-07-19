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
    'default_model' => env('AI_TRANSLATOR_MODEL', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    */
    'claude_key' => env('AI_TRANSLATOR_CLAUDE_KEY'),
    'gemini_key' => env('AI_TRANSLATOR_GEMINI_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Language File Path
    |--------------------------------------------------------------------------
    |
    | The path where the JSON translation files are stored.
    |
    */
    'lang_path' => env('AI_TRANSLATOR_LANG_PATH', base_path('lang')),
];
