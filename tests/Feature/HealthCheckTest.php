<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    config(['app.debug' => false]);
});

it('answers 200 when the database and Redis respond', function () {
    Redis::shouldReceive('ping')->once()->andReturn(true);

    $this->get('/health')->assertOk()->assertSee('Application up');
});

it('answers 500 without details when the database is down', function () {
    Redis::shouldReceive('ping')->andReturn(true);
    // partialMock keeps the real DatabaseManager: Laravel disconnects every connection after each test
    DB::partialMock()->shouldReceive('select')->with('select 1')->andThrow(new RuntimeException('SQLSTATE secret-host'));

    $this->get('/health')->assertServerError()->assertDontSee('secret-host');
});

it('answers 500 without details when Redis is down', function () {
    Redis::shouldReceive('ping')->andThrow(new RuntimeException('Connection refused redis:6379'));

    $this->get('/health')->assertServerError()->assertDontSee('redis:6379');
});
