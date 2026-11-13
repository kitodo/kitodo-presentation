/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// Shows a progress indicator and prevents a second submit while the demo site is created.
import '@typo3/backend/element/spinner-element.js';

document.querySelector('form[name="bootstrap"]')?.addEventListener('submit', (event) => {
    event.target.querySelector('[type="submit"]').disabled = true;
    event.target.querySelector('.dlf-bootstrap-progress').hidden = false;
});
