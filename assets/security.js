(function(){
  var csrfToken = '';

  window.escapeHTML = function(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  window.ensureCsrfToken = async function() {
    if(csrfToken) return csrfToken;
    var res = await fetch('api/auth/session.php', { credentials: 'same-origin' });
    var data = await res.json();
    csrfToken = data.csrf_token || '';
    return csrfToken;
  };

  window.apiFetch = async function(url, options) {
    options = options || {};
    var method = String(options.method || 'GET').toUpperCase();
    options.credentials = options.credentials || 'same-origin';
    if(method !== 'GET' && method !== 'HEAD') {
      var token = await window.ensureCsrfToken();
      options.headers = Object.assign({}, options.headers || {}, { 'X-CSRF-Token': token });
    }
    return fetch(url, options);
  };

  window.setCsrfToken = function(token) {
    csrfToken = token || csrfToken;
  };
})();
