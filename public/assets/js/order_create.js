(function () {
    "use strict";

    function moneyFormatBR(value) {
        var n = Number(value);
        if (!isFinite(n)) {
            n = 0;
        }
        return n.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function recalc() {
        var lines = document.querySelectorAll("[data-line]");
        var total = 0;

        lines.forEach(function (line) {
            var select = line.querySelector(".product-select");
            var qtyInput = line.querySelector(".qty-input");
            var help = line.querySelector("[data-help]");
            var unitEl = line.querySelector("[data-unit-price]");

            var opt = select.options[select.selectedIndex];
            var price = opt ? parseFloat(opt.getAttribute("data-price") || "0") : 0;
            var stock = opt ? parseInt(opt.getAttribute("data-stock") || "0", 10) : 0;
            var qty = qtyInput ? parseInt(qtyInput.value || "0", 10) : 0;

            if (select.value) {
                unitEl.textContent = moneyFormatBR(price);
            } else {
                unitEl.textContent = "R$ —";
            }

            if (select.value && qty > 0) {
                total += price * qty;
            }

            if (help) {
                if (select.value && qty > stock) {
                    help.textContent = "Atenção: quantidade acima do estoque disponível (" + stock + ").";
                    help.className = "small mt-1 text-danger";
                } else if (select.value) {
                    help.textContent = "O preço exibido é o do catálogo no momento do envio (histórico na venda).";
                    help.className = "small mt-1 text-muted";
                } else {
                    help.textContent = "";
                    help.className = "small mt-1";
                }
            }
        });

        var totalEl = document.getElementById("orderTotal");
        if (totalEl) {
            totalEl.textContent = moneyFormatBR(total);
        }
    }

    function addLine() {
        var tpl = document.getElementById("lineTemplate");
        var host = document.getElementById("lines");
        if (!tpl || !host) {
            return;
        }
        var node = tpl.content.firstElementChild.cloneNode(true);
        host.appendChild(node);

        node.querySelector(".product-select").addEventListener("change", recalc);
        node.querySelector(".qty-input").addEventListener("input", recalc);
        node.querySelector("[data-remove-line]").addEventListener("click", function () {
            node.remove();
            recalc();
        });

        recalc();
    }

    document.addEventListener("DOMContentLoaded", function () {
        var btnAdd = document.getElementById("btnAddLine");
        if (btnAdd) {
            btnAdd.addEventListener("click", addLine);
        }

        addLine();

        var form = document.getElementById("orderForm");
        if (!form) {
            return;
        }

        form.addEventListener("submit", function (e) {
            e.preventDefault();

            var customerId = parseInt(document.getElementById("customerId").value || "0", 10);
            var installmentCount = parseInt(document.getElementById("installmentCount").value || "1", 10);
            var customerSelect = document.getElementById("customerId");
            if (!customerId) {
                customerSelect.classList.add("is-invalid");
                if (window.MiniErp && window.MiniErp.toast) {
                    window.MiniErp.toast("Validação", "Selecione um cliente.", "danger");
                }
                return;
            }
            customerSelect.classList.remove("is-invalid");

            var items = [];
            var lines = document.querySelectorAll("[data-line]");
            lines.forEach(function (line) {
                var select = line.querySelector(".product-select");
                var qtyInput = line.querySelector(".qty-input");
                if (!select || !qtyInput) {
                    return;
                }
                var pid = parseInt(select.value || "0", 10);
                var qty = parseInt(qtyInput.value || "0", 10);
                if (pid > 0 && qty > 0) {
                    items.push({ product_id: pid, quantity: qty });
                }
            });

            if (items.length === 0) {
                if (window.MiniErp && window.MiniErp.toast) {
                    window.MiniErp.toast("Validação", "Adicione ao menos um item válido.", "danger");
                }
                return;
            }

            var btn = document.getElementById("btnSubmit");
            var spinner = document.getElementById("btnSpinner");
            if (btn) {
                btn.disabled = true;
            }
            if (spinner) {
                spinner.classList.remove("d-none");
            }

            fetch(window.__ORDER_STORE_URL__, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    items: items,
                    installment_count: installmentCount,
                }),
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        return { ok: res.ok, status: res.status, body: body };
                    });
                })
                .then(function (result) {
                    if (result.ok && result.body && result.body.success) {
                        if (window.MiniErp && window.MiniErp.toast) {
                            window.MiniErp.toast("Sucesso", "Venda #" + result.body.order_id + " registrada.", "success");
                        }
                        var id = result.body.order_id;
                        setTimeout(function () {
                            window.location.href =
                                window.MiniErp.baseUrl.replace(/\/$/, "") + "/orders/show?id=" + encodeURIComponent(String(id));
                        }, 450);
                        return;
                    }

                    var msg = "Não foi possível registrar a venda.";
                    if (result.body && result.body.errors) {
                        msg = Object.values(result.body.errors).join(" ");
                    } else if (result.body && result.body.message) {
                        msg = result.body.message;
                    }

                    if (window.MiniErp && window.MiniErp.toast) {
                        window.MiniErp.toast("Erro", msg, "danger");
                    }
                })
                .catch(function () {
                    if (window.MiniErp && window.MiniErp.toast) {
                        window.MiniErp.toast("Erro", "Falha de rede ou servidor.", "danger");
                    }
                })
                .finally(function () {
                    if (btn) {
                        btn.disabled = false;
                    }
                    if (spinner) {
                        spinner.classList.add("d-none");
                    }
                });
        });
    });
})();
