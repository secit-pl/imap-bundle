# PHP-IMAP integration bundle

Simple [php-imap](https://github.com/barbushin/php-imap) integration for Symfony 2.8, 3.x and 4.x.



## Installation

#### 1. Composer
From the command line run

```
$ composer require secit-pl/imap-bundle
```

If you're using Symfony Flex you're done and you can go to the configuration section otherwise you must manually register this bundle.

#### 2. Register bundle

If you're not using Symfony Flex you must manually register this bundle in your AppKernel by adding the bundle declaration

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            ...
            new SecIT\ImapBundle\ImapBundle(),
        ];

        ...
    }
}
```

## Configuration

Setup your mailbox configuration. If your are using symfony 2.8 or 3.x without Symfony Flex add your configuration in `app/config/config.yml`.
If you're using Symfony 4 or Symfony 3.x with Flex open the `config/packages/imap.yaml` and adjust its content.

Here is the example configuration:

```yaml
imap:
    connections:
        example_connection:
            mailbox: "{localhost:993/imap/ssl/novalidate-cert}INBOX"
            username: "email@example.com"
            password: "password"

        another_connection:
            mailbox: "{localhost:143}INBOX"
            username: "username"
            password: "password"
            attachments_dir: "%kernel.root_dir%/../var/imap/attachments"
            server_encoding: "UTF-8"
```

## Usage
#### With autowiring
In your controller:

```php
<?php

namespace App\Controller;

use SecIT\ImapBundle\Service\Imap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    public function indexAction(Imap $imap)
    {
        $exampleConnection = $imap->get('example_connection');
        $anotherConnection = $imap->get('another_connection');

        ...
    }

    ...
}

```

#### With service container
In your controller:

```php
<?php

namespace App\Controller;

use SecIT\ImapBundle\Service\Imap;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller
{
    public function indexAction(Imap $imap)
    {
        $exampleConnection = $this->get('secit.imap')->get('example_connection');
        $anotherConnection = $this->get('secit.imap')->get('another_connection');

        ...
    }

    ...
}

```

From this point you can use any of the methods provided by the [php-imap](https://github.com/barbushin/php-imap) library. For example


```php
$exampleConnection = $this->get('secit.imap')->get('example_connection');
$exampleConnection->getMailboxInfo();
```

To quickly test the connection to the server you can use the `testConnection()` method

```php
// testing with a boolean response
$isConnectable = $this->get('secit.imap')->testConnection('example_connection');
var_dump($isConnectable);

// testing with a full error message
try {
    $isConnectable = $this->get('secit.imap')->testConnection('example_connection', true);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

Be aware that this will disconnect your current connection and create a new one on success. In most cases this is not a problem.
