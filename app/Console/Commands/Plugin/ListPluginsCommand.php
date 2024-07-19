<?php

namespace App\Console\Commands\Plugin;

use App\Models\Plugin;
use Illuminate\Console\Command;

class ListPluginsCommand extends Command
{
    protected $signature = 'p:plugin:list';

    protected $description = 'Lists all installed plugins';

    public function handle(): void
    {
        $plugins = Plugin::query()->get(['package', 'class', 'status', 'name', 'author', 'version', 'panel', 'category']);

        $this->table(['Package Name', 'Main Class', 'Status', 'Name', 'Author', 'Version', 'Panel', 'Category'], $plugins->toArray());

        $this->output->newLine();
    }
}
