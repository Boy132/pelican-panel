<?php

namespace App\Filament\Server\Widgets;

use App\Enums\QueryType;
use App\Models\Server;
use App\Services\Servers\ServerQueryService;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

class ServerOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '1s';

    public ?Server $server = null;

    protected function getStats(): array
    {
        $stats = [
            Stat::make('Name', $this->server->name)
                ->description($this->server->description),
            Stat::make('Status', $this->status()),
            Stat::make('Address', $this->server->allocation->address),
        ];

        if ($this->server->egg->query_type !== QueryType::None) {
            // @phpstan-ignore-next-line
            $queryData = app(ServerQueryService::class)->normalize()->handle($this->server);
            $stats[] = Stat::make('Players', !empty($queryData) ? $queryData['players']['current'] . ' / ' . $queryData['players']['max'] . ' Players' : 'Offline');
        }

        return $stats;
    }

    private function status(): string
    {
        $status = Str::title($this->server->condition);
        $uptime = collect(cache()->get("servers.{$this->server->id}.uptime"))->last() ?? 0;

        if ($uptime === 0) {
            return $status;
        }

        $uptime = now()->subMillis($uptime)->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE, short: true, parts: 2);

        return "$status ($uptime)";
    }
}
