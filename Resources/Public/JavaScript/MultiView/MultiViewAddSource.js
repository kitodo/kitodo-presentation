/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

const multiViewAddSource = document.querySelector('.multiViewAddSource');
if (multiViewAddSource) {
  document.querySelector('.multiViewAddSource').addEventListener('submit', function (e) {
    e.preventDefault();
    const url = new URL(window.location.href);
    const params = [...url.searchParams.keys()];
    const multiview = params.filter(p => p.startsWith("tx_dlf[multiview]"));
    if (multiview.length === 0) {
      url.searchParams.append("tx_dlf[multiview]", 1);
    }
    const multiViewSources = params.filter(p => p.startsWith("tx_dlf[multiViewSource]["));
    const nextIndex = multiViewSources.length;
    const urlValue = document.getElementById('location-field').value.trim();
    url.searchParams.append(`tx_dlf[multiViewSource][${nextIndex}]`, urlValue);
    // eslint-disable-next-line
    window.location.href = url.toString();
  });
}
