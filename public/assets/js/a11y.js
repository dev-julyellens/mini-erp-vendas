(function () {
    "use strict";

    function syncDocumentTitle() {
        var appName = document.body && document.body.dataset.appName;
        var heading = document.querySelector(".page-title") || document.querySelector(".auth-card h1");
        if (!heading || !appName) {
            return;
        }
        var page = heading.textContent.trim();
        if (page !== "") {
            document.title = page + " — " + appName;
        }
    }

    function announce(message) {
        var el = document.getElementById("srAnnounce");
        if (!el || !message) {
            return;
        }
        el.textContent = "";
        window.setTimeout(function () {
            el.textContent = message;
        }, 50);
    }

    function initSkipLink() {
        var skip = document.querySelector(".skip-link");
        var target = document.getElementById("mainContent") || document.getElementById("authMain");
        if (!skip || !target) {
            return;
        }
        skip.addEventListener("click", function (ev) {
            ev.preventDefault();
            target.focus({ preventScroll: false });
        });
    }

    function initIconOnlyButtons() {
        document.querySelectorAll("button, a.btn").forEach(function (btn) {
            if (btn.getAttribute("aria-label") || btn.getAttribute("aria-labelledby")) {
                return;
            }
            var text = btn.textContent.replace(/\s+/g, " ").trim();
            if (text !== "") {
                return;
            }
            var icon = btn.querySelector("i.bi");
            if (!icon) {
                return;
            }
            var title = btn.getAttribute("title");
            if (title) {
                btn.setAttribute("aria-label", title);
            }
        });
    }

    function enhanceDataTable(table) {
        if (!table || !table.classList.contains("js-datatable")) {
            return;
        }

        var label = table.getAttribute("aria-label");
        if (!label) {
            var heading = document.querySelector(".page-title") || document.querySelector(".auth-card h1");
            if (heading) {
                label = "Tabela: " + heading.textContent.trim();
                table.setAttribute("aria-label", label);
            }
        }

        var wrapper = table.closest(".dataTables_wrapper");
        if (!wrapper) {
            return;
        }

        var filterInput = wrapper.querySelector(".dataTables_filter input");
        if (filterInput) {
            var searchLabel = label ? "Buscar em " + label.replace(/^Tabela:\s*/, "") : "Buscar na tabela";
            filterInput.setAttribute("aria-label", searchLabel);
        }

        var info = wrapper.querySelector(".dataTables_info");
        if (info) {
            info.setAttribute("role", "status");
            info.setAttribute("aria-live", "polite");
        }
    }

    function initDataTablesA11y() {
        document.querySelectorAll("table.js-datatable").forEach(enhanceDataTable);
    }

    function initDashboardTabKeyboard() {
        var root = document.getElementById("dashboardRoot");
        if (!root) {
            return;
        }
        var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"][data-dash-tab]'));
        if (!tabs.length) {
            return;
        }

        tabs.forEach(function (tab) {
            tab.addEventListener("keydown", function (ev) {
                var idx = tabs.indexOf(tab);
                var next = -1;
                if (ev.key === "ArrowRight") {
                    next = (idx + 1) % tabs.length;
                } else if (ev.key === "ArrowLeft") {
                    next = (idx - 1 + tabs.length) % tabs.length;
                } else if (ev.key === "Home") {
                    next = 0;
                } else if (ev.key === "End") {
                    next = tabs.length - 1;
                }
                if (next >= 0) {
                    ev.preventDefault();
                    tabs[next].focus();
                    tabs[next].click();
                }
            });
        });
    }

    function watchGlobalLoading() {
        var overlay = document.getElementById("globalLoading");
        if (!overlay) {
            return;
        }
        var observer = new MutationObserver(function () {
            var busy = overlay.classList.contains("is-active");
            overlay.setAttribute("aria-hidden", busy ? "false" : "true");
            overlay.setAttribute("aria-busy", busy ? "true" : "false");
            if (busy) {
                announce("Processando, aguarde.");
            }
        });
        observer.observe(overlay, { attributes: true, attributeFilter: ["class"] });
    }

    document.addEventListener("DOMContentLoaded", function () {
        syncDocumentTitle();
        initSkipLink();
        initIconOnlyButtons();
        initDataTablesA11y();
        initDashboardTabKeyboard();
        watchGlobalLoading();
    });

    window.MiniErp = window.MiniErp || {};
    window.MiniErp.a11y = {
        announce: announce,
        syncDocumentTitle: syncDocumentTitle,
        enhanceDataTable: enhanceDataTable,
    };
})();
