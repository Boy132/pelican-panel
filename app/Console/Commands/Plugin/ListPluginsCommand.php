<?php

namespace App\Console\Commands\Plugin;

use App\Models\Plugin;
use Illuminate\Console\Command;

class ListPluginsCommand extends Command
{
    protected $signature = 'p:plugin:list {--format=text : The output format: "text" or "json". }';

    protected $description = 'Lists all installed plugins';

    public function handle(): void
    {
        $plugins = Plugin::query()->get(['package', 'class', 'status', 'name', 'panel', 'category']);

        if ($this->option('format') === 'json') {
            $this->output->write($plugins->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Package Name', 'Main Class', 'Status', 'Display Name', 'Panel', 'Category'], $plugins->toArray());
        }

        $this->output->newLine();
    }
}
