<?php

namespace App\Http\Controllers\Api\Client\Servers;

use App\Models\Server;
use App\Http\Controllers\Api\Client\ClientApiController;
use App\Services\Servers\ServerQueryService;

class QueryController extends ClientApiController
{
    public function __construct(private ServerQueryService $service)
    {
        parent::__construct();
    }

    public function index(Server $server): array
    {
        return $this->service->normalize()->handle($server);
    }
}
