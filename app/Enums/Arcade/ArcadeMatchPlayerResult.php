<?php
namespace App\Enums\Arcade;
enum ArcadeMatchPlayerResult: string { case Win = 'win'; case Loss = 'loss'; case Draw = 'draw'; case Cancelled = 'cancelled'; }
