/**
 * DGO+ - bloco 4a: auto-save do painel da porta.
 *
 * Este arquivo e' carregado em TODA pagina do GLPI (o hook ADD_JAVASCRIPT e'
 * global), entao a primeira coisa que ele faz e' procurar o formulario do
 * painel: se nao achar, sai sem tocar em nada.
 *
 * Principio do bloco: o formulario continua sendo um POST completo e valido.
 * O JS apenas INTERCEPTA. Se este arquivo nao carregar, ou se o fetch falhar,
 * o botao Salvar volta a recarregar a pagina como antes do 4a - nenhuma
 * funcionalidade depende de JavaScript para existir.
 */
(function () {
    'use strict';

    var SELECTOR = 'form[data-dgoplus-port-form]';

    /**
     * Token CSRF para AJAX. O core expoe getAjaxCsrfToken() em js/common.js,
     * que le o <meta property="glpi:csrf_token"> do head. A leitura direta do
     * meta fica como reserva, para o caso de ordem de carregamento.
     *
     * No GLPI 11.0.6 o token de AJAX NAO e' consumido na validacao
     * (CheckCsrfListener chama checkCSRF com preserve_token: true), entao o
     * mesmo valor serve para todas as requisicoes da pagina.
     */
    function csrfToken() {
        if (typeof window.getAjaxCsrfToken === 'function') {
            var fromCore = window.getAjaxCsrfToken();
            if (fromCore) {
                return fromCore;
            }
        }

        var meta = document.querySelector('meta[property="glpi:csrf_token"]');

        return meta !== null ? meta.getAttribute('content') : '';
    }

    function mount(form) {
        // A area de status so e' impressa quando o usuario tem direito de
        // escrita, entao a ausencia dela e' o sinal de "somente leitura" - sem
        // precisar de atributo novo para dizer a mesma coisa.
        var flag = form.querySelector('[data-dgoplus-flag]');
        if (flag === null) {
            return;
        }

        var endpoint = form.getAttribute('data-dgoplus-endpoint');
        var cellKey = form.getAttribute('data-dgoplus-cell-key');
        if (!endpoint || !cellKey) {
            return;
        }

        var code = form.querySelector('[name="code"]');
        var comment = form.querySelector('[name="comment"]');
        var noCoupler = form.querySelector('input[type="checkbox"][name="is_no_coupler"]');

        var inFlight = false;
        var lastSaved = snapshot();

        function snapshot() {
            return JSON.stringify([
                code ? code.value.trim() : '',
                comment ? comment.value.trim() : '',
                noCoupler && noCoupler.checked ? 1 : 0
            ]);
        }

        function setFlag(text, cssClass) {
            flag.textContent = text;
            flag.className = 'small ' + (cssClass || '');
        }

        function replaceCell(html) {
            var cell = document.querySelector('[data-dgoplus-cell="' + cellKey + '"]');
            if (cell !== null && html) {
                cell.outerHTML = html;
            }
        }

        function replaceBadges(html) {
            var badges = document.getElementById('dgoplus-badges');
            if (badges !== null && html) {
                badges.innerHTML = html;
            }
        }

        /**
         * @param {boolean} fallbackOnFailure Se true, uma falha de rede faz o
         *        formulario ser postado de verdade, em vez de perder a edicao.
         */
        function save(fallbackOnFailure) {
            var current = snapshot();
            if (inFlight || current === lastSaved) {
                return;
            }

            inFlight = true;
            setFlag('Salvando…', 'text-muted');

            var body = new FormData(form);

            // FormData de checkbox desmarcado nao inclui a chave. O <input
            // hidden name="is_no_coupler" value="0"> que vem antes do checkbox
            // no HTML garante o 0 (licao 45) - e por isso nao se mexe aqui.

            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': csrfToken()
                },
                body: body
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            }).then(function (data) {
                inFlight = false;

                if (!data || data.ok !== true) {
                    // Recusa de regra (ex.: sem acoplador com numero de loja).
                    // A celula NAO e' tocada: nada foi gravado.
                    setFlag(data && data.message ? data.message : 'Não foi possível salvar.', 'text-danger');
                    return;
                }

                lastSaved = current;
                replaceCell(data.cell_html);
                replaceBadges(data.badges_html);
                setFlag('Salvo ✓', 'text-success');
            }).catch(function () {
                inFlight = false;

                if (fallbackOnFailure) {
                    // Ultimo recurso: posta o formulario de verdade. submit()
                    // programatico nao dispara o listener de submit, entao nao
                    // ha risco de laco.
                    setFlag('Salvando pela página…', 'text-muted');
                    form.submit();
                    return;
                }

                setFlag('Falha ao salvar. Use o botão Salvar.', 'text-danger');
            });
        }

        // Auto-save ao sair do campo, nao a cada tecla: Port tem
        // $dohistory = true, e gravar a cada letra encheria o Historico do DGO
        // de versoes parciais. O interruptor salva na hora porque nao ha o que
        // digitar nele.
        if (code) {
            code.addEventListener('blur', function () {
                save(false);
            });
        }
        if (comment) {
            comment.addEventListener('blur', function () {
                save(false);
            });
        }
        if (noCoupler) {
            noCoupler.addEventListener('change', function () {
                save(false);
            });
        }

        // O submit tambem passa a ser AJAX, para evitar corrida: clicar em
        // Salvar dispara o blur do campo, e sem isso a mesma porta seria
        // gravada duas vezes ao mesmo tempo.
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            lastSaved = null;
            save(true);
        });
    }

    function init() {
        var forms = document.querySelectorAll(SELECTOR);
        for (var i = 0; i < forms.length; i++) {
            mount(forms[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
