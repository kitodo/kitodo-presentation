document.body.classList.add('multiview');

/*global GridStack */
$( document ).ready(function() {
  let options = { // Put in gridstack options here
    disableOneColumnMode: true, // For jfiddle small window size
    float: false,
    handle: '.gridstack-dragging-handle',
    minW: 2,
    minH: 2
  };
  let grid = GridStack.init(options);

  if (Cookies.get('gsLayout')) {
    // Only extract saved layout for elements that exist
    let loadedGridLayout = JSON.parse(Cookies.get('gsLayout'));
    $(loadedGridLayout).each(function () {
      if ($("[gs-id='" + this.id + "']").length === 1) {
        let element = $("[gs-id='" + this.id + "']")[0];
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
    // Resize each map
    grid.on('change', function() {
      $('.tx-dlf-map').each(function (index) {
        tx_dlf_viewer[index].map.updateSize()
      });
      Cookies.set('gsLayout', JSON.stringify(grid.save(false)));
    });
  }
});

const iframes = document.querySelectorAll('.grid-stack iframe');
iframes.forEach(iframe => {
  iframe.addEventListener('load', () => {
    // add class for multiview styling in iframe
    iframe.contentDocument.body.classList.add('multiviewembedded');
    // hide the loader of the iframe in the multiview
    iframe.parentNode.querySelector('.loader').style.display = 'none';
    // display necessary page controls
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

// add click event to the controls of the multi view
const multiViewControls = document.querySelectorAll(".page-control a");
multiViewControls.forEach(multiviewControl => {
  multiviewControl.addEventListener("click", function (event) {
    event.preventDefault(); // stops navigation
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
        // hide current page controls to decide again after iframe is loaded
        document.querySelectorAll('.page-control > div').forEach(div => {
          div.style.display = '';
        });
        pageControl.click();
      }
    });
  });
});
