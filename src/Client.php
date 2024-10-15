<?php

namespace SkyDiablo\ReactCrate;

use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use SkyDiablo\ReactCrate\Exceptions\CrateResponseException;

class Client
{

    private const string BASE_URL_PATH = '_sql';
    private const string QUERY_PARAM_STATMENT = 'stmt';
    private const string QUERY_PARAM_ARGUMENTS = 'args';
    private const string QUERY_PARAM_BULK_ARGUMENTS = 'bulk_args';
    const string DEFAULT_SCHEMA = 'doc';
    protected Browser $connection;

    public function __construct(string $host, array $connectorContext = [])
    {
        $this->connection = (new Browser(new Connector($connectorContext)))
            ->withBase(rtrim($host, '/') . '/' . self::BASE_URL_PATH);
    }

    protected function defaultHeaders(array $headers = []): array
    {
        return $headers + [
                'Content-Type' => 'application/json',
                'Default-Schema' => self::DEFAULT_SCHEMA
            ];
    }

    protected function prepareStatement(string $statement, array $args = []): string
    {
        $q = [
            self::QUERY_PARAM_STATMENT => $statement,
            ($args && is_array(reset($args))) ?
                self::QUERY_PARAM_BULK_ARGUMENTS :
                self::QUERY_PARAM_ARGUMENTS => $args
        ];
        return json_encode($q);
    }

    /**
     * @TODO handle data types and convert it to PHP equivalents
     * @see  // @see https://cratedb.com/docs/crate/reference/en/master/general/ddl/data-types.html
     *
     * @param ResponseInterface $response
     *
     * @return PromiseInterface
     */
    protected function handleResponse(ResponseInterface $response): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($response) {
            $dbResponse = json_decode($response->getBody()->getContents(), true);

            if (isset($dbResponse['error'])) {
                $reject(new CrateResponseException($dbResponse['error']['message'], $dbResponse['error']['code']));
            }
            $resolve($dbResponse);
        });
    }

    /**
     * @param string $statement
     * @param array $arguments
     * @return PromiseInterface<array>
     */
    public function query(string $statement, array $arguments = []): PromiseInterface
    {

        return $this->connection->post(
            '?types', // "types", @see https://cratedb.com/docs/crate/reference/en/master/interfaces/http.html#column-types
            $this->defaultHeaders(),
            $this->prepareStatement($statement, $arguments)
        )
            ->then(fn(ResponseInterface $response) => $this->handleResponse($response))
            ->catch(fn(ResponseException $e) => $this->handleResponse($e->getResponse()));
    }


}