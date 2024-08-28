<?php

declare(strict_types=1);

namespace Kitodo\Dlf\Validation;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

trait LibXmlTrait
{
    /**
     * Add the errors from the libxml error buffer as validation error.
     *
     * To enable user error handling, you need to use libxml_use_internal_errors(true) beforehand.
     *
     * @return void
     */
    public function addErrorsOfBuffer(): void
    {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $this->addError($error->message, $error->code);
        }
        libxml_clear_errors();
    }

    public function enableErrorBuffer(): void
    {
        libxml_use_internal_errors(true);
    }

    public function disableErrorBuffer(): void
    {
        libxml_use_internal_errors(false);
    }
}
