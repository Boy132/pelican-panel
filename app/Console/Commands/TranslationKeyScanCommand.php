<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class TranslationKeyScanCommand extends Command
{
    protected $description = 'Scans for translation keys.';

    protected $signature = 'p:scan-translation-keys';

    private const TRANSLATION_METHODS = [
        '__',
        'trans',
        'trans_choice',
        '@lang',
        'Lang::get',
        'Lang::choice',
        'Lang::trans',
        'Lang::transChoice',
        '@choice',
    ];

    public function handle(): void
    {
        $results = $this->scan([app_path(), resource_path()]);
        $this->table(['File', 'Key', 'EN Translation'], $results);
    }

    public function scan(array $paths): array
    {
        $results = [];

        foreach (resolve(Filesystem::class)->allFiles($paths) as $file) {
            if (preg_match_all($this->pattern(), $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    if (!empty($key)) {
                        $results[] = [
                            'file' => $file->getRelativePathname(),
                            'key' => $key,
                            'translation' => trans($key) === $key ? '' : trans($key),
                        ];
                    }
                }
            }
        }

        return $results;
    }

    private function pattern(): string
    {
        // See https://regex101.com/r/jS5fX0/5
        return
            '/' .
            "[^\w]" . // Must not start with any alphanum or _
            '(?<!->)' . // Must not start with ->
            '(' . implode('|', self::TRANSLATION_METHODS) . ')' .// Must start with one of the functions
            "\(" .// Match opening parentheses
            "[\r\n|\r|\n]*?" .// Ignore new lines
            "[\'\"]" .// Match " or '
            '(' .// Start a new group to match:
            '.*' .// Must start with group
            ')' .// Close group
            "[\'\"]" .// Closing quote
            "[\r\n|\r|\n]*?" .// Ignore new lines
            "[\),]" . // Close parentheses or new parameter
            '/siuU';
    }
}
