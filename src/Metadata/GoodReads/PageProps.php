<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class PageProps
{
    public ?Params $params;
    public mixed $jwtToken;
    public mixed $dataSource;
    public ?ApolloState $apolloState;
    public ?AuthContextParams $authContextParams;
    public ?UserAgentContextParams $userAgentContextParams;
    public ?string $userAgent;

    public function __construct(
        ?Params $params,
        mixed $jwtToken,
        mixed $dataSource,
        ?ApolloState $apolloState,
        ?AuthContextParams $authContextParams,
        ?UserAgentContextParams $userAgentContextParams,
        ?string $userAgent
    ) {
        $this->params = $params;
        $this->jwtToken = $jwtToken;
        $this->dataSource = $dataSource;
        $this->apolloState = $apolloState;
        $this->authContextParams = $authContextParams;
        $this->userAgentContextParams = $userAgentContextParams;
        $this->userAgent = $userAgent;
    }

    public function getParams(): ?Params
    {
        return $this->params;
    }

    public function getJwtToken(): mixed
    {
        return $this->jwtToken;
    }

    public function getDataSource(): mixed
    {
        return $this->dataSource;
    }

    public function getApolloState(): ?ApolloState
    {
        return $this->apolloState;
    }

    public function getAuthContextParams(): ?AuthContextParams
    {
        return $this->authContextParams;
    }

    public function getUserAgentContextParams(): ?UserAgentContextParams
    {
        return $this->userAgentContextParams;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            ($data['params'] ?? null) !== null ? Params::fromJson($data['params']) : null,
            $data['jwtToken'] ?? null,
            $data['dataSource'] ?? null,
            ($data['apolloState'] ?? null) !== null ? ApolloState::fromJson($data['apolloState']) : null,
            ($data['authContextParams'] ?? null) !== null ? AuthContextParams::fromJson($data['authContextParams']) : null,
            ($data['userAgentContextParams'] ?? null) !== null ? UserAgentContextParams::fromJson($data['userAgentContextParams']) : null,
            $data['userAgent'] ?? null
        );
    }
}
