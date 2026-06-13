<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'params' => Params::fromJson(...),
            'jwtToken' => null,
            'dataSource' => null,
            'apolloState' => ApolloState::fromJson(...),
            'authContextParams' => AuthContextParams::fromJson(...),
            'userAgentContextParams' => UserAgentContextParams::fromJson(...),
            'userAgent' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
