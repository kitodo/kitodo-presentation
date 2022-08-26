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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Provide document JSON for client side access
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class DocumentController extends AbstractController
{
   /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument($this->requestData);
        if (
            $this->document === null
            || $this->document->getDoc() === null
            || $this->document->getDoc()->numPages < 1
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if ((int) $this->requestData['page'] > 0 || empty($this->requestData['page'])) {
                $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
            $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
        }

        $doc = $this->document->getDoc();

        $imageFileGroups = array_reverse(GeneralUtility::trimExplode(',', $this->extConf['fileGrpImages']));
        $fulltextFileGroups = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        $config = [
            'forceAbsoluteUrl' => !empty($this->settings['forceAbsoluteUrl']),
            'proxyFileGroups' => !empty($this->settings['useInternalProxy'])
                ? array_merge($imageFileGroups, $fulltextFileGroups)
                : [],
        ];
        $tx_dlf_loaded = [
            'state' => [
                'documentId' => $this->requestData['id'],
                'page' => $this->requestData['page'],
                'simultaneousPages' => (int) $this->requestData['double'] + 1,
            ],
            'urlTemplate' => $this->getUrlTemplate(),
            'fileGroups' => [
                'images' => $imageFileGroups,
                'fulltext' => $fulltextFileGroups,
                'download' => GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']),
            ],
            'document' => $this->document->getDoc()->toArray($this->uriBuilder, $config),
        ];

        // TODO: Rethink global tx_dlf_loaded
        $docConfiguration = '
            tx_dlf_loaded = ' . json_encode($tx_dlf_loaded) . ';

            tx_dlf_loaded.getVisiblePages = function (firstPageNo = tx_dlf_loaded.state.page) {
                const result = [];
                for (let i = 0; i < tx_dlf_loaded.state.simultaneousPages; i++) {
                    const pageNo = firstPageNo + i;
                    const pageObj = tx_dlf_loaded.document.pages[pageNo - 1];
                    if (pageObj !== undefined) {
                        result.push({ pageNo, pageObj });
                    }
                }
                return result;
            };

            tx_dlf_loaded.makePageUrl = function (pageNo, pageGrid = false) {
                const doublePage = tx_dlf_loaded.state.simultaneousPages >= 2 ? 1 : 0;
                return tx_dlf_loaded.urlTemplate
                    .replace(/DOUBLE_PAGE/, doublePage)
                    .replace(/PAGE_NO/, pageNo)
                    .replace(/PAGE_GRID/, pageGrid ? "1" : "0");
            };

            window.addEventListener("DOMContentLoaded", function() {
                new dlfController();
            });';

        $this->view->assign('docConfiguration', $docConfiguration);
    }

    /**
     * Get URL template with the following placeholders:
     *
     * * `PAGE_NO` (for value of `tx_dlf[page]`)
     * * `DOUBLE_PAGE` (for value of `tx_dlf[double]`)
     *
     * @return string
     */
    protected function getUrlTemplate()
    {
        // Should work for route enhancers like this:
        //
        //   routeEnhancers:
        //     KitodoWorkview:
        //     type: Plugin
        //     namespace: tx_dlf
        //     routePath: '/{page}/{double}'
        //     requirements:
        //       page: \d+
        //       double: 0|1

        $make = function ($page, $double, $pagegrid) {
            $result = $this->uriBuilder->reset()
                ->setTargetPageUid($GLOBALS['TSFE']->id)
                ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? true : false)
                ->setArguments([
                    'tx_dlf' => array_merge($this->requestData, [
                        'page' => $page,
                        'double' => $double,
                        'pagegrid' => $pagegrid
                    ]),
                ])
                ->build();

            $cHashIdx = strpos($result, '&cHash=');
            if ($cHashIdx !== false) {
                $result = substr($result, 0, $cHashIdx);
            }

            return $result;
        };

        // Generate two URLs that differ only in tx_dlf[page] and tx_dlf[double].
        // We don't know the order of page and double parameters, so use the values for matching.
        $a = $make(2, 1, 0);
        $b = $make(3, 0, 1);

        $lastIdx = 0;
        $result = '';
        for ($i = 0, $len = strlen($a); $i < $len; $i++) {
            if ($a[$i] === $b[$i]) {
                continue;
            }

            $result .= substr($a, $lastIdx, $i - $lastIdx);
            $lastIdx = $i + 1;

            if ($a[$i] === '2') {
                $placeholder = 'PAGE_NO';
            } else if ($a[$i] === '1') {
                $placeholder = 'DOUBLE_PAGE';
            } else {
                $placeholder = 'PAGE_GRID';
            }

            $result .= $placeholder;
        }
        $result .= substr($a, $lastIdx);

        return $result;
    }

}
