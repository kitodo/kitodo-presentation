/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// Abort the validation request if the server does not answer within this
// time (in milliseconds), so the user is not left with a spinner forever.
const dlfValidationRequestTimeout = 60000;

const dlfValidationForms = document.querySelectorAll('.tx-dlf-validationform form');

/**
 * Get the data from an URL.
 *
 * @param {string} url to get data for
 * @returns {Promise} the response as json
 */
async function getData(url) {
  const response = await fetch(url);
  const data = await response.json()
  let success = true;
    if (!response.ok) {
      success = false;
    }
  return {success: success, content: data};
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
     * Create the messages' container.
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

    const form = event.target.parentElement;
    const submitButton = event.target.querySelector('.submit');

    /**
     * Show the given error message below the form.
     *
     * @param {string} message The message to show
     */
    function showError(message) {
      let validation = form.querySelector('.validation');
      if (validation) {
        form.removeChild(validation);
      }
      validation = document.createElement('div');
      validation.classList.add("validation");
      validation.appendChild(createMessagesContainer('error', [message], validationForm.dataset.i18nHeadlineError));
      form.appendChild(validation);
    }

    // a new request aborts a pending one so the requests do not stack
    let controller = new AbortController();
    const abortTimer = window.setTimeout(() => controller.abort(), dlfValidationRequestTimeout);
    // lock the form so repeated clicks do not start parallel requests
    if (submitButton) {
      submitButton.disabled = true;
    }

    let dataUrl = this.action + '&validationType=' + encodeURIComponent(data.validationType) + '&url=' + encodeURIComponent(data.url);
    if (data.enableValidator && Array.isArray(data.enableValidator)) {
      dataUrl += '&enableValidators=' + encodeURIComponent(data.enableValidator.join(','));
    }

    const loader = buildLoader(event.target);

    fetch(dataUrl, {signal: controller.signal})
        .then(response => {
          return response.json().catch(() => {
            // a non-JSON response (e.g. an HTML error page) must not leave
            // the loader running: report it like a plain error
            throw new TypeError('The server did not respond with JSON (status ' + response.status + ').');
          }).then(data => ({ok: response.ok, data: data}));
        })
        .then(({ok, data}) => {
          let validation = form.querySelector('.validation');
          if (validation) {
            form.removeChild(validation);
          }
          validation = document.createElement('div');
          validation.classList.add("validation");
          if (ok) {
            data.forEach(item => {
              validation.appendChild(createValidationEntry(item));
            });
          } else {
            validation.appendChild(createMessagesContainer('error', [data], validationForm.dataset.i18nHeadlineError));
          }
          form.appendChild(validation);
        })
        .catch(error => {
          if (error && error.name === 'AbortError') {
            showError(validationForm.dataset.i18nTimeout);
          } else {
            showError(validationForm.dataset.i18nError);
          }
        })
        .finally(() => {
          window.clearTimeout(abortTimer);
          if (submitButton) {
            submitButton.disabled = false;
          }
          loader.remove();
        });
  });
});
