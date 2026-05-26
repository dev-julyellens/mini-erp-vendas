(function () {
    "use strict";

    var THEME_KEY = "mini-erp-theme";

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
            (message ? '<div class="small">' + escapeHtml(message) + "</div>" : "") +
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
                var submitter = ev.submitter;
                var btn =
                    submitter && submitter.tagName === "BUTTON"
                        ? submitter
                        : form.querySelector('button[type="submit"]');
                if (btn) {
                    setButtonLoading(btn, true);
                }
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initThemeToggle();
        initFlashToasts();
        initFormLoading();
    });

    window.MiniErp = {
        toast: showToast,
        theme: {
            get: getStoredTheme,
            set: applyTheme,
        },
    };
})();
