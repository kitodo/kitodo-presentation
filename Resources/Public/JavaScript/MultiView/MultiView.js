/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/*global GridStack, Cookies */

$( document ).ready(function() {
  const options = { // Put in gridstack options here
    disableOneColumnMode: true, // For jfiddle small window size
    float: false,
    handle: '.gridstack-dragging-handle',
    minW: 2,
    minH: 2
  };
  const grid = GridStack.init(options);

  if (Cookies.get('gsLayout')) {
    // Only extract saved layout for elements that exist
    const loadedGridLayout = JSON.parse(Cookies.get('gsLayout'));
    $(loadedGridLayout).each(function () {
      const elements = $("[gs-id='" + this.id + "']");
      if (elements.length === 1) {
        const element = elements[0];
        grid.update(element, this);
      }
    });
  }

  $('.reset-gridstack-layout').on('click', function () {
    Cookies.set('gsLayout', '');
    location.reload();
    return false;
  });

  if (grid) {
    grid.on('change', function() {
      Cookies.set('gsLayout', JSON.stringify(grid.save(false)));
    });
  }
});

const iframes = document.querySelectorAll('.grid-stack iframe');
iframes.forEach(iframe => {
  iframe.addEventListener('load', () => {
    // Hide the loader of the iframe in the multiview
    iframe.parentNode.querySelector('.loader').style.display = 'none';
    // Display necessary page controls
    document.querySelectorAll('.page-control > div a').forEach(link => {
      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
      const pageControl = iframeDoc.querySelector('.page-control ' + link.dataset.pageControlSelector);
      const div = link.closest('div');
      if(pageControl) {
        div.style.display = 'block';
      }
    });
  });
});

// Add click event to the controls of the multi view
const multiViewControls = document.querySelectorAll(".page-control a");
multiViewControls.forEach(multiviewControl => {
  multiviewControl.addEventListener("click", function (event) {
    event.preventDefault(); // Stops navigation
    const clicked = event.currentTarget;

    const loadedIframes = Array.from(document.querySelectorAll('.grid-stack iframe')).filter(iframe => {
      try {
        // Accessing contentDocument throws an error if cross-origin
        return iframe.contentDocument && iframe.contentDocument.readyState === 'complete';
      } catch (e) {
        return false;
      }
    });

    loadedIframes.forEach(iframe => {
      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
      const pageControl = iframeDoc.querySelector('.page-control ' + clicked.dataset.pageControlSelector);
      if(pageControl) {
        // Hide current page controls to decide again after iframe is loaded
        document.querySelectorAll('.page-control > div').forEach(div => {
          div.style.display = '';
        });
        pageControl.click();
      }
    });
  });
});
