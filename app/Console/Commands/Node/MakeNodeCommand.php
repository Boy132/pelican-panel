<?php

namespace App\Console\Commands\Node;

use Illuminate\Console\Command;
use App\Services\Nodes\NodeCreationService;

class MakeNodeCommand extends Command
{
    protected $signature = 'p:node:make
                            {--name= : A name to identify the node.}
                            {--description= : A description to identify the node.}
                            {--locationId= : A valid locationId.}
                            {--fqdn= : The domain name (e.g node.example.com) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.}
                            {--public= : Should the node be public or private? (public=1 / private=0).}
                            {--scheme= : Which scheme should be used? (Enable SSL=https / Disable SSL=http).}
                            {--proxy= : Is the daemon behind a proxy? (Yes=1 / No=0).}
                            {--maintenance= : Should maintenance mode be enabled? (Enable Maintenance mode=1 / Disable Maintenance mode=0).}
                            {--maxMemory= : Set the max memory amount.}
                            {--overallocateMemory= : Enter the amount of ram to overallocate (% or -1 to overallocate the maximum).}
                            {--maxDisk= : Set the max disk amount.}
                            {--overallocateDisk= : Enter the amount of disk to overallocate (% or -1 to overallocate the maximum).}
                            {--uploadSize= : Enter the maximum upload filesize.}
                            {--daemonListeningPort= : Enter the daemon listening port.}
                            {--daemonSFTPPort= : Enter the daemon SFTP listening port.}
                            {--daemonBase= : Enter the base folder.}';

    protected $description = 'Creates a new node on the system via the CLI.';

    /**
     * MakeNodeCommand constructor.
     */
    public function __construct(private NodeCreationService $creationService)
    {
        parent::__construct();
    }

    /**
     * Handle the command execution process.
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function handle(): void
    {
        $data['name'] = $this->option('name') ?? $this->ask(__('commands.make_node.name'));
        $data['description'] = $this->option('description') ?? $this->ask(__('commands.make_node.description'));
        $data['scheme'] = $this->option('scheme') ?? $this->anticipate(
            __('commands.make_node.scheme'),
            ['https', 'http'],
            'https'
        );
        $data['fqdn'] = $this->option('fqdn') ?? $this->ask(__('commands.make_node.fqdn'));
        $data['public'] = $this->option('public') ?? $this->confirm(__('commands.make_node.public'), true);
        $data['behind_proxy'] = $this->option('proxy') ?? $this->confirm(__('commands.make_node.behind_proxy'));
        $data['maintenance_mode'] = $this->option('maintenance') ?? $this->confirm(__('commands.make_node.maintenance_mode'));
        $data['memory'] = $this->option('maxMemory') ?? $this->ask(__('commands.make_node.memory'));
        $data['memory_overallocate'] = $this->option('overallocateMemory') ?? $this->ask(__('commands.make_node.memory_overallocate'));
        $data['disk'] = $this->option('maxDisk') ?? $this->ask(__('commands.make_node.disk'));
        $data['disk_overallocate'] = $this->option('overallocateDisk') ?? $this->ask(__('commands.make_node.disk_overallocate'));
        $data['upload_size'] = $this->option('uploadSize') ?? $this->ask(__('commands.make_node.upload_size'), '100');
        $data['daemonListen'] = $this->option('daemonListeningPort') ?? $this->ask(__('commands.make_node.daemonListen'), '8080');
        $data['daemonSFTP'] = $this->option('daemonSFTPPort') ?? $this->ask(__('commands.make_node.daemonSFTP'), '2022');
        $data['daemonBase'] = $this->option('daemonBase') ?? $this->ask(__('commands.make_node.daemonBase'), '/var/lib/panel/volumes');

        $node = $this->creationService->handle($data);
        $this->line(__('commands.make_node.succes1') . $data['name'] . __('commands.make_node.succes2')  . $node->id . '.');
    }
}
