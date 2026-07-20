<?php

declare(strict_types=1);

namespace InstaRequest\InstaTranslate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InstaRequest\InstaTranslate\Support\PhpArrayFileHandler;
use InstaRequest\InstaTranslate\TranslationManager;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class TranslationGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:generate
                            {--batch=10 : The number of keys to translate per API request}
                            {--model= : The model to use for translation (e.g., claude, gemini, gemma-2-2b-it)}
                            {--all : Translate all keys, overwriting existing ones}
                            {--key=* : Only translate specific keys (can be used multiple times)}
                            {--lang= : Only translate for a specific language code}
                            {--multiple : Generate 3 variations per key and ask user to choose}
                            {--context= : Provide context for the AI about the application domain or terminology}
                            {--php : Process PHP array files instead of JSON files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing translations using AI';

    /**
     * Execute the console command.
     */
    public function handle(TranslationManager $manager): int
    {
        $this->line('Translation generation started.');

        $defaultLang = config('insta-translate.default_language', 'en');
        $langDir = rtrim(config('insta-translate.lang_path', base_path('lang')), '/');
        $phpMode = (bool) $this->option('php');

        if ($phpMode) {
            return $this->handlePhpFiles($manager, $langDir, $defaultLang);
        }

        return $this->handleJsonFiles($manager, $langDir, $defaultLang);
    }

    private function handleJsonFiles(TranslationManager $manager, string $langDir, string $defaultLang): int
    {
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
        $context = is_string($this->option('context')) ? $this->option('context') : null;

        $langOption = is_string($this->option('lang')) ? $this->option('lang') : null;

        if (! empty($specificKeys) && ! $langOption) {
            $langOption = $this->ask('For which language code do you want to translate these keys? (Leave empty for all available languages)');

            if (empty($langOption)) {
                $langOption = null;
            }
        }

        if ($langOption) {
            $targetLocales = [$langOption];
        } else {
            $targetLocales = $manager->getJsonLocales($langDir, $defaultLang);
        }

        foreach ($targetLocales as $targetLocale) {
            $localeFile = $targetLocale.'.json';
            $localePath = $langDir.'/'.$localeFile;
            $this->info("Processing locale: {$targetLocale}");

            $existingTranslations = File::exists($localePath) ? json_decode(File::get($localePath), true) ?? [] : [];

            $missingKeys = $manager->resolveMissingKeys($baseTranslations, $existingTranslations, $specificKeys, $translateAll);

            if (empty($missingKeys)) {
                $this->line("No missing translations for {$targetLocale}.");

                continue;
            }

            $this->info('Found '.count($missingKeys)." missing keys for {$targetLocale}.");

            $chunks = array_chunk($missingKeys, $batchSize, true);

            foreach ($chunks as $index => $chunk) {
                $this->line('Translating batch '.($index + 1).' of '.count($chunks).'...');

                try {
                    $translatedChunk = $manager->translateChunk($chunk, $targetLocale, $model, $defaultLang, $multiple, $context);

                    if ($translatedChunk) {
                        $translatedChunk = $manager->applyGlossaryOverrides($translatedChunk, $targetLocale);

                        foreach ($translatedChunk as $key => $value) {
                            if ($multiple && is_array($value)) {
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
                } catch (Throwable $e) {
                    $this->error('API Error: '.$e->getMessage());
                }
            }

            ksort($existingTranslations);
            File::put($localePath, json_encode($existingTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');
            $this->info("Saved {$localeFile}.");
        }

        $this->info('Translation generation complete.');

        return self::SUCCESS;
    }

    private function handlePhpFiles(TranslationManager $manager, string $langDir, string $defaultLang): int
    {
        $baseDir = $langDir.'/'.$defaultLang;

        if (! File::isDirectory($baseDir)) {
            $this->error("Base language directory {$defaultLang}/ does not exist.");

            return self::FAILURE;
        }

        $handler = new PhpArrayFileHandler;
        $batchSize = max(1, (int) $this->option('batch'));
        $model = is_string($this->option('model')) ? $this->option('model') : (string) config('insta-translate.default_model', 'claude');
        $translateAll = (bool) $this->option('all');
        $context = is_string($this->option('context')) ? $this->option('context') : null;

        $langOption = is_string($this->option('lang')) ? $this->option('lang') : null;

        /** @var list<SplFileInfo> $baseFiles */
        $baseFiles = File::files($baseDir);

        if ($langOption) {
            $targetLocales = [$langOption];
        } else {
            $targetLocales = $manager->getPhpLocales($langDir, $defaultLang);
        }

        foreach ($baseFiles as $baseFile) {
            if ($baseFile->getExtension() !== 'php') {
                continue;
            }

            $filename = $baseFile->getFilename();
            $baseTranslations = $handler->read($baseFile->getPathname());
            $baseFlat = $handler->flattenWithDot($baseTranslations);

            $this->info("Processing PHP file: {$filename}");

            foreach ($targetLocales as $targetLocale) {
                $targetPath = $langDir.'/'.$targetLocale.'/'.$filename;
                $this->info("  Locale: {$targetLocale}");

                $existingFlat = File::exists($targetPath)
                    ? $handler->flattenWithDot($handler->read($targetPath))
                    : [];

                $missingKeys = $manager->resolveMissingKeys($baseFlat, $existingFlat, [], $translateAll);

                if (empty($missingKeys)) {
                    $this->line("  No missing translations for {$targetLocale}/{$filename}.");

                    continue;
                }

                $this->info('  Found '.count($missingKeys).' missing keys.');

                $chunks = array_chunk($missingKeys, $batchSize, true);

                foreach ($chunks as $index => $chunk) {
                    $this->line('  Translating batch '.($index + 1).' of '.count($chunks).'...');

                    try {
                        $translatedChunk = $manager->translateChunk($chunk, $targetLocale, $model, $defaultLang, false, $context);

                        if ($translatedChunk) {
                            $translatedChunk = $manager->applyGlossaryOverrides($translatedChunk, $targetLocale);

                            foreach ($translatedChunk as $key => $translatedValue) {
                                $existingFlat[$key] = (string) $translatedValue;
                            }
                        } else {
                            $this->error('  Failed to translate batch '.($index + 1).'. Skipping.');
                        }
                    } catch (Throwable $e) {
                        $this->error('  API Error: '.$e->getMessage());
                    }
                }

                $rebuilt = $handler->unflattenDotNotation($existingFlat);
                ksort($rebuilt);
                $handler->write($targetPath, $rebuilt);
                $this->info("  Saved {$targetLocale}/{$filename}.");
            }
        }

        $this->info('Translation generation complete.');

        return self::SUCCESS;
    }
}
