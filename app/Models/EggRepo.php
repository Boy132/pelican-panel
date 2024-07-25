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

    public const OFFICIAL_REPOS = [
        'pelican-eggs/minecraft',
        'pelican-eggs/games-steamcmd',
        'pelican-eggs/games-standalone',
        'pelican-eggs/database',
        'pelican-eggs/software',
        'pelican-eggs/storage',
        'pelican-eggs/generic',
        'pelican-eggs/chatbots',
        'pelican-eggs/monitoring',
        'pelican-eggs/voice',
    ];

    public function getRows()
    {
        $repos = [];

        foreach (self::OFFICIAL_REPOS as $repo) {
            $repos[] = [
                'name' => $repo,
                'eggs' => $this->discoverRepo($repo),
            ];
        }

        return $repos;
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

        $client = new Client();

        $headers = ['User-Agent' => config('app.name') . ' Panel'];
        if (!empty(config('panel.github_token'))) {
            $headers['Authorization'] = 'Bearer ' . config('panel.github_token');
        }

        try {
            $response = $client->request('GET', 'https://api.github.com/repos/' . $repo . '/contents/' . $dir,
                [
                    'timeout' => config('panel.guzzle.timeout'),
                    'connect_timeout' => config('panel.guzzle.connect_timeout'),
                    'headers' => $headers,
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
