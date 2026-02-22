(function () {
  'use strict';

  function escHtml(str) {
    return String(str || '').replace(/[&<>"']/g, c =>
      ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])
    );
  }

  function render(repos) {
    var root = document.getElementById('ghr-root');

    if (!repos.length) {
      root.innerHTML = '<p class="ghr-empty">Nessuna repository trovata.</p>';
      return;
    }

    root.innerHTML =
      '<div class="ghr-grid">' +
      repos.map(function (r) {
        var desc = r.description
          ? '<p class="ghr-desc">' + escHtml(r.description) + '</p>'
          : '<p class="ghr-desc ghr-desc--empty">No description</p>';

        return (
          '<a class="ghr-card" href="' + escHtml(r.html_url) + '" target="_blank" rel="noopener">' +
            '<div class="ghr-card-inner">' +
              '<div class="ghr-card-top">' +
                '<span class="ghr-icon">⬡</span>' +
                '<span class="ghr-name">' + escHtml(r.name) + '</span>' +
              '</div>' +
              desc +
              '<span class="ghr-link">View on GitHub →</span>' +
            '</div>' +
          '</a>'
        );
      }).join('') +
      '</div>';
  }

  function renderError(msg) {
    document.getElementById('ghr-root').innerHTML =
      '<p class="ghr-error">⚠ ' + escHtml(msg) + '</p>';
  }

  function renderLoading() {
    document.getElementById('ghr-root').innerHTML =
      '<div class="ghr-loading"><span class="ghr-spinner"></span> Loading repositories…</div>';
  }

  function init() {
    renderLoading();

    var url = new URL(window.ghrConfig.ajaxUrl);
    url.searchParams.set('action', 'ghr_get_repos');
    url.searchParams.set('nonce',  window.ghrConfig.nonce);

    fetch(url.toString(), { method: 'GET', credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (data) {
        if (!data.success) throw new Error(data.data || 'Errore sconosciuto');
        render(data.data);
      })
      .catch(function (err) { renderError(err.message); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
