<?php

namespace App\Console\Commands\Translation;

use App\Traits\TranslationScannerTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationGenerateCommand extends Command
{
    use TranslationScannerTrait;

    protected $description = 'Generates translation template files.';

    protected $signature = 'p:translation:generate';

    public function handle(): void
    {
        $generated = [];

        $results = $this->scanForTranslations([app_path(), resource_path()]);
        foreach ($results as $result) {
            $explodedKey = explode('.', $result['key']);
            $file = $explodedKey[0] . '.php';
            unset($explodedKey[0]);

            array_set($generated[$file], implode('.', $explodedKey), $result['key']);
        }

        $path = lang_path('generated');
        foreach ($generated as $file => $data) {
            $this->comment('Generating "' . $file . '"');

            File::ensureDirectoryExists(File::dirname($path . '/'. $file));
            File::put($path . '/'. $file, '<?php return ' . var_export($data, true) . ';');
        }

        $this->info('All Template files created.');
    }
}
