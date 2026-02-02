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

namespace Kitodo\Dlf\Command\DbDocs;

/**
 * Simple utility to write .rst (reStructuredText).
 *
 * @author Kajetan Dvoracek <kajetan.dvoracek@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class RstSection
{
    /** @var string */
    protected string $header = '';

    /** @var string */
    protected string $text = '';

    /** @var RstSection[] */
    protected array $subsections = [];

    /**
     * Format text with given format options.
     *
     * @access public
     *
     * @param string $text
     * @param array<string, bool> $format
     *
     * @return string
     */
    public static function format(string $text, array $format = []): string
    {
        if (!empty($text)) {
            if ($format['bold'] ?? false) {
                $text = '**' . $text . '**';
            } elseif ($format['italic'] ?? false) {
                $text = '*' . $text . '*';
            }
        }

        return $text;
    }

    /**
     * Join paragraphs with double new lines.
     *
     * @access public
     *
     * @param mixed[] $paragraphs
     *
     * @return string
     */
    public static function paragraphs(array $paragraphs): string
    {
        $paragraphs = array_values(array_filter($paragraphs, function ($entry) {
            return !empty($entry);
        }));

        return implode("\n\n", $paragraphs);
    }

    /**
     * Create and return a new subsection.
     *
     * @access public
     *
     * @return RstSection
     */
    public function subsection(): RstSection
    {
        $section = new self();
        $this->subsections[] = $section;
        return $section;
    }

    /**
     * Set header text.
     *
     * @access public
     *
     * @param string $text
     *
     * @return void
     */
    public function setHeader(string $text): void
    {
        $this->header = $text;
    }

    /**
     * Add text to section.
     *
     * @access public
     *
     * @param string $text
     *
     * @return void
     */
    public function addText(string $text): void
    {
        if (!empty($text)) {
            $this->text .= $text . "\n\n";
        }
    }

    /**
     * Add a field list table to section.
     *
     * @access public
     *
     * @param array<array<string, string>> $rows
     * @param array<array<string, string>> $headerRows
     *
     * @return void
     */
    public function addTable(array $rows, array $headerRows): void
    {
        $numHeaderRows = count($headerRows);

        $tableRst = <<<RST
.. t3-field-list-table::
   :header-rows: $numHeaderRows


RST;

        // Pattern for a row:
        //
        // - :key1:      value
        //   :key2:      another
        //               value that may
        //               span multiple lines
        //
        foreach (array_merge($headerRows, $rows) as $row) {
            $entry = '';
            foreach ($row as $key => $value) {
                $valueLines = explode("\n", $value);
                $numLines = count($valueLines);
                for ($i = 0; $i < $numLines; $i++) {
                    $prefix = $i === 0
                        ? '     :' . $key . ':'
                        : '';

                    $entry .= str_pad($prefix, 32) . trim($valueLines[$i]) . "\n";
                }
            }

            if (!empty($entry)) {
                $entry[3] = '-';
                $entry .= "\n";
            }

            $tableRst .= $entry;
        }

        $this->addText($tableRst);
    }

    /**
     * Render section and its subsections recursively.
     *
     * @access public
     *
     * @param int $level
     *
     * @return string
     */
    public function render(int $level = 0): string
    {
        $result = '';

        $result .= $this->renderHeader($level);
        $result .= $this->text;

        foreach ($this->subsections as $section) {
            $result .= $section->render($level + 1);
            $result .= "\n";
        }

        return $result;
    }

    /**
     * Render header of given level.
     *
     * @access protected
     *
     * @param int $level
     *
     * @return string
     */
    protected function renderHeader(int $level): string
    {
        $result = '';

        $headerChar = ['=', '=', '-', '~', '"'][$level];
        $headerSep = str_repeat($headerChar, mb_strlen($this->header));

        if ($level === 0) {
            $result .= $headerSep . "\n";
        }

        $result .= $this->header . "\n" . $headerSep . "\n\n";

        return $result;
    }
}
