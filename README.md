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
## Options
U can override the default options by instantiating a `Session` class and pass in an _array_ as the second argument.

```php
$options = array(
    // If enabled (default) extra meta data is added (name,created,updated)
    'meta' => true,
    // Provide custom session name
    'name' => null,
    'lifetime' => 0,
    'path' => '/',
    'domain' => null,
    'secure' => true,
    'httponly' => false
);

// Instantiate class
$session = new Session(null,$options);

```
## start();
Start a new session.
```php
$session->start();
```

## destroy();
Destory the session.
```php
$session->destroy();
```

## close();
Close the session.
```php
$session->close();
```

## active();
Find out if their is a session active.
```php
$session->active();
```

## id($string);
Set session id, Get current/previous session id.
```php
$session->id($string);
```

## regenerate();
Regenerate session id, optional bool true for session deletion.
```php
$session->regenerate();
```

## ArryAccess
U can access the session object as an array.
```php
$session["key"] = "value";
```

## Testing
In the tests folder u can find several tests.

## License
[DBAD](http://www.dbad-license.org/ "DBAD") Public License.

## Acknowledgement
All the great session managment solutions.