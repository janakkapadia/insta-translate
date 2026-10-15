<?php

declare(strict_types=1);

namespace JanakKapadia\InstaTranslate;

use Exception;
use Illuminate\Support\Facades\File;
use JanakKapadia\InstaTranslate\Support\PhpArrayFileHandler;
use Laravel\Ai\AnonymousAgent;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class TranslationManager
{
    /**
     * Glossary data loaded from glossary.json.
     *
     * @var array{never_translate?: list<string>, specific_translations?: array<string, array<string, string>>}
     */
    private array $glossary = [];

    public function __construct()
    {
        $this->loadGlossary();
    }

    /**
     * Load glossary from the configured path.
     */
    private function loadGlossary(): void
    {
        $glossaryPath = config('insta-translate.glossary_path', base_path('lang/glossary.json'));

        if (! File::exists($glossaryPath)) {
            return;
        }

        $data = json_decode(File::get($glossaryPath), true);

        if (is_array($data)) {
            $this->glossary = $data;
        }
    }

    /**
     * Build prompt instructions from the glossary for a given target locale.
     */
    public function buildGlossaryPrompt(string $targetLocale): string
    {
        $parts = [];

        $neverTranslate = $this->glossary['never_translate'] ?? [];

        if ($neverTranslate !== []) {
            $terms = implode(', ', array_map(fn (string $t) => "\"$t\"", $neverTranslate));
            $parts[] = "IMPORTANT: The following terms are brand names or technical terms and must NEVER be translated. Keep them exactly as-is in the output: {$terms}. ";
        }

        $specificTranslations = $this->glossary['specific_translations'] ?? [];
        $localeOverrides = [];
        foreach ($specificTranslations as $term => $locales) {
            if (isset($locales[$targetLocale])) {
                $localeOverrides[] = "\"{$term}\" must be translated as \"{$locales[$targetLocale]}\"";
            }
        }

        if ($localeOverrides !== []) {
            $parts[] = 'Use these mandatory translations: '.implode('; ', $localeOverrides).'. ';
        }

        return implode('', $parts);
    }

    /**
     * Apply glossary-specific translation overrides after getting AI response.
     *
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    public function applyGlossaryOverrides(array $translations, string $targetLocale): array
    {
        $specificTranslations = $this->glossary['specific_translations'] ?? [];

        if (empty($specificTranslations)) {
            return $translations;
        }

        foreach ($translations as $key => $value) {
            if (is_string($value)) {
                foreach ($specificTranslations as $term => $locales) {
                    if (isset($locales[$targetLocale]) && stripos($value, $term) !== false) {
                        $value = str_ireplace($term, $locales[$targetLocale], $value);
                    }
                }
                $translations[$key] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $idx => $variation) {
                    if (is_string($variation)) {
                        foreach ($specificTranslations as $term => $locales) {
                            if (isset($locales[$targetLocale]) && stripos($variation, $term) !== false) {
                                $variation = str_ireplace($term, $locales[$targetLocale], $variation);
                            }
                        }
                        $value[$idx] = $variation;
                    }
                }
                $translations[$key] = $value;
            }
        }

        return $translations;
    }

    /**
     * Mask placeholders in a translation chunk before sending to the AI model.
     *
     * @param  array<string, mixed>  $chunk
     * @return array{masked: array<string, mixed>, token_map: array<string, string>}
     */
    public function maskPlaceholdersInChunk(array $chunk): array
    {
        $masked = [];
        $tokenMap = [];
        $placeholderToToken = [];
        $counter = 0;

        foreach ($chunk as $key => $value) {
            if (is_string($value)) {
                $masked[$key] = $this->maskString($value, $tokenMap, $placeholderToToken, $counter);
            } else {
                $masked[$key] = $value;
            }
        }

        return [
            'masked' => $masked,
            'token_map' => $tokenMap,
        ];
    }

    /**
     * Mask placeholders in a single string before sending to the AI model.
     *
     * @return array{masked: string, token_map: array<string, string>}
     */
    public function maskPlaceholdersInString(string $text): array
    {
        $tokenMap = [];
        $placeholderToToken = [];
        $counter = 0;

        $masked = $this->maskString($text, $tokenMap, $placeholderToToken, $counter);

        return [
            'masked' => $masked,
            'token_map' => $tokenMap,
        ];
    }

    /**
     * Mask all colon-prefixed and curly-brace placeholders in a string.
     *
     * @param  array<string, string>  $tokenMap
     * @param  array<string, string>  $placeholderToToken
     */
    private function maskString(string $text, array &$tokenMap, array &$placeholderToToken, int &$counter): string
    {
        $pattern = '/(?:\{\{\s*\$?[a-zA-Z0-9_.-]+\s*\}\}|\{\s*\$?[a-zA-Z0-9_.-]+\s*\}|(?<![a-zA-Z0-9_:]):([a-zA-Z_][a-zA-Z0-9_]*))/';

        return preg_replace_callback($pattern, function (array $matches) use (&$tokenMap, &$placeholderToToken, &$counter): string {
            $placeholder = $matches[0];

            if (isset($placeholderToToken[$placeholder])) {
                return $placeholderToToken[$placeholder];
            }

            $token = "__IT_VAR_{$counter}__";
            $counter++;

            $placeholderToToken[$placeholder] = $token;
            $tokenMap[$token] = $placeholder;

            return $token;
        }, $text) ?? $text;
    }

    /**
     * Restore masked placeholder tokens in translated content.
     *
     * @param  string|array<mixed>  $content
     * @param  array<string, string>  $tokenMap
     * @return string|array<mixed>
     */
    public function restorePlaceholders(string|array $content, array $tokenMap): string|array
    {
        if (empty($tokenMap)) {
            return $content;
        }

        if (is_array($content)) {
            $restored = [];
            foreach ($content as $key => $value) {
                $restored[$key] = is_string($value) || is_array($value)
                    ? $this->restorePlaceholders($value, $tokenMap)
                    : $value;
            }

            return $restored;
        }

        // Step 1: Direct token replacement
        $result = strtr($content, $tokenMap);

        // Step 2: Resilient regex replacement for case-insensitive or whitespace variations (e.g. __ IT_VAR_0 __ or __it_var_0__)
        $result = preg_replace_callback('/_{1,2}\s*IT_VAR_(\d+)\s*_{1,2}/i', function (array $matches) use ($tokenMap): string {
            $standardToken = "__IT_VAR_{$matches[1]}__";

            return $tokenMap[$standardToken] ?? $matches[0];
        }, $result) ?? $result;

        return $result;
    }

    /**
     * @param  array<string, string>  $chunk
     * @return array<string, mixed>|null
     */
    public function translateChunk(array $chunk, string $targetLocale, string $model, string $defaultLang, bool $multiple = false, ?string $context = null): ?array
    {
        $maskedData = $this->maskPlaceholdersInChunk($chunk);
        /** @var array<string, string> $chunkToSend */
        $chunkToSend = $maskedData['masked'];
        $tokenMap = $maskedData['token_map'];

        $glossaryInstructions = $this->buildGlossaryPrompt($targetLocale);
        $contextLine = $context !== null ? "Context: {$context}\n" : '';

        $placeholderInstructions = ! empty($tokenMap)
            ? 'IMPORTANT: Placeholder tokens like __IT_VAR_0__, __IT_VAR_1__ represent variables (such as :name, :attribute, or {value}) and must NEVER be translated, modified, or omitted. Preserve every __IT_VAR_X__ token exactly as-is in your output at the grammatically appropriate position. '
            : 'Do not translate variables or placeholders. ';

        if ($multiple) {
            $prompt = $contextLine.
                "Translate the following JSON key-value pairs from {$defaultLang} to {$targetLocale}. ".
                'Keep the keys exactly the same. '.
                $placeholderInstructions.
                $glossaryInstructions.
                'Provide 3 distinct translation variations for each key. '.
                "Return ONLY a valid JSON object where keys are the same, and the value is a JSON array of 3 strings. No markdown formatting or other text.\n\n".
                json_encode($chunkToSend, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $prompt = $contextLine.
                "Translate the following JSON key-value pairs from {$defaultLang} to {$targetLocale}. ".
                'Keep the keys exactly the same. '.
                $placeholderInstructions.
                $glossaryInstructions.
                "Return ONLY a valid JSON object without markdown formatting or other text.\n\n".
                json_encode($chunkToSend, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $actualModel = $this->resolveModelName($model);

        $result = null;

        if (str_starts_with($actualModel, 'claude')) {
            $result = $this->callAi($prompt, $actualModel, 'anthropic');
        } elseif (str_starts_with($actualModel, 'gemini') || str_starts_with($actualModel, 'gemma')) {
            $result = $this->callAi($prompt, $actualModel, 'gemini');
        } else {
            throw new Exception("Unknown or unsupported model prefix: {$actualModel}");
        }

        if (is_array($result)) {
            $result = $this->applyGlossaryOverrides($result, $targetLocale);
            /** @var array<string, mixed> $result */
            $result = $this->restorePlaceholders($result, $tokenMap);
        }

        return $result;
    }

    public function resolveModelName(string $model): string
    {
        if ($model === 'claude') {
            return 'claude-3-5-sonnet-20241022';
        }

        if ($model === 'gemini') {
            return 'gemini-1.5-flash';
        }

        return $model;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function callAi(string $prompt, string $model, string $provider): ?array
    {
        // Increase time limit so retry sleeps and API timeouts don't cause a 500 Fatal Error (which breaks JSON response)
        set_time_limit(120);

        $maxRetries = 2;
        $attempt = 1;

        while (true) {
            try {
                $systemPrompt = 'You are a professional localization and translation assistant. You accurately translate text while strictly preserving all placeholder tokens, formatting, and variables.';
                $agent = new AnonymousAgent($systemPrompt, [], []);
                $response = $agent->prompt($prompt, [], $provider, $model);

                return $this->parseJsonResponse($response->text);
            } catch (Throwable $e) {
                // If it's the last attempt or not a transient error, throw it.
                if ($attempt >= $maxRetries || ! preg_match('/(overloaded|429|503|rate limit)/i', $e->getMessage())) {
                    throw new Exception(ucfirst($provider).' API Error: '.$e->getMessage());
                }

                // Exponential backoff: 2s, 4s, etc.
                sleep(2 * $attempt);
                $attempt++;
            }
        }
    }

    /**
     * Parse the AI response expecting a JSON block.
     *
     * @return array<string, mixed>|null
     */
    public function parseJsonResponse(?string $content): ?array
    {
        if (! $content) {
            return null;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } else {
            // ... fallback parsing ...
            $content = trim($content);
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON response: '.json_last_error_msg());
        }

        array_walk_recursive($data, function (mixed &$value) {
            if (is_string($value)) {
                $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        });

        return $data;
    }

    /**
     * Automatically scan the codebase to find where a key is used to provide context to the AI.
     */
    public function findKeyContextInCode(string $key): ?string
    {
        $paths = array_filter([
            resource_path('views'),
            app_path(),
            resource_path('js'),
            base_path('routes'),
        ], fn (string $path) => is_dir($path));

        if (empty($paths)) {
            return null;
        }

        $finder = new Finder;
        $finder->in($paths)->name('*.php')->name('*.vue')->name('*.js')->name('*.jsx')->name('*.tsx')->files();

        $usages = [];
        foreach ($finder as $file) {
            $contents = $file->getContents();

            if (str_contains($contents, $key)) {
                $lines = explode("\n", $contents);
                foreach ($lines as $i => $line) {
                    if (str_contains($line, $key)) {
                        $start = max(0, $i - 1);
                        $end = min(count($lines) - 1, $i + 1);
                        $snippet = implode("\n", array_slice($lines, $start, $end - $start + 1));

                        $filename = $file->getRelativePathname();
                        $usages[] = "File: {$filename}\nCode snippet:\n{$snippet}";

                        if (count($usages) >= 3) {
                            break 2;
                        }
                    }
                }
            }
        }

        if (empty($usages)) {
            return null;
        }

        return "This text is used in the following codebase locations (use this to understand the context of how to translate it):\n\n".implode("\n\n---\n\n", $usages);
    }

    /**
     * Resolve which keys need to be translated.
     *
     * @param  array<string, string>  $baseTranslations
     * @param  array<string, string>  $existingTranslations
     * @param  array<int, string>  $specificKeys
     * @return array<string, string>
     */
    public function resolveMissingKeys(array $baseTranslations, array $existingTranslations, array $specificKeys = [], bool $translateAll = false): array
    {
        $missingKeys = [];

        if (! empty($specificKeys)) {
            foreach ($specificKeys as $key) {
                if (isset($baseTranslations[$key])) {
                    if (! isset($existingTranslations[$key]) || $translateAll) {
                        $missingKeys[$key] = $baseTranslations[$key];
                    }
                }
            }
        } else {
            foreach ($baseTranslations as $key => $value) {
                if ($translateAll || ! isset($existingTranslations[$key])) {
                    $missingKeys[$key] = $value;
                }
            }
        }

        return $missingKeys;
    }

    /**
     * Get all target locales for JSON
     *
     * @return array<string>
     */
    public function getJsonLocales(string $langDir, string $defaultLang): array
    {
        if (! File::isDirectory($langDir)) {
            return [];
        }

        return collect(File::files($langDir))
            ->map(fn (SplFileInfo $file) => $file->getFilename())
            ->filter(fn (string $file) => str_ends_with($file, '.json') && $file !== $defaultLang.'.json' && ! str_starts_with($file, 'php_'))
            ->map(fn (string $file) => str_replace('.json', '', $file))
            ->values()
            ->all();
    }

    /**
     * Get all target locales for PHP
     *
     * @return array<string>
     */
    public function getPhpLocales(string $langDir, string $defaultLang): array
    {
        if (! File::isDirectory($langDir)) {
            return [];
        }

        return collect(File::directories($langDir))
            ->map(fn (string $dir) => basename($dir))
            ->filter(fn (string $dir) => $dir !== $defaultLang)
            ->values()
            ->all();
    }

    /**
     * Get summary of all missing translations for the dashboard.
     *
     * @return array<string, array{base_value: string, missing_in: list<string>}>
     */
    public function getMissingTranslationsSummary(string $langDir, string $defaultLang, string $mode = 'json'): array
    {
        $summary = [];

        if ($mode === 'php') {
            $baseDir = $langDir.'/'.$defaultLang;

            if (! File::isDirectory($baseDir)) {
                return [];
            }

            $handler = new PhpArrayFileHandler;
            $locales = $this->getPhpLocales($langDir, $defaultLang);

            /** @var list<SplFileInfo> $baseFiles */
            $baseFiles = File::files($baseDir);

            foreach ($baseFiles as $baseFile) {
                if ($baseFile->getExtension() !== 'php') {
                    continue;
                }

                $filename = $baseFile->getFilename();
                $baseTranslations = $handler->read($baseFile->getPathname());
                $baseFlat = $handler->flattenWithDot($baseTranslations);

                foreach ($locales as $locale) {
                    $targetPath = $langDir.'/'.$locale.'/'.$filename;
                    $existingFlat = File::exists($targetPath)
                        ? $handler->flattenWithDot($handler->read($targetPath))
                        : [];

                    $missingKeys = $this->resolveMissingKeys($baseFlat, $existingFlat);

                    foreach ($missingKeys as $key => $value) {
                        $fullKey = $filename.'::'.$key;

                        if (! isset($summary[$fullKey])) {
                            $summary[$fullKey] = [
                                'base_value' => $value,
                                'missing_in' => [],
                            ];
                        }
                        $summary[$fullKey]['missing_in'][] = $locale;
                    }
                }
            }
        } else {
            $baseLangFile = $langDir.'/'.$defaultLang.'.json';

            if (! File::exists($baseLangFile)) {
                return [];
            }

            $baseTranslations = json_decode(File::get($baseLangFile), true) ?? [];

            if (! is_array($baseTranslations)) {
                return [];
            }

            $locales = $this->getJsonLocales($langDir, $defaultLang);

            foreach ($locales as $locale) {
                $localePath = $langDir.'/'.$locale.'.json';
                $existingTranslations = File::exists($localePath) ? json_decode(File::get($localePath), true) ?? [] : [];

                $missingKeys = $this->resolveMissingKeys($baseTranslations, $existingTranslations);

                foreach ($missingKeys as $key => $value) {
                    if (! isset($summary[$key])) {
                        $summary[$key] = [
                            'base_value' => $value,
                            'missing_in' => [],
                        ];
                    }
                    $summary[$key]['missing_in'][] = $locale;
                }
            }
        }

        return $summary;
    }

    /**
     * Get summary of all translations for the dashboard.
     *
     * @return array<string, array{base_value: string, translations: array<string, string>, missing_in: list<string>}>
     */
    public function getAllTranslationsSummary(string $langDir, string $defaultLang, string $mode = 'json'): array
    {
        /** @var array<string, array{base_value: string, translations: array<string, string>, missing_in: list<string>}> $summary */
        $summary = [];

        if ($mode === 'php') {
            $baseDir = $langDir.'/'.$defaultLang;

            if (! File::isDirectory($baseDir)) {
                return [];
            }

            $handler = new PhpArrayFileHandler;
            $locales = $this->getPhpLocales($langDir, $defaultLang);

            /** @var list<SplFileInfo> $baseFiles */
            $baseFiles = File::files($baseDir);

            foreach ($baseFiles as $baseFile) {
                if ($baseFile->getExtension() !== 'php') {
                    continue;
                }

                $filename = $baseFile->getFilename();
                $baseTranslations = $handler->read($baseFile->getPathname());
                $baseFlat = $handler->flattenWithDot($baseTranslations);

                // Initialize all base keys
                foreach ($baseFlat as $key => $value) {
                    $fullKey = $filename.'::'.$key;
                    $summary[$fullKey] = [
                        'base_value' => (string) $value,
                        'translations' => [],
                        'missing_in' => [],
                    ];
                }

                foreach ($locales as $locale) {
                    $targetPath = $langDir.'/'.$locale.'/'.$filename;
                    $existingFlat = File::exists($targetPath)
                        ? $handler->flattenWithDot($handler->read($targetPath))
                        : [];

                    foreach ($baseFlat as $key => $value) {
                        $fullKey = $filename.'::'.$key;

                        /** @var array{base_value: string, translations: array<string, string>, missing_in: list<string>} $data */
                        $data = $summary[$fullKey];

                        if (isset($existingFlat[$key])) {
                            $data['translations'][$locale] = (string) $existingFlat[$key];
                        } else {
                            $data['missing_in'][] = $locale;
                        }

                        $summary[$fullKey] = $data;
                    }
                }
            }
        } else {
            $baseLangFile = $langDir.'/'.$defaultLang.'.json';

            if (! File::exists($baseLangFile)) {
                return [];
            }

            $baseTranslations = json_decode(File::get($baseLangFile), true) ?? [];

            if (! is_array($baseTranslations)) {
                return [];
            }

            $locales = $this->getJsonLocales($langDir, $defaultLang);

            // Initialize all base keys
            foreach ($baseTranslations as $key => $value) {
                $summary[$key] = [
                    'base_value' => is_array($value) ? (string) json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                    'translations' => [],
                    'missing_in' => [],
                ];
            }

            foreach ($locales as $locale) {
                $localePath = $langDir.'/'.$locale.'.json';
                $existingTranslations = File::exists($localePath) ? json_decode(File::get($localePath), true) ?? [] : [];

                foreach ($baseTranslations as $key => $value) {
                    /** @var array{base_value: string, translations: array<string, string>, missing_in: list<string>} $data */
                    $data = $summary[$key];

                    if (isset($existingTranslations[$key])) {
                        $data['translations'][$locale] = is_array($existingTranslations[$key]) ? (string) json_encode($existingTranslations[$key], JSON_UNESCAPED_UNICODE) : (string) $existingTranslations[$key];
                    } else {
                        $data['missing_in'][] = $locale;
                    }

                    $summary[$key] = $data;
                }
            }
        }

        return $summary;
    }

    /**
     * @param  array<string>  $targetLocales
     * @return array<string, mixed>|null
     */
    public function translateKeyForLocales(string $key, string $baseValue, array $targetLocales, string $model, string $defaultLang, ?string $context = null): ?array
    {
        if (empty($targetLocales)) {
            return [];
        }

        $maskedData = $this->maskPlaceholdersInString($baseValue);
        $valueToSend = $maskedData['masked'];
        $tokenMap = $maskedData['token_map'];

        $contextLine = $context !== null ? "Context: {$context}\n" : '';

        $placeholderInstructions = ! empty($tokenMap)
            ? 'IMPORTANT: Placeholder tokens like __IT_VAR_0__, __IT_VAR_1__ represent variables (such as :name, :attribute, or {value}) and must NEVER be translated, modified, or omitted. Preserve every __IT_VAR_X__ token exactly as-is in your output at the grammatically appropriate position. '
            : 'Do not translate variables or placeholders. ';

        $localesString = implode(', ', $targetLocales);

        $prompt = $contextLine.
            "Translate the following string from {$defaultLang} into the following target locales: {$localesString}. ".
            $placeholderInstructions.
            "Return ONLY a valid JSON object where the keys are the target locale codes and the values are the translated strings. No markdown formatting or other text.\n\n".
            "String to translate:\n{$valueToSend}";

        $actualModel = $this->resolveModelName($model);

        $result = null;

        if (str_starts_with($actualModel, 'claude')) {
            $result = $this->callAi($prompt, $actualModel, 'anthropic');
        } elseif (str_starts_with($actualModel, 'gemini') || str_starts_with($actualModel, 'gemma')) {
            $result = $this->callAi($prompt, $actualModel, 'gemini');
        } else {
            throw new Exception("Unknown or unsupported model prefix: {$actualModel}");
        }

        if (is_array($result)) {
            // Apply glossary overrides per locale
            foreach ($targetLocales as $locale) {
                if (isset($result[$locale])) {
                    $overridden = $this->applyGlossaryOverrides([$key => $result[$locale]], $locale);
                    $result[$locale] = $overridden[$key] ?? $result[$locale];
                }
            }

            /** @var array<string, mixed> $result */
            $result = $this->restorePlaceholders($result, $tokenMap);
        }

        return $result;
    }
}
