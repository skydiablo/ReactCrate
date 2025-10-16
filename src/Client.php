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
    private const string QUERY_PARAM_STATEMENT = 'stmt';
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
                'Default-Schema' => self::DEFAULT_SCHEMA,
            ];
    }

    protected function prepareStatement(string $statement, array $args = []): string
    {
        // handle enum types
        array_walk_recursive($args, function (&$value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $value = $value->name;
            }
        });

        $q = [
            self::QUERY_PARAM_STATEMENT => $statement,
        ];
        if ($args && is_array(reset($args))) {
            $q += [self::QUERY_PARAM_BULK_ARGUMENTS => array_values($args)];
        } else {
            $q += [self::QUERY_PARAM_ARGUMENTS => $args];
        }

        return json_encode($q, JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @TODO handle data types and convert it to PHP equivalents
     * @see  // @see https://cratedb.com/docs/crate/reference/en/master/general/ddl/data-types.html
     *
     * @param ResponseInterface $response
     * @param string $statement
     * @param array $arguments
     * @return PromiseInterface
     */
    protected function handleResponse(ResponseInterface $response, string $statement, array $arguments): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($response, $statement, $arguments) {
            $dbResponse = json_decode($response->getBody()->getContents(), true);

            if (isset($dbResponse['error'])) {
                $message = sprintf(
                    '%s [%s]: %s',
                    $dbResponse['error']['message'],
                    $statement,
                    json_encode($arguments, JSON_PRESERVE_ZERO_FRACTION),
                );
                $reject(new CrateResponseException($message, $dbResponse['error']['code']));
            }

            // Convert rows to associative arrays with column names as keys
            if (isset($dbResponse['cols']) && isset($dbResponse['rows'])) {
                $columnNames = $dbResponse['cols'];
                $dbResponse['rows'] = array_map(function ($row) use ($columnNames) {
                    return array_combine($columnNames, $row);
                }, $dbResponse['rows']);
            }

            $resolve($dbResponse);
        });
    }

    /**
     * @param string $statement
     * @param array $arguments
     *
     * @return PromiseInterface<array>
     */
    public function query(string $statement, array $arguments = []): PromiseInterface
    {
        return $this->connection
            ->post(
                '?types', // "types", @see https://cratedb.com/docs/crate/reference/en/master/interfaces/http.html#column-types
                $this->defaultHeaders(),
                $this->prepareStatement($statement, $arguments),
            )
            ->then(
                fn(ResponseInterface $response) => $this->handleResponse($response, $statement, $arguments),
            )->catch(
                fn(ResponseException $e) => $this->handleResponse($e->getResponse(), $statement, $arguments),
            );
    }


}