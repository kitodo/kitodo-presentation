<?php

namespace Kitodo\Dlf\Controller;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\DocumentAnnotation;

/**
 * Controller class for plugin 'Annotation'.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class AnnotationController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $this->loadDocument();

        if (
            $this->document === null
            || $this->document->getDoc() === null
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            $documentAnnotation = DocumentAnnotation::getInstance($this->document);

            $this->view->assign('annotations', $documentAnnotation->getAnnotations());
            $this->view->assign('currentPage', $this->requestData['page']);
        }
    }
}
