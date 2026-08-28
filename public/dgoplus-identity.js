/**
 * DGO+ - bloco 3t: QR de identidade da DGO e auto-save do comentario do ativo.
 *
 * Carregado SO na tela do mapa (por <script src> emitido em DgoIdentity), e
 * nao pelo hook global ADD_JAVASCRIPT: junto com a biblioteca de QR sao ~60 KB
 * que nao tem o que fazer nas outras paginas do GLPI.
 *
 * Como o dgoplus.js do bloco 4a, este arquivo procura o seu markup antes de
 * qualquer coisa e sai calado se nao achar.
 */
(function () {
    'use strict';

    /** Tamanho do modulo do QR na TELA, em pixels de canvas */
    var CELL_SCREEN = 5;

    /** Tamanho do modulo do QR no arquivo BAIXADO (impressao) */
    var CELL_PRINT = 14;

    /** Borda clara obrigatoria do QR, em modulos (o padrao pede 4) */
    var QUIET = 4;

    /**
     * Bloco 5g-1b: o mesmo desenho do dgoplus.js (5g-1), com um gatilho
     * diferente. Aqui a recusa de ESCRITA chega como HTTP 200 + denied:true,
     * porque applyComment devolve erro em vez de lancar; o 403 so aparece se o
     * direito de LER cair, derrubando o checkRight do proprio endpoint.
     *
     * Nos dois casos o direito e' da SESSAO: falhou uma vez, falha sempre.
     * Sem esta trava, cada blur reenviava o mesmo texto e o log acumulava uma
     * requisicao por saida de campo (licao 133).
     */
    var permissionDenied = false;

    /**
     * Licao 119: mensagem de permissao que nao nomeia o direito faltante custa
     * horas. Reserva para o caso de o endpoint recusar sem texto - o normal e'
     * exibir a frase que vem do PHP, que e' a mesma do POST classico.
     */
    var DENIED_MESSAGE = 'Sem permissão para comentar. Exige «Atualizar» em «Portas de DGO» (Administração → Perfis → aba DGO+).';

    /**
     * Token CSRF para AJAX. Mesma leitura do dgoplus.js: o core expoe
     * getAjaxCsrfToken() em js/common.js, e o <meta> fica como reserva.
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

    // ------------------------------------------------------------------
    // QR
    // ------------------------------------------------------------------

    /**
     * Desenha o QR num canvas.
     *
     * O canvas e' dimensionado em multiplo exato do numero de modulos: QR com
     * modulo de tamanho fracionario fica com as bordas borradas pelo
     * antialiasing do navegador e para de ser lido por celular barato.
     *
     * @param {HTMLCanvasElement} canvas
     * @param {Object} qr          Objeto ja "made" da biblioteca
     * @param {number} cell        Pixels por modulo
     * @param {string|null} label  Texto opcional impresso abaixo do QR
     * @return {void}
     */
    function draw(canvas, qr, cell, label) {
        var count = qr.getModuleCount();
        var side = (count + QUIET * 2) * cell;
        var labelHeight = label ? Math.round(cell * 3.2) : 0;

        canvas.width = side;
        canvas.height = side + labelHeight;

        var ctx = canvas.getContext('2d');

        // Fundo branco explicito, sempre: canvas nasce transparente, e PNG
        // transparente impresso em papel sai com o QR invertido dependendo do
        // visualizador. QR invertido nao le.
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#000000';
        for (var r = 0; r < count; r++) {
            for (var c = 0; c < count; c++) {
                if (qr.isDark(r, c)) {
                    ctx.fillRect((c + QUIET) * cell, (r + QUIET) * cell, cell, cell);
                }
            }
        }

        if (label) {
            var fontSize = Math.round(cell * 2);
            ctx.fillStyle = '#000000';
            ctx.font = 'bold ' + fontSize + 'px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // Nome longo e' cortado com reticencias em vez de vazar do canvas.
            var text = label;
            var maxWidth = side - cell * 2;
            if (ctx.measureText(text).width > maxWidth) {
                while (text.length > 1 && ctx.measureText(text + '…').width > maxWidth) {
                    text = text.slice(0, -1);
                }
                text += '…';
            }

            ctx.fillText(text, side / 2, side + labelHeight / 2 - cell * 0.6);
        }
    }

    /**
     * @param {string} text
     * @return {Object|null} QR pronto, ou null se a biblioteca falhar
     */
    function build(text) {
        if (typeof window.qrcode !== 'function') {
            return null;
        }

        try {
            // typeNumber 0 = a biblioteca escolhe a menor versao que couber
            // (qrcode.js:424). 'M' e' o nivel de correcao usual: ~15% de
            // tolerancia, que e' o que salva etiqueta suja de sala tecnica.
            var qr = window.qrcode(0, 'M');
            qr.addData(text);
            qr.make();
            return qr;
        } catch (e) {
            return null;
        }
    }

    /**
     * @param {HTMLElement} root Elemento com data-dgoplus-qr
     * @return {void}
     */
    function mountQr(root) {
        var canvas = root.querySelector('[data-qr-canvas]');
        var fallback = root.querySelector('[data-qr-fallback]');
        var button = root.querySelector('[data-qr-download]');

        if (canvas === null) {
            return;
        }

        var path = root.getAttribute('data-qr-path') || '';
        var label = root.getAttribute('data-qr-label') || '';
        var filename = root.getAttribute('data-qr-filename') || 'dgo';

        if (!path) {
            return;
        }

        // O host vem do navegador, nao do PHP: e' o mesmo endereco pelo qual a
        // pessoa ja esta acessando o GLPI, entao o QR nunca aponta para um
        // host que so existe do lado do servidor.
        var url = window.location.origin + path;

        var qr = build(url);
        if (qr === null) {
            return;
        }

        draw(canvas, qr, CELL_SCREEN, null);

        if (fallback !== null) {
            fallback.parentNode.removeChild(fallback);
        }

        // A URL nao e' mais impressa embaixo do QR. Fica no title: passar o
        // mouse mostra o destino sem ocupar a coluna, e sem precisar escanear.
        canvas.title = url;

        if (button !== null) {
            button.hidden = false;
            button.addEventListener('click', function () {
                // Canvas separado, em alta resolucao e com o nome da DGO
                // embaixo: o da tela e' pequeno de proposito e reaproveita-lo
                // daria um arquivo de 150 px, inutil impresso.
                var out = document.createElement('canvas');
                draw(out, qr, CELL_PRINT, label);

                var link = document.createElement('a');
                link.href = out.toDataURL('image/png');
                link.download = 'qr-' + filename + '.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    }

    // ------------------------------------------------------------------
    // Comentario do ativo
    // ------------------------------------------------------------------

    /**
     * @param {HTMLFormElement} form
     * @return {void}
     */
    function mountComment(form) {
        // A area de status so e' impressa quando o usuario tem direito de
        // escrita, entao a ausencia dela e' o sinal de "somente leitura" -
        // mesma convencao do bloco 4a.
        var flag = form.querySelector('[data-dgoplus-dgo-flag]');
        if (flag === null) {
            return;
        }

        var endpoint = form.getAttribute('data-dgoplus-endpoint');
        var field = form.querySelector('[name="comment"]');
        if (!endpoint || field === null) {
            return;
        }

        var inFlight = false;
        var lastSaved = field.value;

        // A frase exibida enquanto a permissao estiver negada. Comeca com a
        // reserva e passa a ser a do PHP assim que o endpoint responder: a
        // recusa do ponto unico e a da tela nao podem divergir (licao 47).
        var deniedText = DENIED_MESSAGE;

        function setFlag(text, cssClass) {
            flag.textContent = text;
            flag.className = 'small ' + (cssClass || '');
        }

        /**
         * @param {boolean} fallbackOnFailure Se true, uma falha de rede posta o
         *        formulario de verdade em vez de perder a edicao.
         */
        function save(fallbackOnFailure) {
            if (permissionDenied) {
                // Nao reenvia - mas tambem nao fica mudo (licao 16): quem
                // continuar digitando tem que seguir vendo POR QUE nao salva.
                setFlag(deniedText, 'text-danger');
                return;
            }

            var current = field.value;
            if (inFlight || current === lastSaved) {
                return;
            }

            inFlight = true;
            setFlag('Salvando…', 'text-muted');

            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': csrfToken()
                },
                body: new FormData(form)
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
                    if (data && data.denied === true) {
                        // Recusa de PERMISSAO: vale para a pagina inteira e
                        // nao muda se o usuario digitar outra coisa.
                        permissionDenied = true;
                        deniedText = data.message ? data.message : DENIED_MESSAGE;
                        setFlag(deniedText, 'text-danger');
                        return;
                    }

                    setFlag(
                        data && data.message ? data.message : 'Não foi possível salvar.',
                        'text-danger'
                    );
                    return;
                }

                lastSaved = current;
                setFlag('Salvo ✓', 'text-success');
            }).catch(function (error) {
                inFlight = false;

                if (error && error.status === 403) {
                    // Aqui o 403 vem do checkRight(READ) do proprio endpoint:
                    // o direito de LER caiu com a aba aberta. O fallback de
                    // postar a pagina NAO roda - ele tomaria o mesmo 403,
                    // agora levando junto o comentario que o usuario digitou.
                    permissionDenied = true;
                    deniedText = 'Sem permissão para ver este elemento. Sua permissão mudou: copie o texto e recarregue a página.';
                    setFlag(deniedText, 'text-danger');
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

        // Ao sair do campo, nao a cada tecla: o ativo tem historico, e gravar a
        // cada letra encheria a ficha de versoes parciais.
        field.addEventListener('blur', function () {
            save(false);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            lastSaved = null;
            save(true);
        });
    }

    function init() {
        var cards = document.querySelectorAll('[data-dgoplus-qr]');
        for (var i = 0; i < cards.length; i++) {
            mountQr(cards[i]);
        }

        var forms = document.querySelectorAll('form[data-dgoplus-dgo-comment]');
        for (var j = 0; j < forms.length; j++) {
            mountComment(forms[j]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
