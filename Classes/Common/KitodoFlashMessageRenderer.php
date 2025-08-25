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

namespace Kitodo\Dlf\Common;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * A class representing a bootstrap flash messages.
 * This class renders flash messages as markup, based on the
 * bootstrap HTML/CSS framework. It is used in backend context.
 * The created output contains all classes which are required for
 * the TYPO3 backend. Any kind of message contains also a nice icon.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class KitodoFlashMessageRenderer implements FlashMessageRendererInterface
{
    /**
     * Render method
     * 
     * @access public
     *
     * @param FlashMessage[] $flashMessages
     *
     * @return string Representation of the flash message
     */
    public function render(array $flashMessages): string
    {
        return $this->getMessageAsMarkup($flashMessages);
    }

    /**
     * Gets the message severity class name
     *
     * @access public
     *
     * @param FlashMessage $flashMessage
     *
     * @return string The message severity class name
     */
    protected function getClass(FlashMessage $flashMessage): string
    {
        return 'alert-' . $flashMessage->getSeverity()->getCssClass();
    }

    /**
     * Gets the message severity icon name
     *
     * @access public
     *
     * @param FlashMessage $flashMessage
     *
     * @return string The message severity icon name
     */
    protected function getIconName(FlashMessage $flashMessage): string
    {
        return $flashMessage->getSeverity()->getIconIdentifier();
    }

    /**
     * Gets the message rendered as clean and secure markup
     *
     * @access public
     *
     * @param FlashMessage[] $flashMessages
     *
     * @return string
     */
    protected function getMessageAsMarkup(array $flashMessages): string
    {
        // \TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer::render() uses htmlspecialchars()
        // on all messages, but we have messages with HTML tags. Therefore we copy the official
        // implementation and remove the htmlspecialchars() call on the message body.
        $markup = [];
        $markup[] = '<div class="typo3-messages">';
        foreach ($flashMessages as $flashMessage) {
            $messageTitle = $flashMessage->getTitle();
            $markup[] = '<div class="alert ' . htmlspecialchars($this->getClass($flashMessage)) . '">';
            $markup[] = '  <div class="alert-inner">';
            $markup[] = '    <div class="alert-content">';
            if ($messageTitle !== '') {
                $markup[] = '      <div class="alert-title">' . htmlspecialchars($messageTitle) . '</div>';
            }
            $markup[] = '      <p class="alert-message">' . $flashMessage->getMessage() . '</p>';
            $markup[] = '    </div>';
            $markup[] = '  </div>';
            $markup[] = '</div>';
        }
        $markup[] = '</div>';
        return implode('', $markup);
    }
}
