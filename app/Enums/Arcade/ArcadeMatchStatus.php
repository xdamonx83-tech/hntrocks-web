<?php
namespace App\Enums\Arcade;
enum ArcadeMatchStatus: string { case WaitingReady = 'waiting_ready'; case Active = 'active'; case Finished = 'finished'; case Cancelled = 'cancelled'; case Expired = 'expired'; }
