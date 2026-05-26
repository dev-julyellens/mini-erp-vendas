(function () {
    "use strict";

    function parseMoney(value) {
        if (window.MiniErp && window.MiniErp.masks && typeof window.MiniErp.masks.parseMoney === "function") {
            return window.MiniErp.masks.parseMoney(value);
        }
        if (!value) {
            return 0;
        }
        var v = String(value).trim().replace(/\s/g, "");
        if (v.indexOf(",") >= 0 && v.indexOf(".") >= 0) {
            v = v.replace(/\./g, "").replace(",", ".");
        } else if (v.indexOf(",") >= 0) {
            v = v.replace(",", ".");
        }
        var n = parseFloat(v);
        return isFinite(n) ? n : 0;
    }

    function refreshMoneyFields() {
        if (!window.MiniErp || !window.MiniErp.masks) {
            return;
        }
        document.querySelectorAll("#productForm [data-mask-money], #serviceForm [data-mask-money]").forEach(
            function (el) {
                window.MiniErp.masks.maskMoney(el);
            }
        );
    }

    function formatPercent(n) {
        if (!isFinite(n)) {
            return "—";
        }
        return n.toFixed(2);
    }

    function recalcMargins() {
        var costEl = document.getElementById("serviceCostPrice");
        var priceEl = document.getElementById("serviceSalePrice");
        var marginEl = document.getElementById("serviceMarginPercent");
        var markupEl = document.getElementById("serviceMarkupPercent");
        if (!costEl || !priceEl || !marginEl || !markupEl) {
            return;
        }

        var cost = parseMoney(costEl.value);
        var price = parseMoney(priceEl.value);

        if (price <= 0) {
            marginEl.value = "—";
            markupEl.value = "—";
            return;
        }

        var margin = cost > 0 ? ((price - cost) / price) * 100 : null;
        var markup = cost > 0 ? ((price - cost) / cost) * 100 : null;

        marginEl.value = margin !== null ? formatPercent(margin) : "—";
        markupEl.value = markup !== null ? formatPercent(markup) : "—";
    }

    document.addEventListener("DOMContentLoaded", function () {
        var costEl = document.getElementById("serviceCostPrice");
        var priceEl = document.getElementById("serviceSalePrice");
        var skuEl = document.getElementById("serviceSku");

        if (costEl) {
            costEl.addEventListener("input", recalcMargins);
        }
        if (priceEl) {
            priceEl.addEventListener("input", recalcMargins);
        }
        if (skuEl) {
            skuEl.addEventListener("blur", function () {
                skuEl.value = skuEl.value.trim().toUpperCase();
            });
        }

        recalcMargins();
        refreshMoneyFields();
    });
})();
