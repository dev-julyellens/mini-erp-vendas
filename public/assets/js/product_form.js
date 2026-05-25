(function () {
    "use strict";

    function parseMoney(value) {
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

    function formatPercent(n) {
        if (!isFinite(n)) {
            return "—";
        }
        return n.toFixed(2);
    }

    function recalcMargins() {
        var costEl = document.getElementById("costPrice");
        var priceEl = document.getElementById("salePrice");
        var marginEl = document.getElementById("marginPercent");
        var markupEl = document.getElementById("markupPercent");
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

    function toggleServiceMode() {
        var typeEl = document.getElementById("productType");
        var stockFields = document.getElementById("stockFields");
        var hint = document.getElementById("serviceStockHint");
        var stockQty = document.getElementById("stockQty");
        var minStockQty = document.getElementById("minStockQty");
        var unitEl = document.getElementById("productUnit");

        if (!typeEl) {
            return;
        }

        var isService = typeEl.value === "service";

        if (stockFields) {
            stockFields.classList.toggle("opacity-50", isService);
        }
        if (hint) {
            hint.classList.toggle("d-none", !isService);
        }
        if (stockQty) {
            stockQty.readOnly = isService;
            if (isService) {
                stockQty.value = "0";
            }
        }
        if (minStockQty) {
            minStockQty.readOnly = isService;
            if (isService) {
                minStockQty.value = "0";
            }
        }
        if (unitEl && isService && unitEl.value === "UN") {
            unitEl.value = "HR";
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        var costEl = document.getElementById("costPrice");
        var priceEl = document.getElementById("salePrice");
        var typeEl = document.getElementById("productType");
        var skuEl = document.getElementById("productSku");

        if (costEl) {
            costEl.addEventListener("input", recalcMargins);
        }
        if (priceEl) {
            priceEl.addEventListener("input", recalcMargins);
        }
        if (typeEl) {
            typeEl.addEventListener("change", toggleServiceMode);
        }
        if (skuEl) {
            skuEl.addEventListener("blur", function () {
                skuEl.value = skuEl.value.trim().toUpperCase();
            });
        }

        recalcMargins();
        toggleServiceMode();
    });
})();
