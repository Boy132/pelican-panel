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
use Illuminate\Support\Composer;

class PluginController extends ApplicationApiController
{
    /**
     * Return all the plugins that are currently installed.
     */
    public function index(GetPluginRequest $request): array
    {
        $plugins = QueryBuilder::for(Plugin::query())
            ->allowedFilters(['package', 'name', 'panel', 'category'])
            ->allowedSorts(['package', 'name', 'panel', 'category'])
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
        $model = (new Plugin())->fill($request->validated());
        $model->saveOrFail();

        $plugin = $model->fresh();

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->requirePackages([$plugin->package]);

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
        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->removePackages([$plugin->package]);

        $plugin->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
