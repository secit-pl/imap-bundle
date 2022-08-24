<?php
namespace SecIT\ImapBundle\Command;

use SecIT\ImapBundle\Service\Imap;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * USE: php bin/console imap-bundle:validate-connections
 */
# Symfony > 5.3
##[AsCommand(name: 'imap-bundle:validate-connections', description: 'Validate if all Mailboxes can connect correct. If not, return 1')]
class ValidateConnectionsCommand extends Command
{
    protected static $defaultName = 'imap-bundle:validate-connections';
    protected static $defaultDescription = 'Validate if all Mailboxes can connect correct. If not, return 1';

    protected ?InputInterface $input;
	protected ?OutputInterface $output;
	protected bool $failed = false;

	public function __construct(protected Imap $imap, protected ParameterBagInterface $parameter)
    {
	    parent::__construct();

        $this->output = null;
	    $this->input = null;
	}

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('connections', InputArgument::IS_ARRAY, 'Connections. Will fail if not correct'),
            ]);
    }

    /**
     * @return string[]
     * @throws \Exception
     */
	protected function getRow(string $key, array $connection): array
    {
        $connection_test = $this->imap->testConnection($key, false);
        if(!$connection_test)
        {$this->failed = true;}

	    return [
           $key,
           ($connection_test) ? 'SUCCESS' : 'FAILED',
	       $connection["mailbox"],
	       $connection["username"],
        ];
	}

    protected function dumpToScreen(array $connections): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Connection', 'Connect Result', 'Mailbox', 'Username']);

        foreach($connections as $key => $connection)
        {
            $table->addRow($this->getRow($key, $connection));
        }

        $table->render();
    }

	protected function execute(InputInterface $input, OutputInterface $output): int
    {
	    $this->input = $input;
	    $this->output = $output;
        $allowedConnections = $input->getArgument('connections');
        $allAvailableConnections = $this->parameter->get('secit.imap.connections');
        $connections = [];

        # Filter to the allowed connections if set
        if($allowedConnections)
        {
            foreach($allowedConnections as $key => $connection)
            {
                if(array_key_exists($connection, $allAvailableConnections))
                {
                    $connections[$connection] = $allAvailableConnections[$connection];
                }
                else
                {
                    $this->output->writeln('One or more Connections given are not available');
                    return 1;
                }
            }
        }
        else
        {
            $connections = $allAvailableConnections;
        }

    	$this->dumpToScreen($connections);
        $this->output->writeln('Total Connections: '.count($connections));

        if($this->failed)
        {
         return 1;
        }
        else
        {
         return 0;
         #return Command::SUCCESS; # Symfony > 5
        }
	}
}
