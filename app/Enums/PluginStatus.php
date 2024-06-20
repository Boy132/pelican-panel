<?php

namespace App\Enums;

enum PluginStatus: string
{
    case Disabled = 'disabled';
    case Enabled = 'enabled';
    case Errored = 'errored';

    public function icon(): string
    {
        return match ($this) {
            self::Disabled => 'tabler-circle-off',
            self::Enabled => 'tabler-circle-check',
            self::Errored => 'tabler-circle-x',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Disabled => 'gray',
            self::Enabled => 'success',
            self::Errored => 'danger',
        };
    }
}
