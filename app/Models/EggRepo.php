<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Schema\Blueprint;
use Sushi\Sushi;

/**
 * @property string name
 * @property array $eggs
 */
class EggRepo extends Model
{
    use Sushi;

    public function getRows()
    {
        return [
            [
                'name' => 'minecraft',
                'eggs' => $this->discoverRepo('minecraft'),
            ],
            [
                'name' => 'games-steamcmd',
                'eggs' => $this->discoverRepo('games-steamcmd'),
            ],
            [
                'name' => 'games-standalone',
                'eggs' => $this->discoverRepo('games-standalone'),
            ],
            [
                'name' => 'database',
                'eggs' => $this->discoverRepo('database'),
            ],
            [
                'name' => 'software',
                'eggs' => $this->discoverRepo('software'),
            ],
            [
                'name' => 'storage',
                'eggs' => $this->discoverRepo('storage'),
            ],
            [
                'name' => 'generic',
                'eggs' => $this->discoverRepo('generic'),
            ],
            [
                'name' => 'chatbots',
                'eggs' => $this->discoverRepo('chatbots'),
            ],
            [
                'name' => 'monitoring',
                'eggs' => $this->discoverRepo('monitoring'),
            ],
            [
                'name' => 'voice',
                'eggs' => $this->discoverRepo('voice'),
            ],
        ];
    }

    protected function afterMigrate(Blueprint $table)
    {
        $table->index('name');
    }

    private function discoverRepo(string $repo): array
    {
        return cache()->remember('panel:egg-repo:' . $repo, CarbonImmutable::now()->addHours(2), fn () => $this->discoverDir($repo));
    }

    private function discoverDir(string $repo, string $dir = ''): array
    {
        $foundEggs = [];

        try {
            $client = new Client();

            $response = $client->request('GET', 'https://api.github.com/repos/pelican-eggs/' . $repo . '/contents/' . $dir,
                [
                    'timeout' => config('panel.guzzle.timeout'),
                    'connect_timeout' => config('panel.guzzle.connect_timeout'),
                ]
            );
            if ($response->getStatusCode() === 200) {
                $dirData = json_decode($response->getBody(), true);

                foreach ($dirData as $data) {
                    if ($data['type'] === 'dir') {
                        $foundEggs[] = $this->discoverDir($repo, $data['path']);

                        continue;
                    }

                    if ($data['type'] === 'file' && starts_with($data['name'], 'egg-') && !starts_with($data['name'], 'egg-ptero') && ends_with($data['name'], '.json')) {
                        $foundEggs[] = [
                            'name' => str($data['name'])->after('egg-')->headline(),
                            'repo' => $repo,
                            'download_url' => $data['download_url'],
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            report($e);
        }

        return $foundEggs;
    }
}
