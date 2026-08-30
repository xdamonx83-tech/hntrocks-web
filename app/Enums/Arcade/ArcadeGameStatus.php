<?php

namespace App\Enums\Arcade;

enum ArcadeGameStatus: string
{
    case Active = 'active';
    case ComingSoon = 'coming_soon';
    case Maintenance = 'maintenance';
    case Event = 'event';
    case Disabled = 'disabled';
}
