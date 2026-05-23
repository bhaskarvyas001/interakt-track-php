# Interakt Track PHP

A PHP port of the Interakt Track SDK with user and event tracking support.

## Installation

```bash
composer require interakt/track-php
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

## Testing

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist
```
