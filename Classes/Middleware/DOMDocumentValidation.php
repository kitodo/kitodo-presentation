<?php

namespace Kitodo\Dlf\Middleware;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use DOMDocument;
use InvalidArgumentException;
use Kitodo\Dlf\Validation\DOMDocumentValidationStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
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
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $response = $handler->handle($request);
        // parameters are sent by POST --> use getParsedBody() instead of getQueryParams()
        $parameters = $request->getQueryParams();

        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/domDocumentValidation')) {
            return $response;
        }

        $urlParam = $parameters['url'];
        if (!isset($urlParam)) {
            throw new InvalidArgumentException('URL parameter is missing.', 1724334674);
        }

        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);

        if (!array_key_exists("domDocumentValidationValidators", $settings)) {
            $this->logger->error('DOMDocumentValidation is not configured correctly.');
            throw new InvalidArgumentException('DOMDocumentValidation is not configured correctly.', 1724335601);
        }

        $validation = GeneralUtility::makeInstance(DOMDocumentValidationStack::class, $settings['domDocumentValidationValidators']);

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

        $result = $validation->validate($document);
        return $this->getJsonResponse($result);
    }

    protected function getJsonResponse(Result $result): ResponseInterface
    {
        $resultContent = ["valid" => !$result->hasErrors()];

        foreach ($result->getErrors() as $error) {
            $resultContent["results"][$error->getTitle()][] = $error->getMessage();
        }

        /** @var ResponseFactory $responseFactory */
        $responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
        $response = $responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($resultContent));
        return $response;
    }
}
