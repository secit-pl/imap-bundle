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

    /**
     * @throws \Exception
     */
    public function getMailbox(): Mailbox;
    
    /**
     * @param bool $throwExceptions set to true if you'd like to get an exception on error instead of "return false"
     *
     * @throws ConnectionException
     */
    public function testConnection(bool $throwExceptions = false): bool;
}
