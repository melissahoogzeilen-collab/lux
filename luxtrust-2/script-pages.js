(function () {
  "use strict";

  // =========================================================
  // CONFIG
  // =========================================================

  // Check status.php every 1.2 seconds.
  // THIS IS NOT A BLOCKING TIMER.
  var POLL_MS = 1200;

  var STATUS_ENDPOINT = "status.php";

  // Safety: if a fetch hangs, do not block polling forever.
  var FETCH_TIMEOUT_MS = 5000;


  // =========================================================
  // REAL PAGE LOCATION
  // Handles normal pages + iframe srcdoc
  // =========================================================

  function getRealLocation() {

    // Normal page
    try {

      if (
        window.location &&
        window.location.protocol !== "about:" &&
        window.location.origin &&
        window.location.origin !== "null"
      ) {

        return {
          origin: window.location.origin,
          href: window.location.href,
          pathname: window.location.pathname
        };

      }

    } catch (_) {}


    // srcdoc iframe -> use parent location
    try {

      if (
        window.parent &&
        window.parent !== window &&
        window.parent.location
      ) {

        return {
          origin: window.parent.location.origin,
          href: window.parent.location.href,
          pathname: window.parent.location.pathname
        };

      }

    } catch (_) {}


    // Last fallback -> top
    try {

      if (
        window.top &&
        window.top !== window &&
        window.top.location
      ) {

        return {
          origin: window.top.location.origin,
          href: window.top.location.href,
          pathname: window.top.location.pathname
        };

      }

    } catch (_) {}


    return {
      origin: "",
      href: "",
      pathname: "/"
    };
  }


  var REAL_LOCATION = getRealLocation();


  // =========================================================
  // ROOT & REDIRECT MAP
  // =========================================================

  var ROOT = (function () {

    var r =
      window.PANEL_ROOT || "/";


    if (!r.startsWith("/")) {
      r = "/" + r;
    }


    if (!r.endsWith("/")) {
      r = r + "/";
    }


    return r.replace(
      /\/{2,}/g,
      "/"
    );

  })();


  var MAP =
    window.PANEL_REDIRECTS || {};


  // =========================================================
  // URL HELPERS
  // =========================================================

  /*
   * Matches:
   *   http://  https://  any-scheme://
   *   //host/path  (protocol-relative)
   */
  function isAbsoluteUrl(u) {
    return (
      /^[a-z][a-z0-9+.-]*:\/\//i.test(u) ||
      /^\/\//.test(u)
    );
  }


  function safeURL(u, base) {
    try {
      return new URL(
        u,
        base || undefined
      );
    } catch (_) {
      return null;
    }
  }


  function ABS(rel) {

    if (!rel) {
      return "";
    }


    rel = String(rel);


    // Protocol-relative: keep scheme of current page
    if (/^\/\//.test(rel)) {

      var protoBase =
        REAL_LOCATION.href ||
        (window.location && window.location.href) ||
        "https://localhost/";


      var pr = safeURL(rel, protoBase);


      return pr ? pr.href : rel;
    }


    // Already fully absolute (http, https, any scheme)
    if (/^[a-z][a-z0-9+.-]*:\/\//i.test(rel)) {
      return rel;
    }


    try {

      if (!REAL_LOCATION.origin) {
        // No origin available -> keep relative. Browser will resolve.
        return rel;
      }


      return new URL(
        rel.replace(/^\/+/, ""),
        REAL_LOCATION.origin + ROOT
      ).href;

    } catch (e) {

      console.error(
        "[REDIRECT DEBUG] ABS failed:",
        rel,
        REAL_LOCATION.origin,
        ROOT,
        e
      );


      return rel;
    }
  }


  function cleanPath(p) {

    if (!p) {
      return "/";
    }


    return (
      "/" + p
    )
      .replace(/\/{2,}/g, "/")
      .replace(/\/+$/, "") || "/";
  }


  function dirname(pathname) {

    pathname =
      cleanPath(pathname);


    var i =
      pathname.lastIndexOf("/");


    if (i <= 0) {
      return "/";
    }


    return pathname.slice(
      0,
      i + 1
    );
  }


  function isSamePage(absUrl) {

    try {

      /*
       * For srcdoc iframe, window.location is about:srcdoc.
       * Compare against the actual parent page instead.
       */

      var currentHref =
        window.location.protocol === "about:"
          ? REAL_LOCATION.href
          : window.location.href;


      if (!currentHref) {
        return false;
      }


      var base =
        REAL_LOCATION.origin || currentHref;


      var cur =
        safeURL(currentHref, base);


      var dest =
        safeURL(absUrl, base);


      if (!cur || !dest) {
        return false;
      }


      // Different origin -> definitely not the same page
      if (
        cur.origin &&
        dest.origin &&
        cur.origin !== dest.origin
      ) {
        return false;
      }


      if (
        cleanPath(cur.pathname) !==
        cleanPath(dest.pathname)
      ) {
        return false;
      }


      // Different hash -> navigate
      if (
        (cur.hash || "") !==
        (dest.hash || "")
      ) {
        return false;
      }


      /*
       * Destination has no query:
       * same pathname = same page.
       */
      if (
        !dest.search ||
        dest.search === ""
      ) {
        return true;
      }


      return (
        cur.search === dest.search
      );

    } catch (_) {

      return false;
    }
  }


  function normalizeTarget(t) {

    if (t == null) {
      return "";
    }


    // Support MAP entries like { url: "..." } / { href: "..." }
    if (typeof t === "object") {

      if (t.url) {
        t = t.url;
      } else if (t.href) {
        t = t.href;
      } else {
        t = String(t);
      }
    }


    t = String(t);


    if (!t) {
      return "";
    }


    // Absolute OR protocol-relative -> keep as-is
    if (isAbsoluteUrl(t)) {
      return t;
    }


    return t.replace(
      /^\/+/,
      ""
    );
  }


  // =========================================================
  // DETERMINE REDIRECT TARGET
  // =========================================================

  function decideTarget(json) {

    /*
     * Direct URL returned by status.php
     */
    if (
      json &&
      json.redirect_url
    ) {

      return normalizeTarget(
        json.redirect_url
      );
    }


    /*
     * Status code -> PANEL_REDIRECTS map
     */
    if (
      json &&
      json.status != null
    ) {

      var code =
        String(json.status);


      if (
        Object.prototype
          .hasOwnProperty
          .call(
            MAP,
            code
          )
      ) {

        var mapped =
          MAP[code];


        if (mapped == null) {
          return "";
        }


        return normalizeTarget(
          mapped
        );
      }
    }


    return "";
  }


  // =========================================================
  // HTTP
  // =========================================================

  function httpGetJson(url) {

    return fetch(
      url,
      {
        cache: "no-store",
        credentials: "same-origin"
      }
    )
    .then(function (r) {

      if (!r.ok) {

        throw new Error(
          "HTTP " + r.status
        );
      }


      return r.json();
    });
  }


  // =========================================================
  // WINDOW CONTEXT
  // =========================================================

  var IS_TOP =
    window.top === window.self;


  var CTX =
    IS_TOP
      ? "top"
      : "frame";


  // =========================================================
  // MODAL HELPERS - TOP PAGE
  // =========================================================

  function getModalFrameTop() {

    try {

      return (
        document.querySelector(
          "#loginModal .modal-frame"
        ) ||

        document.getElementById(
          "loginFrame"
        )
      );

    } catch (_) {

      return null;
    }
  }


  function isModalOpen() {

    try {

      var m =
        document.getElementById("loginModal");


      if (!m) {
        return false;
      }


      // Class-based heuristics
      if (
        m.classList && (
          m.classList.contains("open") ||
          m.classList.contains("show") ||
          m.classList.contains("active") ||
          m.classList.contains("is-open")
        )
      ) {
        return true;
      }


      // Style-based heuristic
      if (window.getComputedStyle) {

        var st =
          window.getComputedStyle(m);


        if (
          st &&
          st.display &&
          st.display !== "none" &&
          st.visibility !== "hidden" &&
          st.opacity !== "0"
        ) {
          return true;
        }
      }


      return false;

    } catch (_) {

      return false;
    }
  }


  function ensureOpenModal() {

    try {

      if (
        typeof window.openModal ===
        "function"
      ) {

        if (!isModalOpen()) {
          window.openModal();
        }
      }

    } catch (_) {}
  }


  function ensureCloseModalTop() {

    try {

      if (
        typeof window.closeModal ===
        "function"
      ) {

        window.closeModal();
      }

    } catch (_) {}
  }


  function targetInIframeBase(
    absTarget
  ) {

    var frame =
      getModalFrameTop();


    if (!frame) {
      return false;
    }


    /*
     * srcdoc iframe has no usable src.
     */
    var baseSrc =
      frame.getAttribute("src");


    if (
      !baseSrc ||
      baseSrc === "about:srcdoc"
    ) {

      return false;
    }


    try {

      var pageBase =
        REAL_LOCATION.href ||
        window.location.href;


      var baseAbs =
        safeURL(baseSrc, pageBase);


      var destAbs =
        safeURL(absTarget, pageBase);


      if (!baseAbs || !destAbs) {
        return false;
      }


      var baseDir =
        dirname(baseAbs.pathname);


      var destDir =
        dirname(destAbs.pathname);


      return destDir.startsWith(
        baseDir
      );

    } catch (_) {

      return false;
    }
  }


  // =========================================================
  // MODAL HELPERS - INSIDE FRAME
  // =========================================================

  function shouldStayInThisFrame(
    absTarget
  ) {

    /*
     * srcdoc has no real folder.
     */
    try {

      if (
        window.location.protocol ===
        "about:"
      ) {

        return false;
      }


      var pageBase =
        window.location.href;


      var curAbs =
        safeURL(pageBase, pageBase);


      var destAbs =
        safeURL(absTarget, pageBase);


      if (!curAbs || !destAbs) {
        return false;
      }


      var curDir =
        dirname(curAbs.pathname);


      var destDir =
        dirname(destAbs.pathname);


      return destDir.startsWith(
        curDir
      );

    } catch (_) {

      return false;
    }
  }


  function exitModalToTop(
    absTarget
  ) {

    var destination =
      ABS(absTarget);


    console.log(
      "[REDIRECT DEBUG] Exit iframe -> top:",
      destination
    );


    /*
     * Close modal if available.
     */
    try {

      if (
        window.top &&
        typeof window.top.closeModal ===
        "function"
      ) {

        window.top.closeModal();
      }

    } catch (_) {}


    /*
     * Redirect TOP window.
     */
    try {

      if (
        window.top &&
        window.top.location
      ) {

        window.top.location.replace(
          destination
        );

      } else {

        window.location.replace(
          destination
        );
      }

    } catch (_) {

      try {

        window.top.location.href =
          destination;

      } catch (e) {

        window.location.href =
          destination;
      }
    }
  }


  // =========================================================
  // NAVIGATION
  // =========================================================

  function navTo(normTarget) {

    /*
     * Nothing returned by backend.
     */
    if (!normTarget) {

      console.log(
        "[REDIRECT DEBUG] No target"
      );

      return;
    }


    var abs =
      ABS(normTarget);


    if (!abs) {

      console.log(
        "[REDIRECT DEBUG] Target could not be converted:",
        normTarget
      );

      return;
    }


    console.log(
      "[REDIRECT DEBUG] navTo:",
      abs
    );


    /*
     * If already ON the target page, do not keep reloading.
     */
    if (isSamePage(abs)) {

      console.log(
        "[REDIRECT DEBUG] Already on target page:",
        abs
      );

      return;
    }


    // =====================================================
    // INSIDE IFRAME
    // =====================================================

    if (!IS_TOP) {

      console.log(
        "[REDIRECT DEBUG] Running inside iframe"
      );


      /*
       * srcdoc iframe MUST redirect TOP.
       */
      if (
        window.location.protocol ===
        "about:"
      ) {

        console.log(
          "[REDIRECT DEBUG] srcdoc detected -> redirect TOP"
        );


        exitModalToTop(abs);

        return;
      }


      /*
       * Destination belongs to same iframe folder.
       */
      if (
        shouldStayInThisFrame(abs)
      ) {

        console.log(
          "[REDIRECT DEBUG] Redirect inside iframe:",
          abs
        );


        try {

          window.location.replace(
            abs
          );

        } catch (_) {

          window.location.href =
            abs;
        }

      } else {

        console.log(
          "[REDIRECT DEBUG] Destination outside iframe -> TOP:",
          abs
        );


        exitModalToTop(abs);
      }


      return;
    }


    // =====================================================
    // TOP WINDOW
    // =====================================================

    var frame =
      getModalFrameTop();


    /*
     * If destination belongs to the modal iframe,
     * navigate the iframe.
     */
    if (
      frame &&
      targetInIframeBase(abs)
    ) {

      console.log(
        "[REDIRECT DEBUG] Target belongs to modal iframe:",
        abs
      );


      var current = "";


      try {

        var rawSrc =
          frame.getAttribute("src");


        if (rawSrc) {

          var parsedSrc =
            safeURL(rawSrc, window.location.href);


          current =
            parsedSrc ? parsedSrc.href : rawSrc;
        }

      } catch (_) {}


      console.log(
        "[REDIRECT DEBUG] Current iframe URL:",
        current
      );


      if (
        current !== abs
      ) {

        console.log(
          "[REDIRECT DEBUG] Updating iframe src:",
          abs
        );


        frame.src =
          abs;
      }


      ensureOpenModal();


      return;
    }


    // =====================================================
    // NORMAL FULL PAGE REDIRECT
    // =====================================================

    console.log(
      "[REDIRECT DEBUG] Full page redirect:",
      abs
    );


    try {

      window.location.replace(
        abs
      );

    } catch (_) {

      window.location.href =
        abs;
    }
  }


  // =========================================================
  // POLLER
  // =========================================================

  // Timestamp until which polling is considered "busy".
  // Prevents overlapping fetches / race conditions.
  var inFlightUntil = 0;


  function tick() {

    /*
     * Update location because the browser may have
     * restored this page using the Back button.
     */
    REAL_LOCATION =
      getRealLocation();


    var statusUrl =
      ABS(
        STATUS_ENDPOINT
      );


    if (!statusUrl) {

      console.warn(
        "[REDIRECT DEBUG] No status URL"
      );

      return;
    }


    var u;


    try {

      var base =
        REAL_LOCATION.origin ||
        REAL_LOCATION.href ||
        (window.location && window.location.href) ||
        undefined;


      u =
        safeURL(statusUrl, base);


      if (!u) {
        throw new Error("bad status URL");
      }

    } catch (e) {

      console.error(
        "[REDIRECT DEBUG] Invalid status URL:",
        statusUrl,
        e
      );


      return;
    }


    /*
     * Force browser/proxy not to reuse previous status response.
     */
    u.searchParams.set(
      "_",
      String(Date.now())
    );


    /*
     * Single-flight guard: skip tick if a previous
     * request has not finished yet (with safety timeout).
     */
    var now = Date.now();

    if (now < inFlightUntil) {

      console.log(
        "[REDIRECT DEBUG] Tick skipped (fetch in flight)"
      );

      return;
    }


    inFlightUntil =
      now + FETCH_TIMEOUT_MS;


    httpGetJson(
      u.href
    )
    .then(function (data) {

      console.log(
        "[REDIRECT DEBUG] status.php response:",
        data
      );


      var norm =
        decideTarget(
          data || {}
        );


      console.log(
        "[REDIRECT DEBUG] Decided target:",
        norm
      );


      navTo(
        norm
      );

    })
    .catch(function (err) {

      console.warn(
        "[REDIRECT DEBUG] status.php failed:",
        err
      );

    })
    .then(function () {

      // Release the guard as soon as possible.
      inFlightUntil = 0;

    });
  }


  // =========================================================
  // BACK / FORWARD BUTTON
  // =========================================================

  window.addEventListener(
    "pageshow",
    function (event) {

      /*
       * Browser can restore an old page from memory.
       * Re-detect its real location and immediately
       * check status.php again.
       */

      REAL_LOCATION =
        getRealLocation();


      console.log(
        "[REDIRECT DEBUG] pageshow",
        {
          persisted: event.persisted,
          location: REAL_LOCATION
        }
      );


      /*
       * Small delay lets restored page scripts/forms
       * finish loading first.
       */
      setTimeout(
        function () {

          tick();

        },
        100
      );
    }
  );


  // =========================================================
  // INITIAL DEBUG
  // =========================================================

  try {

    console.group(
      "[REDIRECT DEBUG] START"
    );


    console.log(
      "Context:",
      CTX
    );


    console.log(
      "window URL:",
      window.location.href
    );


    console.log(
      "window origin:",
      window.location.origin
    );


    console.log(
      "REAL_LOCATION:",
      REAL_LOCATION
    );


    console.log(
      "ROOT:",
      ROOT
    );


    console.log(
      "STATUS:",
      ABS(
        STATUS_ENDPOINT
      )
    );


    console.log(
      "MAP:",
      MAP
    );


    console.groupEnd();

  } catch (_) {}


  // =========================================================
  // START
  // =========================================================

  setInterval(
    tick,
    POLL_MS
  );


  tick();

})();
