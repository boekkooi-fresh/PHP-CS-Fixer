<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
final class FunctionReturnTypeSpaceFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $startParenthesisIndex = $tokens->getNextTokenOfKind($index, array('(', ';', array(T_CLOSE_TAG)));
            if (!$tokens[$startParenthesisIndex]->equals('(')) {
                continue;
            }

            $endParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startParenthesisIndex);

            $returnTypeIndex = $tokens->getNextTokenOfKind($endParenthesisIndex, array(':', ';', '{'));
            if ($tokens[$returnTypeIndex]->equals(':')) {
                $tokens->ensureWhitespaceAtIndex($returnTypeIndex + 1, 1, ' ');
                $tokens->ensureWhitespaceAtIndex($returnTypeIndex - 1, 1, ' ');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Add missing space between function definition and its return type.';
    }
}
