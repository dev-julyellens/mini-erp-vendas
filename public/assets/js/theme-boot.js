(function () {
    try {
        var t = localStorage.getItem("mini-erp-theme");
        if (t === "dark") {
            document.documentElement.setAttribute("data-theme", "dark");
        }
        if (localStorage.getItem("mini-erp-sidebar-collapsed") === "1") {
            document.documentElement.classList.add("sidebar-collapsed-pending");
        }
        if (localStorage.getItem("mini-erp-sidebar-pinned") === "1") {
            document.documentElement.classList.add("sidebar-pinned-pending");
        }
    } catch (e) {
        /* ignore */
    }
})();
