# PHP-IMAP integration bundle

Simple [php-imap](https://github.com/barbushin/php-imap) integration for Symfony.

## Compatibility matrix


| Bundle version | Maintained | Symfony versions | Min. PHP version |
|----------------|------------|------------------|------------------|
| 3.x            | Yes        | 6.4 to 7.x       | 8.1.0            |
| 2.1            | No         | 4.4 to 6.4       | 8.0.0            |
| 1.5            | No         | 4.0 to 4.3       | 5.4.0            |
| 1.4            | No         | 2.8 to 3.4       | 5.4.0            |

## Want to support this bundle?

Consider buying our macOS app which allows you to customize your macOS menu bar. 

[Infinite Menu Bar](https://infinitemenubar.com/appstore/github-imap-bundle) allows you to add to your menu bar custom elements. Want to have current IP (local or external), Macbook battery state, Bitcoin price or even custom content from any HTTP URL or API? No problem! This app can do this and many more!

[![Infinite Menu Bar](https://infinitemenubar.com/assets/icon-100.jpg)](https://infinitemenubar.com/appstore/github-imap-bundle)

## Installation

#### 1. Composer
From the command line run

```bash
composer require secit-pl/imap-bundle
```


## Configuration

To set up your mailbox configuration open the `config/packages/imap.yaml` and adjust its content.

Here is the example configuration:

```yaml
imap:
    connections:
        example:
            imap_path: "{localhost:993/imap/ssl/novalidate-cert}INBOX"
            username: "email@example.com"
            password: "password"

        another:
            imap_path: "{localhost:143}INBOX"
            username: "username"
            password: "password"
            attachments_dir: "%kernel.project_dir%/var/imap/attachments"
            server_encoding: "UTF-8"

        full_config:
            imap_path: "{localhost:143}INBOX"
            username: "username"
            password: "password"
            attachments_dir: "%kernel.project_dir%/var/imap/attachments"
            create_attachments_dir_if_not_exists: true # default true
            created_attachments_dir_permissions: 777 # default 770
            server_encoding: "UTF-8"
            enabled: true
```

If you're using Symfony to connect to a Microsoft 365 business environment, there's a good chance you'll want to connect to a shared mailbox. 
In that case you need to specify the parameters ```authuser``` and ```user```. 
Where *shared_account* is the username without domain, like:

```yaml
imap:
    connections:
        example:
            imap_path: "{outlook.office365.com:993/imap/ssl/authuser=first.last@example.com/user=shared_account}Root/Folder"
            username: "email@example.com"
            password: "password"
```

### Security

It's good practice to do not set the sensitive data like mailbox, username and password directly in the config-files. You may have to [encode the values](https://symfony.com/doc/current/doctrine.html#configuring-the-database).
[Configuration Based on Environment Variables](https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables)
[Referencing Secrets in Configuration Files](https://symfony.com/doc/current/configuration/secrets.html#referencing-secrets-in-configuration-files)
Better set them in ```.env.local```, use Symfony Secrets or CI-Secrets.

```yaml
imap:
    connections:
        example:
            imap_path:  '%env(EXAMPLE_CONNECTION_MAILBOX)%'
            username: '%env(EXAMPLE_CONNECTION_USERNAME)%'
            password: '%env(EXAMPLE_CONNECTION_PASSWORD)%'
```

### Dump actual config:

```bash
php bin/console debug:config imap
```

### Validate if the mailboxes can connect correct

```bash
php bin/console secit:imap:validate-connections
```

Result:
```
+------------------+---------------------+---------------------------------+--------------------+---------+
| Connection       | Connect Result      | Mailbox                         | Username           | Enabled |
+------------------+---------------------+---------------------------------+--------------------+---------+
| example          | SUCCESS             | {imap.example.com:993/imap/ssl} | user@mail.com      | YES     |
| example_WRONG    | FAILED: Reason..... | {imap.example.com:993/imap/ssl} | WRONG              | YES     |
| example_DISABLED | FAILED: not enabled | {imap.example.com:993/imap/ssl} | user2@mail.com     | NO      |
+------------------+---------------------+---------------------------------+--------------------+---------+
```

This command can take some while if any connection failed. That is because of a long connection-timeout.
If you use this in CI-Pipeline add the parameter `-q`.
Password is not displayed for security reasons.
You can set an array of connections to validate.

```
php bin/console secit:imap:validate-connections example example2
```

## Usage

Let's say your config looks like this

```yaml
imap:
    connections:
        example:
            imap_path: ...
            
        second:
            imap_path: ...
            
        connection3:
            imap_path: ...
```

You can get the connection inside a class by using service [autowiring](https://symfony.com/doc/current/service_container/autowiring.html) and using camelCased connection name + `Connection` as parameter name.  

```php
<?php

namespace App\Controller;

use SecIT\ImapBundle\ConnectionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    public function index(
        ConnectionInterface $exampleConnection,
        ConnectionInterface $secondConnection,
        ConnectionInterface $connection3Connection,
    ) {
        $mailbox = $exampleConnection->getMailbox(); // instance of PhpImap\Mailbox
        $isConnectable = $secondConnection->testConnection();
        $connectionName = $connection3Connection->getName(); // connection3

        ...
    }

    ...
}

```

Connections can also be injected thanks to their name and the [Target](https://symfony.com/doc/current/service_container/autowiring.html#dealing-with-multiple-implementations-of-the-same-type) attribute:

```php
<?php

namespace App\Controller;

use SecIT\ImapBundle\ConnectionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;

class IndexController extends AbstractController
{
    public function index(
        #[Target('exampleConnection')] 
        ConnectionInterface $example,
        #[Target('secondConnection')] 
        ConnectionInterface $customName,
        #[Target('connection3Connection')] 
        ConnectionInterface $connection,
    ) {
        $mailbox = $exampleConnection->getMailbox(); // instance of PhpImap\Mailbox
        $isConnectable = $secondConnection->testConnection();
        $connectionName = $connection3Connection->getName(); // connection3

        ...
    }

    ...
}

```

To get all connections you can use [TaggedIterator](https://symfony.com/doc/current/service_container/tags.html#reference-tagged-services)  

```php
<?php

namespace App\Controller;

use SecIT\ImapBundle\ConnectionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class IndexController extends AbstractController
{
    public function index(
        #[TaggedIterator('secit.imap.connection')]
        iterable $connections,
    ) {
        foreach ($connections as $connection) {
            $mailbox = $connection->getMailbox();
        }

        ...
    }

    ...
}

```

From this point you can use any of the methods provided by the [php-imap](https://github.com/barbushin/php-imap) library. For example


```php
$mailbox = $exampleConnection->getMailbox();
$mailbox->getMailboxInfo();
```

To quickly test the connection to the server you can use the `testConnection()` method

```php
// testing with a boolean response
$isConnectable = $exampleConnection->testConnection();
var_dump($isConnectable);

// testing with a full error message
try {
    $isConnectable = $exampleConnection->testConnection(true);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

Be aware that this will disconnect your current connection and create a new one on success. In most cases this is not a problem.

## Migration guide

Version 3.0.0 introduces some BC breaks.

### Configuration

To better match [PhpImap\Mailbox](https://github.com/barbushin/php-imap/blob/master/src/PhpImap/Mailbox.php) constructor arguments the `mailbox` configuration parameter was renamed to `imap_path`.

Previous version:

```yaml
imap:
    connections:
        example_connection:
            mailbox: ...
            username: ...
            password: ...
```

Current version:


```yaml
imap:
    connections:
        example:
            imap_path: ...
            username: ...
            password: ...
```

### Connections getting

Previously to get the connection, you had to inject the `SecIT\ImapBundle\Service\Imap` service and get a connection from it.

```php
public function index(Imap $imap)
{
    $mailbox = $imap->get('example_connection')->getConnection();
}
```

After migration, you should use [autowiring](https://symfony.com/doc/current/service_container/autowiring.html) to inject dynamically created services for each connection

```php

use SecIT\ImapBundle\Connection\ConnectionInterface;

public function index(ConnectionInterface $exampleConnection)
{
    $mailbox = $exampleConnection->getMailbox();
}
```

or use [Target](https://symfony.com/doc/current/service_container/autowiring.html#dealing-with-multiple-implementations-of-the-same-type) attribute

```php

use SecIT\ImapBundle\ConnectionInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

public function index(
    #[Target('exampleConnection')] 
    ConnectionInterface $customName,
) {
    $mailbox = $customName->getMailbox();
}
```

### Console command

The command changes its name from `imap-bundle:validate` to `secit:imap:validate-connections`.
