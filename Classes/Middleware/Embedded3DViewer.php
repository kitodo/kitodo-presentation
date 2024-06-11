<?php

namespace Kitodo\Dlf\Middleware;

/*
 *  Copyright notice
 *
 *  (c) 2014 Alexander Bigga <typo3@slub-dresden.de>
 *  (c) 2023 Beatrycze Volk <typo3@slub-dresden.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Error\Http\InternalServerErrorException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Plugin 'DFG-Viewer: SRU Client Middleware for the 'dfgviewer' extension.
 *
 * @package TYPO3
 * @subpackage tx_dfgviewer
 * @access public
 */
class Embedded3DViewer implements MiddlewareInterface, LoggerAwareInterface
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

        $modelInfo = pathinfo($parameters['model']);
        $modelFormat = $modelInfo["extension"];
        if (!isset($modelFormat) || empty($modelFormat)) {
            return $this->warningResponse('Model path "' . $parameters['model'] . '" has no extension format', $request);
        }

        $viewer = $this->getViewerByModelFormat($modelFormat);

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
     * @param $viewerModelFormatMapping
     * @return string
     */
    private function getViewerByModelFormat($modelFormat): string
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXT_KEY, '3dviewer');
        $viewerModelFormatMappings = explode(";", $extConf['viewerModelFormatMapping']);
        foreach ($viewerModelFormatMappings as $viewerModelFormatMapping) {
            $explodedViewerModelMapping = explode(":", $viewerModelFormatMapping);
            if(sizeof($explodedViewerModelMapping) == 2) {
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
