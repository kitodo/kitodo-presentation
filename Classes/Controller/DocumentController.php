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
        $this->loadDocument();
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        $this->setPage();

        $metadataUrl = null;

        if (!empty($this->settings['targetPidMetadata'])) {
            $metadataUrl = $this->uriBuilder
                ->reset()
                ->setTargetPageUid((int) $this->settings['targetPidMetadata'])
                ->setCreateAbsoluteUri(true)
                ->setArguments([
                    'tx_dlf' => [
                        'id' => $this->requestData['id'],
                    ],
                ])
                ->build();
        }

        $filesConfiguration = $this->extConf['files'];
        $imageFileGroups = array_reverse(GeneralUtility::trimExplode(',', $filesConfiguration ['fileGrpImages']));
        $fulltextFileGroups = GeneralUtility::trimExplode(',', $filesConfiguration ['fileGrpFulltext']);

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
            'metadataUrl' => $metadataUrl,
            'fileGroups' => [
                'images' => $imageFileGroups,
                'fulltext' => $fulltextFileGroups,
                'download' => GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']),
            ],
            'document' => $this->document->getCurrentDocument()->toArray($this->uriBuilder, $config),
        ];

        $docConfiguration = '
            window.addEventListener("DOMContentLoaded", function() {
                const tx_dlf_loaded = ' . json_encode($tx_dlf_loaded) . ';
                window.dispatchEvent(new CustomEvent("tx-dlf-documentLoaded", {
                    detail: {
                        docController: new dlfController(tx_dlf_loaded)
                    }
                }));
            });';

        $this->view->assign('docConfiguration', $docConfiguration);
    }

    /**
     * Get URL template with the following placeholders:
     *
     * * `PAGE_NO` (for value of `tx_dlf[page]`)
     * * `DOUBLE_PAGE` (for value of `tx_dlf[double]`)
     * * `PAGE_GRID` (for value of `tx_dlf[pagegrid]`)
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

        // Generate two URLs that differ in tx_dlf[page], tx_dlf[double] and tx_dlf[highlight].
        // We don't know the order of these parameters, so use the values for matching.
        $first = $make(2, 1, 0);
        $second = $make(3, 0, 1);

        $lastIdx = 0;
        $result = '';
        for ($i = 0, $len = strlen($first); $i < $len; $i++) {
            if ($first[$i] === $second[$i]) {
                continue;
            }

            $result .= substr($first, $lastIdx, $i - $lastIdx);
            $lastIdx = $i + 1;

            if ($first[$i] === '2') {
                $placeholder = 'PAGE_NO';
            } else if ($first[$i] === '1') {
                $placeholder = 'DOUBLE_PAGE';
            } else {
                $placeholder = 'PAGE_GRID';
            }

            $result .= $placeholder;
        }
        $result .= substr($first, $lastIdx);

        return $result;
    }

}