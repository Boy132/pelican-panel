<?php

namespace App\Models\Traits;

interface ApiResourceInterface
{
    /**
     * Returns the name of the api key resource.
     */
    public function getApiResourceName(): string;
}
