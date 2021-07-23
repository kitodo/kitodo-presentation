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

namespace Kitodo\Dlf\ExpressionLanguage;

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provider class for additional "getDocumentType" function to the ExpressionLanguage.
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class DocumentTypeFunctionProvider implements ExpressionFunctionProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * This holds the extension's parameter prefix
     * @see \Kitodo\Dlf\Common\AbstractPlugin
     *
     * @var string
     * @access protected
     */
    protected $prefixId = 'tx_dlf';

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [
            $this->getDocumentTypeFunction(),
        ];
    }

    /**
     * Shortcut function to access field values
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    protected function getDocumentTypeFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'getDocumentType',
            function()
            {
                // Not implemented, we only use the evaluator
            },
            function($arguments, $cPid)
            {
                /** @var RequestWrapper $requestWrapper */
                $requestWrapper = $arguments['request'];
                $queryParams = $requestWrapper->getQueryParams();

                $type = 'undefined';

                // Load document with current plugin parameters.
                $doc = $this->loadDocument($queryParams[$this->prefixId]);
                if ($doc === null) {
                    return $type;
                }
                // Set PID for metadata definitions.
                $doc->cPid = $cPid;

                $metadata = $doc->getTitledata($cPid);
                if (!empty($metadata['type'][0])) {
                    // Calendar plugin does not support IIIF (yet). Abort for all newspaper related types.
                    if (
                        $doc instanceof IiifManifest
                        && array_search($metadata['type'][0], ['newspaper', 'ephemera', 'year', 'issue']) !== false
                    ) {
                        return $type;
                    }
                    $type = $metadata['type'][0];
                }
                return $type;
            });
    }

    /**
     * Loads the current document
     * @see \Kitodo\Dlf\Common\AbstractPlugin->loadDocument()
     *
     * @access protected
     *
     * @param array $piVars The current plugin variables containing a document identifier
     *
     * @return \Kitodo\Dlf\Common\Document Instance of the current document
     */
    protected function loadDocument(array $piVars)
    {
        // Check for required variable.
        if (!empty($piVars['id'])) {
            // Get instance of document.
            $doc = Document::getInstance($piVars['id']);
            if ($doc->ready) {
                return $doc;
            } else {
                $this->logger->warning('Failed to load document with UID ' . $piVars['id']);
            }
        } elseif (!empty($piVars['recordId'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Get UID of document with given record identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.uid AS uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($piVars['recordId'])),
                    Helper::whereExpression('tx_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                // Try to load document.
                return $this->loadDocument(['id' => $resArray['uid']]);
            } else {
                $this->logger->warning('Failed to load document with record ID "' . $piVars['recordId'] . '"');
            }
        }
    }
}
