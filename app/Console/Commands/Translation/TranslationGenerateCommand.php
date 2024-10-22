<?php

namespace App\Console\Commands\Translation;

use App\Traits\TranslationScannerTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class TranslationGenerateCommand extends Command
{
    use TranslationScannerTrait;

    protected $description = 'Generates translation files.';

    protected $signature = 'p:translation:generate {lang?}';

    public function handle(): void
    {
        $lang = $this->argument('lang') ?? 'en';
        $path = lang_path($lang);

        $fileData = [];
        foreach ($this->scanForTranslations([app_path(), resource_path()], 'php') as $result) {
            $explodedKey = explode('.', $result['key']);
            $file = $explodedKey[0] . '.php';
            unset($explodedKey[0]);

            // @phpstan-ignore-next-line
            array_set($fileData[$file], implode('.', $explodedKey), trans($result['key'], locale: $lang));
        }

        // @phpstan-ignore-next-line
        foreach ($fileData as $file => $data) {
            $this->comment('Generating "' . $file . '"');

            File::ensureDirectoryExists(File::dirname($path . '/'. $file));
            File::put($path . '/'. $file, '<?php return ' . var_export($data, true) . ';');
        }

        $this->comment('Running Pint to format translation files...');
        Process::run('.\vendor\bin\pint ' . $path);

        $this->info('All translation files for ' . Str::upper($lang) . ' created.');
    }
}
