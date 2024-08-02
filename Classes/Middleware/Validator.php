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

use Kitodo\Dlf\Validation\XmlValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware for embedding custom 3D Viewer implementation of the 'dlf' extension.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Validator implements MiddlewareInterface
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
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        // parameters are sent by POST --> use getParsedBody() instead of getQueryParams()
        $parameters = $request->getQueryParams();

        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/validator')) {
            return $response;
        }

        /** @var XmlValidator $xmlValidator */
        $xmlValidator = GeneralUtility::makeInstance(XmlValidator::class);
        $data = $xmlValidator->validate($parameters['url']);

        $errorMessages = [];

        foreach ($data->getErrors() as &$error) {
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
