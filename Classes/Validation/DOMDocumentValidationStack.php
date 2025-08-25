<?php

declare(strict_types=1);

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Validation;

/**
 * Implementation of AbstractDlfValidationStack for validating DOMDocument against the configured validators.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DOMDocumentValidationStack extends AbstractDlfValidationStack
{
    public function __construct(array $configuration)
    {
        parent::__construct(\DOMDocument::class);
        $this->addValidators($configuration);
    }
}
