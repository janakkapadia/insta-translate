<?php

declare(strict_types=1);

namespace InstaRequest\InstaTranslate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Ai\AnonymousAgent;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class TranslationGenerateCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'translation:generate 
                            {--batch=50 : Number of keys to translate per API request}
                            {--model= : Which model to use (e.g. claude-3-opus-20240229, gemini-1.5-pro). Overrides env config.}
                            {--lang= : Specific language code to translate/create (e.g., fr, hi).}
                            {--key=* : Specific keys to translate (can be used multiple times). Overrides the missing check.}
                            {--multiple : Generate multiple translation options to choose from.}
                            {--all : Translate all keys, overwriting existing translations.}';

    /**
     * The command description.
     */
    protected $description = 'Generate translations using Anthropic or Google AI models.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Translation generation started.');

        $defaultLang = config('insta-translate.default_language', 'en');
        $langDir = rtrim(config('insta-translate.lang_path', base_path('lang')), '/');
        $baseLangFile = $langDir.'/'.$defaultLang.'.json';

        if (! File::exists($baseLangFile)) {
            $this->error("Base language file {$defaultLang}.json does not exist.");

            return self::FAILURE;
        }

        $baseTranslations = json_decode(File::get($baseLangFile), true);

        if (! is_array($baseTranslations)) {
            $this->error("Invalid {$defaultLang}.json format.");

            return self::FAILURE;
        }

        $batchSize = max(1, (int) $this->option('batch'));
        $model = is_string($this->option('model')) ? $this->option('model') : (string) config('insta-translate.default_model', 'claude');
        $translateAll = (bool) $this->option('all');
        $specificKeys = (array) $this->option('key');
        $multiple = (bool) $this->option('multiple');

        $langOption = is_string($this->option('lang')) ? $this->option('lang') : null;

        if (! empty($specificKeys) && ! $langOption) {
            $langOption = $this->ask('For which language code do you want to translate these keys? (Leave empty for all available languages)');

            if (empty($langOption)) {
                $langOption = null;
            }
        }

        if ($langOption) {
            $localeFile = str_ends_with($langOption, '.json') ? $langOption : $langOption.'.json';
            $locales = collect([$localeFile]);
        } else {
            $locales = collect(File::files($langDir))
                ->map(fn (SplFileInfo $file) => $file->getFilename())
                ->filter(fn (string $file) => str_ends_with($file, '.json') && $file !== $defaultLang.'.json' && ! str_starts_with($file, 'php_'));
        }

        foreach ($locales as $localeFile) {
            $localePath = $langDir.'/'.$localeFile;
            $targetLocale = str_replace('.json', '', $localeFile);
            $this->info("Processing locale: {$targetLocale}");

            $existingTranslations = File::exists($localePath) ? json_decode(File::get($localePath), true) ?? [] : [];

            $missingKeys = [];

            if (! empty($specificKeys)) {
                foreach ($specificKeys as $key) {
                    if (isset($baseTranslations[$key])) {
                        if (isset($existingTranslations[$key]) && ! $translateAll) {
                            if (! $this->confirm("Key '{$key}' already exists in {$targetLocale}. Do you want to regenerate it?", false)) {
                                continue;
                            }
                        }
                        $missingKeys[$key] = $baseTranslations[$key];
                    } else {
                        $this->warn("Key '{$key}' not found in {$defaultLang}.json. Skipping.");
                    }
                }
            } else {
                foreach ($baseTranslations as $key => $value) {
                    if ($translateAll || ! isset($existingTranslations[$key])) {
                        $missingKeys[$key] = $value;
                    }
                }
            }

            if (empty($missingKeys)) {
                $this->line("No missing translations for {$targetLocale}.");

                continue;
            }

            $this->info('Found '.count($missingKeys)." missing keys for {$targetLocale}.");

            $chunks = array_chunk($missingKeys, $batchSize, true);

            foreach ($chunks as $index => $chunk) {
                $this->line('Translating batch '.($index + 1).' of '.count($chunks).'...');

                $translatedChunk = $this->translateChunk($chunk, $targetLocale, $model, $defaultLang, $multiple);

                if ($translatedChunk) {
                    foreach ($translatedChunk as $key => $value) {
                        if ($multiple && is_array($value)) {
                            // Flatten scalar values to strings for PHPStan, though they should be strings
                            $options = array_map(fn (mixed $val) => (string) $val, $value);
                            $selected = $this->choice("Select translation for '{$key}' in {$targetLocale}", $options, 0);
                            $existingTranslations[$key] = $selected;
                        } else {
                            $existingTranslations[$key] = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
                        }
                    }
                } else {
                    $this->error('Failed to translate batch '.($index + 1).'. Skipping.');
                }
            }

            // Save the updated translations, sorted by key for consistency
            ksort($existingTranslations);
            File::put($localePath, json_encode($existingTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');
            $this->info("Saved {$localeFile}.");
        }

        $this->info('Translation generation complete.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $chunk
     * @return array<string, mixed>|null
     */
    private function translateChunk(array $chunk, string $targetLocale, string $model, string $defaultLang, bool $multiple = false): ?array
    {
        if ($multiple) {
            $prompt = "Translate the following JSON key-value pairs from {$defaultLang} to {$targetLocale}. ".
                'Keep the keys exactly the same. Do not translate placeholders like :name or {value}. '.
                'Provide 3 distinct translation variations for each key. '.
                "Return ONLY a valid JSON object where keys are the same, and the value is a JSON array of 3 strings. No markdown formatting or other text.\n\n".
                json_encode($chunk, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $prompt = "Translate the following JSON key-value pairs from {$defaultLang} to {$targetLocale}. ".
                'Keep the keys exactly the same. Do not translate placeholders like :name or {value}. '.
                "Return ONLY a valid JSON object without markdown formatting or other text.\n\n".
                json_encode($chunk, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $actualModel = $this->resolveModelName($model);

        if (str_starts_with($actualModel, 'claude')) {
            return $this->callAi($prompt, $actualModel, 'anthropic');
        } elseif (str_starts_with($actualModel, 'gemini') || str_starts_with($actualModel, 'gemma')) {
            return $this->callAi($prompt, $actualModel, 'gemini');
        }

        $this->error("Unknown or unsupported model prefix: {$actualModel}");

        return null;
    }

    private function resolveModelName(string $model): string
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
    private function callAi(string $prompt, string $model, string $provider): ?array
    {
        try {
            $agent = new AnonymousAgent('You are a helpful translation assistant.', [], []);
            $response = $agent->prompt($prompt, [], $provider, $model);

            return $this->parseJsonResponse($response->text);
        } catch (Throwable $e) {
            $this->error(ucfirst($provider).' API Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonResponse(?string $content): ?array
    {
        if (! $content) {
            return null;
        }

        // Remove possible markdown backticks
        $content = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $content);
        $content = preg_replace('/```\s*(.*?)\s*```/s', '$1', $content);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse JSON response: '.json_last_error_msg());
            $this->line('Raw response: '.$content);

            return null;
        }

        return $decoded;
    }
}
