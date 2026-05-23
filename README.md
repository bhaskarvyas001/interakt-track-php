# Interakt APIs PHP

A PHP port of the Interakt api SDK with user and event tracking support.

## Installation

```bash
composer require bhaskarvyas001/interakt-api-php
```

## Usage

```php
require 'vendor/autoload.php';

use Interakt\Track\Track;

Track::$apiKey = 'YOUR_API_KEY';
Track::$syncMode = false; // default is async queue flushed on shutdown
Track::$debug = true;

Track::user(
    userId: 'user123',
    countryCode: '+91',
    phoneNumber: '9999999999',
    traits: ['name' => 'John Doe', 'email' => 'john@example.com']
);

Track::event(
    userId: 'user123',
    event: 'Order Placed',
    traits: ['orderValue' => '50.00'],
    countryCode: '+91',
    phoneNumber: '9999999999'
);

Track::flush();
```

## API

- `Track::user($userId, $countryCode, $phoneNumber, $traits)`
- `Track::event($userId, $event, $traits, $countryCode, $phoneNumber)`
- `Track::flush()`
- `Track::join()`
- `Track::shutdown()`

## Template Message (WhatsApp)

You can send WhatsApp template (HSM) messages using the SDK. The SDK provides a convenience method `Track::sendTemplate()` and a raw `Track::message()` call which posts to the `/v1/public/message/` endpoint.

Example using `sendTemplate`:

```php
use Interakt\Track\Track;

Track::$apiKey = 'YOUR_API_KEY';

$template = [
    'name' => 'delivered_alert_101',
    'languageCode' => 'en',
    'bodyValues' => ['John', 'Order123'],
    // optional: 'headerValues' => ['Header text or media url'],
    // optional: 'buttonValues' => ['0' => ['payload0'], '1' => ['payload1']],
    // optional: 'fileName' => 'invoice.pdf' // when header is a document
];

// sends the template message (async by default)
Track::sendTemplate('+91', '9999999999', $template, 'optional-callback-data', null);
```

Example sending a raw payload with `Track::message`:

```php
$body = [
    'countryCode' => '+91',
    'phoneNumber' => '9999999999',
    'type' => 'Template',
    'template' => $template,
    'callbackData' => 'some_callback_data',
];

Track::message($body);
```

The API responds with an `id` for the created message; delivery/read/failure status will be delivered later via webhooks.

## Webhooks (message status and incoming messages)

Interakt sends webhooks for template delivery status (`message_api_sent`, `message_api_delivered`, `message_api_read`, `message_api_failed`) and for incoming messages (`message_received`). Webhooks are delivered to the URL configured in your Interakt developer settings.

Security: Interakt signs webhook payloads with HMAC SHA256 in the `Interakt-Signature` header (prefixed with `sha256=`). Use the SDK helper `Interakt\Track\Webhook::verify()` to validate the signature.

Example webhook verifier (PHP):

```php
use Interakt\Track\Webhook;

$secret = 'YOUR_WEBHOOK_SECRET';
$payload = file_get_contents('php://input');
$headers = getallheaders();
$signature = $headers['Interakt-Signature'] ?? ($headers['interakt-signature'] ?? '');

if (!Webhook::verify($secret, $payload, $signature)) {
    http_response_code(401);
    echo 'invalid signature';
    exit;
}

$data = json_decode($payload, true);
// handle webhook types in $data['type'] and $data['data']

http_response_code(200);
```

Notes:
- Your webhook endpoint must be HTTPS and respond within 3 seconds.
- Any non-200 response or timeout is treated as a delivery failure by Interakt.
- The `callbackData` you passed when sending a template will be returned in the webhook payload so you can correlate messages.

## Testing

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist
```
