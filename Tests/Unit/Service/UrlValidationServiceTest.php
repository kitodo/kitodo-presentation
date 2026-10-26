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

namespace Kitodo\Dlf\Tests\Unit\Service;

use Kitodo\Dlf\Service\UrlValidationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests the rules that decide whether a URL may be fetched by the server.
 *
 * The tested methods are pure (they depend only on their arguments and on
 * DNS/IP resolution), so they can be exercised in a unit test without a full
 * TYPO3 instance.
 */
class UrlValidationServiceTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var UrlValidationService
     */
    protected UrlValidationService $service;

    /**
     * Sets up the test environment.
     *
     * @access protected
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UrlValidationService();
    }

    /**
     * Provide a comma-separated list of hosts and the expected parsed result.
     *
     * @return mixed[]
     */
    public static function hostListProvider(): array
    {
        return [
            'empty string' => ['', []],
            'whitespace only' => ['   ', []],
            'single host' => ['example.org', ['example.org']],
            'upper case is normalised' => ['Example.ORG', ['example.org']],
            'comma separated' => ['a.example.org, b.example.org', ['a.example.org', 'b.example.org']],
            'empty entries are dropped' => ['a.example.org,,b.example.org', ['a.example.org', 'b.example.org']],
            'wildcard is kept' => ['*.example.org', ['*.example.org']],
            'array is accepted' => [['a.example.org', 'b.example.org'], ['a.example.org', 'b.example.org']],
            'non string is ignored' => [42, []],
        ];
    }

    #[DataProvider('hostListProvider')]
    #[Test]
    public function parsesHostList(mixed $input, array $expected): void
    {
        self::assertSame($expected, $this->service->parseHostList($input));
    }

    /**
     * Provide a URL that must be rejected when no host allow-list is given.
     *
     * @return mixed[]
     */
    public static function rejectByDefaultProvider(): array
    {
        return [
            'loopback IPv4' => ['http://127.0.0.1/doc.xml'],
            'private IPv4 RFC1918 (10/8)' => ['http://10.0.0.1/doc.xml'],
            'private IPv4 RFC1918 (172.16/12)' => ['http://172.16.0.1/doc.xml'],
            'private IPv4 RFC1918 (192.168/16)' => ['http://192.168.1.1/doc.xml'],
            'cloud metadata (link-local IPv4)' => ['http://169.254.169.254/latest/meta-data/'],
            'CGNAT reserved IPv4' => ['http://100.64.0.1/doc.xml'],
            'unspecified IPv4' => ['http://0.0.0.0/doc.xml'],
            'loopback IPv6' => ['http://[::1]/doc.xml'],
            'link-local IPv6' => ['http://[fe80::1]/doc.xml'],
            'unique local IPv6' => ['http://[fd00::1]/doc.xml'],
            'documentation reserved IPv6' => ['http://[2001:db8::1]/doc.xml'],
            'IPv4-mapped IPv6 loopback' => ['http://[::ffff:127.0.0.1]/doc.xml'],
            'IPv4-mapped IPv6 cloud metadata' => ['http://[::ffff:169.254.169.254]/doc.xml'],
            'no host at all' => ['http:///doc.xml'],
        ];
    }

    #[DataProvider('rejectByDefaultProvider')]
    #[Test]
    public function rejectsNonPublicHostsByDefault(string $url): void
    {
        self::assertFalse(
            $this->service->isHostAllowed($url),
            'URL "' . $url . '" must be rejected when no host allow-list is configured.'
        );
    }

    /**
     * Provide a public URL that must be accepted when no host allow-list is given.
     *
     * @return mixed[]
     */
    public static function allowByDefaultProvider(): array
    {
        return [
            'public IPv4' => ['http://93.184.216.34/doc.xml'],
            'public IPv4 (8.8.8.8)' => ['http://8.8.8.8/doc.xml'],
            'public IPv6' => ['http://[2606:4700:4700::1111]/doc.xml'],
            'public URL with scheme https' => ['https://93.184.216.34/doc.xml'],
        ];
    }

    #[DataProvider('allowByDefaultProvider')]
    #[Test]
    public function allowsPublicHostsByDefault(string $url): void
    {
        self::assertTrue(
            $this->service->isHostAllowed($url),
            'URL "' . $url . '" must be allowed when it resolves to a public address.'
        );
    }

    /**
     * Provide a URL, an allow-list and the expected result.
     *
     * @return mixed[]
     */
    public static function allowListProvider(): array
    {
        return [
            'exact host match is allowed' => ['http://web:8001/doc.xml', ['web'], true],
            'non-matching public host is blocked' => ['http://93.184.216.34/doc.xml', ['web'], false],
            'comma separated list, second entry matches' => ['http://intranet.example.org/doc.xml', ['web', 'intranet.example.org'], true],
            'wildcard matches a subdomain' => ['http://files.example.org/doc.xml', ['*.example.org'], true],
            'wildcard matches the base domain' => ['http://example.org/doc.xml', ['*.example.org'], true],
            'wildcard matches deeper subdomains' => ['http://a.b.example.org/doc.xml', ['*.example.org'], true],
            'wildcard does not match a look-alike domain' => ['http://notexample.org/doc.xml', ['*.example.org'], false],
            'allow-list permits an internal host' => ['http://10.0.0.1/doc.xml', ['10.0.0.1'], true],
            'allow-list permits the cloud metadata host' => ['http://169.254.169.254/doc.xml', ['169.254.169.254'], true],
            'allow-list still rejects other internal hosts' => ['http://10.0.0.2/doc.xml', ['10.0.0.1'], false],
        ];
    }

    #[DataProvider('allowListProvider')]
    #[Test]
    public function appliesHostAllowList(string $url, array $allowedHosts, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->service->isHostAllowed($url, $allowedHosts),
            'URL "' . $url . '" with allow-list "' . implode(', ', $allowedHosts) . '".'
        );
    }

    /**
     * Provide an IP address and whether it is a public address.
     *
     * @return mixed[]
     */
    public static function publicAddressProvider(): array
    {
        return [
            'public IPv4' => ['93.184.216.34', true],
            'public IPv4 (8.8.8.8)' => ['8.8.8.8', true],
            'public IPv6' => ['2606:4700:4700::1111', true],
            'loopback IPv4' => ['127.0.0.1', false],
            'private 10/8' => ['10.1.2.3', false],
            'private 172.16/12' => ['172.16.5.5', false],
            'private 192.168/16' => ['192.168.0.10', false],
            'link-local IPv4 / cloud metadata' => ['169.254.169.254', false],
            'CGNAT (RFC 6598)' => ['100.64.0.1', false],
            'unspecified' => ['0.0.0.0', false],
            'loopback IPv6' => ['::1', false],
            'link-local IPv6' => ['fe80::1', false],
            'documentation reserved IPv6' => ['2001:db8::1', false],
            'unique local IPv6' => ['fd00::1', false],
            'IPv4-mapped public' => ['::ffff:93.184.216.34', true],
            'IPv4-mapped loopback' => ['::ffff:127.0.0.1', false],
            'IPv4-mapped cloud metadata' => ['::ffff:169.254.169.254', false],
            'IPv4-mapped private' => ['::ffff:10.0.0.1', false],
            'not an IP address' => ['not-an-ip', false],
        ];
    }

    #[DataProvider('publicAddressProvider')]
    #[Test]
    public function classifiesPublicAddresses(string $address, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->service->isPublicAddress($address),
            'Address "' . $address . '".'
        );
    }

    /**
     * Provide two URLs and whether they use the same host.
     *
     * @return mixed[]
     */
    public static function sameHostProvider(): array
    {
        return [
            'same host' => ['https://example.org/a', 'https://example.org/b', true],
            'same host, different port' => ['https://example.org/a', 'https://example.org:8443/b', true],
            'same host, different scheme' => ['https://example.org/a', 'http://example.org/b', true],
            'different host' => ['https://example.org/a', 'https://evil.example.com/b', false],
            'host suffix' => ['https://example.org/a', 'https://notexample.org/b', false],
        ];
    }

    #[DataProvider('sameHostProvider')]
    #[Test]
    public function detectsSameHost(string $url, string $otherUrl, bool $expected): void
    {
        self::assertSame($expected, $this->service->isSameHost($url, $otherUrl));
    }

    /**
     * Provide a base URL, a "Location" header value and the expected result.
     *
     * @return mixed[]
     */
    public static function redirectProvider(): array
    {
        return [
            'absolute URL is kept' => [
                'https://example.org/a/b',
                'https://other.example.org/c',
                'https://other.example.org/c',
            ],
            'absolute URL with a different scheme is kept' => [
                'https://example.org/a/b',
                'http://other.example.org/c',
                'http://other.example.org/c',
            ],
            'protocol relative URL inherits the scheme' => [
                'https://example.org/a/b',
                '//other.example.org/c',
                'https://other.example.org/c',
            ],
            'absolute path' => [
                'https://example.org/a/b',
                '/c',
                'https://example.org/c',
            ],
            'relative path' => [
                'https://example.org/a/b',
                'c',
                'https://example.org/a/c',
            ],
            'relative path with dot segments' => [
                'https://example.org/a/b/c',
                '../d',
                'https://example.org/a/d',
            ],
            'relative path with current directory' => [
                'https://example.org/a/b/c',
                './d',
                'https://example.org/a/b/d',
            ],
            'query only' => [
                'https://example.org/a/b?x=1',
                '?y=2',
                'https://example.org/a/b?y=2',
            ],
            'fragment only' => [
                'https://example.org/a/b',
                '#top',
                'https://example.org/a/b#top',
            ],
            'port is kept' => [
                'https://example.org:8443/a',
                '/b',
                'https://example.org:8443/b',
            ],
            'empty location cannot be resolved' => [
                'https://example.org/a',
                '',
                null,
            ],
        ];
    }

    #[DataProvider('redirectProvider')]
    #[Test]
    public function resolvesRedirectUrl(string $baseUrl, string $location, ?string $expected): void
    {
        self::assertSame(
            $expected,
            $this->service->resolveRedirectUrl($baseUrl, $location),
            'Redirect from "' . $baseUrl . '" to "' . $location . '".'
        );
    }
}
