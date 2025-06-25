<?php

namespace App\Sniffing\ServiceLearningStandard\Sniffs\Classes;

use App\Sniffing\ServiceLearningStandard\Sniffs\AbstractSniff;
use PHP_CodeSniffer\Files\File;

class RepositoryPatternSniff extends AbstractSniff
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
        
        // Check if class name ends with 'Repository'
        if (!preg_match('/Repository$/', $className)) {
            return; // Not a repository class
        }

        // Check for required methods
        $requiredMethods = [
            'find',
            'findAll',
            'create',
            'update',
            'delete'
        ];

        $methods = $this->getClassMethods($phpcsFile, $stackPtr);
        $methodNames = [];
        
        foreach ($methods as $methodPtr) {
            $methodName = $phpcsFile->findNext(T_STRING, $methodPtr);
            if ($methodName !== false) {
                $methodNames[] = $tokens[$methodName]['content'];
            }
        }

        foreach ($requiredMethods as $requiredMethod) {
            if (!in_array($requiredMethod, $methodNames)) {
                $this->addViolation(
                    $phpcsFile,
                    $stackPtr,
                    "Repository class must implement '{$requiredMethod}' method",
                    'RepositoryMethodRequired'
                );
            }
        }

        // Check for model property
        $hasModelProperty = false;
        for ($i = $stackPtr; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if ($tokens[$i]['code'] === T_VARIABLE && $tokens[$i]['content'] === '$model') {
                $hasModelProperty = true;
                break;
            }
        }

        if (!$hasModelProperty) {
            $this->addViolation(
                $phpcsFile,
                $stackPtr,
                'Repository class must have a $model property',
                'RepositoryModelPropertyRequired'
            );
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