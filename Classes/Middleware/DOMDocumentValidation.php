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

namespace Kitodo\Dlf\Middleware;

use DOMDocument;
use InvalidArgumentException;
use Kitodo\Dlf\Validation\DOMDocumentValidationStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\CMS\Extbase\Error\Result;

/**
 * Middleware for validation of DOMDocuments.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class DOMDocumentValidation implements MiddlewareInterface
{
    use LoggerAwareTrait;

    private ServerRequestInterface $request;

    /**
     * The main method of the middleware.
     *
     * @access public
     *
     * @param ServerRequestInterface $request for processing
     * @param RequestHandlerInterface $handler for processing
     *
     * @throws InvalidArgumentException
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->request = $request;
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $response = $handler->handle($request);
        $parameters = $request->getQueryParams();

        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/domDocumentValidation')) {
            return $response;
        }

        // check required parameters
        $urlParam = $parameters['url'];
        if (!isset($urlParam)) {
            throw new InvalidArgumentException('URL parameter is missing.', 1724334674);
        }

        $typeParam = $parameters['type'];
        if (!isset($typeParam)) {
            throw new InvalidArgumentException('Type parameter is missing.', 1744373423);
        }

        // load dom document from url
        if (!GeneralUtility::isValidUrl($urlParam)) {
            $this->logger->debug('Parameter "' . $urlParam . '" is not a valid url.');
            throw new InvalidArgumentException('Value of url parameter is not a valid url.', 1724852611);
        }

        $content = GeneralUtility::getUrl($urlParam);
        if ($content === false) {
            $this->logger->debug('Error while loading content of "' . $urlParam . '"');
            throw new InvalidArgumentException('Error while loading content of url.', 1724420640);
        }

        $document = new DOMDocument();
        if ($document->loadXML($content) === false) {
            $this->logger->debug('Error converting content of "' . $urlParam . '" to xml.');
            throw new InvalidArgumentException('Error converting content to xml.', 1724420648);
        }

        // retrieve validation configuration from plugin.tx_dlf typoscript
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $typoScript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $settings = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScript['plugin.']['tx_dlf.']['settings.']);

        if (!array_key_exists("domDocumentValidation", $settings)) {
            $this->logger->error('DOMDocumentValidation is not configured.');
            throw new InvalidArgumentException('DOMDocumentValidation is not correctly.', 1724335601);
        }

        if (!array_key_exists($typeParam, $settings["domDocumentValidation"])) {
            $this->logger->error('Validation configuration type in type parameter "' . $typeParam . '" does not exist.');
            throw new InvalidArgumentException('Validation configuration type does not exist.', 1744373532);
        }

        $validationConfiguration = $this->removeDisabledValidators($parameters, $settings['domDocumentValidation'][$typeParam]);

        $validation = GeneralUtility::makeInstance(DOMDocumentValidationStack::class, $validationConfiguration);
        // validate and return json response
        return $this->getJsonResponse($validationConfiguration, $validation->validate($document));
    }

    protected function getJsonResponse(array $configurations, ?Result $result): ResponseInterface
    {
        $validationResults = [];
        $index = 0;

        if ($result != null) {
            foreach ($configurations as $configuration) {
                $validationResult = [];
                $validationResult['validator']['title'] = $this->getTranslation($configuration['title']);
                if (is_array($configuration['description'])) {
                    $validationResult['validator']['description'] = $this->getTranslation($configuration['description']['key'], $configuration['description']['arguments']);
                } else {
                    $validationResult['validator']['description'] = $this->getTranslation($configuration['description']);
                }
                $stackResult = $result->forProperty((string) $index);
                if ($stackResult->hasErrors()) {
                    $validationResult['results']['errors'] = array_map($this->getMessageText(), $stackResult->getErrors());
                }
                if ($stackResult->hasWarnings()) {
                    $validationResult['results']['warnings'] = array_map($this->getMessageText(), $stackResult->getWarnings());
                }
                if ($stackResult->hasNotices()) {
                    $validationResult['results']['notices'] = array_map($this->getMessageText(), $stackResult->getNotices());
                }
                $validationResults[] = $validationResult;
                $index++;
            }
        }

        /** @var ResponseFactory $responseFactory */
        $responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
        $response = $responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($validationResults));
        return $response;
    }

    /**
     * Get the message.
     *
     * @return \Closure
     */
    protected function getMessageText(): \Closure
    {
        return function (Message $message): string {
            return $message->getMessage();
        };
    }

    /**
     * Removes validators marked as disabled from the configuration.
     *
     * If a validator in the configuration has the disabled flag set to true and its key does not exist in the enabledValidators parameter, it will be removed.
     *
     * @param array $parameters The parameters of the middleware.
     * @param array $validationConfiguration The validation configuration to remove from
     * @return array The validator configuration without the disabled validators
     */
    protected function removeDisabledValidators(array $parameters, array $validationConfiguration): array
    {
        $enableValidators = [];
        if (array_key_exists("enableValidators", $parameters)) {
            $enableValidators = explode(",", $parameters['enableValidators']);
        }
        foreach ($validationConfiguration as $key => $value) {
            if (isset($value['disabled']) && $value['disabled'] === "true" && !in_array($key, $enableValidators)) {
                unset($validationConfiguration[$key]);
            }
        }
        return $validationConfiguration;
    }

    protected function getTranslation(string $key, ?array $arguments = null): string
    {
        /** @var SiteLanguage $siteLanguage */
        $siteLanguage = $this->request->getAttribute('language') ?? $this->request->getAttribute('site')->getDefaultLanguage();

        /** @var LanguageServiceFactory $languageServiceFactory */
        $languageServiceFactory = GeneralUtility::makeInstance(
            LanguageServiceFactory::class,
        );

        /** @var LanguageService $languageService */
        $languageService = $languageServiceFactory
            ->createFromSiteLanguage($siteLanguage);

        if (isset($arguments) && count($arguments) > 0) {
            return vsprintf(
                $languageService->sL($key),
                array_map(fn($value) => str_starts_with($value, 'EXT:') ? PathUtility::getPublicResourceWebPath($value) : $value, $arguments)
            );
        }
        return $languageService->sL($key);
    }
}
