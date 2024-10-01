<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class AuthContextParams
{
    public ?bool $signedIn;
    public null $customerId;
    public null $legacyCustomerId;
    public ?string $role;

    public function __construct(
        ?bool $signedIn,
        null $customerId,
        null $legacyCustomerId,
        ?string $role
    ) {
        $this->signedIn = $signedIn;
        $this->customerId = $customerId;
        $this->legacyCustomerId = $legacyCustomerId;
        $this->role = $role;
    }

    public function getSignedIn(): ?bool
    {
        return $this->signedIn;
    }

    public function getCustomerId(): null
    {
        return $this->customerId;
    }

    public function getLegacyCustomerId(): null
    {
        return $this->legacyCustomerId;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['signedIn'] ?? null,
            $data['customerId'] ?? null,
            $data['legacyCustomerId'] ?? null,
            $data['role'] ?? null
        );
    }
}
