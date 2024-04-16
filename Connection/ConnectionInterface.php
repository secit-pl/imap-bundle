<?php

namespace SecIT\ImapBundle\Connection;

use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;

interface ConnectionInterface
{
    public function getName(): string;
    public function getImapPath(): string;
    public function getUsername(): string;
    public function getPassword(): string;
    public function getServerEncoding(): string;
    public function getAttachmentsDir(): ?string;
    public function isCreateAttachmentsDirIfNotExists(): bool;
    public function getCreatedAttachmentsDirPermissions(): int;
    public function isEnabled(): bool;

    /**
     * @throws \Exception
     */
    public function getMailbox(): Mailbox;
    
    /**
     * @deprecated since 3.1 setting the "testConnection()" method $throwExceptions argument to true is deprecated. The argument will be removed in imap-bundle 4.0. Use tryTestConnection() instead.
     *
     * @param bool $throwExceptions set to true if you'd like to get an exception on error instead of "return false"
     *
     * @throws ConnectionException
     */
    public function testConnection(bool $throwExceptions = false): bool;

    /**
     * @throws ConnectionException
     */
    public function tryTestConnection(): void;
}
