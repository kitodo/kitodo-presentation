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

namespace Kitodo\Dlf\Common;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use OverflowException;
use Psr\Http\Message\StreamInterface;

/**
 * Stream decorator that refuses to grow beyond a maximum size.
 *
 * It is used as the "sink" of an HTTP request, so that the transfer of a
 * response body is aborted by the HTTP client as soon as more bytes than
 * allowed have been received, whichever handler (curl or PHP streams) the
 * client uses.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SizeLimitedStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /**
     * @access protected
     * @var int The number of bytes written so far
     */
    protected int $bytesWritten = 0;

    /**
     * @access protected
     * @var bool Whether a write was refused because of the size limit
     */
    protected bool $limitExceeded = false;

    /**
     * Constructor
     *
     * @access public
     *
     * @param StreamInterface $stream The stream that receives the data
     * @param int $maxSize The maximum number of bytes that may be written, or 0 for no limit
     */
    public function __construct(protected readonly StreamInterface $stream, protected readonly int $maxSize)
    {
    }

    /**
     * Write data to the stream unless the size limit would be exceeded.
     *
     * @access public
     *
     * @param string $string The data to write
     *
     * @throws OverflowException if the size limit would be exceeded
     *
     * @return int The number of bytes written
     */
    public function write(string $string): int
    {
        $this->bytesWritten += strlen($string);
        if ($this->maxSize > 0 && $this->bytesWritten > $this->maxSize) {
            $this->limitExceeded = true;
            throw new OverflowException(
                'The response body exceeds the maximum allowed size of ' . $this->maxSize . ' bytes.'
            );
        }
        return $this->stream->write($string);
    }

    /**
     * @access public
     *
     * @return bool Whether a write was refused because of the size limit
     */
    public function isLimitExceeded(): bool
    {
        return $this->limitExceeded;
    }

    /**
     * @access public
     *
     * @return int The number of bytes that were offered for writing so far
     */
    public function getBytesWritten(): int
    {
        return $this->bytesWritten;
    }
}
