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

namespace Kitodo\Dlf\Bootstrap;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates SVG placeholder images for the pages of the demo documents of the bootstrap mechanism, see BootstrapService.
 *
 * Every image is labeled with the page number, the title of the document and the page label. The words of the page's
 * ALTO full text are drawn at their positions, so that the full text matches the image.
 *
 * PlaceholderImageInfos: Title of a document and its images with their path relative to the document's directory,
 * whether they belong to the file group "THUMBS", the page number (ORDER) and label (ORDERLABEL) of their page,
 * and the path of the page's ALTO full text file relative to the document's directory (if any).
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @phpstan-type PlaceholderImageInfos array{
 *     title: string,
 *     images: list<array{path: string, isThumbnail: bool, order: string, label: ?string, fulltext: ?string}>
 * }
 */
class PlaceholderImageGenerator
{
    /**
     * SVG of a placeholder image with the arguments: drawing width and height, image width and height,
     * vertical position and text of the page number, the title and the page label, and the full text elements
     */
    protected const PLACEHOLDER_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" width="%3$d" height="%4$d">'
        . '<rect width="100%%" height="100%%" fill="#fff"/>'
        . '<g fill="#333" font-family="sans-serif" text-anchor="middle">'
        . '<text x="50%%" y="%5$d" font-size="140">Page %6$s</text>'
        . '<text x="50%%" y="%7$d" font-size="44">%8$s</text>'
        . '<text x="50%%" y="%9$d" font-size="44">%10$s</text>'
        . '</g>'
        . '<g fill="#333" font-family="sans-serif">%11$s</g>'
        . '</svg>';

    /**
     * SVG text of a line of the full text with the arguments: horizontal and vertical position (baseline), font size,
     * width and words of the line. The line is fitted to its width by adjusting the spacing, without stretching glyphs.
     */
    protected const PLACEHOLDER_SVG_LINE = '<text x="%d" y="%d" font-size="%d" textLength="%d" lengthAdjust="spacing">%s</text>';

    /**
     * Generate the placeholder images of the given METS file: every SVG file (MIMETYPE "image/svg+xml") referenced by
     * a page with a URL starting with the given document URL placeholder.
     *
     * @access public
     *
     * @param string $mets Content of the METS file
     * @param string $documentUrlPlaceholder Placeholder in the METS file for the URL of the document's directory
     * @param string $documentDir Directory of the document, which the image paths are relative to
     * @param int[] $pageSize Width and height of the page images
     *
     * @return void
     */
    public function generate(string $mets, string $documentUrlPlaceholder, string $documentDir, array $pageSize): void
    {
        $this->generatePlaceholderImages($this->getPlaceholderImageInfos($mets, $documentUrlPlaceholder), $documentDir, $pageSize);
    }

    /**
     * Extract the information required to generate placeholder images from the given METS file: the title of the
     * document and every SVG file referenced by a page with a URL relative to the document URL placeholder.
     *
     * @access protected
     *
     * @param string $mets Content of the METS file
     * @param string $documentUrlPlaceholder Placeholder in the METS file for the URL of the document's directory
     *
     * @return PlaceholderImageInfos
     */
    protected function getPlaceholderImageInfos(string $mets, string $documentUrlPlaceholder): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($mets);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('mets', 'http://www.loc.gov/METS/');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $prefix = $documentUrlPlaceholder . '/';
        $images = [];
        foreach ($xpath->query('//mets:structMap[@TYPE="PHYSICAL"]//mets:div[@TYPE="page"]') ?: [] as $page) {
            if (!$page instanceof \DOMElement) {
                continue;
            }
            $fulltextHref = '';
            foreach ($xpath->query('mets:fptr/@FILEID', $page) ?: [] as $fileId) {
                $fulltextHref = $fulltextHref ?: (string) $xpath->evaluate(
                    'string(//mets:fileGrp[@USE="FULLTEXT"]/mets:file[@ID="' . $fileId->nodeValue . '"]/mets:FLocat/@xlink:href)'
                );
            }
            foreach ($xpath->query('mets:fptr/@FILEID', $page) ?: [] as $fileId) {
                $file = ($xpath->query('//mets:file[@ID="' . $fileId->nodeValue . '"][@MIMETYPE="image/svg+xml"]') ?: null)?->item(0);
                if (!$file instanceof \DOMElement || !$file->parentNode instanceof \DOMElement) {
                    continue;
                }
                $href = (string) $xpath->evaluate('string(mets:FLocat/@xlink:href)', $file);
                if (!str_starts_with($href, $prefix)) {
                    continue;
                }
                $images[] = [
                    'path' => substr($href, strlen($prefix)),
                    'isThumbnail' => $file->parentNode->getAttribute('USE') === 'THUMBS',
                    'order' => $page->getAttribute('ORDER'),
                    'label' => $page->hasAttribute('ORDERLABEL') ? $page->getAttribute('ORDERLABEL') : null,
                    'fulltext' => str_starts_with($fulltextHref, $prefix) ? substr($fulltextHref, strlen($prefix)) : null,
                ];
            }
        }

