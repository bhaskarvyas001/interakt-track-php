<?php

namespace Interakt\Track\Tests;

use Interakt\Track\Client;
use Interakt\Track\Request;
use Interakt\Track\Track;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setHandler(null);
        Track::resetClient();
    }

    public function testSyncEventPostsDirectly(): void
    {
        Request::setHandler(function ($apiKey, $host, $path, $body, $timeout) {
            $this->assertSame('test-api-key', $apiKey);
            $this->assertSame('/v1/public/track/events/', $path);
            return ['result' => 'ok', 'body' => $body];
        });

        $client = new Client('test-api-key', null, false, true, 10, 10000, null, 0);
        $response = $client->event('1', 'Order Placed', ['orderValue' => '50.00'], '+91', '9999999999');

        $this->assertSame(['result' => 'ok', 'body' => ['event' => 'Order Placed', 'traits' => ['orderValue' => '50.00'], 'userId' => '1', 'countryCode' => '+91', 'phoneNumber' => '9999999999']], $response);
    }

    public function testUserQueuesInAsyncMode(): void
    {
        Request::setHandler(function () {
            $this->fail('Request handler should not be called when queueing');
        });

        $client = new Client('test-api-key', null, false, false, 10, 2, null, 0);
        [$success, $queueMsg] = $client->user('1', '+91', '9999999999', ['name' => 'Jane']);

        $this->assertTrue($success);
        $this->assertSame('/v1/public/track/users/', $queueMsg['path']);
        $this->assertSame('1', $queueMsg['body']['userId']);
        $this->assertSame(1, $client->getQueueSize());
    }

    public function testFlushSendsQueuedMessages(): void
    {
        $calls = 0;
        Request::setHandler(function ($apiKey, $host, $path, $body, $timeout) use (&$calls) {
            $calls++;
            return ['result' => 'ok'];
        });

        $client = new Client('test-api-key', null, false, false, 10, 10000, null, 0);
        $client->user('1', '+91', '9999999999', ['name' => 'Jane']);
        $client->event('1', 'Order Placed', ['orderValue' => '50.00'], '+91', '9999999999');

        $this->assertSame(2, $client->getQueueSize());
        $client->flush();
        $this->assertSame(0, $client->getQueueSize());
        $this->assertSame(2, $calls);
    }

    public function testEventRequiresEventName(): void
    {
        $client = new Client('test-api-key', null, false, true, 10, 10000, null, 0);
        $this->expectException(\InvalidArgumentException::class);
        $client->event('1', null, [], '+91', '9999999999');
    }
}
