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
use Exception;
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

    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const INTERNAL_SERVER_ERROR = 500;

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
            return $this->getJsonResponse('URL parameter is missing.', self::BAD_REQUEST);
        }

        $typeParam = $parameters['type'];
        if (!isset($typeParam)) {
            return $this->getJsonResponse('Type parameter is missing.', self::BAD_REQUEST);
        }
        // load dom document from url
        if (!GeneralUtility::isValidUrl($urlParam)) {
            return $this->getJsonResponse('Parameter "' . $urlParam . '" is not a valid URL.', self::NOT_FOUND);
        }

        $content = GeneralUtility::getUrl($urlParam);
        if ($content === false) {
            return $this->getJsonResponse('Unable to load content from the URL.', self::NOT_FOUND);
        }

        $document = new DOMDocument();
        try {
            if ($document->loadXML($content) === false) {
                return $this->getJsonResponse('Failed to load XML.', self::NOT_FOUND);
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
            return $this->getJsonResponse("Content from the URL is not valid XML.", self::BAD_REQUEST);
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
            return $this->getJsonResponse('An internal server error occurred.', self::INTERNAL_SERVER_ERROR);
        }

        if (!array_key_exists($typeParam, $settings["domDocumentValidation"])) {
            $this->logger->debug('Validation configuration type in type parameter "' . $typeParam . '" does not exist.');
            return $this->getJsonResponse('Type parameter does not exist.', self::NOT_FOUND);
        }

        $validationConfiguration = $this->removeDisabledValidators($parameters, $settings['domDocumentValidation'][$typeParam]);

        $validation = GeneralUtility::makeInstance(DOMDocumentValidationStack::class, $validationConfiguration);

        try {
            $result = $validation->validate($document);
        } catch (Exception $e) {
            return $this->getJsonResponse($e->getMessage(), self::BAD_REQUEST);
        }

        // validate and return json response
        $validationResults = $this->getValidationResults($validationConfiguration, $result);

        return $this->getJsonResponse($validationResults);
    }

    /**
     * Get the validation results.
     *
     * @access protected
     *
     * @param mixed[] $configurations The validation configurations.
     * @param Result|null $result The validation result.
     *
     * @return mixed[] The validation results.
     */
    protected function getValidationResults(array $configurations, ?Result $result): array
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
        return $validationResults;
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
     * @access protected
     *
     * @param mixed[] $parameters The parameters of the middleware.
     * @param mixed[] $validationConfiguration The validation configuration to remove from
     *
     * @return mixed[] The validator configuration without the disabled validators
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

    /**
     * Get the translation for a given key.
     *
     * @access protected
     *
     * @param string $key The localization key.
     * @param mixed[]|null $arguments The arguments for the translation.
     *
     * @return string The translated string.
     */
    protected function getTranslation(string $key, ?array $arguments = null): string
    {
        /** @var SiteLanguage $siteLanguage */
        $siteLanguage = $this->request->getAttribute('language') ?? $this->request->getAttribute('site')->getDefaultLanguage();

        /** @var LanguageServiceFactory $languageServiceFactory */
        $languageServiceFactory = GeneralUtility::makeInstance(
            LanguageServiceFactory::class,
        );

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

    /**
     * Get the JSON response.
     *
     * @access protected
     *
     * @param mixed $payload The data to add in the body.
     * @param int $statusCode The status code of the response.
     *
     * @return ResponseInterface The JSON response object.
     */
    public function getJsonResponse(mixed $payload, int $statusCode = 200): ResponseInterface
    {
        /** @var ResponseFactory $responseFactory */
        $responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
        $response = $responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($payload));
        return $response;
    }
}
