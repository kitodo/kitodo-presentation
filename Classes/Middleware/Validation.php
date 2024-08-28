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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Middleware for embedding custom 3D Viewer implementation of the 'dlf' extension.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Validation implements MiddlewareInterface
{
    use LoggerAwareTrait;

    const SETTINGS_KEY_VALIDATION = "validation";

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
        $response = $handler->handle($request);
        // parameters are sent by POST --> use getParsedBody() instead of getQueryParams()
        $parameters = $request->getQueryParams();

        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/validation')) {
            return $response;
        }

        $validationParam = $parameters['validation'];
        $urlParam = $parameters['url'];
        if (!isset($validationParam) || $urlParam) {
            throw new InvalidArgumentException('No valid parameter passed.', 1724334674);
        }

        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);

        if (!array_key_exists(self::SETTINGS_KEY_VALIDATION, $settings) ||
            !array_key_exists($validationParam, $settings[self::SETTINGS_KEY_VALIDATION])) {
            $this->logger->error('Validation "' . $validationParam . '" is not configured.');
            throw new InvalidArgumentException('Validation is not configured.', 1724335328);
        }

        if (!array_key_exists("className", $settings[self::SETTINGS_KEY_VALIDATION][$validationParam]) ||
            !array_key_exists("validators", $settings[self::SETTINGS_KEY_VALIDATION][$validationParam])) {
            $this->logger->error('Validation "' . $validationParam . '" is not configured correctly.');
            throw new InvalidArgumentException('Validation is not configured correctly.', 1724335601);
        }

        $validationClassName = $settings[self::SETTINGS_KEY_VALIDATION][$validationParam]["className"];
        if (!class_exists($validationClassName)) {
            $this->logger->error('Unable to load class "' . $validationClassName . '".');
            throw new InvalidArgumentException('Unable to load validation class.', 1724336440);
        }

        $validation = GeneralUtility::makeInstance($validationClassName, $settings[self::SETTINGS_KEY_VALIDATION][$validationParam]["validators"]);

        if (GeneralUtility::isValidUrl($urlParam)) {
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

        $errorMessages = [];
        foreach ($result->getErrors() as $error) {
            $errorMessages[$error->getTitle()][] = $error->getMessage();
        }

        /** @var ResponseFactory $responseFactory */
        $responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
        $response = $responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($errorMessages));
        return $response;
    }
}
