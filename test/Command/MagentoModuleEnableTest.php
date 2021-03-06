<?php

namespace Jh\WorkflowTest\Command;

use Jh\Workflow\Command\MagentoModuleEnable;
use Jh\Workflow\Command\Pull;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @author Michael Woodward <michael@wearejh.com>
 */
class MagentoModuleEnableTest extends AbstractTestCommand
{
    /**
     * @var ComposerUpdate
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

        $this->command     = new MagentoModuleEnable($this->processFactory->reveal());
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
        $description = 'Enable Magento module and updates the config.php file';

        static::assertEquals('module:enable', $this->command->getName());
        static::assertEquals($description, $this->command->getDescription());
    }

    public function testModuleEnableCommand()
    {
        $this->useValidEnvironment();
        $this->input->getArgument('module')->shouldBeCalled()->willReturn('Jh_Brands');

        $cmd = 'docker exec -u www-data m2-php bin/magento module:enable Jh_Brands --ansi';
        $this->processTest($cmd);

        $expectedInput = new ArrayInput(['files' => ['app/etc/config.php']]);
        $this->pullCommand->run($expectedInput, $this->output)->shouldBeCalled();

        $this->command->execute($this->input->reveal(), $this->output->reveal());
    }

    public function testExceptionThrownIfContainerNameNotFound()
    {
        $this->useInvalidEnvironment();
        $this->expectException(\RuntimeException::class);

        $this->command->execute($this->input->reveal(), $this->output->reveal());
    }
}
