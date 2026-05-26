(function () {
    "use strict";

    var THEME_KEY = "mini-erp-theme";
    var SIDEBAR_COLLAPSED_KEY = "mini-erp-sidebar-collapsed";
    var SIDEBAR_PINNED_KEY = "mini-erp-sidebar-pinned";
    var DASHBOARD_TAB_KEY = "mini-erp-dashboard-tab";
    var NAV_GROUPS_KEY = "mini-erp-nav-groups";
    var pendingConfirmForm = null;
    var prefsSaveTimer = null;

    function getBaseUrl() {
        var el = document.body;
        return el && el.dataset.baseUrl ? el.dataset.baseUrl.replace(/\/$/, "") : "";
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function getStoredTheme() {
        try {
            return localStorage.getItem(THEME_KEY) === "dark" ? "dark" : "light";
        } catch (e) {
            return "light";
        }
    }

    function applyTheme(theme) {
        var next = theme === "dark" ? "dark" : "light";
        document.documentElement.setAttribute("data-theme", next);
        try {
            localStorage.setItem(THEME_KEY, next);
        } catch (e) {
            /* ignore */
        }
        document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
            var icon = btn.querySelector("i");
            if (!icon) {
                return;
            }
            icon.className = next === "dark" ? "bi bi-sun" : "bi bi-moon-stars";
            btn.setAttribute("aria-label", next === "dark" ? "Modo claro" : "Modo escuro");
            btn.setAttribute("title", next === "dark" ? "Modo claro" : "Modo escuro");
        });
    }

    function initThemeToggle() {
        if (!hasServerPrefs()) {
            applyTheme(getStoredTheme());
        }
        document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var current = document.documentElement.getAttribute("data-theme") || "light";
                applyTheme(current === "dark" ? "light" : "dark");
            });
        });
    }

    function showToast(title, message, variant) {
        var container = document.getElementById("toastContainer");
        if (!container) {
            return;
        }

        var bg = "text-bg-primary";
        if (variant === "success") {
            bg = "text-bg-success";
        } else if (variant === "danger") {
            bg = "text-bg-danger";
        } else if (variant === "warning") {
            bg = "text-bg-warning";
        } else if (variant === "info" || variant === "primary") {
            bg = "text-bg-primary";
        }
        var id = "toast-" + Date.now();
        var html =
            '<div id="' +
            id +
            '" class="toast align-items-center ' +
            bg +
            ' border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="d-flex">' +
            '<div class="toast-body">' +
            "<strong>" +
            escapeHtml(title) +
            "</strong>" +
            (message ? "<div class=\"small\">" + escapeHtml(message) + "</div>" : "") +
            "</div>" +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>' +
            "</div>" +
            "</div>";

        container.insertAdjacentHTML("beforeend", html);
        var node = document.getElementById(id);
        if (window.bootstrap && node) {
            var t = new window.bootstrap.Toast(node, { delay: 5000 });
            t.show();
            node.addEventListener("hidden.bs.toast", function () {
                node.remove();
            });
        }
    }

    function initNotificationToasts() {
        document.querySelectorAll("[data-notification-toast]").forEach(function (el) {
            var title = el.getAttribute("data-toast-title") || "Notificação";
            var message = el.getAttribute("data-toast-message") || "";
            var variant = el.getAttribute("data-toast-variant") || "warning";
            if (message) {
                showToast(title, message, variant);
            }
            el.remove();
        });
    }

    function initFlashToasts() {
        var hasToast = false;
        document.querySelectorAll("[data-flash-toast]").forEach(function (el) {
            var variant = el.getAttribute("data-flash-variant") || "primary";
            var title = el.getAttribute("data-flash-title") || "Aviso";
            var message = el.textContent.trim();
            if (message) {
                showToast(title, message, variant);
                hasToast = true;
            }
            el.remove();
        });
        if (hasToast) {
            document.querySelectorAll(".flash-fallback").forEach(function (alert) {
                alert.remove();
            });
        }
    }

    function setButtonLoading(btn, loading) {
        if (!btn || btn.tagName !== "BUTTON") {
            return;
        }
        if (loading) {
            if (!btn.dataset.originalHtml) {
                btn.dataset.originalHtml = btn.innerHTML;
            }
            btn.disabled = true;
            btn.classList.add("is-loading");
            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
                (btn.dataset.loadingText || "Aguarde...");
        } else {
            btn.disabled = false;
            btn.classList.remove("is-loading");
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
            }
        }
    }

    function initFormLoading() {
        document.querySelectorAll("form:not([data-no-loading])").forEach(function (form) {
            form.addEventListener("submit", function (ev) {
                if (ev.defaultPrevented) {
                    return;
                }
                if (form.getAttribute("data-confirm") && !form.dataset.confirmed) {
                    return;
                }
                var submitter = ev.submitter;
                var btn =
                    submitter && submitter.tagName === "BUTTON"
                        ? submitter
                        : form.querySelector('button[type="submit"]');
                if (btn) {
                    setButtonLoading(btn, true);
                }
                var overlay = document.getElementById("globalLoading");
                if (overlay && form.dataset.globalLoading !== "false") {
                    overlay.classList.add("is-active");
                    overlay.setAttribute("aria-busy", "true");
                }
            });
        });
    }

    function initConfirmModal() {
        var modalEl = document.getElementById("confirmModal");
        if (!modalEl || !window.bootstrap) {
            return;
        }
        var modal = new window.bootstrap.Modal(modalEl);
        var titleEl = document.getElementById("confirmModalLabel");
        var messageEl = document.getElementById("confirmModalMessage");
        var submitBtn = document.getElementById("confirmModalSubmit");

        document.querySelectorAll("form[data-confirm]").forEach(function (form) {
            form.addEventListener("submit", function (ev) {
                if (form.dataset.confirmed === "1") {
                    form.dataset.confirmed = "";
                    return;
                }
                ev.preventDefault();
                pendingConfirmForm = form;
                if (titleEl) {
                    titleEl.textContent = form.getAttribute("data-confirm-title") || "Confirmar ação";
                }
                if (messageEl) {
                    messageEl.textContent =
                        form.getAttribute("data-confirm") || "Deseja continuar com esta ação?";
                }
                modal.show();
            });
        });

        if (submitBtn) {
            submitBtn.addEventListener("click", function () {
                if (!pendingConfirmForm) {
                    modal.hide();
                    return;
                }
                var form = pendingConfirmForm;
                pendingConfirmForm = null;
                form.dataset.confirmed = "1";
                modal.hide();
                if (typeof form.requestSubmit === "function") {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        }

        modalEl.addEventListener("hidden.bs.modal", function () {
            pendingConfirmForm = null;
        });
    }

    function tableHasData(table) {
        var rows = table.querySelectorAll("tbody tr");
        if (rows.length === 0) {
            return false;
        }
        if (rows.length === 1) {
            var only = rows[0];
            if (only.querySelector("td[colspan]") || only.classList.contains("empty-row")) {
                return false;
            }
        }
        return true;
    }

    function initDataTables() {
        if (!window.DataTable) {
            return;
        }

        var lang = {
            search: "Buscar na página:",
            zeroRecords: "Nenhum registro encontrado",
            infoEmpty: "",
            info: "Exibindo _TOTAL_ registro(s) nesta página",
            infoFiltered: "(filtrado de _MAX_ no total)",
            emptyTable: "Sem dados",
        };

        document.querySelectorAll("table.js-datatable").forEach(function (table) {
            if (!tableHasData(table)) {
                return;
            }
            if (table.dataset.dtInit === "1") {
                return;
            }

            var actionsCol = table.getAttribute("data-dt-actions-col");
            var options = {
                paging: false,
                searching: true,
                info: true,
                lengthChange: false,
                order: [],
                language: lang,
                responsive: true,
                dom: '<"row mb-2"<"col-12 col-md"f>>rt<"row mt-2"<"col-12"i>>',
            };
            if (actionsCol !== null && actionsCol !== "") {
                options.columnDefs = [{ orderable: false, targets: parseInt(actionsCol, 10) }];
            }

            var dt = new window.DataTable(table, options);
            table.dataset.dtInit = "1";
            window.setTimeout(function () {
                if (window.MiniErp && window.MiniErp.a11y && typeof window.MiniErp.a11y.enhanceDataTable === "function") {
                    window.MiniErp.a11y.enhanceDataTable(table);
                    dt.on("draw", function () {
                        window.MiniErp.a11y.enhanceDataTable(table);
                    });
                }
            }, 0);
        });
    }

    function getStoredJson(key, fallback) {
        try {
            var raw = localStorage.getItem(key);
            if (!raw) {
                return fallback;
            }
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    function setStoredJson(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            /* ignore */
        }
    }

    function isSidebarPinned() {
        return document.body.classList.contains("sidebar-pinned");
    }

    function setSidebarPinned(pinned) {
        document.body.classList.toggle("sidebar-pinned", pinned);
        if (pinned) {
            document.body.classList.remove("sidebar-collapsed");
            try {
                localStorage.setItem(SIDEBAR_COLLAPSED_KEY, "0");
            } catch (e) {
                /* ignore */
            }
        }
        try {
            localStorage.setItem(SIDEBAR_PINNED_KEY, pinned ? "1" : "0");
        } catch (e) {
            /* ignore */
        }
        document.querySelectorAll("[data-sidebar-collapse]").forEach(function (btn) {
            btn.disabled = pinned;
        });
        var collapsedPref = document.querySelector("[data-pref-sidebar]");
        if (collapsedPref) {
            collapsedPref.disabled = pinned;
            if (pinned) {
                collapsedPref.checked = false;
            }
        }
    }

    function hasServerPrefs() {
        return !!(document.body && document.body.dataset.userPrefs);
    }

    function initSidebarPin() {
        if (hasServerPrefs()) {
            /* estado aplicado em initServerPreferences */
            return;
        }
        if (document.documentElement.classList.contains("sidebar-pinned-pending")) {
            setSidebarPinned(true);
            document.documentElement.classList.remove("sidebar-pinned-pending");
            return;
        }
        try {
            if (localStorage.getItem(SIDEBAR_PINNED_KEY) === "1") {
                setSidebarPinned(true);
            }
        } catch (e) {
            /* ignore */
        }
    }

    function initSidebarCollapse() {
        if (hasServerPrefs()) {
            /* estado aplicado em initServerPreferences */
        } else if (document.documentElement.classList.contains("sidebar-collapsed-pending")) {
            document.body.classList.add("sidebar-collapsed");
            document.documentElement.classList.remove("sidebar-collapsed-pending");
        } else {
            try {
                if (!isSidebarPinned() && localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === "1") {
                    document.body.classList.add("sidebar-collapsed");
                }
            } catch (e) {
                /* ignore */
            }
        }

        function syncSidebarCollapseButton(btn) {
            var collapsed = document.body.classList.contains("sidebar-collapsed");
            var label = collapsed ? "Expandir menu" : "Recolher menu";
            btn.setAttribute("aria-label", label);
            btn.setAttribute("title", label);
        }

        function toggleCollapsed() {
            if (isSidebarPinned()) {
                return;
            }
            var collapsed = document.body.classList.toggle("sidebar-collapsed");
            try {
                localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed ? "1" : "0");
            } catch (e) {
                /* ignore */
            }
            document.querySelectorAll("[data-sidebar-collapse]").forEach(syncSidebarCollapseButton);
            queuePreferencesSave(collectUiPreferences());
        }

        document.querySelectorAll("[data-sidebar-collapse]").forEach(function (btn) {
            syncSidebarCollapseButton(btn);
            btn.addEventListener("click", toggleCollapsed);
        });
    }

    function initNavGroups() {
        var stored = getStoredJson(NAV_GROUPS_KEY, {});
        document.querySelectorAll("[data-nav-group]").forEach(function (group) {
            var id = group.getAttribute("data-nav-group");
            if (!id) {
                return;
            }
            if (stored[id] === true) {
                group.classList.add("is-open");
                var btn = group.querySelector("[data-nav-group-toggle]");
                if (btn) {
                    btn.setAttribute("aria-expanded", "true");
                }
            } else if (stored[id] === false) {
                group.classList.remove("is-open");
                var btnClosed = group.querySelector("[data-nav-group-toggle]");
                if (btnClosed) {
                    btnClosed.setAttribute("aria-expanded", "false");
                }
            }
        });

        document.querySelectorAll("[data-nav-group-toggle]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var group = btn.closest("[data-nav-group]");
                if (!group) {
                    return;
                }
                var id = group.getAttribute("data-nav-group");
                var open = group.classList.toggle("is-open");
                btn.setAttribute("aria-expanded", open ? "true" : "false");
                if (id) {
                    var state = getStoredJson(NAV_GROUPS_KEY, {});
                    state[id] = open;
                    setStoredJson(NAV_GROUPS_KEY, state);
                }
            });
        });
    }

    function initSidebar() {
        var sidebar = document.querySelector(".sidebar");
        var toggle = document.querySelector("[data-sidebar-toggle]");
        var backdrop = document.querySelector(".sidebar-backdrop");
        if (!sidebar) {
            return;
        }

        function closeSidebar() {
            sidebar.classList.remove("is-open");
            if (backdrop) {
                backdrop.classList.remove("is-visible");
            }
            document.body.style.overflow = "";
        }

        function openSidebar() {
            sidebar.classList.add("is-open");
            if (backdrop) {
                backdrop.classList.add("is-visible");
            }
            document.body.style.overflow = "hidden";
        }

        if (toggle) {
            toggle.addEventListener("click", function () {
                if (sidebar.classList.contains("is-open")) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (backdrop) {
            backdrop.addEventListener("click", closeSidebar);
        }

        sidebar.querySelectorAll(".nav-link:not(.disabled)").forEach(function (link) {
            link.addEventListener("click", function () {
                if (window.matchMedia("(max-width: 991px)").matches) {
                    closeSidebar();
                }
            });
        });
    }

    function initDashboardTabs() {
        var root = document.getElementById("dashboardRoot");
        if (!root) {
            return;
        }
        var tabs = root.querySelectorAll("[data-dash-tab]");
        var panels = root.querySelectorAll("[data-dash-panel]");
        if (!tabs.length || !panels.length) {
            return;
        }

        tabs.forEach(function (tab) {
            var tabId = tab.getAttribute("data-dash-tab");
            if (!tabId) {
                return;
            }
            if (!tab.id) {
                tab.id = "dash-tab-" + tabId;
            }
            var panel = root.querySelector('[data-dash-panel="' + tabId + '"]');
            if (!panel) {
                return;
            }
            if (!panel.id) {
                panel.id = "dash-panel-" + tabId;
            }
            tab.setAttribute("aria-controls", panel.id);
            panel.setAttribute("aria-labelledby", tab.id);
        });

        function activate(tabId) {
            tabs.forEach(function (tab) {
                var active = tab.getAttribute("data-dash-tab") === tabId;
                tab.classList.toggle("active", active);
                tab.setAttribute("aria-selected", active ? "true" : "false");
                tab.setAttribute("tabindex", active ? "0" : "-1");
            });
            panels.forEach(function (panel) {
                var active = panel.getAttribute("data-dash-panel") === tabId;
                panel.classList.toggle("is-active", active);
                panel.hidden = !active;
                if (active) {
                    panel.setAttribute("tabindex", "0");
                } else {
                    panel.removeAttribute("tabindex");
                }
            });
            try {
                localStorage.setItem(DASHBOARD_TAB_KEY, tabId);
            } catch (e) {
                /* ignore */
            }
        }

        var initial = "overview";
        try {
            var hash = (window.location.hash || "").replace("#dash-", "");
            if (hash && root.querySelector('[data-dash-panel="' + hash + '"]')) {
                initial = hash;
            } else {
                var saved = localStorage.getItem(DASHBOARD_TAB_KEY);
                if (saved && root.querySelector('[data-dash-panel="' + saved + '"]')) {
                    initial = saved;
                }
            }
        } catch (e) {
            /* ignore */
        }
        activate(initial);

        tabs.forEach(function (tab) {
            tab.addEventListener("click", function (ev) {
                ev.preventDefault();
                var id = tab.getAttribute("data-dash-tab");
                if (id) {
                    activate(id);
                    if (history.replaceState) {
                        history.replaceState(null, "", "#dash-" + id);
                    } else {
                        window.location.hash = "dash-" + id;
                    }
                }
            });
        });
    }

    function initInputMasks() {
        if (window.MiniErp && window.MiniErp.masks && typeof window.MiniErp.masks.init === "function") {
            window.MiniErp.masks.init();
        }
    }

    function initFilterPanels() {
        document.querySelectorAll(".filter-panel form, form.filter-form").forEach(function (form) {
            form.classList.add("filter-form");
        });
    }

    function getCsrfToken() {
        var body = document.body;
        if (body && body.dataset.csrfToken) {
            return body.dataset.csrfToken;
        }
        var input = document.querySelector('input[name="_csrf"]');
        return input ? input.value : "";
    }

    function getPrefsUrl() {
        var body = document.body;
        if (body && body.dataset.prefsUrl) {
            return body.dataset.prefsUrl;
        }
        var card = document.getElementById("profilePrefsCard");
        return card ? card.getAttribute("data-prefs-url") || "" : "";
    }

    function collectUiPreferences() {
        var theme = document.documentElement.getAttribute("data-theme") || getStoredTheme();
        return {
            theme: theme === "dark" ? "dark" : "light",
            sidebar_collapsed: document.body.classList.contains("sidebar-collapsed") ? "1" : "0",
            sidebar_pinned: isSidebarPinned() ? "1" : "0",
            dashboard_tab: (function () {
                try {
                    return localStorage.getItem(DASHBOARD_TAB_KEY) || "overview";
                } catch (e) {
                    return "overview";
                }
            })(),
        };
    }

    function applyServerPreferences(prefs) {
        if (!prefs || typeof prefs !== "object") {
            return;
        }
        if (prefs.theme === "dark" || prefs.theme === "light") {
            applyTheme(prefs.theme);
        }
        if (prefs.sidebar_pinned) {
            setSidebarPinned(true);
        } else {
            setSidebarPinned(false);
            if (prefs.sidebar_collapsed) {
                document.body.classList.add("sidebar-collapsed");
            } else {
                document.body.classList.remove("sidebar-collapsed");
            }
            try {
                localStorage.setItem(SIDEBAR_COLLAPSED_KEY, prefs.sidebar_collapsed ? "1" : "0");
            } catch (e) {
                /* ignore */
            }
        }
        if (prefs.dashboard_tab) {
            try {
                localStorage.setItem(DASHBOARD_TAB_KEY, prefs.dashboard_tab);
            } catch (e) {
                /* ignore */
            }
        }
        var themeEl = document.querySelector("[data-pref-theme]");
        var sidebarEl = document.querySelector("[data-pref-sidebar]");
        var pinEl = document.querySelector("[data-pref-sidebar-pinned]");
        var dashTabEl = document.querySelector("[data-pref-dashboard-tab]");
        if (themeEl && prefs.theme) {
            themeEl.value = prefs.theme;
        }
        if (pinEl) {
            pinEl.checked = !!prefs.sidebar_pinned;
        }
        if (sidebarEl) {
            sidebarEl.checked = !!prefs.sidebar_collapsed;
            sidebarEl.disabled = !!prefs.sidebar_pinned;
        }
        if (dashTabEl && prefs.dashboard_tab) {
            dashTabEl.value = prefs.dashboard_tab;
        }
    }

    function initServerPreferences() {
        var body = document.body;
        if (!body || !body.dataset.userPrefs) {
            return;
        }
        try {
            var prefs = JSON.parse(body.dataset.userPrefs);
            applyServerPreferences({
                theme: prefs.theme,
                sidebar_collapsed: !!prefs.sidebar_collapsed,
                sidebar_pinned: !!prefs.sidebar_pinned,
                dashboard_tab: prefs.dashboard_tab,
            });
        } catch (e) {
            /* ignore */
        }
    }

    function queuePreferencesSave(payload) {
        var url = getPrefsUrl();
        if (!url) {
            return;
        }
        if (prefsSaveTimer) {
            clearTimeout(prefsSaveTimer);
        }
        prefsSaveTimer = setTimeout(function () {
            savePreferencesRemote(url, payload);
        }, 400);
    }

    function savePreferencesRemote(url, payload) {
        var status = document.getElementById("prefSaveStatus");
        if (status) {
            status.textContent = "Salvando preferências…";
        }
        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-Token": getCsrfToken(),
            },
            body: JSON.stringify(payload),
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { ok: res.ok, body: body };
                });
            })
            .then(function (result) {
                if (status) {
                    status.textContent = result.ok ? "Preferências salvas." : "Não foi possível salvar.";
                }
                if (result.ok && result.body && result.body.data) {
                    applyServerPreferences({
                        theme: result.body.data.theme,
                        sidebar_collapsed: !!result.body.data.sidebar_collapsed,
                        sidebar_pinned: !!result.body.data.sidebar_pinned,
                        dashboard_tab: result.body.data.dashboard_tab,
                    });
                }
            })
            .catch(function () {
                if (status) {
                    status.textContent = "Falha ao sincronizar preferências.";
                }
            });
    }

    function initProfilePreferences() {
        var card = document.getElementById("profilePrefsCard");
        if (!card) {
            return;
        }
        var themeEl = card.querySelector("[data-pref-theme]");
        var sidebarEl = card.querySelector("[data-pref-sidebar]");
        var pinEl = card.querySelector("[data-pref-sidebar-pinned]");
        var dashTabEl = card.querySelector("[data-pref-dashboard-tab]");

        function onChange() {
            if (themeEl) {
                applyTheme(themeEl.value);
            }
            if (pinEl) {
                setSidebarPinned(pinEl.checked);
            }
            if (sidebarEl && !isSidebarPinned()) {
                var collapsed = sidebarEl.checked;
                document.body.classList.toggle("sidebar-collapsed", collapsed);
                try {
                    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed ? "1" : "0");
                } catch (e) {
                    /* ignore */
                }
            }
            if (dashTabEl) {
                try {
                    localStorage.setItem(DASHBOARD_TAB_KEY, dashTabEl.value);
                } catch (e) {
                    /* ignore */
                }
            }
            queuePreferencesSave(collectUiPreferences());
        }

        [themeEl, sidebarEl, pinEl, dashTabEl].forEach(function (el) {
            if (el) {
                el.addEventListener("change", onChange);
            }
        });
    }

    function initPasswordStrength() {
        document.querySelectorAll("[data-password-strength]").forEach(function (input) {
            var meter = document.querySelector("[data-password-strength-meter]");
            var fill = meter ? meter.querySelector(".password-strength-fill") : null;
            var label = meter ? meter.querySelector("[data-password-strength-label]") : null;
            var bar = meter ? meter.querySelector(".password-strength-bar") : null;

            function score(pw) {
                var s = 0;
                if (!pw) {
                    return 0;
                }
                if (pw.length >= 8) {
                    s += 1;
                }
                if (pw.length >= 12) {
                    s += 1;
                }
                if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) {
                    s += 1;
                }
                if (/\d/.test(pw)) {
                    s += 1;
                }
                if (/[^a-zA-Z0-9]/.test(pw)) {
                    s += 1;
                }
                return Math.min(4, s);
            }

            function update() {
                if (!meter || !fill || !label) {
                    return;
                }
                var pw = input.value;
                if (!pw) {
                    meter.hidden = true;
                    return;
                }
                meter.hidden = false;
                var level = score(pw);
                var pct = [0, 25, 50, 75, 100][level];
                var texts = ["", "Fraca", "Razoável", "Boa", "Forte"];
                var classes = ["", "is-weak", "is-fair", "is-good", "is-strong"];
                fill.style.width = pct + "%";
                label.textContent = texts[level];
                meter.className = "password-strength mt-2 " + (classes[level] || "");
                if (bar) {
                    bar.setAttribute("aria-valuenow", String(pct));
                }
            }

            input.addEventListener("input", update);
            update();
        });
    }

    function buildSkeletonHtml(rows) {
        var html = '<div class="skeleton-panel" aria-hidden="true">';
        for (var i = 0; i < rows; i++) {
            html += '<div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text"></div>';
        }
        html += "</div>";
        return html;
    }

    function initAjaxSkeletons() {
        document.querySelectorAll("[data-ajax-skeleton]").forEach(function (el) {
            var rows = parseInt(el.getAttribute("data-ajax-skeleton-rows") || "3", 10);
            el.addEventListener("ajax-skeleton:start", function () {
                if (!el.dataset.skeletonBackup) {
                    el.dataset.skeletonBackup = el.innerHTML;
                }
                el.setAttribute("aria-busy", "true");
                el.innerHTML = buildSkeletonHtml(rows);
            });
            el.addEventListener("ajax-skeleton:stop", function () {
                el.removeAttribute("aria-busy");
                if (el.dataset.skeletonBackup) {
                    el.innerHTML = el.dataset.skeletonBackup;
                    delete el.dataset.skeletonBackup;
                }
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initServerPreferences();
        initThemeToggle();
        initNotificationToasts();
        initFlashToasts();
        initFormLoading();
        initConfirmModal();
        initSidebarPin();
        initSidebarCollapse();
        initNavGroups();
        initSidebar();
        initDashboardTabs();
        initInputMasks();
        initFilterPanels();
        initProfilePreferences();
        initPasswordStrength();
        initAjaxSkeletons();
        initDataTables();
    });

    window.MiniErp = {
        baseUrl: getBaseUrl(),
        toast: showToast,
        theme: {
            get: getStoredTheme,
            set: function (theme) {
                applyTheme(theme);
                queuePreferencesSave(collectUiPreferences());
            },
        },
        prefs: {
            sidebarCollapsedKey: SIDEBAR_COLLAPSED_KEY,
            sidebarPinnedKey: SIDEBAR_PINNED_KEY,
            dashboardTabKey: DASHBOARD_TAB_KEY,
            save: function () {
                queuePreferencesSave(collectUiPreferences());
            },
        },
        skeleton: {
            start: function (el) {
                if (el) {
                    el.dispatchEvent(new CustomEvent("ajax-skeleton:start"));
                }
            },
            stop: function (el) {
                if (el) {
                    el.dispatchEvent(new CustomEvent("ajax-skeleton:stop"));
                }
            },
        },
    };
})();
