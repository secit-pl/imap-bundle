<?php

declare(strict_types=1);

namespace SecIT\ImapBundle\Service;

use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;

/**
 * Class Imap.
 *
 * @author Tomasz Gemza
 */
class Imap
{
    /** @var Mailbox[] $instances */
    protected array $instances = [];

    /**
     * Imap constructor.
     *
     * @param array<string, mixed> $connections
     */
    public function __construct(protected array $connections)
    {
    }

    /**
     * Get a connection to the specified mailbox.
     *
     * @param bool $flush force to create a new Mailbox instance
     *
     * @throws \Exception
     */
    public function get(string $name, bool $flush = false): Mailbox
    {
        if ($flush || !isset($this->instances[$name])) {
            $this->instances[$name] = $this->getMailbox($name);
        }

        return $this->instances[$name];
    }

    /**
     * Get a connection for all mailboxes.
     *
     * @param bool $flush force to create a new Mailbox instance
     * 
     * @return Mailbox[]
     *
     * @throws \Exception
     */
    public function getAll(): array
    {
        $this->instances = $this->getMailboxes();
        return $this->instances;
    }

    /**
     * Test mailbox connection.
     *
     * @param bool $throwExceptions set to true if you'd like to get an exception on error instead of "return false"
     *
     * @throws ConnectionException
     */
    public function testConnection(string $name, bool $throwExceptions = false): bool
    {
        try {
            return $this->getMailbox($name)->getImapStream(true) !== null;
        } catch (ConnectionException $exception) {
            if ($throwExceptions) {
                throw $exception;
            }
        }

        return false;
    }

    /**
     * Get new mailbox instance.
     *
     * @throws \Exception
     */
    protected function getMailbox(string $name): Mailbox
    {
        if (!isset($this->connections[$name])) {
            throw new \RuntimeException(sprintf('Imap connection %s is not configured.', $name));
        }

        $config = $this->connections[$name];

        if (isset($config['attachments_dir'])) {
            $this->checkAttachmentsDir(
                $config['attachments_dir'],
                $config['create_attachments_dir_if_not_exists'],
                $config['created_attachments_dir_permissions']
            );
        }

        return new Mailbox(
            $config['mailbox'],
            $config['username'],
            $config['password'],
            $config['attachments_dir'],
            $config['server_encoding']
        );
    }


    /**
     * Get new mailboxes instance.
     * 
     * @return Mailbox[]
     *
     * @throws \Exception
     */
    protected function getMailboxes(): array
    {
        if (!isset($this->connections)) {
            throw new \RuntimeException(sprintf('Imap connections are not configured.'));
        }

        $config = $this->connections;
        $mailboxes = array();

        foreach ($config as $mailbox) {
           if (isset($mailbox['attachments_dir'])) {
                $this->checkAttachmentsDir(
                    $mailbox['attachments_dir'],
                    $mailbox['create_attachments_dir_if_not_exists'],
                    $mailbox['created_attachments_dir_permissions']
                );
            }
            $mailboxes[] = new Mailbox(
                $mailbox['mailbox'],
                $mailbox['username'],
                $mailbox['password'],
                $mailbox['attachments_dir'],
                $mailbox['server_encoding']
            );
        }

        return $mailboxes;
    }

    /**
     * Check attachments directory.
     *
     * @param int $directoryPermissions In decimal format! 775 instead of 0775
     *
     * @throws \Exception
     */
    protected function checkAttachmentsDir(?string $directoryPath, bool $createIfNotExists, int $directoryPermissions): void
    {
        if (!$directoryPath) {
            return;
        }

        if (file_exists($directoryPath)) {
            if (!is_dir($directoryPath)) {
                throw new \RuntimeException(sprintf('File "%s" exists but it is not a directory', $directoryPath));
            }

            if (!is_readable($directoryPath) || !is_writable($directoryPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" does not have enough access permissions', $directoryPath));
            }
        } elseif ($createIfNotExists) {
            $umask = umask(0);
            $created = mkdir($directoryPath, octdec(''.$directoryPermissions), true);
            umask($umask);

            if (!$created) {
                throw new \RuntimeException(sprintf('Cannot create the attachments directory "%s"', $directoryPath));
            }
        }
    }
}
