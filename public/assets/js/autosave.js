(function () {
    "use strict";

    var PREFIX = "mini-erp-autosave:";

    function scopeSuffix() {
        var body = document.body;
        if (!body) {
            return "";
        }
        var uid = body.dataset.userId || "0";
        var cid = body.dataset.companyId || "0";
        return "u" + uid + ":c" + cid;
    }

    function fullKey(baseKey) {
        return PREFIX + scopeSuffix() + ":" + baseKey;
    }

    function read(baseKey) {
        try {
            var raw = localStorage.getItem(fullKey(baseKey));
            if (!raw) {
                return null;
            }
            var parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== "object" || !parsed.state) {
                return null;
            }
            return parsed;
        } catch (e) {
            return null;
        }
    }

    function write(baseKey, state) {
        try {
            var payload = {
                state: state,
                savedAt: new Date().toISOString(),
            };
            localStorage.setItem(fullKey(baseKey), JSON.stringify(payload));
            return payload.savedAt;
        } catch (e) {
            return null;
        }
    }

    function clear(baseKey) {
        try {
            localStorage.removeItem(fullKey(baseKey));
        } catch (e) {
            /* ignore */
        }
    }

    function formatTimeBr(iso) {
        try {
            var d = new Date(iso);
            return d.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
        } catch (e) {
            return "";
        }
    }

    function updateStatus(el, iso) {
        if (!el) {
            return;
        }
        if (!iso) {
            el.classList.add("d-none");
            el.textContent = "";
            return;
        }
        var t = formatTimeBr(iso);
        el.classList.remove("d-none");
        el.innerHTML =
            '<i class="bi bi-cloud-check me-1" aria-hidden="true"></i>' +
            "Rascunho salvo automaticamente" +
            (t ? " às " + t : "") +
            '. <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-autosave-discard>Descartar</button>';
    }

    /**
     * @param {{
     *   key: string,
     *   form: HTMLFormElement,
     *   getState: function(): object,
     *   applyState: function(object): void,
     *   isEmpty?: function(object): boolean,
     *   skipRestore?: boolean,
     *   statusEl?: HTMLElement|null,
     *   debounceMs?: number,
     *   clearOnSubmit?: boolean
     * }} options
     */
    function init(options) {
        var baseKey = options.key;
        var form = options.form;
        var getState = options.getState;
        var applyState = options.applyState;
        var isEmpty = options.isEmpty || function () {
            return false;
        };
        var statusEl = options.statusEl || null;
        var debounceMs = options.debounceMs || 700;
        var timer = null;

        function persist() {
            var state = getState();
            if (isEmpty(state)) {
                clear(baseKey);
                updateStatus(statusEl, null);
                return;
            }
            var savedAt = write(baseKey, state);
            updateStatus(statusEl, savedAt);
        }

        function scheduleSave() {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(persist, debounceMs);
        }

        function restoreDraft() {
            var entry = read(baseKey);
            if (!entry) {
                return false;
            }
            applyState(entry.state);
            updateStatus(statusEl, entry.savedAt);
            if (window.MiniErp && window.MiniErp.toast) {
                window.MiniErp.toast("Rascunho", "Dados restaurados do salvamento automático.", "info");
            }
            return true;
        }

        if (!options.skipRestore) {
            var entry = read(baseKey);
            if (entry && !isEmpty(entry.state)) {
                var emptyNow = isEmpty(getState());
                if (emptyNow) {
                    restoreDraft();
                } else if (statusEl) {
                    statusEl.classList.remove("d-none");
                    statusEl.innerHTML =
                        '<i class="bi bi-cloud-download me-1" aria-hidden="true"></i>' +
                        "Há um rascunho salvo" +
                        (entry.savedAt ? " às " + formatTimeBr(entry.savedAt) : "") +
                        '. <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-autosave-restore>Restaurar</button>' +
                        ' <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-autosave-discard>Descartar</button>';
                }
            }
        }

        form.addEventListener("input", scheduleSave);
        form.addEventListener("change", scheduleSave);

        if (options.clearOnSubmit !== false) {
            form.addEventListener("submit", function () {
                clear(baseKey);
                updateStatus(statusEl, null);
            });
        }

        if (statusEl) {
            statusEl.addEventListener("click", function (ev) {
                var target = ev.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }
                if (target.matches("[data-autosave-restore]")) {
                    restoreDraft();
                    scheduleSave();
                }
                if (target.matches("[data-autosave-discard]")) {
                    clear(baseKey);
                    updateStatus(statusEl, null);
                }
            });
        }

        return {
            save: persist,
            clear: function () {
                clear(baseKey);
                updateStatus(statusEl, null);
            },
            restore: restoreDraft,
        };
    }

    window.MiniErp = window.MiniErp || {};
    window.MiniErp.autosave = {
        init: init,
        clear: clear,
        read: read,
        write: write,
    };
})();
