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

namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;

/**
 * Make sure there is one blank line above and below a method.
 *
 * The exception is when a method is the first or last item in a 'classy'.
 *
 * @author SpacePossum
 */
final class PropertySeparationFixer extends AbstractFixer
{
    /**
     * @var string[]
     */
    private $configuration;

    /**
     * Default target/configuration.
     *
     * @var string[]
     */
    private static $defaultConfiguration = array(
        'property',
        'const',
    );

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        if (null === $configuration) {
            $this->configuration = self::$defaultConfiguration;

            return;
        }

        foreach ($configuration as $name) {
            if (!in_array($name, self::$defaultConfiguration, true)) {
                throw new InvalidFixerConfigurationException(
                    $this->getName(),
                    sprintf('Unknown configuration option "%s".', $name)
                );
            }
        }

        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function fix(SplFileInfo $file, Tokens $tokens)
    {
        $analyzer = new TokensAnalyzer($tokens);
        $elements = array_reverse($analyzer->getClassyElements(), true);

        foreach ($elements as $index => $element) {
            if (!in_array($element['type'], $this->configuration, true)) {
                continue; // not in configuration
            }

            $this->fixElement($tokens, $index);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // Must run before BracesFixer and NoTabIndentationFixer fixers because this fixer
        // might add line breaks to the code without indenting.
        return 55;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Properties must be separated with one blank line.';
    }

    private function fixElement(Tokens $tokens, int $index)
    {
        $propertyEnd = $tokens->getNextTokenOfKind($index, array(';'));
        $nextNotWhite = $tokens->getNextNonWhitespace($propertyEnd);

        $this->correctLineBreaks($tokens, $propertyEnd, $nextNotWhite);
    }

    /**
     * @param Tokens $tokens
     * @param int    $startIndex
     * @param int    $endIndex
     * @param int    $reqLineCount
     */
    private function correctLineBreaks(Tokens $tokens, $startIndex, $endIndex, $reqLineCount = 2)
    {
        ++$startIndex;
        $numbOfWhiteTokens = $endIndex - $startIndex;
        if (0 === $numbOfWhiteTokens) {
            $tokens->insertAt($startIndex, new Token(array(T_WHITESPACE, str_repeat("\n", $reqLineCount))));

            return;
        }

        $lineBreakCount = $this->getLineBreakCount($tokens, $startIndex, $endIndex);
        if ($reqLineCount === $lineBreakCount) {
            return;
        }

        if ($lineBreakCount < $reqLineCount) {
            $tokens[$startIndex]->setContent(
                str_repeat("\n", $reqLineCount - $lineBreakCount).$tokens[$startIndex]->getContent()
            );

            return;
        }

        // $lineCount = > $reqLineCount : check the one Token case first since this one will be true most of the time
        if (1 === $numbOfWhiteTokens) {
            $tokens[$startIndex]->setContent(
                preg_replace('/[\r\n]/', '', $tokens[$startIndex]->getContent(), $lineBreakCount - $reqLineCount)
            );

            return;
        }

        // $numbOfWhiteTokens = > 1
        $toReplaceCount = $lineBreakCount - $reqLineCount;
        for ($i = $startIndex; $i < $endIndex && $toReplaceCount > 0; ++$i) {
            $tokenLineCount = substr_count($tokens[$i]->getContent(), "\n");
            if ($tokenLineCount > 0) {
                $tokens[$i]->setContent(
                    preg_replace('/[\r\n]/', '', $tokens[$i]->getContent(), min($toReplaceCount, $tokenLineCount))
                );
                $toReplaceCount -= $tokenLineCount;
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $whiteStart
     * @param int    $whiteEnd
     *
     * @return int
     */
    private function getLineBreakCount(Tokens $tokens, $whiteStart, $whiteEnd)
    {
        $lineCount = 0;
        for ($i = $whiteStart; $i < $whiteEnd; ++$i) {
            $lineCount += substr_count($tokens[$i]->getContent(), "\n");
        }

        return $lineCount;
    }
}
