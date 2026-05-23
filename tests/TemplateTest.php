<?php

namespace Interakt\Track\Tests;

use Interakt\Track\Client;
use Interakt\Track\Request;
use Interakt\Track\Webhook;
use Interakt\Track\Track;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setHandler(null);
        Track::resetClient();
    }

    public function testSendTemplatePostsToMessageEndpoint(): void
    {
        Request::setHandler(function ($apiKey, $host, $path, $body, $timeout) {
            $this->assertSame('test-api-key', $apiKey);
            $this->assertSame('/v1/public/message/', $path);
            $this->assertArrayHasKey('template', $body);
            $this->assertSame('Template', $body['type']);
            return ['result' => true, 'message' => 'Message created successfully', 'id' => 'abc-123'];
        });

        $client = new Client('test-api-key', null, false, true, 10, 10000, null, 0);

        $template = [
            'name' => 'delivered_alert_101',
            'languageCode' => 'en',
            'bodyValues' => ['Alice', 'Order001'],
        ];

        $response = $client->sendTemplate('+91', '9999999999', $template, 'cbdata');

        $this->assertIsArray($response);
        $this->assertTrue($response['result']);
        $this->assertSame('abc-123', $response['id']);
    }

    public function testWebhookSignatureVerification(): void
    {
        $secret = 'examplekey';
        $payload = json_encode(['foo' => 1, 'bar' => 2]);

        $sig = Webhook::computeSignature($secret, $payload);
        $this->assertStringStartsWith('sha256=', $sig);
        $this->assertTrue(Webhook::verify($secret, $payload, $sig));

        // tampered signature should fail
        $this->assertFalse(Webhook::verify($secret, $payload, 'sha256=deadbeef'));
    }
}
