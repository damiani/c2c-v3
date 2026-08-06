<?php

namespace App\IdentityProviders;

use Laravel\Socialite\Contracts\User as SocialiteUser;

readonly class ExternalIdentity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public ?string $email,
        public ?string $name,
        public array $metadata = [],
    ) {}

    public static function fromSocialite(string $provider, SocialiteUser $user): self
    {
        return new self(
            provider: $provider,
            providerUserId: (string) $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            metadata: [
                'nickname' => $user->getNickname(),
                'avatar' => $user->getAvatar(),
                'approved_scopes' => $user->approvedScopes ?? [],
            ],
        );
    }
}
