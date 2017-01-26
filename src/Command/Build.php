<?php

namespace Jh\Workflow\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author Michael Woodward <michael@wearejh.com>
 */
class Build extends Command implements CommandInterface
{
    use DockerAwareTrait;

    /**
     * @var ProcessBuilder
     */
    private $processBuilder;

    public function __construct(ProcessBuilder $processBuilder)
    {
        parent::__construct();
        $this->processBuilder = $processBuilder;
    }

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Runs docker build to create an image ready for use')
            ->addOption('prod', 'p', InputOption::VALUE_NONE, 'Ommits development configurations');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $service  = $this->getServiceConfig('php');
        $buildArg = $input->getOption('prod') ? '--build-arg BUILD_ENV=prod' : '';

        if (!isset($service['image'])) {
            throw new \RuntimeException('No image specified for PHP container');
        }

        $this->processBuilder->setArguments([
            'docker build',
            '-t ' . $service['image'],
            '-f app.php.dockerfile',
            $buildArg,
            './'
        ]);

        $process = $this->processBuilder->setTimeout(null)->getProcess();

        $process->run(function ($type, $buffer) use ($output) {
            Process::ERR === $type
                ? $output->writeln('ERR > '. $buffer)
                : $output->writeln('OUT > '. $buffer);
        });

        $output->writeln('<info>Build complete!</info>');
    }
}
