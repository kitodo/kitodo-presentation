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

namespace Kitodo\Dlf\Service;

/**
 * Decides whether a URL may be fetched by the server.
 *
 * The rules implemented here prevent server-side request forgery (SSRF) and
 * local file inclusion (LFI):
 *
 * - only http(s) URLs are considered, so that no stream wrapper such as
 *   "file://" or "php://" can be used to read local files,
 * - a host may be fetched if it is explicitly listed in an allow-list, or
 * - if all of the addresses it resolves to are globally routable.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 **/
class UrlValidationService
{
    /**
     * The IPv4 and IPv6 address ranges that are not globally routable and
     * therefore must never be fetched by the server.
     *
     * This is an explicit list (instead of relying on
     * FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE, whose coverage
     * varies between PHP versions) so the result is deterministic and also
     * covers ranges such as CGNAT (RFC 6598) and IPv6 documentation space.
     *
     * @var string[]
     */
    public const RESERVED_RANGES = [
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
     * Split a comma-separated list of host patterns into a list of hosts.
     *
     * @access public
     *
     * @param mixed $hostList Comma-separated list of host names, optionally
     *                        using a leading "*." for wildcard subdomains
     *
     * @return string[] List of host patterns
     */
    public function parseHostList(mixed $hostList): array
    {
        if (is_array($hostList)) {
            $hostList = implode(',', $hostList);
        }
        if (!is_string($hostList)) {
            return [];
        }
        $hosts = [];
        foreach (explode(',', $hostList) as $host) {
            $host = strtolower(trim($host));
            if ($host !== '') {
                $hosts[] = $host;
            }
        }
        return $hosts;
    }

    /**
     * Get the host of the given URL.
     *
     * @access public
     *
     * @param string $url The URL to inspect
     *
     * @return string|null The lower-cased host or null if the URL has none
     */
    public function getHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }
        return strtolower($host);
    }

    /**
     * Check whether the given URL may be fetched server-side.
     *
     * @access public
     *
     * @param string $url The URL to check
     * @param string[] $allowedHosts Host patterns that may be fetched even if
     *                               they do not resolve to a public address
     *
     * @return bool Whether the URL may be fetched
     */
    public function isHostAllowed(string $url, array $allowedHosts = []): bool
    {
        $host = $this->getHost($url);
        if ($host === null) {
            return false;
        }
        if (!empty($allowedHosts)) {
            return $this->isHostInAllowList($host, $allowedHosts);
        }
        // default: reject hosts that resolve to private or reserved ranges
        return $this->resolvesToPublicAddress($host);
    }

    /**
     * Check whether the given host matches one of the allowed host patterns.
     *
     * A pattern is either an exact host name or a wildcard such as
     * "*.example.org", which matches "example.org" and all of its subdomains.
     *
     * @access public
     *
     * @param string $host The host to check
     * @param string[] $allowedHosts List of host patterns
     *
     * @return bool Whether the host is allowed
     */
    public function isHostInAllowList(string $host, array $allowedHosts): bool
    {
        $host = strtolower($host);
        foreach ($allowedHosts as $allowed) {
            $allowed = strtolower(trim((string) $allowed));
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

    /**
     * Check whether two URLs point to the same host.
     *
     * @access public
     *
     * @param string $url The first URL
     * @param string $otherUrl The second URL
     *
     * @return bool Whether both URLs use the same host
     */
    public function isSameHost(string $url, string $otherUrl): bool
    {
        $host = $this->getHost($url);
        $otherHost = $this->getHost($otherUrl);
        return $host !== null && $host === $otherHost;
    }

    /**
     * Check whether a hostname resolves to a public (non-reserved) address.
     *
     * @access public
     *
     * @param string $host The hostname to check
     *
     * @return bool Whether all resolved addresses are public
     */
    public function resolvesToPublicAddress(string $host): bool
    {
        // treat the host as an IP address directly (this also covers IPv6
        // literals, with or without surrounding square brackets)
        $candidate = ltrim($host, '[');
        $candidate = rtrim($candidate, ']');
        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicAddress($candidate);
        }

        $addresses = $this->resolveAddresses($host);
        if ($addresses === []) {
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
     * Resolve a host name to all of its addresses.
     *
     * Both IPv4 (A) and IPv6 (AAAA) records are taken into account, because
     * a host with a public A record but a private AAAA record would
     * otherwise be able to bypass an IPv4-only check.
     *
     * @access protected
     *
     * @param string $host The hostname to resolve
     *
     * @return string[] List of IP addresses
     */
    protected function resolveAddresses(string $host): array
    {
        $addresses = [];
        $ipv4Addresses = gethostbynamel($host);
        if (is_array($ipv4Addresses)) {
            $addresses = $ipv4Addresses;
        }
        if (function_exists('dns_get_record')) {
            $ipv6Records = @dns_get_record($host, DNS_AAAA);
            if (is_array($ipv6Records)) {
                foreach ($ipv6Records as $record) {
                    if (!empty($record['ipv6'])) {
                        $addresses[] = $record['ipv6'];
                    }
                }
            }
        }
        return array_values(array_unique($addresses));
    }

    /**
     * Check whether an IP address is a public address, i.e. not loopback,
     * private (RFC 1918), link-local, or otherwise reserved.
     *
     * IPv4-mapped and IPv4-compatible IPv6 addresses (for example
     * ::ffff:127.0.0.1) are unwrapped and checked as their IPv4 address,
     * because otherwise the IPv4 checks could be bypassed.
     *
     * @access public
     *
     * @param string $address The IP address to check
     *
     * @return bool Whether the address is a public address
     */
    public function isPublicAddress(string $address): bool
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
     * @access public
     *
     * @param string $binaryIp The IP address packed with inet_pton()
     * @param string $cidr A CIDR notation such as "10.0.0.0/8"
     *
     * @return bool Whether the address is in the given range
     */
    public function isInRange(string $binaryIp, string $cidr): bool
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
     * Resolve the target of a redirect against the URL it was sent from.
     *
     * @access public
     *
     * @param string $baseUrl The URL the redirect was sent from
     * @param string $location The value of the "Location" header
     *
     * @return string|null The absolute redirect target or null if it cannot
     *                     be resolved
     */
    public function resolveRedirectUrl(string $baseUrl, string $location): ?string
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }
        // an absolute URL with a scheme is used as it is
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*:#', $location)) {
            return $location;
        }
        $base = parse_url($baseUrl);
        if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }
        // a protocol-relative URL inherits the scheme of the base URL
        if (str_starts_with($location, '//')) {
            return $base['scheme'] . ':' . $location;
        }

        // split off fragment and query
        $fragment = '';
        if (($position = strpos($location, '#')) !== false) {
            $fragment = substr($location, $position);
            $location = substr($location, 0, $position);
        }
        $query = '';
        if (($position = strpos($location, '?')) !== false) {
            $query = substr($location, $position);
            $location = substr($location, 0, $position);
        }

        // build the path
        if ($location === '') {
            $path = $base['path'] ?? '/';
        } elseif (str_starts_with($location, '/')) {
            $path = $location;
        } else {
            $basePath = $base['path'] ?? '/';
            $path = substr($basePath, 0, (int) strrpos($basePath, '/') + 1) . $location;
        }
        $path = $this->normalizePath($path);

        // build the URL
        $url = $base['scheme'] . '://';
        if (!empty($base['user'])) {
            $url .= $base['user'];
            if (!empty($base['pass'])) {
                $url .= ':' . $base['pass'];
            }
            $url .= '@';
        }
        $url .= $base['host'];
        if (!empty($base['port'])) {
            $url .= ':' . $base['port'];
        }
        return $url . $path . $query . $fragment;
    }

    /**
     * Remove "." and ".." segments from a path.
     *
     * @access protected
     *
     * @param string $path The path to normalize
     *
     * @return string The normalized path
     */
    protected function normalizePath(string $path): string
    {
        $isAbsolute = str_starts_with($path, '/');
        $result = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if (!empty($result) && end($result) !== '..') {
                    array_pop($result);
                    continue;
                }
                if ($isAbsolute) {
                    continue;
                }
            }
            $result[] = $segment;
        }
        $normalized = implode('/', $result);
        if ($isAbsolute && !str_starts_with($normalized, '/')) {
            $normalized = '/' . $normalized;
        }
        return $normalized;
    }
}
