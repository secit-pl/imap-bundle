<?php

namespace SecIT\ImapBundle\Service;

use PhpImap\Mailbox;

/**
 * Class Imap.
 *
 * @author Tomasz Gemza
 */
class Imap
{
    /**
     * @var array
     */
    protected $connections;

    /**
     * @var array|Mailbox[]
     */
    protected $instances = [];

    /**
     * Imap constructor.
     *
     * @param array $connections
     */
    public function __construct(array $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Get a connection to the specified mailbox.
     *
     * @param string $name
     * @param bool   $flush force to create a new Mailbox instance
     *
     * @return Mailbox
     *
     * @throws \Exception
     */
    public function get($name, $flush = false)
    {
        if ($flush || !isset($this->instances[$name])) {
            $this->instances[$name] = $this->getMailbox($name);
        }

        return $this->instances[$name];
    }

    /**
     * Test mailbox connection.
     *
     * @param string $name
     * @param bool   $throwExceptions set to true if you'd like to get an exception on error instead of "return false"
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function testConnection($name, $throwExceptions = false)
    {
        try {
            return $this->getMailbox($name)->getImapStream(true) !== null;
        } catch (\Exception $exception) {
            if ($throwExceptions) {
                throw $exception;
            }
        }

        return false;
    }

    /**
     * Get new mailbox instance.
     *
     * @param string $name
     *
     * @return Mailbox
     *
     * @throws \Exception
     */
    protected function getMailbox($name)
    {
        if (!isset($this->connections[$name])) {
            throw new \Exception(sprintf('Imap connection %s is not configured.', $name));
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
     * Check attachments directory.
     *
     * @param null|string $directoryPath
     * @param bool        $createIfNotExists
     * @param int         $directoryPermissions In decimal format! 775 instead of 0775
     *
     * @throws \Exception
     */
    protected function checkAttachmentsDir($directoryPath, $createIfNotExists, $directoryPermissions)
    {
        if (!$directoryPath) {
            return;
        }

        if (file_exists($directoryPath)) {
            if (!is_dir($directoryPath)) {
                throw new \Exception(sprintf('File "%s" exists but it is not a directory', $directoryPath));
            }

            if (!is_readable($directoryPath) || !is_writable($directoryPath)) {
                throw new \Exception(sprintf('Directory "%s" does not have enough access permissions', $directoryPath));
            }
        } elseif($createIfNotExists) {
            $umask = umask(0);
            $created = mkdir($directoryPath, octdec($directoryPermissions), true);
            umask($umask);

            if (!$created) {
                throw new \Exception(sprintf('Cannot create the attachments directory "%s"', $directoryPath));
            }
        }
    }
}
