<?php

namespace Tests\Feature;

use Tests\TestCase;

class TestDatabaseIsolationTest extends TestCase
{
    public function test_phpunit_defaults_to_memory_sqlite_and_blocks_live_database(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertNotSame('piie_main', config('database.connections.mysql.database'));
        $this->assertSame('__piie_phpunit_blocked__', config('database.connections.mysql.database'));
    }
}
