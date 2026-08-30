<?php
namespace App\Enums\Arcade;
enum ArcadeInvitationStatus: string { case Pending = 'pending'; case Accepted = 'accepted'; case Declined = 'declined'; case Cancelled = 'cancelled'; case Expired = 'expired'; }
