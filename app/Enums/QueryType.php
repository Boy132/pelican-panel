<?php

namespace App\Enums;

enum QueryType: string
{
    case Source = 'source';
    case GoldSource = 'gold_source';
    case Minecraft = 'minecraft';
}
