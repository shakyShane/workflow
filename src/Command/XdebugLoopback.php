<?php

namespace Jh\Workflow\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author Michael Woodward <michael@wearejh.com>
 */
class XdebugLoopback extends Command implements CommandInterface
{
    /**
     * @var ProcessBuilder
     */
    private $processBuilder;

    public function __construct(ProcessBuilder $processBuilder)
    {
        parent::__construct();
        $this->processBuilder = $processBuilder;
    }

    public function configure()
    {
        $this
            ->setName('xdebug-loopback')
            ->setAliases(['xdebug'])
            ->setDescription('Starts the network loopback to allow Xdebug from Docker');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processBuilder->setArguments(['sudo', 'ifconfig', 'lo0', 'alias', '10.254.254.254']);
        $process = $this->processBuilder->setTimeout(null)->getProcess();
        $process->setPty(true);

        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });
    }
}
