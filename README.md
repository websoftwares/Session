# Session
PHP 5.3+ Session Class that accepts optional save handlers.

[![Build Status](https://api.travis-ci.org/websoftwares/Session.png)](https://travis-ci.org/websoftwares/Session)

## Installing via Composer (recommended)

Install composer in your project:
```
curl -s http://getcomposer.org/installer | php
```

Create a composer.json file in your project root:
```
{
    "require": {
        "websoftwares/session": "dev-master"
    }
}
```
Install via composer
```
php composer.phar install
```

## Usage
Basic usage of the `Session` class.

```php
use Websoftwares\Session;

// Instantiate class
$session = new Session;

// Start session
$session->start();

// Store in session
$session["key"] = 'value';

var_dump($_SESSION);

// Destroy
$session->destroy();

```

## Testing
In the tests folder u can find several tests.

## License
[DBAD](http://www.dbad-license.org/ "DBAD") Public License.

## Acknowledgement
Inspired by all the great session managment solutions already out their.