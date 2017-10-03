# PHP-IMAP integration bundle

Simple [php-imap](https://github.com/barbushin/php-imap) integration for Symfony 2.8 and 3.0+.



## Installation

From the command line run

```
$ composer require secit-pl/imap-bundle
```

Update your AppKernel by adding the bundle declaration

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

Setup your config.yml

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

In your controller:

```php
$exampleConnection = $this->get('secit.imap')->get('example_connection');
$anotherConnection = $this->get('secit.imap')->get('another_connection');
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
    $isConnectable = $this->get('secit.imap')->testConnection('example_connection', true)
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
```

Be aware that this will disconnect your current connection and create a new one on success. In most cases this is not a problem.