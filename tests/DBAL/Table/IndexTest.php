<?php

declare(strict_types=1);

namespace SkyDiablo\ReactCrate\Tests\DBAL\Table;

use PHPUnit\Framework\TestCase;
use SkyDiablo\ReactCrate\DBAL\Table\Index;

class IndexTest extends TestCase
{
    public function testCreateIndex(): void
    {
        $index = Index::create('user_username_idx')
            ->on('user')
            ->columns(['username']);

        $expected = 'CREATE INDEX "user_username_idx" ON "user" ("username")';
        $this->assertEquals($expected, (string) $index);
    }

    public function testCreateIndexIfNotExists(): void
    {
        $index = Index::create('user_username_idx')
            ->on('user')
            ->columns(['username'])
            ->ifNotExists();

        $expected = 'CREATE INDEX IF NOT EXISTS "user_username_idx" ON "user" ("username")';
        $this->assertEquals($expected, (string) $index);
    }

    public function testMissingTableThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $index = Index::create('user_username_idx')->columns(['username']);
        (string) $index;
    }
}
