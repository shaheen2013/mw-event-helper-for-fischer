"use strict";

(function ($) {
  function track_inspiration_event() {
    const $section = $("section.zone-slider").first();
    if ($section.length === 0) return;

    const $zoneBtn = $section.find(".zone-btn");
    const $nextBtn = $section.find("#nextButton, #next2Button");
    const $slider = $section.find("#slider");
    const $slides = $slider.find(".slide");
    const $activeLinks = $(".active-link .elen");
    const $popupBtns = $("a.popup-btn");

    if ($slider.length === 0 || $slides.length === 0) return;

    const COOKIE_PREFIX = "insp_";

    // ---------------- Cookie Helpers ----------------
    const setCookie = (name, value) => {
      document.cookie = `${COOKIE_PREFIX + name}=${value}; path=/; SameSite=Lax`;
    };

    const getCookie = (name) => {
      const cname = COOKIE_PREFIX + name + "=";
      const decoded = decodeURIComponent(document.cookie);
      const parts = decoded.split(";");
      for (let i = 0; i < parts.length; i++) {
        let c = parts[i].trim();
        if (c.indexOf(cname) === 0)
          return c.substring(cname.length, c.length);
      }
      return null;
    };

    const hasCookie = (name) => !!getCookie(name);

    // ---------------- Logging Helper ----------------
    const sendLog = function (trackerName, metaObj) {
      console.info("Log:", trackerName, metaObj);
      $.ajax({
        url: mwhpJSObj.ajax_url,
        method: "POST",
        data: {
          action: "mwhp_log_inspiration",
          nonce: mwhpJSObj.nonce,
          tracker_name: String(trackerName || "").toUpperCase(),
          meta: JSON.stringify(metaObj || {}),
        },
      });
    };

    // ---------------- Slider State ----------------
    const setActive = (idx) => $slides.removeClass("active").eq(idx).addClass("active");
    const getActiveIndex = () => {
      const i = $slides.index($slides.filter(".active").first());
      return i >= 0 ? i : 0;
    };

    let currentIndex = getActiveIndex();
    const lastIndex = $slides.length - 1;
    const halfIndex = Math.floor($slides.length / 2);

    // ---------------- Log Once Helper ----------------
    const logOnce = (label, key, meta) => {
      if (!hasCookie(key)) {
        sendLog(label, meta);
        setCookie(key, "1");
      }
    };

    // ---------------- Event Bindings ----------------

    // When first zone button clicked (OPENED)
    $zoneBtn.on("click", function () {
      logOnce("OPENED", "opened", { i: currentIndex });
    });

    // When next button clicked
    $nextBtn.on("click", function (e) {
      const isAnchor = this.tagName && this.tagName.toLowerCase() === "a";
      if (isAnchor) e.preventDefault();

      if (currentIndex < lastIndex) currentIndex += 1;
      setActive(currentIndex);

      // second product view
      if (currentIndex === 1) {
        logOnce("SECOND_PRODUCT", "second", { i: currentIndex });
      }

      // reached end
      if (currentIndex === lastIndex) {
        logOnce("ALL_PRODUCTS", "all", { i: currentIndex });
      }

      // half viewed
      if (currentIndex >= halfIndex) {
        logOnce("HALF_VIEWED", "half_viewed", { i: currentIndex });
      }
    });

    // Edge case — already at last slide
    if (currentIndex === lastIndex && !hasCookie("all")) {
      logOnce("ALL_PRODUCTS", "all", { i: currentIndex });
    }

    // Product icon click — OPEN_PRODUCT_PAGE
    $activeLinks.on("click", function () {
      logOnce("OPEN_PRODUCT_PAGE", "open_product_page", { href: $(this).attr("href") });
    });

    // Popup buttons — USER_LEFT
    $popupBtns.on("click", function () {
      const isCloseButton = $(this).hasClass("close-button");

      // if (hasCookie("all")) return;

      if (isCloseButton) {
        logOnce("USER_LEFT", "user_left", { id: $(this).attr("id") });
      }
    });
  }

  $(document).ready(function () {
    track_inspiration_event();
  });
})(jQuery);
