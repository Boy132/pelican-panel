<?php

namespace App\Console\Commands;

use App\Traits\TranslationScannerTrait;
use Illuminate\Console\Command;

class TranslationKeyScanCommand extends Command
{
    use TranslationScannerTrait;

    protected $description = 'Scans for translation keys.';

    protected $signature = 'p:scan-translation-keys';

    public function handle(): void
    {
        $results = $this->scanForTranslations([app_path(), resource_path()], 'php');
        $this->table(['File', 'Key'], $results);
    }
}
