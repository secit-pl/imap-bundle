<?php

namespace SecIT\ImapBundle\Connection;

use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;

class Connection implements ConnectionInterface
{
    private ?Mailbox $phpImapMailbox = null;
    
    public function __construct(
        private readonly string $name,
        private readonly string $mailbox,
        private readonly string $username,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly string $serverEncoding = 'UTF-8',
        private readonly ?string $attachmentsDir = null,
        private readonly bool $createAttachmentsDirIfNotExists = true,
        private readonly int $createdAttachmentsDirPermissions = 770,
    ) {
        if (!extension_loaded('imap')) {
            throw new \ErrorException('PHP imap extension not loaded.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMailbox(): string
    {
        return $this->mailbox;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getServerEncoding(): string
    {
        return $this->serverEncoding;
    }

    public function getAttachmentsDir(): ?string
    {
        return $this->attachmentsDir;
    }

    public function isCreateAttachmentsDirIfNotExists(): bool
    {
        return $this->createAttachmentsDirIfNotExists;
    }

    public function getCreatedAttachmentsDirPermissions(): int
    {
        return $this->createdAttachmentsDirPermissions;
    }

    public function getConnection(): Mailbox
    {
        if (null === $this->phpImapMailbox) {
            if (null !== $this->attachmentsDir) {
                $this->checkAttachmentsDir(
                    $this->attachmentsDir,
                    $this->createAttachmentsDirIfNotExists,
                    $this->createdAttachmentsDirPermissions,
                );
            }

            $this->phpImapMailbox = new Mailbox(
                $this->mailbox,
                $this->username,
                $this->password,
                $this->attachmentsDir,
                $this->serverEncoding,
            );
        }

        return $this->phpImapMailbox;
    }
    
    public function testConnection(bool $throwExceptions = false): bool
    {
        try {
            return $this->getConnection()->getImapStream(true) !== null;
        } catch (ConnectionException $exception) {
            if ($throwExceptions) {
                throw $exception;
            }
        }

        return false;
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
