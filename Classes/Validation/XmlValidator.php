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

use LibXmlTrait;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class XmlValidator extends AbstractValidator
{
    use LibXmlTrait;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    protected function isValid($value)
    {
        $this->enableErrorBuffer();
        simplexml_load_file($value);
        $this->addErrorsOfBuffer();
        $this->disableErrorBuffer();
    }
}
