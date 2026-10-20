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

namespace Kitodo\Dlf\Tests\Unit\Middleware;

use Kitodo\Dlf\Middleware\DOMDocumentValidation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests the server-side request forgery (SSRF) protection of the
 * DOMDocumentValidation middleware, i.e. whether a given URL may be
 * fetched server-side.
 *
 * The tested methods are pure (they depend only on their arguments and on
 * DNS/IP resolution), so they can be exercised in a unit test without a full
 * TYPO3 instance.
 */
class DOMDocumentValidationTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var DOMDocumentValidation
     */
    protected DOMDocumentValidation $middleware;

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
        $this->middleware = new DOMDocumentValidation();
    }

    /**
     * Call a protected method of the middleware under test.
     *
     * @access protected
     *
     * @param string $name The method name
     * @param mixed[] $args The arguments
     *
     * @return mixed
     */
    protected function invokeProtected(string $name, array $args): mixed
    {
        // setAccessible() is a no-op since PHP 8.1, so it is intentionally
        // not called (it is deprecated in PHP 8.5).
        $method = new ReflectionMethod($this->middleware, $name);
        return $method->invoke($this->middleware, ...$args);
    }

    /**
     * Provide a URL that should be rejected (server-side request forgery /
     * local file inclusion) when no host allow-list is configured.
     *
     * @return mixed[]
     */
    public static function rejectByDefaultProvider(): array
    {
        return [
            'loopback IPv4' => ['http://127.0.0.1/doc.xml', []],
            'private IPv4 RFC1918 (10/8)' => ['http://10.0.0.1/doc.xml', []],
            'private IPv4 RFC1918 (172.16/12)' => ['http://172.16.0.1/doc.xml', []],
            'private IPv4 RFC1918 (192.168/16)' => ['http://192.168.1.1/doc.xml', []],
            'cloud metadata (link-local IPv4)' => ['http://169.254.169.254/latest/meta-data/', []],
            'CGNAT reserved IPv4' => ['http://100.64.0.1/doc.xml', []],
            'unspecified IPv4' => ['http://0.0.0.0/doc.xml', []],
            'loopback IPv6' => ['http://[::1]/doc.xml', []],
            'link-local IPv6' => ['http://[fe80::1]/doc.xml', []],
            'documentation reserved IPv6' => ['http://[2001:db8::1]/doc.xml', []],
            // IPv4-mapped IPv6 addresses must be unwrapped and rejected,
            // otherwise the IPv4 checks could be bypassed
            'IPv4-mapped IPv6 loopback' => ['http://[::ffff:127.0.0.1]/doc.xml', []],
            'IPv4-mapped IPv6 cloud metadata' => ['http://[::ffff:169.254.169.254]/doc.xml', []],
            'IPv4-mapped IPv6 private' => ['http://[::ffff:10.0.0.1]/doc.xml', []],
            // the file:// scheme must never reach a fetch (local file inclusion)
            'file scheme (no host)' => ['file:///etc/passwd', []],
            'file scheme with localhost host' => ['file://localhost/etc/passwd', []],
            'php stream scheme' => ['php://filter/resource=php://stdin', []],
            'no host at all' => ['http:///doc.xml', []],
        ];
    }

    #[DataProvider('rejectByDefaultProvider')]
    #[Test]
    public function rejectsNonPublicHostsByDefault(string $url, array $configuration): void
    {
        self::assertFalse(
            $this->invokeProtected('isHostAllowed', [$url, $configuration]),
            'URL "' . $url . '" must be rejected when no host allow-list is configured.'
        );
    }

    /**
     * Provide a public URL that should be accepted when no host allow-list is
     * configured.
     *
     * @return mixed[]
     */
    public static function allowByDefaultProvider(): array
    {
        return [
            'public IPv4' => ['http://93.184.216.34/doc.xml', []],
            'public IPv4 high' => ['http://8.8.8.8/doc.xml', []],
            'public IPv6' => ['http://[2606:4700:4700::1111]/doc.xml', []],
            'public URL with scheme https' => ['https://93.184.216.34/doc.xml', []],
        ];
    }

    #[DataProvider('allowByDefaultProvider')]
    #[Test]
    public function allowsPublicHostsByDefault(string $url, array $configuration): void
    {
        self::assertTrue(
            $this->invokeProtected('isHostAllowed', [$url, $configuration]),
            'URL "' . $url . '" must be allowed when it resolves to a public address.'
        );
    }

    /**
     * Provide a URL and an explicit host allow-list with the expected result.
     * An allow-list, when present, is the single source of truth: it may
     * restrict public hosts and it may explicitly permit internal hosts.
     *
     * @return mixed[]
     */
    public static function allowListProvider(): array
    {
        return [
            'exact host match is allowed' => ['http://web:8001/doc.xml', ['hostAllowList' => 'web'], true],
            'non-matching public host is blocked' => ['http://93.184.216.34/doc.xml', ['hostAllowList' => 'web'], false],
            'comma separated list, second entry matches' => ['http://intranet.example.org/doc.xml', ['hostAllowList' => 'web, intranet.example.org'], true],
            'wildcard matches a subdomain' => ['http://files.example.org/doc.xml', ['hostAllowList' => '*.example.org'], true],
            'wildcard matches the base domain' => ['http://example.org/doc.xml', ['hostAllowList' => '*.example.org'], true],
            'wildcard matches deeper subdomains' => ['http://a.b.example.org/doc.xml', ['hostAllowList' => '*.example.org'], true],
            'wildcard does not match a look-alike domain' => ['http://notexample.org/doc.xml', ['hostAllowList' => '*.example.org'], false],
            'explicit allow-list permits an internal host' => ['http://10.0.0.1/doc.xml', ['hostAllowList' => '10.0.0.1'], true],
            'explicit allow-list permits the cloud metadata host' => ['http://169.254.169.254/doc.xml', ['hostAllowList' => '169.254.169.254'], true],
            // an empty allow-list value is ignored, the default (public only) applies
            'empty allow-list falls back to default' => ['http://93.184.216.34/doc.xml', ['hostAllowList' => ''], true],
            'whitespace only allow-list falls back to default' => ['http://93.184.216.34/doc.xml', ['hostAllowList' => '   '], true],
        ];
    }

    #[DataProvider('allowListProvider')]
    #[Test]
    public function appliesHostAllowList(string $url, array $configuration, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->invokeProtected('isHostAllowed', [$url, $configuration]),
            'URL "' . $url . '" with allow-list "' . ($configuration['hostAllowList'] ?? '') . '".'
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
            // IPv4-mapped IPv6 addresses are unwrapped to their IPv4 part
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
            $this->invokeProtected('isPublicAddress', [$address]),
            'Address "' . $address . '".'
        );
    }
}
