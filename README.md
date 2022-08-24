# PHP-IMAP integration bundle

Simple [php-imap](https://github.com/barbushin/php-imap) integration for Symfony 4.x, 5.x and 6.x.

> The version 1.5 and above are only compatible with Symfony 4+. Previous versions support was dropped. If you'd like to use it with Symfony 2.8 or 3.x you should use the version 1.4 which was the last compatible with Symfony 2.8 and 3.x. 

## Want to support this bundle?

Consider buying our MacOS app which allows you to customize your MacOS menu bar. 

[Infinite Menu Bar](https://infinitemenubar.com/appstore/github-imap-bundle) allows you to add to your menu bar custom elements. Want to have current IP (local or external), Macbook battery state, Bitcoin price or even custom contet from any HTTP URL or API? No problem! This app can do this and many more!

[![Infinite Menu Bar](https://infinitemenubar.com/assets/icon-100.jpg)](https://infinitemenubar.com/appstore/github-imap-bundle)

## Installation

#### 1. Composer
From the command line run

```
$ composer require secit-pl/imap-bundle
```

If you're using Symfony Flex you're done and you can go to the configuration section otherwise you must manually register this bundle.

#### 2. Register bundle

If you're not using Symfony Flex you must manually register this bundle in /config/bundles.php by adding the bundle declaration. 

```php
return [
  ...
  new SecIT\ImapBundle\ImapBundle(),
];
```

## Configuration

Setup your mailbox configuration.
If you're using Symfony 4 with Flex open the `config/packages/imap.yaml` and adjust its content.

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
            attachments_dir: "%kernel.project_dir%/var/imap/attachments"
            server_encoding: "UTF-8"

        full_config_connection:
            mailbox: "{localhost:143}INBOX"
            username: "username"
            password: "password"
            attachments_dir: "%kernel.project_dir%/var/imap/attachments"
            create_attachments_dir_if_not_exists: true # default true
            created_attachments_dir_permissions: 777 # default 770
            server_encoding: "UTF-8"
```

If you're using Symfony to connect to a Microsoft 365 business environment, there's a good chance you'll want to connect to a shared mailbox. 
In that case you need to specify the parameters ```authuser``` and ```user```. 
Where *shared_account* is the username without domain, like:

```yaml
imap:
    connections:
        example_connection:
            mailbox: "{outlook.office365.com:993/imap/ssl/authuser=first.last@example.com/user=shared_account}Root/Folder"
            username: "email@example.com"
            password: "password"
```

### Security

Do not set the sensitive Data like mailbox, username and password directly in the config-files. You may have to [encode the values](https://symfony.com/doc/current/doctrine.html#configuring-the-database).
[Configuration Based on Environment Variables](https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables)
[Referencing Secrets in Configuration Files](https://symfony.com/doc/current/configuration/secrets.html#referencing-secrets-in-configuration-files)
Better set them in ```.env.local```, use Symfony Secrets or CI-Secrets.

```yaml
imap:
    connections:
        example_connection:
            mailbox:  '%env(EXAMPLE_CONNECTION_MAILBOX)%'
            username: '%env(EXAMPLE_CONNECTION_USERNAME)%'
            password: '%env(EXAMPLE_CONNECTION_PASSWORD)%'
```

### Dump actual config:

```
php bin/console debug:config imap
```

### Validate if the mailboxes can connect correct

```
php bin/console imap-bundle:validate
```

Result:
```
+--------------------------+----------------+-------------------------------+--------------------+
| Connection               | Connect Result | Mailbox                       | Username           |
+--------------------------+----------------+-------------------------------+--------------------+
| example_connection       | SUCCESS        | {imap.strato.de:993/imap/ssl} | user@mail.com      |
| example_WRONG_connection | FAILED         | {imap.strato.de:993/imap/ssl} | WRONG              |
+--------------------------+----------------+-------------------------------+--------------------+
```

This command can take some while if a connect failed. That is because of a long connection-timeout.
If you use this in CI-Pipeline add the parameter `-q`.
Password is not displayed for security reasons.
You can set an array of connections to validate.

```
php bin/console imap-bundle:validate example_connection example_connection2
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

#### With service container (Only works in Symfony < 6)
In your controller:

```php
<?php

namespace App\Controller;

use SecIT\ImapBundle\Service\Imap;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller
{
    public function indexAction()
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
$exampleConnection = $imap->get('example_connection');
$exampleConnection->getMailboxInfo();
```

To quickly test the connection to the server you can use the `testConnection()` method

```php
// testing with a boolean response
$isConnectable = $imap->testConnection('example_connection');
var_dump($isConnectable);

// testing with a full error message
try {
    $isConnectable = $imap->testConnection('example_connection', true);
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

Be aware that this will disconnect your current connection and create a new one on success. In most cases this is not a problem.
