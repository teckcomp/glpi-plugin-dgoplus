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
     * Bloco 5g-1: o 403 do endpoint quer dizer que o perfil NAO tem o direito
     * de escrita - a tela foi montada com ele, ou o direito saiu no meio da
     * sessao. E' estado da SESSAO, nao da celula: negado numa porta, negado em
     * todas as portas da pagina. Sem esta trava, cada blur reenviava o mesmo
     * conteudo e o log acumulava um 403 por campo (licao 133).
     */
    var permissionDenied = false;

    /**
     * Licao 119: mensagem de permissao que nao nomeia o direito faltante custa
     * horas. Esta e' a mesma frase que o PHP usa nas recusas do 5f-1a e 5f-2a.
     */
    var DENIED_MESSAGE = 'Sem permissão para documentar portas. Exige «Atualizar» em «Portas de DGO» (Administração → Perfis → aba DGO+).';

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
            if (permissionDenied) {
                // Nao reenvia - mas tambem nao fica mudo (licao 16): quem
                // continuar digitando tem que seguir vendo POR QUE nao salva.
                setFlag(DENIED_MESSAGE, 'text-danger');
                return;
            }

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
                    // O status precisa CHEGAR ao catch: sem ele, 403 de
                    // permissao e queda de rede sao o mesmo erro generico, e a
                    // tela mente para o usuario (licao 14).
                    var httpError = new Error('HTTP ' + response.status);
                    httpError.status = response.status;
                    throw httpError;
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
            }).catch(function (error) {
                inFlight = false;

                if (error && error.status === 403) {
                    // Falha de PERMISSAO, nao de rede. O fallback de postar a
                    // pagina nao roda: ele tomaria o mesmo 403, agora levando
                    // junto o que o usuario digitou.
                    permissionDenied = true;
                    setFlag(DENIED_MESSAGE, 'text-danger');
                    return;
                }

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

/**
 * DGO+ - bloco 4c: proposta de vinculo (secao "Alimenta" do painel).
 *
 * Desabilita as entradas E1-E4 ja ocupadas do elemento de destino escolhido,
 * lendo o JSON que o PHP embute no formulario. PROGRESSIVO: sem este arquivo
 * o formulario posta normal e o servidor recusa entrada ocupada com mensagem
 * amigavel (Link::propose) - a validacao que conta e' a do servidor; aqui e'
 * so' conveniencia para nao deixar o usuario escolher o que vai ser recusado.
 */
(function () {
    'use strict';

    function mount(form) {
        var dst = form.querySelector('select[data-dgoplus-link-dst]');
        var slot = form.querySelector('select[data-dgoplus-link-slot]');
        var dataEl = form.querySelector('script[data-dgoplus-link-occupied]');
        if (!dst || !slot || !dataEl) {
            return;
        }

        var occupied = {};
        try {
            var parsed = JSON.parse(dataEl.textContent || '{}');
            if (parsed && typeof parsed === 'object') {
                occupied = parsed;
            }
        } catch (e) {
            // JSON podre: melhor nao mexer em nada do que desabilitar errado -
            // o servidor continua validando de qualquer forma.
            return;
        }

        // Rotulos originais (E1..E4), para reescrever o sufixo a cada troca
        // de elemento sem acumular " - ocupada - ocupada".
        var labels = [];
        for (var i = 0; i < slot.options.length; i++) {
            labels.push(slot.options[i].textContent);
        }

        function refresh() {
            var taken = occupied[dst.value];
            if (!Array.isArray(taken)) {
                taken = [];
            }

            for (var i = 0; i < slot.options.length; i++) {
                var opt = slot.options[i];
                var isTaken = taken.indexOf(parseInt(opt.value, 10)) !== -1;
                opt.disabled = isTaken;
                opt.textContent = labels[i] + (isTaken ? ' — ocupada' : ' — livre');
            }

            // Se a selecao atual caiu numa ocupada, pula para a primeira livre.
            if (slot.selectedIndex === -1 || slot.options[slot.selectedIndex].disabled) {
                for (var j = 0; j < slot.options.length; j++) {
                    if (!slot.options[j].disabled) {
                        slot.selectedIndex = j;
                        break;
                    }
                }
            }
        }

        dst.addEventListener('change', refresh);
        refresh();
    }

    function init() {
        var forms = document.querySelectorAll('form[data-dgoplus-link-form]');
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

/**
 * DGO+ - bloco 5a: escopo do elemento de destino (Localizacao > Piso).
 *
 * Poda o <select dst_items_id> para os elementos da localizacao e do piso
 * escolhidos, lendo o JSON que o PHP embute no formulario. Roda DEPOIS do
 * modulo 4c, entao o listener de entradas ocupadas ja esta montado e o
 * dispatch de 'change' o obriga a recalcular E1-E4 do novo elemento.
 *
 * PROGRESSIVO: sem este arquivo os tres selects aparecem completos e o
 * formulario posta igual. Os seletores de localizacao e piso nao tem `name`
 * e nunca chegam ao servidor - quem valida o vinculo e' o Link::propose.
 */
(function () {
    'use strict';

    function mount(form) {
        var loc = form.querySelector('select[data-dgoplus-link-loc]');
        var floor = form.querySelector('select[data-dgoplus-link-floor]');
        var dst = form.querySelector('select[data-dgoplus-link-dst]');
        var dataEl = form.querySelector('script[data-dgoplus-link-scope]');
        if (!loc || !floor || !dst || !dataEl) {
            return;
        }

        var scope = { items: {}, floors: {} };
        try {
            var parsed = JSON.parse(dataEl.textContent || '{}');
            if (parsed && typeof parsed === 'object') {
                scope.items = parsed.items || {};
                scope.floors = parsed.floors || {};
            }
        } catch (e) {
            // JSON podre: deixa os tres selects completos, como sem JS.
            return;
        }

        // Guarda as listas originais: podar tem que ser sempre a partir do
        // conjunto inteiro, nunca do resultado da poda anterior.
        var allDst = [];
        for (var i = 0; i < dst.options.length; i++) {
            allDst.push({ value: dst.options[i].value, label: dst.options[i].textContent });
        }
        var allFloors = [];
        for (var j = 1; j < floor.options.length; j++) {
            allFloors.push({ value: floor.options[j].value, label: floor.options[j].textContent });
        }
        var floorEmpty = floor.options.length ? floor.options[0].textContent : 'Todos os pisos';

        function rebuild(select, rows, keepValue) {
            var previous = keepValue ? select.value : '';
            select.innerHTML = '';
            for (var k = 0; k < rows.length; k++) {
                var opt = document.createElement('option');
                opt.value = rows[k].value;
                opt.textContent = rows[k].label;
                select.appendChild(opt);
            }
            if (previous !== '') {
                select.value = previous;
                if (select.selectedIndex === -1 && select.options.length) {
                    select.selectedIndex = 0;
                }
            }
        }

        function refreshFloors() {
            var wanted = parseInt(loc.value, 10) || 0;
            var rows = [{ value: '0', label: floorEmpty }];
            for (var k = 0; k < allFloors.length; k++) {
                var owner = parseInt(scope.floors[allFloors[k].value], 10) || 0;
                if (wanted === 0 || owner === wanted) {
                    rows.push(allFloors[k]);
                }
            }
            rebuild(floor, rows, true);
        }

        function refreshDst() {
            var wantedLoc = parseInt(loc.value, 10) || 0;
            var wantedFloor = parseInt(floor.value, 10) || 0;
            var rows = [];
            for (var k = 0; k < allDst.length; k++) {
                var info = scope.items[allDst[k].value];
                if (!info) {
                    continue;
                }
                var okLoc = wantedLoc === 0 || (parseInt(info.loc, 10) || 0) === wantedLoc;
                var okFloor = wantedFloor === 0 || (parseInt(info.floor, 10) || 0) === wantedFloor;
                if (okLoc && okFloor) {
                    rows.push(allDst[k]);
                }
            }

            // Escopo sem nenhum elemento: em vez de um select vazio e mudo,
            // uma opcao que DIZ o que aconteceu (licao 16).
            if (rows.length === 0) {
                rebuild(dst, [{ value: '', label: '— nenhum elemento neste escopo —' }], false);
                dst.dispatchEvent(new Event('change'));
                return;
            }

            rebuild(dst, rows, true);
            dst.dispatchEvent(new Event('change'));
        }

        loc.addEventListener('change', function () {
            refreshFloors();
            refreshDst();
        });
        floor.addEventListener('change', refreshDst);

        refreshFloors();
        refreshDst();
    }

    function init() {
        var forms = document.querySelectorAll('form[data-dgoplus-link-form]');
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
