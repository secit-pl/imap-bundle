<?php

namespace SecIT\ImapBundle\Connection;

use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;

class Connection implements ConnectionInterface
{
    private ?Mailbox $mailbox = null;
    
    public function __construct(
        private readonly string $name,
        private readonly string $imapPath,
        private readonly string $username,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly string $serverEncoding = 'UTF-8',
        private readonly ?string $attachmentsDir = null,
        private readonly bool $createAttachmentsDirIfNotExists = true,
        private readonly int $createdAttachmentsDirPermissions = 770,
        private readonly bool $enabled = true,
    ) {
        if (!extension_loaded('imap')) {
            throw new \ErrorException('PHP imap extension not loaded.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImapPath(): string
    {
        return $this->imapPath;
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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getMailbox(): Mailbox
    {
        if (null === $this->mailbox) {
            if (null !== $this->attachmentsDir) {
                $this->checkAttachmentsDir(
                    $this->attachmentsDir,
                    $this->createAttachmentsDirIfNotExists,
                    $this->createdAttachmentsDirPermissions,
                );
            }

            $this->mailbox = new Mailbox(
                $this->imapPath,
                $this->username,
                $this->password,
                $this->attachmentsDir,
                $this->serverEncoding,
            );
        }

        return $this->mailbox;
    }
    
    public function testConnection(bool $throwExceptions = false): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return $this->getMailbox()->getImapStream(true) !== null;
        } catch (ConnectionException $exception) {
            if ($throwExceptions) {
                trigger_deprecation(
                    'secit-pl/imap-bundle',
                    '3.1',
                    'Setting the "%s()" method $throwExceptions argument to true is deprecated. The argument will be removed in imap-bundle 4.0. Use tryTestConnection() instead.',
                    __METHOD__,
                );

                throw $exception;
            }
        }

        return false;
    }

    public function tryTestConnection(): void
    {
        if (!$this->isEnabled()) {
            throw new \ErrorException('Mailbox is not enabled');
        }

        $this->getMailbox()->getImapStream(true);
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
