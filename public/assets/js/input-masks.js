(function () {
    "use strict";

    var MONEY_MAX_DIGITS = 13;

    function onlyDigits(value, maxLen) {
        var digits = String(value || "").replace(/\D/g, "");
        if (maxLen && digits.length > maxLen) {
            digits = digits.slice(0, maxLen);
        }
        return digits;
    }

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

    function formatMoneyBr(value) {
        var n = typeof value === "number" ? value : parseMoney(value);
        if (!isFinite(n)) {
            n = 0;
        }
        var negative = n < 0;
        n = Math.abs(n);
        var parts = n.toFixed(2).split(".");
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        var formatted = intPart + "," + parts[1];
        return negative ? "-" + formatted : formatted;
    }

    function formatMoneyFromDigits(digits) {
        if (!digits) {
            return "";
        }
        while (digits.length < 3) {
            digits = "0" + digits;
        }
        var intRaw = digits.slice(0, -2);
        var decPart = digits.slice(-2);
        intRaw = intRaw.replace(/^0+/, "") || "0";
        var intPart = intRaw.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return intPart + "," + decPart;
    }

    function maskPhone(el) {
        var digits = onlyDigits(el.value, 11);
        if (digits.length <= 10) {
            el.value = digits.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3").replace(/-$/, "");
        } else {
            el.value = digits.replace(/(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3").replace(/-$/, "");
        }
    }

    function maskDocument(el) {
        var digits = onlyDigits(el.value, 14);
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
    }

    function maskCep(el) {
        var digits = onlyDigits(el.value, 8);
        el.value = digits.replace(/(\d{5})(\d{0,3})/, "$1-$2").replace(/-$/, "");
    }

    function maskMoney(el) {
        var digits = onlyDigits(el.value, MONEY_MAX_DIGITS);
        el.value = digits ? formatMoneyFromDigits(digits) : "";
    }

    function bindMask(el, formatter) {
        if (!el || el.dataset.maskBound === "1") {
            return;
        }
        el.dataset.maskBound = "1";
        el.addEventListener("input", function () {
            formatter(el);
        });
        el.addEventListener("blur", function () {
            formatter(el);
        });
        if (String(el.value || "").trim() !== "") {
            formatter(el);
        }
    }

    function init() {
        document.querySelectorAll("[data-mask-phone]").forEach(function (el) {
            bindMask(el, maskPhone);
        });
        document.querySelectorAll("[data-mask-document]").forEach(function (el) {
            bindMask(el, maskDocument);
        });
        document.querySelectorAll("[data-mask-cep]").forEach(function (el) {
            bindMask(el, maskCep);
        });
        document.querySelectorAll("[data-mask-money]").forEach(function (el) {
            bindMask(el, maskMoney);
        });
    }

    window.MiniErp = window.MiniErp || {};
    window.MiniErp.masks = {
        init: init,
        parseMoney: parseMoney,
        formatMoneyBr: formatMoneyBr,
        maskPhone: maskPhone,
        maskDocument: maskDocument,
        maskCep: maskCep,
        maskMoney: maskMoney,
    };
})();
