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

namespace Kitodo\Dlf\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * Plugin 'DocumentOpener' for the 'dlf' extension.
 *
 * @package TYPO3
 * @subpackage dlf
 */
class DocumentOpenerController extends AbstractController
{
    /**
     * Render a form that opens a document on the configured viewer page.
     */
    public function mainAction(): ResponseInterface
    {
        $this->view->assign('uniqueId', $this->uniqueId);
        $this->view->assign(
            'targetPid',
            !empty($this->settings['targetPid'])
                ? (int) $this->settings['targetPid']
                : (int) ($this->settings['defaultTargetPid'] ?? 0)
        );
        return $this->htmlResponse();
    }
}
