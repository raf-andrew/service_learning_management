<?php

namespace App\Sniffing\ServiceLearningStandard\Sniffs;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

abstract class AbstractSniff implements Sniff
{
    protected function addViolation(File $phpcsFile, $stackPtr, $message, $code, $severity = 0)
    {
        $phpcsFile->addError($message, $stackPtr, $code, [], $severity);
    }

    protected function addWarning(File $phpcsFile, $stackPtr, $message, $code, $severity = 0)
    {
        $phpcsFile->addWarning($message, $stackPtr, $code, [], $severity);
    }

    protected function getMethodParameters(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $params = [];
        
        if (isset($tokens[$stackPtr]['parenthesis_opener'])) {
            $opener = $tokens[$stackPtr]['parenthesis_opener'];
            $closer = $tokens[$stackPtr]['parenthesis_closer'];
            
            for ($i = $opener + 1; $i < $closer; $i++) {
                if ($tokens[$i]['code'] === T_VARIABLE) {
                    $params[] = $tokens[$i]['content'];
                }
            }
        }
        
        return $params;
    }

    protected function hasDocBlock(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $docBlockPtr = $phpcsFile->findPrevious(T_DOC_COMMENT, $stackPtr);
        
        return $docBlockPtr !== false;
    }

    protected function getDocBlock(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $docBlockPtr = $phpcsFile->findPrevious(T_DOC_COMMENT, $stackPtr);
        
        if ($docBlockPtr === false) {
            return null;
        }
        
        return $tokens[$docBlockPtr]['content'];
    }
} 