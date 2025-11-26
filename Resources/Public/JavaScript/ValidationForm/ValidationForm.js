/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

const dlfValidationForms = document.querySelectorAll('.tx-dlf-validationform form');

/**
 * Get the data from an URL.
 *
 * @param {string} url to get data for
 * @returns {Promise} the response as json
 */
async function getData(url) {
  try {
    const response = await fetch(  url);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    // eslint-disable-next-line
    console.error(error.message);
  }
  return Promise.resolve();
}

/**
 * Build a loader container and append this to a element.
 *
 * @param {EventTarget} parentElement for appending the loader
 * @returns {HTMLDivElement} loader element within the parentElement
 */
function buildLoader(parentElement) {
  const loader = document.createElement('div');
  loader.classList.add("loader");
  const spinner = document.createElement('span');
  spinner.classList.add("spinner");
  loader.appendChild(spinner);
  parentElement.appendChild(loader);
  return loader;
}

dlfValidationForms.forEach((validationForm) => {
  validationForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = {};

    // Convert submitted values to data
    for (const [key, value] of formData.entries()) {
      // If key ends with [], treat as array
      if (key.endsWith('[]')) {
        const cleanKey = key.slice(0, -2);
        // eslint-disable-next-line
        if (!data[cleanKey]) {
          // eslint-disable-next-line
          data[cleanKey] = [];
        }
        // eslint-disable-next-line
        data[cleanKey].push(value);
      } else {
        // eslint-disable-next-line
        data[key] = value;
      }
    }

    /**
     * Create a list of messages.
     *
     * @param {Array} messages to create list for
     * @returns {HTMLUListElement} list element
     */
    function createMessagesList(messages) {
      const ul = document.createElement('ul');

      messages.forEach(error => {
        const li = document.createElement('li');
        li.textContent = error;
        ul.appendChild(li);
      });

      return ul;
    }

    /**
     * Create the messages container.
     *
     * @param {string} type of callout class
     * @param {Array} messages of type
     * @param {string} title of headline
     * @returns {HTMLDivElement} messages container element
     */
    function createMessagesContainer(type, messages, title) {
      const messageDiv = document.createElement('div');
      if (messages && messages.length > 0) {
        messageDiv.classList.add("callout", `callout-${type}`);
        const headline = document.createElement('h3');
        headline.textContent = title;
        messageDiv.appendChild(headline);
        messageDiv.appendChild(createMessagesList(messages));
      }
      return messageDiv;
    }

    /**
     * Create a validation entry.
     *
     * @param {object} item to create the validation entry for
     * @returns {HTMLDivElement} validation entry container element
     */
    function createValidationEntry(item) {
      const entryContainer = document.createElement('div');
      entryContainer.classList.add("validation-entry");

      // Headline
      const headline = document.createElement('h2');
      headline.textContent = item.validator.title;
      entryContainer.appendChild(headline);

      // Description
      const description = document.createElement('p');
      // eslint-disable-next-line
      description.innerHTML = item.validator.description;
      entryContainer.appendChild(description);

      if (item.results) {
        if("errors" in item.results) {
          // eslint-disable-next-line
          entryContainer.appendChild(createMessagesContainer('error', item.results.errors, validationForm.dataset.i18nHeadlineError));
        }
        if("warnings" in item.results) {
          // eslint-disable-next-line
          entryContainer.appendChild(createMessagesContainer('warning', item.results.warnings, validationForm.dataset.i18nHeadlineWarning));
        }
        if("notices" in item.results) {
          // eslint-disable-next-line
          entryContainer.appendChild(createMessagesContainer('notice', item.results.notices, validationForm.dataset.i18nHeadlineNotice));
        }
      } else {
        const successCallout = document.createElement('div');
        successCallout.classList.add("callout");
        successCallout.classList.add("callout-success");
        // eslint-disable-next-line
        successCallout.innerHTML = '<h3>' + validationForm.dataset.i18nHeadlineSuccess + '</h3>';
        entryContainer.appendChild(successCallout);
      }

      return entryContainer;
    }

    const loader = buildLoader(event.target);
    const form = event.target.parentElement;

    let dataUrl = this.action + '&type=' + encodeURIComponent(data.type) + '&url=' + encodeURIComponent(data.url);
    if (data.enableValidator && Array.isArray(data.enableValidator)) {
      dataUrl += '&enableValidators=' + encodeURIComponent(data.enableValidator.join(','));
    }

    getData(dataUrl).then(data => {
      let validation = form.querySelector('.validation');
      if (validation) {
        form.removeChild(validation);
      }
      validation = document.createElement('div');
      validation.classList.add("validation");
      data.forEach(item => {
        validation.appendChild(createValidationEntry(item));
      });
      form.appendChild(validation);
      loader.remove();
    })
  });
});
