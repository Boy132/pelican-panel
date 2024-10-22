<?php

namespace App\Traits;

use Illuminate\Filesystem\Filesystem;

trait TranslationScannerTrait
{
    protected const TRANSLATION_METHODS = [
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

    protected function scanForTranslations(string|array $paths, string|array|null $extensionFilter = null): array
    {
        $pattern = '/' .
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

        $results = [];

        /** @var FileSystem $filesystem */
        $filesystem = app(Filesystem::class); // @phpstan-ignore-line
        foreach ($filesystem->allFiles($paths) as $file) {
            if ((is_string($extensionFilter) && $file->getExtension() !== $extensionFilter) || (is_array($extensionFilter) && !in_array($file->getExtension(), $extensionFilter))) {
                continue;
            }

            if (preg_match_all($pattern, $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    if (!empty($key)) {
                        $results[] = [
                            'file' => $file->getRelativePathname(),
                            'key' => $key,
                        ];
                    }
                }
            }
        }

        return $results;
    }
}
