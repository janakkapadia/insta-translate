<?php

declare(strict_types=1);

namespace InstaRequest\InstaTranslate\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use InstaRequest\InstaTranslate\Support\PhpArrayFileHandler;
use InstaRequest\InstaTranslate\TranslationManager;
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

        $key = $request->input('key');
        $baseValue = $request->input('base_value');
        $targetLocale = $request->input('target_locale');
        $context = $request->input('context');

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
                $translation = is_array($translatedChunk[$actualKey]) ? ($translatedChunk[$actualKey][0] ?? '') : $translatedChunk[$actualKey];

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

    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'translation' => 'required|string',
            'target_locale' => 'required|string',
            'mode' => 'required|in:json,php',
        ]);

        $key = $request->input('key');
        $translation = $request->input('translation');
        $targetLocale = $request->input('target_locale');
        $mode = $request->input('mode');
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
}
