<?php

namespace Tests\Sniffing;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;
use App\Sniffing\ServiceLearningStandard\Sniffs\Classes\ServiceClassSniff;

class ServiceClassSniffTest extends AbstractSniffUnitTest
{
    protected function getSniffCode()
    {
        return 'ServiceLearning.Classes.ServiceClass';
    }

    public function getErrorList()
    {
        return [
            3 => 1,  // ServiceNameConvention
            8 => 1,  // ServiceConstructorRequired
            13 => 1, // ServiceInterfaceRequired
            18 => 1, // ServiceMethodDocumentationRequired
        ];
    }

    public function getWarningList()
    {
        return [];
    }

    protected function getTestFiles()
    {
        return [
            __DIR__ . '/Fixtures/InvalidService.php',
            __DIR__ . '/Fixtures/ValidService.php',
        ];
    }
} 