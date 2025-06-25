<?php

namespace App\Sniffing\ServiceLearningStandard\Sniffs\Classes;

use App\Sniffing\ServiceLearningStandard\Sniffs\AbstractSniff;
use PHP_CodeSniffer\Files\File;

class ServiceClassSniff extends AbstractSniff
{
    public function register()
    {
        return [T_CLASS];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $className = $phpcsFile->findNext(T_STRING, $stackPtr);
        
        if ($className === false) {
            return;
        }

        $className = $tokens[$className]['content'];
        
        // Check if class name ends with 'Service'
        if (!preg_match('/Service$/', $className)) {
            $this->addViolation(
                $phpcsFile,
                $stackPtr,
                "Service class name must end with 'Service'",
                'ServiceNameConvention'
            );
        }

        // Check for constructor
        $constructorPtr = $phpcsFile->findNext(T_FUNCTION, $stackPtr);
        if ($constructorPtr === false || $tokens[$constructorPtr + 2]['content'] !== '__construct') {
            $this->addViolation(
                $phpcsFile,
                $stackPtr,
                'Service class must have a constructor',
                'ServiceConstructorRequired'
            );
        }

        // Check for interface implementation
        $implementsPtr = $phpcsFile->findNext(T_IMPLEMENTS, $stackPtr);
        if ($implementsPtr === false) {
            $this->addViolation(
                $phpcsFile,
                $stackPtr,
                'Service class must implement an interface',
                'ServiceInterfaceRequired'
            );
        }

        // Check for proper method documentation
        $methods = $this->getClassMethods($phpcsFile, $stackPtr);
        foreach ($methods as $methodPtr) {
            if (!$this->hasDocBlock($phpcsFile, $methodPtr)) {
                $this->addViolation(
                    $phpcsFile,
                    $methodPtr,
                    'Service method must have proper documentation',
                    'ServiceMethodDocumentationRequired'
                );
            }
        }
    }

    protected function getClassMethods(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $methods = [];
        
        if (!isset($tokens[$stackPtr]['scope_closer'])) {
            return $methods;
        }

        $endPtr = $tokens[$stackPtr]['scope_closer'];
        for ($i = $stackPtr; $i < $endPtr; $i++) {
            if ($tokens[$i]['code'] === T_FUNCTION) {
                $methods[] = $i;
            }
        }

        return $methods;
    }
} 