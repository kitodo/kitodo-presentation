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
 * Tests how the DOMDocumentValidation middleware applies the hostAllowList
 * of a validation configuration.
 *
 * The general rules (reserved address ranges, wildcard matching, redirects)
 * are covered by the UrlValidationServiceTest; this test only covers the
 * behaviour that is specific to the middleware: a configured hostAllowList
 * is exclusive, without it the public-host default applies.
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
     * Provide a URL, a validation configuration and the expected result.
     *
     * @return mixed[]
     */
    public static function allowListProvider(): array
    {
        return [
            // without a hostAllowList, the public-host default applies
            'no allow-list: public host is allowed' => ['http://93.184.216.34/doc.xml', [], true],
            'no allow-list: internal host is blocked' => ['http://10.0.0.1/doc.xml', [], false],
            'no allow-list: cloud metadata host is blocked' => ['http://169.254.169.254/latest/meta-data/', [], false],
            'no allow-list: URL without host is blocked' => ['http:///doc.xml', [], false],
            // an empty allow-list value is ignored, the default (public only) applies
            'empty allow-list falls back to default' => ['http://93.184.216.34/doc.xml', ['hostAllowList' => ''], true],
            'whitespace only allow-list falls back to default' => ['http://93.184.216.34/doc.xml', ['hostAllowList' => '   '], true],
            // a configured allow-list is exclusive
            'exact host match is allowed' => ['http://web:8001/doc.xml', ['hostAllowList' => 'web'], true],
            'non-matching public host is blocked' => ['http://93.184.216.34/doc.xml', ['hostAllowList' => 'web'], false],
            'comma separated list, second entry matches' => ['http://intranet.example.org/doc.xml', ['hostAllowList' => 'web, intranet.example.org'], true],
            'YAML list is accepted' => ['http://intranet.example.org/doc.xml', ['hostAllowList' => ['web', 'intranet.example.org']], true],
            'wildcard matches a subdomain' => ['http://files.example.org/doc.xml', ['hostAllowList' => '*.example.org'], true],
            'wildcard does not match a look-alike domain' => ['http://notexample.org/doc.xml', ['hostAllowList' => '*.example.org'], false],
            'explicit allow-list permits an internal host' => ['http://10.0.0.1/doc.xml', ['hostAllowList' => '10.0.0.1'], true],
            'allow-list does not permit other internal hosts' => ['http://10.0.0.2/doc.xml', ['hostAllowList' => '10.0.0.1'], false],
            'allow-list does not permit a URL without host' => ['http:///doc.xml', ['hostAllowList' => 'web'], false],
        ];
    }

    #[DataProvider('allowListProvider')]
    #[Test]
    public function appliesHostAllowList(string $url, array $configuration, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->invokeProtected('isHostAllowed', [$url, $configuration]),
            'URL "' . $url . '" with configuration ' . json_encode($configuration) . '.'
        );
    }
}
