(function () {
    "use strict";

    function getBaseUrl() {
        var el = document.body;
        return el && el.dataset.baseUrl ? el.dataset.baseUrl.replace(/\/$/, "") : "";
    }

    function showToast(title, message, variant) {
        var container = document.getElementById("toastContainer");
        if (!container) {
            return;
        }

        var bg = variant === "success" ? "text-bg-success" : variant === "danger" ? "text-bg-danger" : "text-bg-primary";
        var id = "toast-" + Date.now();
        var html =
            '<div id="' +
            id +
            '" class="toast align-items-center ' +
            bg +
            ' border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="d-flex">' +
            '<div class="toast-body">' +
            "<strong>" +
            escapeHtml(title) +
            "</strong><div>" +
            escapeHtml(message) +
            "</div>" +
            "</div>" +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            "</div>" +
            "</div>";

        container.insertAdjacentHTML("beforeend", html);
        var node = document.getElementById(id);
        if (window.bootstrap && node) {
            var t = new window.bootstrap.Toast(node, { delay: 4500 });
            t.show();
            node.addEventListener("hidden.bs.toast", function () {
                node.remove();
            });
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    window.MiniErp = {
        baseUrl: getBaseUrl(),
        toast: showToast,
    };
})();
