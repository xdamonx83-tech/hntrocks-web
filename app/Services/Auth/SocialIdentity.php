<?php

namespace App\Services\Auth;

final readonly class SocialIdentity
{
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public ?string $email = null,
        public bool $emailVerified = false,
        public ?string $name = null,
        public ?string $nickname = null,
        public ?string $avatarUrl = null,
        public array $raw = [],
    ) {
    }
}
