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
        document.querySelectorAll("#productForm [data-mask-money]").forEach(function (el) {
            window.MiniErp.masks.maskMoney(el);
        });
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
        var estimatedField = document.getElementById("estimatedTimeField");
        var salePriceLabel = document.getElementById("salePriceLabel");

        if (!typeEl) {
            return;
        }

        var isService = typeEl.value === "service";

        if (estimatedField) {
            estimatedField.classList.toggle("d-none", !isService);
        }
        if (salePriceLabel) {
            salePriceLabel.innerHTML = isService
                ? 'Valor padrão (R$) <span class="text-danger">*</span>'
                : 'Preço de venda (R$) <span class="text-danger">*</span>';
        }

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

    function collectProductState(form) {
        var data = {};
        form.querySelectorAll("input, select, textarea").forEach(function (el) {
            if (!el.name || el.name === "_csrf") {
                return;
            }
            if (el.type === "checkbox" || el.type === "radio") {
                if (el.checked) {
                    data[el.name] = el.value;
                }
                return;
            }
            data[el.name] = el.value;
        });
        return data;
    }

    function isProductStateEmpty(state) {
        if (!state) {
            return true;
        }
        return (
            String(state.name || "").trim() === "" &&
            String(state.sku || "").trim() === "" &&
            String(state.price || "").trim() === "" &&
            String(state.description || "").trim() === ""
        );
    }

    function applyProductState(form, state) {
        if (!state) {
            return;
        }
        form.querySelectorAll("input, select, textarea").forEach(function (el) {
            if (!el.name || el.name === "_csrf" || state[el.name] === undefined) {
                return;
            }
            if (el.type === "checkbox" || el.type === "radio") {
                el.checked = el.value === state[el.name];
                return;
            }
            el.value = state[el.name];
        });
        recalcMargins();
        toggleServiceMode();
        refreshMoneyFields();
    }

    document.addEventListener("DOMContentLoaded", function () {
        var form = document.getElementById("productForm");
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

        if (form && window.MiniErp && window.MiniErp.autosave) {
            var autosaveKey =
                typeof window.__PRODUCT_AUTOSAVE_KEY__ === "string"
                    ? window.__PRODUCT_AUTOSAVE_KEY__
                    : "product-create";
            window.MiniErp.autosave.init({
                key: autosaveKey,
                form: form,
                getState: function () {
                    return collectProductState(form);
                },
                applyState: function (state) {
                    applyProductState(form, state);
                },
                isEmpty: isProductStateEmpty,
                skipRestore: window.__SKIP_AUTOSAVE_RESTORE__ === true,
                statusEl: document.getElementById("productAutosaveStatus"),
            });
        }
    });
})();
