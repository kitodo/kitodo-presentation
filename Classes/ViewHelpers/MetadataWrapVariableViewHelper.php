<?php
/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * View helper to parse TypoScript that is used to declare wrapping of metadata
 * fields and stores the result into a Fluid variable.
 *
 * The TypoScript should be passed as child and may contain stdWrap information
 * for:
 * - `key`: Used to wrap the metadata label
 * - `value`: Used to wrap the metadata value
 * - `all`: Used to wrap the full metadata line (after wrapping key and value)
 *
 * The name of the injected variable is passed as parameter.
 *
 * Example usage:
 *
 * ::
 *
 *     {typoscript -> kitodo:metadataWrapVariable(name: 'wrapInfo')}
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class MetadataWrapVariableViewHelper extends AbstractViewHelper
{
    /**
     * @access public
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('name', 'string', 'Name of variable to create', true);
    }

    /**
     * @access public
     *
     * @static
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return void
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): void
    {
        $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $parser->parse($renderChildrenClosure());
        $wrap = [
            'key' => $parser->setup['key.'] ?? [],
            'value' => $parser->setup['value.'] ?? [],
            'all' => $parser->setup['all.'] ?? [],
        ];
        $renderingContext->getVariableProvider()->add($arguments['name'], $wrap);
    }
}
