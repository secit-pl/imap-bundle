<?php

declare(strict_types=1);

namespace SecIT\ImapBundle\Command;

use PhpImap\Exceptions\ConnectionException;
use SecIT\ImapBundle\Connection\ConnectionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(
    name: 'secit:imap:validate-connections',
    description: 'Validate if all Mailboxes can connect correct. If not, return 1.',
)]
class ValidateConnectionsCommand extends Command
{
    private ?InputInterface $input = null;
    private ?OutputInterface $output = null;
    private bool $failed = false;
    private array $connections = [];

    public function __construct(
        #[TaggedIterator('secit.imap.connection')]
        iterable $connections
    ) {
        parent::__construct();

        foreach ($connections as $connection) {
            $this->connections[$connection->getName()] = $connection;
        }
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('connections', InputArgument::IS_ARRAY, 'Connections. Will fail if not correct.'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $allowedConnections = $input->getArgument('connections');

        $connections = [];

        // Filter to the allowed connections if set
        if ($allowedConnections) {
            foreach ($allowedConnections as $connection) {
                if (array_key_exists($connection, $this->connections)) {
                    $connections[$connection] = $this->connections[$connection];
                } else {
                    $this->output->writeln('One or more connections given are not available');

                    return self::FAILURE;
                }
            }
        } else {
            $connections = $this->connections;
        }

        $this->dumpToScreen($connections);
        $this->output->writeln('Total connections: '.count($connections));

        if ($this->failed) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function dumpToScreen(array $connections): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Connection', 'Connect Result', 'Mailbox', 'Username']);

        foreach ($connections as $connection) {
            $table->addRow($this->getRow($connection));
        }

        $table->render();
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    private function getRow(ConnectionInterface $connection): array
    {
        try {
            $connection->testConnection(true);

            $result = 'SUCCESS';
        } catch (ConnectionException $exception) {
            $this->failed = true;

            $result = 'FAILED: '.$exception->getErrors('last');
        }

        return [
            $connection->getName(),
            $result,
            $connection->getMailbox(),
            $connection->getUsername(),
        ];
    }
}
