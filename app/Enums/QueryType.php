<?php

namespace App\Enums;

enum QueryType: string
{
    case None = 'none';
    case Minecraft = 'minecraft';
    case GoldSource = 'gold_source';
    case Source = 'source';
    case Cfx = 'cfx';
}
