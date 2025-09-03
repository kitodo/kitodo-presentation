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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Helper class that allows to access TypoScript from backend.
 */
class TypoScriptHelper
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SysTemplateTreeBuilder $treeBuilder,
        private readonly LossyTokenizer $tokenizer,
        private readonly IncludeTreeTraverser $includeTreeTraverser,
        private readonly ConditionVerdictAwareIncludeTreeTraverser $includeConditionVerdictAware,
        private readonly SysTemplateRepository $sysTemplateRepository,
    ) {
        // empty
    }

    /**
     * Extract typoScript configuration from site root for Typo3 v13
     *
     * @access public
     *
     * @param int $pid
     *
     * @return array
     */
    public function getTyposcriptConfigV13(int $pid): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pid);

        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pid)->get();
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootline($rootLine);

        $frontendTypoScriptFactory = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory::class,
            $this->container,
            $this->eventDispatcher,
            $this->treeBuilder,
            $this->tokenizer,
            $this->includeTreeTraverser,
            $this->includeConditionVerdictAware,
        );

        $frontendTypoScript = $frontendTypoScriptFactory->createSettingsAndSetupConditions(
            $site,
            $sysTemplateRows,
            [],
            null,
        );

        $ts = $frontendTypoScriptFactory->createSetupConfigOrFullSetup(
            true,
            $frontendTypoScript,
            $site,
            $sysTemplateRows,
            [],
            '0',
            null,
            null,
        );

        return $ts->getSetupArray();
    }

    /**
     * Extract typoScript configuration from site root for Typo3 v12
     *
     * @access public
     *
     * @param int $pid page id
     *
     * @return array typoscript configuration of site root
     */
    public static function getTyposcriptConfigV12(int $pid): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pid);
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pid)->get();

        $typoScriptFrontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getDefaultLanguage(),
            GeneralUtility::makeInstance(PageArguments::class, $pid, '', []),
            GeneralUtility::makeInstance(FrontendUserAuthentication::class),
        );

        $typoScriptFrontendController->rootLine = $rootLine;
        $request = new ServerRequest();
        $request = $typoScriptFrontendController->getFromCache($request);
        $typoScriptFrontendController->releaseLocks();

        return $request->getAttribute('frontend.typoscript')->getSetupArray();
    }

    /**
     * Get TypoScript configuration from site root
     *
     * Note: When upgrading Typo3, maybe use site settings to store storagePid, see:
     * https://docs.typo3.org/permalink/t3coreapi:sitehandling-settings
     *
     * @access public
     *
     * @param int $pid page id
     *
     * @return array typoscript configuration of site root
     */
    public static function getTyposcriptConfig(int $pid): array
    {
        $typo3Version = (new Typo3Version())->getMajorVersion();
        if ($typo3Version === 13) {
            return GeneralUtility::makeInstance(TypoScriptHelper::class)->getTyposcriptConfigV13($pid);
        }
        return TypoScriptHelper::getTyposcriptConfigV12($pid);
    }
}
