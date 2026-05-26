(function () {
    "use strict";

    var THEME_KEY = "mini-erp-theme";
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

    function initSidebar() {
        var sidebar = document.querySelector(".sidebar");
        var toggle = document.querySelector("[data-sidebar-toggle]");
        var backdrop = document.querySelector(".sidebar-backdrop");
        if (!sidebar || !toggle) {
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

        toggle.addEventListener("click", function () {
            if (sidebar.classList.contains("is-open")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

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
        initSidebar();
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
    };
})();
