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

    public function handle(Server $server): array|bool
    {
        $ip = $server->allocation->ip;
        $port = $server->allocation->port;

        return match ($server->egg->query_type) {
            QueryType::Minecraft => $this->minecraft($ip, $port),
            QueryType::GoldSource => $this->source($ip, $port, SourceQuery::GOLDSOURCE),
            QueryType::Source => $this->source($ip, $port, SourceQuery::SOURCE),
            default => false,
        };
    }

    private function minecraft(string $ip, int $port): array|bool
    {
        try {
            $query = new MinecraftPing($ip, $port, self::QUERY_TIMEOUT, false);

            $data = $query->Query();
        } catch (Exception $exception) {
            report($exception);
        } finally {
            if (isset($query)) {
                $query->Close();
            }
        }

        return $data ?? false;
    }

    private function source(string $ip, int $port, int $engine): array|bool
    {
        try {
            $query = new SourceQuery();
            $query->Connect($ip, $port, self::QUERY_TIMEOUT, $engine);

            $data = [
                'info' => $query->GetInfo(),
                'players' => $query->GetPlayers(),
                'rules' => $query->GetRules(),
            ];
        } catch (Exception $exception) {
            report($exception);
        } finally {
            if (isset($query)) {
                $query->Disconnect();
            }
        }

        return $data ?? false;
    }
}
