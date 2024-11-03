# Lightweight PHP Library for MikroTik API Management

**MikroLink** is a lightweight PHP library designed for interacting with MikroTik routers using their API. This library simplifies the process of managing and automating tasks on MikroTik devices programmatically.

---

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage](#usage)
- [Available Methods](#available-methods)
- [License](#license)

---

## Requirements

To utilize this library effectively, ensure your environment meets the following requirements:

- PHP version 7.0 or higher

## Installation

For seamless installation, use [Composer](http://getcomposer.org/download/):

```bash
composer require imafaz/mikrolink
```

## Quick Start

Once the library is installed, include it in your PHP script:

```php
require_once 'vendor/autoload.php';

use MikrotikApi\MikroLink;
```

## Usage

Initialize the MikroLink object with debug mode, timeout, attempts, and delay settings:

```php
$router = new MikroLink;
```

Connect to a MikroTik router using its IP, username, password, port, and optional SSL mode:

```php
$router->connect('192.168.1.1', 'admin', 'password', 8728, false);
```

Execute commands on the connected router:

```php
$response = $router->exec('/interface/print');

var_dump($response); //print array response
```

disconnect router connection :

```php
$router->disconnect();
```


## Available Methods

### `__construct`

#### Description:

Initializes the MikroLink object with debugging and connection settings.

#### Signature:

```php
$router = new MikroLink( int $timeout = 1,  int $attempts = 3,int  $delay = 0,$logFile = 'mikrolink.log',$printLog = false);
```

#### Attributes:

| Attribute    | Description                   | Type   | Required | Default |
| ------------ | ----------------------------- | ------ | -------- | ------- |
| $timeout     | Connection timeout (seconds)  | int    | No       | 1       |
| $attempts    | Connection attempts           | int    | No       | 3       |
| $delay       | Delay between attempts (sec)  | int    | No       | 0       |
| $logFile       | log file name   | string    | No       | mikrolink.log       |
| $printLog       | print log  | bool    | No       | false       |
---

### `connect`

#### Description:

Connects to a MikroTik router using the provided credentials and connection details.

#### Signature:

```php
$router->connect(string $ip, string $username, string $password, int $port, $ssl = false);
```

#### Attributes:

| Attribute   | Description                    | Type   | Required | Default |
| ----------- | ------------------------------  | ------ | -------- | ------- |
| $ip         | Router IP address              | string | Yes      | N/A     |
| $username   | Router login username          | string | Yes      | N/A     |
| $password   | Router login password          | string | Yes      | N/A     |
| $port       | Router API port                | int    | Yes      | N/A     |
| $ssl        | SSL mode toggle                | bool   | No       | false   |

---

### `exec`

#### Description:

Executes a MikroTik API command with optional parameters.

#### Signature:

```php
$router->exec(string $command, array $params = null);
```

#### Attributes:

| Attribute   | Description                    | Type   | Required | Default |
| ----------- | ------------------------------  | ------ | -------- | ------- |
| $command    | API command to execute         | string | Yes      | N/A     |
| $params     | Additional command parameters  | array  | No       | null    |

---

### `disconnect`

#### Description:

Terminates the connection with the MikroTik router.

#### Signature:

```php
$router->disconnect();
```


## License

This library is licensed under the [MIT License](https://opensource.org/licenses/MIT)
