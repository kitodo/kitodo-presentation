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
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\SelfEmittableStreamInterface;

/**
 * Stream decorator to allow printing a stream to standard output in chunks.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class StdOutStream implements StreamInterface, SelfEmittableStreamInterface
{
    use StreamDecoratorTrait;

    /**
     * Constructor
     */
    public function __construct(protected readonly StreamInterface $stream)
    {
    }

    /**
     * @access public
     * 
     * @return void
     */
    public function emit()
    {
        // Disable output buffering
        ob_end_flush();

        // Stream content in chunks of 8KB
        while (!$this->stream->eof()) {
            echo $this->stream->read(8 * 1024);
        }
    }
}
