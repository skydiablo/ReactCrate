<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests;

use PHPUnit\Framework\TestCase;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use SkyDiablo\ReactCrate\ClientInterface;
use SkyDiablo\ReactCrate\ClientSelection\ClientSelectorInterface;
use SkyDiablo\ReactCrate\ClientSelection\CustomClientSelector;
use SkyDiablo\ReactCrate\ClientSelection\LoadClientSelector;
use SkyDiablo\ReactCrate\ClusterClient;
use SkyDiablo\ReactCrate\Exceptions\CrateResponseException;

use function React\Promise\reject;

class ClusterClientTest extends TestCase
{
    public function testQueryUsesRoundRobinByDefault(): void
    {
        $clientA = new FakeClient();
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB]);

        $cluster->query('SELECT 1');
        $cluster->query('SELECT 2');
        $cluster->query('SELECT 3');

        $this->assertSame(2, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testQueryUsesCustomSelector(): void
    {
        $clientA = new FakeClient();
        $clientB = new FakeClient();

        $selector = new CustomClientSelector(
            fn(array $clients, string $statement, array $arguments) => $clients[1]
        );
        $cluster = new ClusterClient([$clientA, $clientB], $selector);

        $cluster->query('SELECT * FROM foo', ['id' => 1]);

        $this->assertSame(0, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testRefreshTableDelegatesToClientInterfaceMethod(): void
    {
        $clientA = new FakeClient();
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB]);

        $cluster->refreshTable('doc.users');

        $this->assertSame(1, $clientA->refreshCalls);
        $this->assertSame(['doc.users'], $clientA->refreshTables);
        $this->assertSame(0, $clientA->queryCalls);
        $this->assertSame(0, $clientB->refreshCalls);
    }

    public function testLoadSelectorTracksInflightQueriesViaWrapper(): void
    {
        $clientA = new FakeClient();
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB], new LoadClientSelector());

        $cluster->query('SELECT 1');
        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(0, $clientB->queryCalls);

        $cluster->query('SELECT 2');
        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);

        $clientA->resolveNextQuery(['ok' => true]);
        $cluster->query('SELECT 3');

        $this->assertSame(2, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testQueryExcludesClientsFromSelection(): void
    {
        $clientA = new FakeClient();
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB]);

        $cluster->query('SELECT 1', [ClientSelectorInterface::EXCEPT_CLIENTS => [$clientA]]);

        $this->assertSame(0, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testQueryFailsOverToNextClientOnConnectionFailure(): void
    {
        $clientA = new FakeClient();
        $clientA->failQueryWithConnectionError = true;
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB]);

        $cluster->query('SELECT 1');

        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testQueryFailsOverWithLoadSelectorOnConnectionFailure(): void
    {
        $clientA = new FakeClient();
        $clientA->failQueryWithConnectionError = true;
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB], new LoadClientSelector());

        $cluster->query('SELECT 1');

        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testQueryDoesNotFailOverOnCrateResponseException(): void
    {
        $clientA = new FakeClient();
        $clientA->failQueryWithCrateResponseException = true;
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB]);

        $failed = false;
        $cluster->query('SELECT 1')->catch(function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(0, $clientB->queryCalls);
    }

    public function testQueryRejectsWhenAllClientsFailWithConnectionError(): void
    {
        $clientA = new FakeClient();
        $clientA->failQueryWithConnectionError = true;
        $clientB = new FakeClient();
        $clientB->failQueryWithConnectionError = true;
        $cluster = new ClusterClient([$clientA, $clientB]);

        $failed = false;
        $cluster->query('SELECT 1')->catch(function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls);
    }

    public function testQueryDoesNotFailOverWhenMaxTriesIsReached(): void
    {
        $clientA = new FakeClient();
        $clientA->failQueryWithConnectionError = true;
        $clientB = new FakeClient();
        $cluster = new ClusterClient([$clientA, $clientB], failoverMaxTries: 1);

        $failed = false;
        $cluster->query('SELECT 1')->catch(function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(0, $clientB->queryCalls);
    }

    public function testQueryStopsFailoverAtMaxTries(): void
    {
        $clientA = new FakeClient();
        $clientA->failQueryWithConnectionError = true;
        $clientB = new FakeClient();
        $clientB->failQueryWithConnectionError = true;
        $clientC = new FakeClient();
        $clientC->failQueryWithConnectionError = true;
        $cluster = new ClusterClient([$clientA, $clientB, $clientC], failoverMaxTries: 2);

        $failed = false;
        $cluster->query('SELECT 1')->catch(function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
        $this->assertSame(1, $clientA->queryCalls);
        $this->assertSame(1, $clientB->queryCalls + $clientC->queryCalls);
    }
}

final class FakeClient implements ClientInterface
{
    public int $queryCalls = 0;
    public int $refreshCalls = 0;
    public bool $failQueryWithConnectionError = false;
    public bool $failQueryWithCrateResponseException = false;
    /**
     * @var string[]
     */
    public array $refreshTables = [];
    /**
     * @var Deferred[]
     */
    private array $queryDeferreds = [];

    public function getStatus(): PromiseInterface
    {
        $deferred = new Deferred();
        $deferred->resolve(['status' => 'ok']);
        return $deferred->promise();
    }

    public function query(string $statement, array $arguments = []): PromiseInterface
    {
        $this->queryCalls++;

        if ($this->failQueryWithConnectionError) {
            return reject(new \RuntimeException('Connection to tcp://127.0.0.1:4200 failed: Connection refused (ECONNREFUSED)'));
        }

        if ($this->failQueryWithCrateResponseException) {
            return reject(new CrateResponseException('relation unknown', 4041));
        }

        $deferred = new Deferred();
        $this->queryDeferreds[] = $deferred;

        return $deferred->promise();
    }

    public function refreshTable(string $tableName): PromiseInterface
    {
        $this->refreshCalls++;
        $this->refreshTables[] = $tableName;

        $deferred = new Deferred();
        $deferred->resolve(null);
        return $deferred->promise();
    }

    /**
     * @param mixed $value
     */
    public function resolveNextQuery(mixed $value = null): void
    {
        $deferred = array_shift($this->queryDeferreds);
        if ($deferred instanceof Deferred) {
            $deferred->resolve($value);
        }
    }
}
