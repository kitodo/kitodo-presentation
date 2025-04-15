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

async function getData(url) {
  try {
    const response = await fetch(  url);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error(error.message);
  }
}

function buildLoader(parentElement) {
  let loader = document.createElement('div');
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

    // Convert submitted values to data
    const data = Object.fromEntries(new FormData(event.target).entries());

    // Function to create a list of messages
    function createMessagesList(messages) {
      const ul = document.createElement('ul');

      messages.forEach(error => {
        const li = document.createElement('li');
        li.textContent = error;
        ul.appendChild(li);
      });

      return ul;
    }

    function createMessagesContainer(type, messages, title) {
      if (messages && messages.length > 0) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add("callout", `callout-${type}`);

        const headline = document.createElement('h3');
        headline.textContent = title;

        messageDiv.appendChild(headline);
        messageDiv.appendChild(createMessagesList(messages));
        return messageDiv;
      }
    }

    // Function to create a full validation entry
    function createValidationEntry(item) {
      const entryContainer = document.createElement('div');
      entryContainer.classList.add("validation-entry");

      // Headline
      const headline = document.createElement('h2');
      headline.textContent = item.validator.title;
      entryContainer.appendChild(headline);

      // Description
      const description = document.createElement('p');
      description.innerHTML = item.validator.description;
      entryContainer.appendChild(description);

      if (item.results) {
        if("errors" in item.results) {
          entryContainer.appendChild(createMessagesContainer('error', item.results.errors, validationForm.dataset.i18nHeadlineError));
        }
        if("warnings" in item.results) {
          entryContainer.appendChild(createMessagesContainer('warning', item.results.warnings, validationForm.dataset.i18nHeadlineWarning));
        }
        if("notices" in item.results) {
          entryContainer.appendChild(createMessagesContainer('notice', item.results.notices, validationForm.dataset.i18nHeadlineNotice));
        }
      } else {
        const successCallout = document.createElement('div');
        successCallout.classList.add("callout");
        successCallout.classList.add("callout-success");
        successCallout.innerHTML = '<h3>' + validationForm.dataset.i18nHeadlineSuccess + '</h3>';
        entryContainer.appendChild(successCallout);
      }

      return entryContainer;
    }

    let loader = buildLoader(event.target);
    const form = event.target.parentElement;

    getData(this.action + '&type=' + encodeURI(data.type) + '&url=' + encodeURI(data.url)).then(data => {
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