        return [
            'title' => (string) $xpath->evaluate('string(//mets:structMap[@TYPE="LOGICAL"]/mets:div/@LABEL)'),
            'images' => $images,
        ];
    }

    /**
     * Generate the given SVG placeholder images, labeled with the page number, the title of the document and the page
     * label. The words of the page's ALTO full text are drawn at their positions, so that the full text matches the
     * image. Thumbnails are scaled down to a maximum size of 150 pixels.
     *
     * @access protected
     *
     * @param PlaceholderImageInfos $infos Images to generate, see getPlaceholderImageInfos()
     * @param string $documentDir Directory the image paths are relative to
     * @param int[] $pageSize Width and height of the page images
     *
     * @return void
     */
    protected function generatePlaceholderImages(array $infos, string $documentDir, array $pageSize): void
    {
        [$width, $height] = $pageSize;
        $thumbnailScale = 150 / max($width, $height);

        foreach ($infos['images'] as $image) {
            $scale = $image['isThumbnail'] ? $thumbnailScale : 1;
            $path = $documentDir . $image['path'];
            GeneralUtility::mkdir_deep(dirname($path));
            GeneralUtility::writeFile($path, sprintf(
                self::PLACEHOLDER_SVG,
                $width,
                $height,
                (int) round($width * $scale),
                (int) round($height * $scale),
                // Labels at about a third of the height, the full text (if any) is drawn below them
                intdiv($height, 3),
                htmlspecialchars($image['order']),
                intdiv($height, 3) + 100,
                htmlspecialchars($infos['title']),
                intdiv($height, 3) + 170,
                $image['label'] !== null ? 'Label: ' . htmlspecialchars($image['label']) : '',
                $image['fulltext'] !== null ? $this->getFulltextSvg($documentDir . $image['fulltext']) : ''
            ), true);
        }
    }

    /**
     * Get the lines of the given ALTO file as SVG text elements, each spanning from the first to the last word of the
     * line, so that the words are drawn at about their positions with natural gaps.
     *
     * @access protected
     *
     * @param string $altoFile Absolute path of the ALTO file
     *
     * @return string
     */
    protected function getFulltextSvg(string $altoFile): string
    {
        $dom = new \DOMDocument();
        if (!is_file($altoFile) || !$dom->load($altoFile)) {
            return '';
        }
        $xpath = new \DOMXPath($dom);
        $svg = '';
        foreach ($xpath->query('//*[local-name()="TextLine"]') ?: [] as $line) {
            if (!$line instanceof \DOMElement) {
                continue;
            }
            $words = [];
            $left = $right = $top = $height = null;
            foreach ($xpath->query('*[local-name()="String"]', $line) ?: [] as $word) {
                if (!$word instanceof \DOMElement) {
                    continue;
                }
                $words[] = $word->getAttribute('CONTENT');
                $left ??= (int) $word->getAttribute('HPOS');
                $right = (int) $word->getAttribute('HPOS') + (int) $word->getAttribute('WIDTH');
                $top = min($top ?? PHP_INT_MAX, (int) $word->getAttribute('VPOS'));
                $height = max($height ?? 0, (int) $word->getAttribute('HEIGHT'));
            }
            if (empty($words)) {
                continue;
            }
            $svg .= sprintf(
                self::PLACEHOLDER_SVG_LINE,
                $left,
                // The baseline of the text is near the bottom of the words' boxes.
                $top + (int) round($height * 0.8),
                $height,
                $right - $left,
                htmlspecialchars(implode(' ', $words))
            );
        }
        return $svg;
    }
}
