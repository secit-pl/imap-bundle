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

        return new Mailbox(
            $config['mailbox'],
            $config['username'],
            $config['password'],
            isset($config['attachments_dir']) ? $config['attachments_dir'] : null,
            isset($config['server_encoding']) ? $config['server_encoding'] : 'UTF-8'
        );
    }
}
