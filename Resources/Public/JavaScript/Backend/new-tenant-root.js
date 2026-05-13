(function () {
  const refreshPageTree = function () {
    const moduleBody = document.querySelector('.t3js-module-body[data-refresh-page-tree="1"]');
    if (!moduleBody || !window.top || !window.top.document) {
      return;
    }

    window.top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));

    const url = new URL(window.location.href);
    url.searchParams.delete('refreshPageTree');
    window.history.replaceState(window.history.state, document.title, url.toString());
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshPageTree, { once: true });
    return;
  }

  refreshPageTree();
})();
