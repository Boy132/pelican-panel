<?php

namespace App\Http\Controllers\Api\Application\Plugins;

use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Plugin;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Transformers\Api\Application\PluginTransformer;
use App\Http\Requests\Api\Application\Plugins\GetPluginRequest;
use App\Http\Requests\Api\Application\Plugins\StorePluginRequest;
use App\Http\Requests\Api\Application\Plugins\DeletePluginRequest;
use App\Http\Requests\Api\Application\Plugins\UpdatePluginRequest;
use App\Services\Plugins\PluginInstallService;
use App\Services\Plugins\PluginStatusService;

class PluginController extends ApplicationApiController
{
    /**
     * PluginController constructor.
     */
    public function __construct(
        private PluginInstallService $pluginInstallService,
        private PluginStatusService $pluginStatusService
    ) {
        parent::__construct();
    }

    /**
     * Return all the plugins that are currently installed.
     */
    public function index(GetPluginRequest $request): array
    {
        $plugins = QueryBuilder::for(Plugin::query())
            ->allowedFilters(['package', 'status', 'name', 'author', 'panel', 'panel_version', 'category'])
            ->allowedSorts(['package', 'status', 'name', 'author', 'panel', 'panel_version', 'category'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($plugins)
            ->transformWith($this->getTransformer(PluginTransformer::class))
            ->toArray();
    }

    /**
     * Return data for a single instance of a plugin.
     */
    public function view(GetPluginRequest $request, Plugin $plugin): array
    {
        return $this->fractal->item($plugin)
            ->transformWith($this->getTransformer(PluginTransformer::class))
            ->toArray();
    }

    /**
     * Install a new plugin on the panel.
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function store(StorePluginRequest $request): JsonResponse
    {
        $plugin = $this->pluginInstallService->install($request->validated());

        return $this->fractal->item($plugin)
            ->transformWith($this->getTransformer(PluginTransformer::class))
            ->addMeta([
                'resource' => route('api.application.plugins.view', [
                    'plugin' => $plugin->package,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Update an existing plugin on the panel.
     *
     * @throws \Throwable
     */
    public function update(UpdatePluginRequest $request, Plugin $plugin): array
    {
        $plugin->forceFill($request->validated())->save();

        return $this->fractal->item($plugin)
            ->transformWith($this->getTransformer(PluginTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a given plugin from the Panel.
     */
    public function delete(DeletePluginRequest $request, Plugin $plugin): JsonResponse
    {
        $this->pluginInstallService->uninstall($plugin);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Enable a plugin.
     */
    public function enable(StorePluginRequest $request, Plugin $plugin): array
    {
        $this->pluginStatusService->enable($plugin);

        return $this->fractal->item($plugin)
            ->transformWith($this->getTransformer(PluginTransformer::class))
            ->toArray();
    }

    /**
     * Disable a plugin.
     */
    public function disable(StorePluginRequest $request, Plugin $plugin): array
    {
        $this->pluginStatusService->disable($plugin);

        return $this->fractal->item($plugin)
            ->transformWith($this->getTransformer(PluginTransformer::class))
            ->toArray();
    }
}
