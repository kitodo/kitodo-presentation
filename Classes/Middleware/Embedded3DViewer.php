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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Middleware for embedding custom 3D Viewer implementation of the 'dlf' extension.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Embedded3DViewer implements MiddlewareInterface
{
    use LoggerAwareTrait;

    const VIEWER_FOLDER = "dlf_3d_viewers";
    const VIEWER_CONFIG_YML = "dlf-3d-viewer.yml";
    const EXT_KEY = "dlf";

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
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/embedded3DViewer')) {
            return $response;
        }

        if (empty($parameters['model'])) {
            return $this->warningResponse('Model url is missing.', $request);
        }

        $modelInfo = pathinfo($parameters['model']);
        $modelFormat = $modelInfo["extension"];
        if (empty($modelFormat)) {
            return $this->warningResponse('Model path "' . $parameters['model'] . '" has no extension format', $request);
        }

        if (empty($parameters['viewer'])) {
            // determine viewer from extension configuration
            $viewer = $this->getViewerByExtensionConfiguration($modelFormat);
        } else {
            $viewer = $parameters['viewer'];
        }

        // create response object
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $defaultStorage = $storageRepository->getDefaultStorage();

        if (!$defaultStorage->hasFolder(self::VIEWER_FOLDER)) {
            return $this->errorResponse('Required folder "' . self::VIEWER_FOLDER . '" was not found in the default storage "' . $defaultStorage->getName() . '"', $request);
        }

        $viewerModules = $defaultStorage->getFolder(self::VIEWER_FOLDER);
        if (!$viewerModules->hasFolder($viewer)) {
            return $this->errorResponse('Viewer folder "' . $viewer . '" was not found under the folder "' . self::VIEWER_FOLDER . '"', $request);
        }

        $viewerFolder = $viewerModules->getSubfolder($viewer);
        if (!$viewerFolder->hasFile(self::VIEWER_CONFIG_YML)) {
            return $this->errorResponse('Viewer folder "' . $viewer . '" does not contain a file named "' . self::VIEWER_CONFIG_YML . '"', $request);
        }

        /** @var YamlFileLoader $yamlFileLoader */
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $viewerConfigPath = $defaultStorage->getName() . "/" . self::VIEWER_FOLDER . "/" . $viewer . "/";
        $config = $yamlFileLoader->load($viewerConfigPath . self::VIEWER_CONFIG_YML)["viewer"];

        if (!isset($config["supportedModelFormats"]) || empty($config["supportedModelFormats"])) {
            return $this->errorResponse('Required key "supportedModelFormats" does not exist in the file "' . self::VIEWER_CONFIG_YML . '" of viewer "' . $viewer . '" or has no value', $request);
        }

        if (array_search(strtolower($modelFormat), array_map('strtolower', $config["supportedModelFormats"])) === false) {
            return $this->warningResponse('Viewer "' . $viewer . '" does not support the model format "' . $modelFormat . '"', $request);
        }

        $htmlFile = "index.html";
        if(isset($config["base"]) && !empty($config["base"]) ) {
            $htmlFile = $config["base"];
        }

        $viewerUrl = $viewerConfigPath;
        if (isset($config["url"]) && !empty($config["url"])) {
            $viewerUrl = rtrim($config["url"]);
        }

        $html = $viewerFolder->getFile($htmlFile)->getContents();
        $html = str_replace("{{viewerPath}}", $viewerUrl, $html);
        $html = str_replace("{{modelUrl}}", $parameters['model'], $html);
        $html = str_replace("{{modelPath}}", $modelInfo["dirname"], $html);
        $html = str_replace("{{modelResource}}", $modelInfo["basename"], $html);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Build the error response.
     *
     * Logs the given message as error and return internal error response.
     *
     * @param string $message
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Error\Http\InternalServerErrorException
     */
    public function errorResponse(string $message, ServerRequestInterface $request): ResponseInterface
    {
        /** @var ErrorController $errorController */
        $errorController = GeneralUtility::makeInstance(ErrorController::class);
        $this->logger->error($message);
        return $errorController->internalErrorAction($request, $message);
    }

    /**
     * Build the warning response.
     *
     * Logs the given message as warning and return page not found response.
     *
     * @param string $message
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     */
    public function warningResponse(string $message, ServerRequestInterface $request): ResponseInterface
    {
        /** @var ErrorController $errorController */
        $errorController = GeneralUtility::makeInstance(ErrorController::class);
        $this->logger->warning($message);
        return $errorController->pageNotFoundAction($request, $message);
    }

    /**
     * Determines the viewer based on the extension configuration and the given model format.
     *
     * @param $modelFormat string The model format
     * @return string The 3D viewer
     */
    private function getViewerByExtensionConfiguration($modelFormat): string
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXT_KEY, '3dviewer');
        $viewerModelFormatMappings = explode(";", $extConf['viewerModelFormatMapping']);
        foreach ($viewerModelFormatMappings as $viewerModelFormatMapping) {
            $explodedViewerModelMapping = explode(":", $viewerModelFormatMapping);
            if (count($explodedViewerModelMapping) == 2) {
                $viewer = trim($explodedViewerModelMapping[0]);
                $viewerModelFormats = array_map('trim', explode(",", $explodedViewerModelMapping[1]));
                if (in_array($modelFormat, $viewerModelFormats)) {
                    return $viewer;
                }
            }
        }

        return $extConf['defaultViewer'] ?? "";
    }
}
