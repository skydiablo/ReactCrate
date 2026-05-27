<?php

namespace SkyDiablo\ReactCrate;

/**
 * Client variant that adds HTTP Basic Authentication headers.
 *
 * Host-based (trust) authentication works without this client. Use it when
 * CrateDB is configured with password authentication for HTTP clients.
 */
final class BasicAuthClient extends Client
{
    private string $authorizationHeader;

    public function __construct(
        string $host,
        string $username,
        string $password,
        array $connectorContext = [],
    ) {
        $this->authorizationHeader = $this->authorizationHeader($username, $password);
        parent::__construct($host, $connectorContext);
    }

    protected function authorizationHeader(string $username, string $password): string
    {
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    protected function defaultHeaders(array $headers = []): array
    {
        return parent::defaultHeaders($headers + [
            'Authorization' => $this->authorizationHeader,
        ]);
    }
}
