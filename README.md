emfl-api_php-lib
================

PHP API library for the emfluence Marketing Platform

The most simple example possible:

```php
require_once('api.class.inc');
$emfl = new Emfl_Platform_API( '123abc' );
$result = $emfl->ping();
echo '<h1>RESULT</h1><pre>' . var_export($result, TRUE) . '</pre>';
```

This will give you a response, even though the API token is bogus.

Check out example.php for a ready-to-use version of this.
