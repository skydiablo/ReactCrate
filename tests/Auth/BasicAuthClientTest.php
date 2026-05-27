<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\Auth;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\BasicAuthClient;
use SkyDiablo\ReactCrate\Client;

class BasicAuthClientTest extends TestCase
{
    public function testAuthorizationHeaderEncoding(): void
    {
        $client = new BasicAuthClient('http://localhost:4200', 'myuser', 'mypass');
        $method = new \ReflectionMethod($client, 'authorizationHeader');
        $method->setAccessible(true);

        $this->assertSame(
            'Basic ' . base64_encode('myuser:mypass'),
            $method->invoke($client, 'myuser', 'mypass'),
        );
    }

    public function testBasicAuthClientInjectsAuthorizationHeader(): void
    {
        $client = new BasicAuthClient('http://localhost:4200', 'iot', 's3cr3t');
        $headers = (new \ReflectionMethod($client, 'defaultHeaders'))->invoke($client);

        $this->assertSame('Basic ' . base64_encode('iot:s3cr3t'), $headers['Authorization']);
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame(Client::DEFAULT_SCHEMA, $headers['Default-Schema']);
    }
}
