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
use Kitodo\Dlf\Common\Helper;
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

    private const BAD_REQUEST = 400;
    private const NOT_FOUND = 404;
    private const INTERNAL_SERVER_ERROR = 500;

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

        $parameters = $request->getQueryParams();

        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/dom-document-validation')) {
            return $handler->handle($request);
        }

        // check required parameters
        $urlParam = $parameters['url'];
        if (!isset($urlParam)) {
            return $this->getJsonResponse('URL parameter is missing.', self::BAD_REQUEST);
        }

        $typeParam = $parameters['validationType'] ?? null;
        if ($typeParam === null) {
            return $this->getJsonResponse('Validation type parameter is missing.', self::BAD_REQUEST);
        }

        // retrieve validation configuration from plugin.tx_dlf typoscript
        // before fetching the URL, so that requests are rejected without
        // side effects when the feature is not (properly) configured
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $typoScript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $settings = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScript['plugin.']['tx_dlf.']['settings.'] ?? []);

        if (!array_key_exists("domDocumentValidation", $settings)) {
            $this->logger->error('DOMDocumentValidation is not configured.');
            return $this->getJsonResponse('An internal server error occurred.', self::INTERNAL_SERVER_ERROR);
        }

        if (!array_key_exists($typeParam, $settings["domDocumentValidation"])) {
            $this->logger->debug('Validation configuration type in validationType parameter "' . $typeParam . '" does not exist.');
            return $this->getJsonResponse('Validation type does not exist.', self::NOT_FOUND);
        }

        // only http/https URLs may be fetched server-side, anything else
        // could be used for server-side request forgery (SSRF)
        if (!Helper::isValidHttpUrl((string) $urlParam)) {
            return $this->getJsonResponse('Parameter "' . $urlParam . '" is not a valid URL.', self::NOT_FOUND);
        }

        // reject hosts that are not allowed to be fetched to prevent SSRF
        if (!$this->isHostAllowed((string) $urlParam, $settings['domDocumentValidation'][$typeParam])) {
            $this->logger->warning('Attempt to validate document from non-allowed host: ' . $urlParam);
            return $this->getJsonResponse('Host of the given URL is not allowed.', self::BAD_REQUEST);
        }

        // load dom document from url
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

        $validationConfiguration = $this->removeDisabledValidators($parameters, $settings['domDocumentValidation'][$typeParam]);
        // the hostAllowList is middleware configuration, not a validator
        unset($validationConfiguration['hostAllowList']);

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
     * Check whether the host of the given URL may be fetched server-side.
     *
     * By default, only hosts that resolve to public addresses are allowed;
     * hosts that resolve to private or otherwise reserved address ranges
     * (loopback, RFC 1918, link-local, etc.) are rejected to prevent the
     * middleware from being abused for server-side request forgery.
     *
     * Optionally, the validation configuration may define a
     * ``hostAllowList`` (a comma-separated list of hostnames or subdomain
     * wildcards such as ``*.example.org``). If it is set, only these hosts
     * may be fetched; an entry for a host that would otherwise be rejected
     * (e.g. an internal service) explicitly allows it.
     *
     * @access protected
     *
     * @param string $url The URL that is about to be fetched
     * @param mixed[] $validationConfiguration The validation configuration of the requested type
     *
     * @return bool Whether the host of the URL may be fetched
     */
    protected function isHostAllowed(string $url, array $validationConfiguration): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }
        $host = strtolower($host);

        $allowList = $validationConfiguration['hostAllowList'] ?? '';
        if (is_string($allowList) && trim($allowList) !== '') {
            foreach (explode(',', $allowList) as $allowed) {
                $allowed = strtolower(trim($allowed));
                if ($allowed === '') {
                    continue;
                }
                if (str_starts_with($allowed, '*.')) {
                    // wildcard entries such as "*.example.org" match the
                    // host "example.org" and all of its subdomains
                    $base = substr($allowed, 2);
                    if ($host === $base || str_ends_with($host, '.' . $base)) {
                        return true;
                    }
                } elseif ($host === $allowed) {
                    return true;
                }
            }
            return false;
        }

        // default: reject hosts that resolve to private or reserved ranges
        return $this->resolvesToPublicAddress($host);
    }

    /**
     * Check whether a hostname resolves to a public (non-reserved) address.
     *
     * @access protected
     *
     * @param string $host The hostname to check
     *
     * @return bool Whether all resolved addresses are public
     */
    protected function resolvesToPublicAddress(string $host): bool
    {
        // treat the host as an IP address directly (this also covers IPv6
        // literals, with or without surrounding square brackets)
        $candidate = ltrim($host, '[');
        $candidate = rtrim($candidate, ']');
        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicAddress($candidate);
        }

        $addresses = gethostbynamel($host);
        if ($addresses === false || $addresses === []) {
            // unresolvable hosts are rejected to prevent bypassing the
            // check via DNS rebinding
            return false;
        }
        foreach ($addresses as $address) {
            if (!$this->isPublicAddress($address)) {
                return false;
            }
        }
        return true;
    }

    /**
     * The IPv4 and IPv6 address ranges that are not globally routable and
     * therefore must never be fetched by the middleware.
     *
     * This is an explicit list (instead of relying on
     * FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE, whose coverage
     * varies between PHP versions) so the result is deterministic and also
     * covers ranges such as CGNAT (RFC 6598) and IPv6 documentation space.
     *
     * @var string[]
     */
    private const RESERVED_RANGES = [
        // IPv4
        '0.0.0.0/8',        // "this" network
        '10.0.0.0/8',       // RFC 1918 private
        '100.64.0.0/10',    // RFC 6598 carrier-grade NAT
        '127.0.0.0/8',      // loopback
        '169.254.0.0/16',   // link-local (incl. cloud metadata 169.254.169.254)
        '172.16.0.0/12',    // RFC 1918 private
        '192.0.0.0/24',     // IETF protocol assignments
        '192.0.2.0/24',     // TEST-NET-1 (documentation)
        '192.88.99.0/24',   // 6to4 relay anycast
        '192.168.0.0/16',   // RFC 1918 private
        '198.18.0.0/15',    // benchmarking
        '198.51.100.0/24',  // TEST-NET-2 (documentation)
        '203.0.113.0/24',   // TEST-NET-3 (documentation)
        '224.0.0.0/4',      // multicast
        '240.0.0.0/4',      // reserved / future use
        '255.255.255.255/32', // broadcast
        // IPv6
        '::/128',           // unspecified
        '::1/128',          // loopback
        '64:ff9b::/96',     // NAT64 well-known prefix
        '100::/64',         // discard-only
        '2001:db8::/32',    // documentation
        'fc00::/7',         // unique local address
        'fe80::/10',        // link-local
        'ff00::/8',         // multicast
    ];

    /**
     * Check whether an IP address is a public address, i.e. not loopback,
     * private (RFC 1918), link-local, or otherwise reserved.
     *
     * IPv4-mapped and IPv4-compatible IPv6 addresses (for example
     * ::ffff:127.0.0.1) are unwrapped and checked as their IPv4 address,
     * because otherwise the IPv4 checks could be bypassed.
     *
     * @access protected
     *
     * @param string $address The IP address to check
     *
     * @return bool Whether the address is a public address
     */
    protected function isPublicAddress(string $address): bool
    {
        $ip = filter_var($address, FILTER_VALIDATE_IP);
        if ($ip === false) {
            return false;
        }

        $binary = inet_pton($ip);
        if ($binary === false) {
            return false;
        }

        // IPv4-mapped/compatible IPv6 addresses embed an IPv4 address in the
        // last four bytes (e.g. ::ffff:127.0.0.1). Unwrap and check the IPv4
        // address, because otherwise the IPv4 checks could be bypassed.
        if (strlen($binary) === 16) {
            if (substr($binary, 0, 10) === str_repeat("\x00", 10)
                && ord($binary[10]) === 0xff
                && ord($binary[11]) === 0xff) {
                $ipv4 = inet_ntop(substr($binary, 12, 4));
                return $ipv4 !== false && $this->isPublicAddress($ipv4);
            }
            if (substr($binary, 0, 12) === str_repeat("\x00", 12)) {
                // deprecated IPv4-compatible form (::a.b.c.d)
                $ipv4 = inet_ntop(substr($binary, 12, 4));
                return $ipv4 !== false && $this->isPublicAddress($ipv4);
            }
        }

        foreach (self::RESERVED_RANGES as $range) {
            if ($this->isInRange($binary, $range)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check whether a binary-packed IP address is contained in a CIDR range.
     *
     * @access private
     *
     * @param string $binaryIp The IP address packed with inet_pton()
     * @param string $cidr A CIDR notation such as "10.0.0.0/8"
     *
     * @return bool Whether the address is in the given range
     */
    private function isInRange(string $binaryIp, string $cidr): bool
    {
        [$network, $prefix] = explode('/', $cidr, 2);
        $networkBinary = inet_pton($network);
        if ($networkBinary === false || strlen($binaryIp) !== strlen($networkBinary)) {
            // invalid network or different address family (IPv4 vs IPv6)
            return false;
        }
        $prefix = (int) $prefix;
        $fullBytes = strlen($networkBinary);
        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($wholeBytes > 0 && substr($binaryIp, 0, $wholeBytes) !== substr($networkBinary, 0, $wholeBytes)) {
            return false;
        }
        if ($remainingBits === 0) {
            return true;
        }
        $mask = 0xff << (8 - $remainingBits) & 0xff;
        $ipByte = ord($binaryIp[$wholeBytes]);
        $netByte = ord($networkBinary[$wholeBytes]);
        return ($ipByte & $mask) === ($netByte & $mask);
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
                array_map(fn ($value) => str_starts_with($value, 'EXT:') ? PathUtility::getPublicResourceWebPath($value) : $value, $arguments)
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
        $response->getBody()->write(json_encode($payload) ?: '');
        return $response;
    }
}
