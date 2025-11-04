"use strict";

(function ($) {
    function track_inspiration_event() {
        const $section = $("section.zone-slider").first();
        if ($section.length === 0) return;

        const $zoneBtn = $section.find(".zone-btn");
        const $nextBtn = $section.find("#nextButton, #next2Button");
        const $slider  = $section.find("#slider");
        const $slides  = $slider.find(".slide");

        if ($slider.length === 0 || $slides.length === 0) return;

        const COOKIE_PREFIX = "insp_";

        // --- Cookie Helpers -----------------------------------------------------
        const setCookie = function (name, value) {
            // Session cookie (auto-deleted when browser closes)
            document.cookie = `${COOKIE_PREFIX + name}=${value}; path=/; SameSite=Lax`;
        };

        const getCookie = function (name) {
            const cname = COOKIE_PREFIX + name + "=";
            const decoded = decodeURIComponent(document.cookie);
            const parts = decoded.split(';');
            for (let i = 0; i < parts.length; i++) {
                let c = parts[i].trim();
                if (c.indexOf(cname) === 0) return c.substring(cname.length, c.length);
            }
            return null;
        };

        const hasCookie = (name) => !!getCookie(name);

        // --- Helpers ------------------------------------------------------------
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

        const setActive = function (idx) {
            $slides.removeClass("active").eq(idx).addClass("active");
        };

        const getActiveIndex = function () {
            const i = $slides.index($slides.filter(".active").first());
            return i >= 0 ? i : 0;
        };

        // --- State --------------------------------------------------------------
        let currentIndex = getActiveIndex();
        const lastIndex  = $slides.length - 1;

        const logOnce = function (label, key, meta) {
            if (!hasCookie(key)) {
                console.log("Trigger:", label);
                sendLog(label, meta);
                setCookie(key, "1");
            }
        };

        // --- Events -------------------------------------------------------------
        $zoneBtn.on("click", function () {
            const $active = $slides.eq(currentIndex);
            const dataId = $active.attr("data-id") || "";
            logOnce("Opened", "opened", { index: currentIndex, dataId });
        });

        $nextBtn.on("click", function (e) {
            const isAnchor = this.tagName && this.tagName.toLowerCase() === "a";
            if (isAnchor) e.preventDefault();

            if (currentIndex < lastIndex) currentIndex += 1;
            setActive(currentIndex);

            const $active = $slides.eq(currentIndex);
            const dataId = $active.attr("data-id") || "";

            if (currentIndex === 1) {
                logOnce("second_product", "second", { index: currentIndex, dataId });
            }
            if (currentIndex === lastIndex) {
                logOnce("all_products", "all", { index: currentIndex, total: $slides.length, dataId });
            }
        });

        // Edge case: already on last slide
        if (currentIndex === lastIndex && !hasCookie("all")) {
            const $active = $slides.eq(currentIndex);
            const dataId = $active.attr("data-id") || "";
            logOnce("all_products", "all", { index: currentIndex, total: $slides.length, dataId });
        }
    }

    $(document).ready(function () {
        track_inspiration_event();
    });
})(jQuery);
