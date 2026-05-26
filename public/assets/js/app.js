(function () {
    "use strict";

    var THEME_KEY = "mini-erp-theme";
    var SIDEBAR_COLLAPSED_KEY = "mini-erp-sidebar-collapsed";
    var DASHBOARD_TAB_KEY = "mini-erp-dashboard-tab";
    var NAV_GROUPS_KEY = "mini-erp-nav-groups";
    var pendingConfirmForm = null;

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
        applyTheme(getStoredTheme());
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

            new window.DataTable(table, options);
            table.dataset.dtInit = "1";
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

    function initSidebarCollapse() {
        if (document.documentElement.classList.contains("sidebar-collapsed-pending")) {
            document.body.classList.add("sidebar-collapsed");
            document.documentElement.classList.remove("sidebar-collapsed-pending");
        } else {
            try {
                if (localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === "1") {
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
            var collapsed = document.body.classList.toggle("sidebar-collapsed");
            try {
                localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed ? "1" : "0");
            } catch (e) {
                /* ignore */
            }
            document.querySelectorAll("[data-sidebar-collapse]").forEach(syncSidebarCollapseButton);
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

        function activate(tabId) {
            tabs.forEach(function (tab) {
                var active = tab.getAttribute("data-dash-tab") === tabId;
                tab.classList.toggle("active", active);
                tab.setAttribute("aria-selected", active ? "true" : "false");
            });
            panels.forEach(function (panel) {
                var active = panel.getAttribute("data-dash-panel") === tabId;
                panel.classList.toggle("is-active", active);
                panel.hidden = !active;
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
        document.querySelectorAll("[data-mask-phone]").forEach(function (el) {
            el.addEventListener("input", function () {
                var digits = el.value.replace(/\D/g, "").slice(0, 11);
                if (digits.length <= 10) {
                    el.value = digits.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3").replace(/-$/, "");
                } else {
                    el.value = digits.replace(/(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3").replace(/-$/, "");
                }
            });
        });
        document.querySelectorAll("[data-mask-document]").forEach(function (el) {
            el.addEventListener("input", function () {
                var digits = el.value.replace(/\D/g, "").slice(0, 14);
                if (digits.length <= 11) {
                    el.value = digits
                        .replace(/(\d{3})(\d)/, "$1.$2")
                        .replace(/(\d{3})(\d)/, "$1.$2")
                        .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                } else {
                    el.value = digits
                        .replace(/^(\d{2})(\d)/, "$1.$2")
                        .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")
                        .replace(/\.(\d{3})(\d)/, ".$1/$2")
                        .replace(/(\d{4})(\d)/, "$1-$2");
                }
            });
        });
    }

    function initFilterPanels() {
        document.querySelectorAll(".filter-panel form, form.filter-form").forEach(function (form) {
            form.classList.add("filter-form");
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initThemeToggle();
        initNotificationToasts();
        initFlashToasts();
        initFormLoading();
        initConfirmModal();
        initSidebarCollapse();
        initNavGroups();
        initSidebar();
        initDashboardTabs();
        initInputMasks();
        initFilterPanels();
        initDataTables();
    });

    window.MiniErp = {
        baseUrl: getBaseUrl(),
        toast: showToast,
        theme: {
            get: getStoredTheme,
            set: applyTheme,
        },
        prefs: {
            sidebarCollapsedKey: SIDEBAR_COLLAPSED_KEY,
            dashboardTabKey: DASHBOARD_TAB_KEY,
        },
    };
})();
