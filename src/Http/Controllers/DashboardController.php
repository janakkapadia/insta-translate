<?php

declare(strict_types=1);

namespace JanakKapadia\InstaTranslate\Http\Controllers;

use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use JanakKapadia\InstaTranslate\Support\PhpArrayFileHandler;
use JanakKapadia\InstaTranslate\TranslationManager;
use Throwable;

class DashboardController extends Controller
{
    public function index(TranslationManager $manager): View
    {
        $langDir = rtrim(config('insta-translate.lang_path') ?: (function_exists('lang_path') ? lang_path() : base_path('lang')), '/');
        $defaultLang = config('insta-translate.default_language') ?: 'en';
        $mode = config('insta-translate.mode', 'json');

        $missingTranslations = $manager->getMissingTranslationsSummary($langDir, $defaultLang, $mode);
        $allTranslations = $manager->getAllTranslationsSummary($langDir, $defaultLang, $mode);
        $locales = $mode === 'php' ? $manager->getPhpLocales($langDir, $defaultLang) : $manager->getJsonLocales($langDir, $defaultLang);

        /** @var view-string $view */
        $view = 'insta-translate::dashboard';

        return view($view, [
            'missingTranslations' => $missingTranslations,
            'allTranslations' => $allTranslations,
            'defaultLang' => $defaultLang,
            'mode' => $mode,
            'locales' => $locales,
        ]);
    }

