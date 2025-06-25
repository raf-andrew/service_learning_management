<?php

namespace Tests\Unit\Commands;

use Tests\Unit\UnitTestCase;
use App\Console\Commands\InfrastructureManagerCommand;
use Illuminate\Console\Command;

class InfrastructureManagerCommandMetadataTest extends UnitTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new InfrastructureManagerCommand();
    }

    public function test_command_has_correct_signature()
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($this->command);
        
        $expectedSignature = 'infrastructure:manage 
        {action : Action to perform (status|start|stop|restart|cleanup)}
        {--service= : Specific service to manage}
        {--force : Force the action without confirmation}';
        
        $this->assertEquals($expectedSignature, $signature);
    }

    public function test_command_has_description()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertIsString($this->command->getDescription());
    }

    public function test_command_extends_base_command()
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }
} 