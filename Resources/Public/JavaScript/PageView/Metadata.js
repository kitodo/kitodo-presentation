/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

const dlfRootline = {
    /**
     * Only the metadata of the current logical element is shown.
     */
    None: 0,
    /**
     * All rootline metadata is shown.
     */
    All: 1,
    /**
     * Only titledata (toplevel) is shown.
     */
    Titledata: 2,
};

/**
 * Handle dynamic changes to the metadata plugin.
 * - Update visibility of sections depending on page
 */
class dlfMetadata {
    /**
     *
     * @param {dlfController} docController
     * @param {object} config
     * @param {HTMLElement} config.container
     * @param {0 | 1 | 2} config.rootline Rootline configuration, see enum {@link dlfRootline}.
     */
    constructor(docController, config) {
        /** @protected */
        this.docController = docController;
        /** @protected */
        this.config = config;

        this.docController.eventTarget.addEventListener('tx-dlf-stateChanged', () => {
            this.onStateChanged();
        });

        this.fetchMetadata();
    }

    /**
     * @private
     */
    async fetchMetadata() {
        try {
            const metadata = await this.docController.fetchMetadata();
            const element = document.createElement('div');
            element.innerHTML = metadata.htmlCode;
            const metadataContainer = element.querySelector('.dlf-metadata-container');
            if (metadataContainer !== null) {
                this.config.container.replaceWith(metadataContainer);
                this.updateSectionVisibility();
            }
        } catch (error) {
            console.warn("Could not fetch additional metadata:", error);
        }
    }

    /**
     * @private
     */
    onStateChanged() {
        this.updateSectionVisibility();
    }

    /**
     * @protected
     */
    updateSectionVisibility() {
        document.querySelectorAll('[data-metadata-list][data-dlf-section]').forEach((element) => {
            let isShown = false;
            for (const page of this.docController.getVisiblePages()) {
                if (this.shouldShowSection(page.pageObj, element.getAttribute('data-dlf-section'))) {
                    isShown = true;
                    break;
                }
            }

            element.hidden = !isShown;
        });
    }

    /**
     * @protected
     * @param {dlf.PageObject} pageObj
     * @param {string} section
     */
    shouldShowSection(pageObj, section) {
        switch (this.config.rootline) {
            case dlfRootline.None:
            default:
                return section === pageObj.logSections[0];

            case dlfRootline.All:
                return pageObj.logSections.includes(section);

            case dlfRootline.Titledata:
                return section === pageObj.logSections[pageObj.logSections.length - 1];
        }
    }
}