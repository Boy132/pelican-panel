<?php

namespace App\Services\Servers;

use App\Enums\QueryType;
use App\Models\Server;
use Exception;
use xPaw\MinecraftPing;
use xPaw\SourceQuery\SourceQuery;

class ServerQueryService
{
    public const QUERY_TIMEOUT = 5;

    protected bool $normalize = false;

    public function normalize(bool $normalize = true): self
    {
        $this->normalize = $normalize;

        return $this;
    }

    public function handle(Server $server): array
    {
        return cache()->remember("servers.$server->id.query", now()->addMinute(), function () use ($server) {
            $ip = $server->allocation->ip;
            $port = $server->allocation->port;

            return match ($server->egg->query_type) {
                QueryType::Minecraft => $this->minecraft($ip, $port),
                QueryType::GoldSource => $this->source($ip, $port, SourceQuery::GOLDSOURCE),
                QueryType::Source => $this->source($ip, $port, SourceQuery::SOURCE),
                QueryType::Cfx => $this->cfx($ip, $port),
                default => [],
            };
        });
    }

    private function minecraft(string $ip, int $port): array
    {
        try {
            $query = new MinecraftPing($ip, $port, self::QUERY_TIMEOUT, false);

            $data = [
                'format' => 'minecraft',
                'query' => $query->Query(),
            ];

            if ($this->normalize) {
                $data = [
                    'hostname' => $data['query']['description'] ?? 'unknown',
                    'version' => $data['query']['version']['name'] ?? 'unknown',
                    'players' => [
                        'current' => $data['query']['players']['online'] ?? 0,
                        'max' => $data['query']['players']['max'] ?? 0,
                    ],
                ];
            }
        } catch (Exception $exception) {
            report($exception);
        } finally {
            if (isset($query)) {
                $query->Close();
            }
        }

        return $data ?? [];
    }

    private function source(string $ip, int $port, int $engine): array
    {
        try {
            $query = new SourceQuery();
            $query->Connect($ip, $port, self::QUERY_TIMEOUT, $engine);

            $data = [
                'format' => 'source',
                'query' => [
                    'info' => $query->GetInfo(),
                    'players' => $query->GetPlayers(),
                    'rules' => $query->GetRules(),
                ],
            ];

            if ($this->normalize) {
                $data = [
                    'hostname' => $data['query']['info']['HostName'] ?? 'unknown',
                    'version' => $data['query']['info']['Version'] ?? 'unknown',
                    'players' => [
                        'current' => $data['query']['info']['Players'] ?? 0,
                        'max' => $data['query']['info']['MaxPlayers'] ?? 0,
                    ],
                ];
            }
        } catch (Exception $exception) {
            report($exception);
        } finally {
            if (isset($query)) {
                $query->Disconnect();
            }
        }

        return $data ?? [];
    }

    private function cfx(string $ip, int $port): array
    {
        try {
            $data = [
                'format' => 'cfx',
                'query' => [
                    'dynamic' => json_decode(file_get_contents("http://$ip:$port/dynamic.json")),
                    'info' => json_decode(file_get_contents("http://$ip:$port/info.json")),
                    'players' => json_decode(file_get_contents("http://$ip:$port/players.json")),
                ],
            ];

            if ($this->normalize) {
                $data = [
                    'hostname' => $data['query']['dynamic']['hostname'] ?? 'unknown',
                    'version' => $data['query']['dynamic']['iv'] ?? 'unknown',
                    'players' => [
                        'current' => $data['query']['dynamic']['clients'] ?? 0,
                        'max' => $data['query']['dynamic']['sv_maxclients'] ?? 0,
                    ],
                ];
            }
        } catch (Exception $exception) {
            report($exception);
        }

        return $data ?? [];
    }
}
