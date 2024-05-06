<?php

namespace App\Models\Contracts;

interface ApiResourceInterface
{
    /**
     * Returns the name of the api key resource.
     */
    public function getApiResourceName(): string;
}
