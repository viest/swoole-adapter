## SwooleAdapter

### Lumen

```php
require_once __DIR__ . '/../vendor/autoload.php';

$configPath = realpath(__DIR__ . '/../config/swoole.php');
$config     = require_once $configPath;

$application = new \Vtiful\Application($config);

$application->run();
```