    public function generate(Request $request, TranslationManager $manager): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'base_value' => 'required|string',
            'target_locale' => 'required|string',
            'context' => 'nullable|string',
        ]);

        // Bypass TrimStrings middleware
        $raw = json_decode($request->getContent(), true);
        $key = $raw['key'] ?? $request->input('key');
        $baseValue = $raw['base_value'] ?? $request->input('base_value');
        $targetLocale = $request->input('target_locale');
        $context = $raw['context'] ?? $request->input('context');

        $defaultLang = config('insta-translate.default_language') ?: 'en';
        $model = config('insta-translate.default_model') ?: 'claude';

        // Strip filename from key if in PHP mode
        $mode = config('insta-translate.mode', 'json');
        $actualKey = $key;

        if ($mode === 'php') {
            $parts = explode('::', $key, 2);

            if (count($parts) === 2) {
                $actualKey = $parts[1];
            }
        }

        $chunk = [$actualKey => $baseValue];

        if (empty($context)) {
            $context = $manager->findKeyContextInCode($actualKey);
        }

        try {
            $translatedChunk = $manager->translateChunk($chunk, $targetLocale, $model, $defaultLang, false, $context);

            if ($translatedChunk) {
                $translatedChunk = $manager->applyGlossaryOverrides($translatedChunk, $targetLocale);
                $translatedValue = $translatedChunk[$actualKey] ?? (reset($translatedChunk) ?: null);

                if ($translatedValue === null) {
                    throw new Exception('Translation not found in AI response.');
                }

                $translation = is_array($translatedValue) ? ($translatedValue[0] ?? '') : $translatedValue;

                return response()->json([
                    'success' => true,
                    'translation' => (string) $translation,
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate translation.',
        ], 500);
    }

    public function generateBatch(Request $request, TranslationManager $manager): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.key' => 'required|string',
            'items.*.base_value' => 'required',
            'target_locale' => 'required|string',
        ]);

        $items = $request->input('items');
        $targetLocale = $request->input('target_locale');

        $defaultLang = config('insta-translate.default_language') ?: 'en';
        $model = config('insta-translate.default_model') ?: 'claude';
        $mode = config('insta-translate.mode', 'json');

        // Group items by file if in PHP mode, or just create a simple chunk
        $chunk = [];
        $keyMap = []; // Maps actual key -> original key
        foreach ($items as $item) {
            $key = $item['key'];
            $actualKey = $key;

            if ($mode === 'php') {
                $parts = explode('::', $key, 2);

                if (count($parts) === 2) {
                    $actualKey = $parts[1];
                }
            }
            $chunk[$actualKey] = $item['base_value'];
            $keyMap[$actualKey] = $key;
        }

        try {
            // Translate the entire chunk in one go
            $translatedChunk = $manager->translateChunk($chunk, $targetLocale, $model, $defaultLang, false);

            if ($translatedChunk) {
                $translatedChunk = $manager->applyGlossaryOverrides($translatedChunk, $targetLocale);

                // Re-map back to original keys and ensure we have all translations
                $results = [];
                $keys = array_keys($chunk);
                $translatedValues = array_values($translatedChunk);

                foreach ($chunk as $actualKey => $baseVal) {
                    $originalKey = $keyMap[$actualKey];

                    // Match by actual key if present, otherwise try positional fallback if counts match, else null
                    if (isset($translatedChunk[$actualKey])) {
                        $val = $translatedChunk[$actualKey];
                    } elseif (count($keys) === count($translatedValues)) {
                        $idx = array_search($actualKey, $keys);
                        $val = $idx !== false ? $translatedValues[$idx] : null;
                    } else {
                        $val = $translatedChunk[$originalKey] ?? null;
                    }

                    if ($val === null) {
                        // Safe fallback, skip or use empty
                        continue;
                    }

                    $translation = is_array($val) ? ($val[0] ?? '') : $val;
                    $results[$originalKey] = (string) $translation;

                    // Automatically save the translation by mocking a save request
                    $saveRequest = new Request([
                        'key' => $originalKey,
                        'translation' => (string) $translation,
                        'target_locale' => $targetLocale,
                        'mode' => $mode,
                    ]);
                    $this->save($saveRequest);
                }

                return response()->json([
                    'success' => true,
                    'translations' => $results,
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate batch translation.',
        ], 500);
    }

    public function generateMultiLang(Request $request, TranslationManager $manager): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'base_value' => 'required|string',
            'target_locales' => 'required|array',
            'context' => 'nullable|string',
        ]);

        $targetLocales = $request->input('target_locales');
        $context = $request->input('context');

        // Bypass TrimStrings
        $raw = json_decode($request->getContent(), true);
        $key = $raw['key'] ?? $request->input('key');
        $baseValue = $raw['base_value'] ?? $request->input('base_value');

        $defaultLang = config('insta-translate.default_language') ?: 'en';
        $model = config('insta-translate.default_model') ?: 'claude';
        $mode = config('insta-translate.mode', 'json');

        $actualKey = $key;

        if ($mode === 'php') {
            $parts = explode('::', $key, 2);

            if (count($parts) === 2) {
                $actualKey = $parts[1];
            }
        }

        if (empty($context)) {
            $context = $manager->findKeyContextInCode($actualKey);
        }

        try {
            $results = $manager->translateKeyForLocales($actualKey, $baseValue, $targetLocales, $model, $defaultLang, $context);

            if ($results !== null) {
                return response()->json([
                    'success' => true,
                    'translations' => $results,
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate translations.',
        ], 500);
    }

    public function saveMultiLang(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'translations' => 'required|array',
            'mode' => 'required|in:json,php',
        ]);

        $mode = $request->input('mode');

        // Bypass TrimStrings
        $raw = json_decode($request->getContent(), true);
        $key = $raw['key'] ?? $request->input('key');
        $translations = $raw['translations'] ?? $request->input('translations');

        foreach ($translations as $locale => $translation) {
            $saveRequest = new Request([
                'key' => $key,
                'translation' => $translation,
                'target_locale' => $locale,
                'mode' => $mode,
            ]);
            $this->save($saveRequest);
        }

        return response()->json(['success' => true]);
    }

    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'translation' => 'required|string',
            'target_locale' => 'required|string',
            'mode' => 'required|in:json,php',
        ]);

        $targetLocale = $request->input('target_locale');
        $mode = $request->input('mode');

        // Bypass TrimStrings
        $raw = json_decode($request->getContent(), true);
        $key = $raw['key'] ?? $request->input('key');
        $translation = $raw['translation'] ?? $request->input('translation');

        $langDir = rtrim(config('insta-translate.lang_path') ?: (function_exists('lang_path') ? lang_path() : base_path('lang')), '/');

        if ($mode === 'php') {
            $parts = explode('::', $key, 2);

            if (count($parts) !== 2) {
                return response()->json(['success' => false, 'error' => 'Invalid key format for PHP mode.'], 400);
            }

            $filename = $parts[0];
            $actualKey = $parts[1];

            $targetPath = $langDir.'/'.$targetLocale.'/'.$filename;

            $handler = new PhpArrayFileHandler;

            $existingFlat = File::exists($targetPath)
                ? $handler->flattenWithDot($handler->read($targetPath))
                : [];

            $existingFlat[$actualKey] = $translation;

            $rebuilt = $handler->unflattenDotNotation($existingFlat);
            ksort($rebuilt);

            $dir = dirname($targetPath);

            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $handler->write($targetPath, $rebuilt);
        } else {
            $localePath = $langDir.'/'.$targetLocale.'.json';

            $existingTranslations = File::exists($localePath)
                ? json_decode(File::get($localePath), true) ?? []
                : [];

            $existingTranslations[$key] = $translation;
            ksort($existingTranslations);

            File::put($localePath, json_encode($existingTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');
        }

        return response()->json(['success' => true]);
    }

    public function addLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'target_locale' => 'required|string|regex:/^[a-zA-Z0-9_-]+$/',
            'mode' => 'required|in:json,php',
        ]);

        $targetLocale = $request->input('target_locale');
        $mode = $request->input('mode');
        $langDir = rtrim(config('insta-translate.lang_path') ?: (function_exists('lang_path') ? lang_path() : base_path('lang')), '/');

        if (! File::isDirectory($langDir)) {
            File::makeDirectory($langDir, 0755, true);
        }

        if ($mode === 'php') {
            $localePath = $langDir.'/'.$targetLocale;

            if (! File::exists($localePath)) {
                File::makeDirectory($localePath, 0755, true);
            }
        } else {
            $localePath = $langDir.'/'.$targetLocale.'.json';

            if (! File::exists($localePath)) {
                File::put($localePath, '{}');
            }
        }

        return response()->json(['success' => true]);
    }
}
