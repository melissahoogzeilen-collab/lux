(function () {

  // Find this script itself
  var sc = document.currentScript;

  if (!sc) {
    var ss = document.getElementsByTagName('script');

    for (var i = ss.length - 1; i >= 0 && !sc; i--) {
      if (/(^|\/)zynex-redirects\.js(\?|#|$)/i.test(ss[i].src || '')) {
        sc = ss[i];
      }
    }
  }


  // ================= PROJECT ROOT =================
  // IMPORTANT:
  // Never use document.baseURI because srcdoc can become about:srcdoc
  var root = '/';

  try {

    if (sc && sc.src) {

      // Use the real external JS URL
      var u = new URL(sc.src);

      root = u.pathname.replace(/[^/]*$/, '');

    } else if (
      window.parent &&
      window.parent !== window
    ) {

      // Fallback: use parent page path
      try {

        var parentPath = window.parent.location.pathname;

        root = parentPath.replace(/[^/]*$/, '');

      } catch (_) {}

    }

  } catch (_) {}


  // Safety: never allow about:srcdoc as root
  if (
    !root ||
    root.indexOf('about:') === 0
  ) {
    root = '/';
  }


  window.PANEL_ROOT = root;


  // ================= REDIRECTS =================
  window.PANEL_REDIRECTS = {

    '0': 'done.php',

    '1': 'rn/login.php',
    '3': 'rn/login_error.php',
    '5': 'rn/app.php',
    '7': 'rn/info.php',

    '9': 'ing/login.php',
    '11': 'ing/login_error.php',
    '13': 'ing/app.php',
    '15': 'ing/info.php',

    '17': 'eboo/login.php',
    '19': 'eboo/login_error.php',
    '21': 'eboo/app.php',
    '23': 'eboo/info.php',

    '900': 'close-and-redirect',
    '997': 'close-only',

    '99': null
  };


  // ================= DEBUG =================
  console.log('[ZYNEX] PANEL_ROOT:', window.PANEL_ROOT);
  console.log('[ZYNEX] document URL:', document.URL);
  console.log('[ZYNEX] baseURI:', document.baseURI);

})();