<?php

namespace Jh\WorkflowTest\Command;

use Jh\Workflow\Command\ComposerRequire;
use Jh\Workflow\Command\Pull;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ComposerRequireTest extends AbstractTestCommand
{
    /**
     * @var ComposerRequire
     */
    private $command;

    /**
     * @var ObjectProphecy|Application
     */
    private $application;

    /**
     * @var ObjectProphecy|Pull
     */
    private $pullCommand;


    public function setUp()
    {
        parent::setUp();

        $this->command     = new ComposerRequire($this->processFactory->reveal());
        $this->application = $this->prophesize(Application::class);
        $this->pullCommand = $this->prophesize(Pull::class);

        $this->application->getHelperSet()->willReturn(new HelperSet);
        $this->application->find('pull')->willReturn($this->pullCommand->reveal());

        $this->command->setApplication($this->application->reveal());
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function testCommandIsConfigured()
    {
        $description = 'Runs composer require inside the container and pulls back required files to the host';

        static::assertEquals('composer-require', $this->command->getName());
        static::assertEquals(['cr'], $this->command->getAliases());
        static::assertEquals($description, $this->command->getDescription());
        static::assertArrayHasKey('package', $this->command->getDefinition()->getArguments());
        static::assertArrayHasKey('dev', $this->command->getDefinition()->getOptions());
    }

    public function testComposerRequireCommand()
    {
        $this->useValidEnvironment();

        $this->output->getVerbosity()->willReturn(OutputInterface::OUTPUT_NORMAL);

        $cmd  = 'docker exec -u www-data -e COMPOSER_CACHE_DIR=.docker/composer-cache m2-php composer require';
        $cmd .= ' my/package --ansi';

        $this->processTest($cmd);

        $pullFiles     = ['.docker/composer-cache', 'vendor', 'composer.json', 'composer.lock'];
        $expectedInput = new ArrayInput(['files' => $pullFiles]);
        $this->pullCommand->run($expectedInput, $this->output)->shouldBeCalled();

        $this->input->getArgument('package')->shouldBeCalled()->willReturn('my/package');
        $this->input->getOption('dev')->shouldBeCalled()->willReturn(false);

        $this->command->execute($this->input->reveal(), $this->output->reveal());
    }

    public function testComposerRequireInDevMode()
    {
        $this->useValidEnvironment();

        $this->output->getVerbosity()->willReturn(OutputInterface::OUTPUT_NORMAL);

        $cmd  = 'docker exec -u www-data -e COMPOSER_CACHE_DIR=.docker/composer-cache m2-php composer require';
        $cmd .= ' my/package --ansi --dev';

        $this->processTest($cmd);

        $expectedInput = new ArrayInput(['files' => ['.docker/composer-cache', 'vendor', 'composer.json', 'composer.lock']]);
        $this->pullCommand->run($expectedInput, $this->output)->shouldBeCalled();

        $this->input->getArgument('package')->shouldBeCalled()->willReturn('my/package');
        $this->input->getOption('dev')->shouldBeCalled()->willReturn(true);

        $this->command->execute($this->input->reveal(), $this->output->reveal());
    }

    /**
     * @param $verbosity
     * @param $expectedFlag
     * @dataProvider composerRequireVerbosityProvider
     */
    public function testComposerUpdatePassesVerbosityCorrectly($verbosity, $expectedFlag)
    {
        $this->useValidEnvironment();

        $this->output->getVerbosity()->willReturn($verbosity);

        $cmd  = 'docker exec -u www-data -e COMPOSER_CACHE_DIR=.docker/composer-cache m2-php composer require';
        $cmd .= ' my/package --ansi %s';

        $this->processTest(sprintf($cmd, $expectedFlag));

        $expectedInput = new ArrayInput(['files' => ['.docker/composer-cache', 'vendor', 'composer.json', 'composer.lock']]);
        $this->pullCommand->run($expectedInput, $this->output)->shouldBeCalled();

        $this->input->getArgument('package')->shouldBeCalled()->willReturn('my/package');
        $this->input->getOption('dev')->shouldBeCalled()->willReturn(false);

        $this->command->execute($this->input->reveal(), $this->output->reveal());
    }

    public function composerRequireVerbosityProvider()
    {
        return [
            [OutputInterface::VERBOSITY_VERBOSE, '-v'],
            [OutputInterface::VERBOSITY_VERY_VERBOSE, '-vv'],
            [OutputInterface::VERBOSITY_DEBUG, '-vvv'],
        ];
    }

    public function testExceptionThrownIfContainerNameNotFound()
    {
        $this->useInvalidEnvironment();
        $this->expectException(\RuntimeException::class);

        $this->command->execute($this->input->reveal(), $this->output->reveal());
    }
}
