<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
use Html;
use PassiveDCEquipment;

/**
 * DGO+ - bloco 3t: identidade da DGO na coluna da direita.
 *
 * Duas coisas que nao cabiam em MapController sem inchar mais um arquivo de
 * 2000 linhas, e que sao do ATIVO, nao da porta:
 *
 *   1. QR de identidade, que abre esta mesma tela ja posicionada nesta DGO;
 *   2. o campo 'comment' NATIVO do ativo, editavel sem sair do mapa.
 *
 * O comentario e' o mesmo campo que aparece na ficha padrao do GLPI. NAO e'
 * campo novo: nada foi acrescentado ao schema, e por isso este bloco nao toca
 * em Install.php nem exige reinstalacao. O que se escreve aqui aparece la, e
 * vice-versa - inclusive no Historico do ativo, porque a gravacao passa por
 * CommonDBTM::update().
 *
 * NAO confundir com o 'comment' da PORTA (Port::applyInput): sao tabelas
 * diferentes, telas diferentes e direitos diferentes. Este exige UPDATE no
 * ativo (direito 'datacenter'); o da porta exige o direito do plugin.
 */
class DgoIdentity
{
    /** Destaque do projeto, o mesmo do MapController */
    private const ACCENT = '#2FBFB0';

    /**
     * Guarda de idempotencia dos <script>: displayQrCard() e' chamada uma vez
     * por carga de pagina hoje, mas emitir a biblioteca duas vezes seria um
     * defeito silencioso (o segundo <script> redefine `qrcode` e qualquer
     * estado preso a ele).
     *
     * @var bool
     */
    private static bool $assets_done = false;

    /**
     * URL de um estatico do plugin.
     *
     * O arquivo mora em public/, mas o 'public/' NAO entra na URL: o roteador
     * do GLPI 11 ja procura dentro dele (RequestRouterTrait::getTargetFile,
     * 11.0.6:87-97). Mesma regra do dgoplus.js no setup.php (licao 49).
     *
     * @param string $file
     * @return string
     */
    private static function getAssetUrl(string $file): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/' . $file;
    }

    /**
     * URL de um endpoint ajax/ do plugin.
     *
     * @param string $file
     * @return string
     */
    private static function getAjaxUrl(string $file): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/ajax/' . $file;
    }

    /**
     * Carrega a biblioteca de QR e o JS do bloco.
     *
     * De proposito NAO vai pelo hook ADD_JAVASCRIPT: aquele hook e' global e
     * carregaria 56 KB de gerador de QR em TODA pagina do GLPI, inclusive nas
     * que nao tem DGO nenhuma. Aqui os dois arquivos entram so na tela do
     * mapa, e depois do markup que eles procuram.
     *
     * @return void
     */
    private static function displayAssets(): void
    {
        if (self::$assets_done) {
            return;
        }
        self::$assets_done = true;

        echo "<script src='" . htmlescape(self::getAssetUrl('qrcode.js')) . "'></script>";
        echo "<script src='" . htmlescape(self::getAssetUrl('dgoplus-identity.js')) . "'></script>";
    }

    /**
     * Caminho (relativo a' raiz do site) que o QR vai apontar.
     *
     * Devolve o caminho, nao a URL absoluta, e quem completa o host e' o
     * NAVEGADOR (location.origin, no JS). Montar o absoluto aqui exigiria
     * decidir entre $CFG_GLPI['url_base'] e root_doc, e as duas se sobrepoem
     * quando o GLPI vive num subdiretorio - o resultado seria um QR com
     * /glpi/glpi/ no meio, que so falharia na hora de escanear, longe da tela.
     * O origin do navegador e' exatamente o host pelo qual as pessoas ja
     * acessam o sistema.
     *
     * @param CommonDBTM $dgo
     * @return string
     */
    private static function qrPath(CommonDBTM $dgo): string
    {
        return MapController::getUrlForDgo($dgo);
    }

    /**
     * Card do QR de identidade da DGO.
     *
     * O desenho e' feito no navegador, em <canvas>, a partir da biblioteca
     * vendorizada em public/qrcode.js (MIT, Kazuhiko Arase). Nada de CDN: a
     * tela tem que funcionar em rede fechada, que e' o caso comum de sala
     * tecnica.
     *
     * @param CommonDBTM $dgo
     * @return void
     */
    public static function displayQrCard(CommonDBTM $dgo): void
    {
        $items_id = (int) $dgo->getID();
        $name     = (string) ($dgo->fields['name'] ?? '');
        if ($name === '') {
            $name = '#' . $items_id;
        }

        self::displayAssets();

        echo "<div class='card mb-3'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-qrcode'></i>" . htmlescape(__('QR da DGO', 'dgoplus')) . "</h3>";
        echo "</div>";

        echo "<div class='card-body text-center'>";

        // data-* sao o contrato com o JS. O canvas nasce vazio e sem tamanho
        // fixo: quem define as medidas e' o JS, ja sabendo quantos modulos o
        // QR tem, para nao existir meio-pixel (QR borrado nao le).
        //
        // A URL NAO e' impressa embaixo do QR (decisao do usuario, 06/08/2026):
        // o QR ja E' o link, e o texto so ocupava a coluna. Ela vai para o
        // title do canvas - passar o mouse ainda mostra, para conferir sem
        // precisar de celular.
        echo "<div data-dgoplus-qr='1'"
            . " data-qr-path='" . htmlescape(self::qrPath($dgo)) . "'"
            . " data-qr-label='" . htmlescape($name) . "'"
            . " data-qr-filename='" . htmlescape(self::slug($name)) . "'>";

        echo "<canvas data-qr-canvas='1' style='max-width:100%;height:auto;border-radius:6px'></canvas>";

        // Sem JS o canvas fica em branco, e branco parece defeito (licao 16).
        // Esta linha e' apagada pelo proprio JS quando o desenho da certo.
        echo "<div data-qr-fallback='1' class='text-muted small'>"
            . htmlescape(__('Ative o JavaScript para gerar o QR.', 'dgoplus'))
            . "</div>";

        echo "<div class='d-flex justify-content-center gap-2 mt-2'>";
        echo "<button type='button' class='btn btn-sm btn-outline-primary' data-qr-download='1' hidden>"
            . "<i class='ti ti-download me-1'></i>"
            . htmlescape(__('Baixar QR para impressão', 'dgoplus'))
            . "</button>";
        echo "</div>";

        echo "</div>"; // wrapper data-dgoplus-qr
        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * Nome de arquivo seguro a partir do nome da DGO.
     *
     * O nome da DGO e' texto livre e vira nome de arquivo baixado; barra,
     * aspas e acento em nome de download quebram de um jeito diferente em
     * cada navegador. Aqui o resultado e' sempre [a-z0-9-].
     *
     * @param string $name
     * @return string
     */
    private static function slug(string $name): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        if ($ascii === false) {
            $ascii = $name;
        }

        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $ascii));
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'dgo';
    }

    /**
     * Quem pode escrever no comentario do ativo.
     *
     * E' o direito do ATIVO (datacenter UPDATE), nao o do plugin: o campo
     * pertence a' ficha do PassiveDCEquipment. Quem tem o direito do plugin
     * mas nao o do ativo continua vendo o texto, em somente leitura - e ve por
     * que, em vez de encontrar um campo que nao obedece.
     *
     * @param CommonDBTM $dgo
     * @return bool
     */
    public static function canWriteComment(CommonDBTM $dgo): bool
    {
        $items_id = (int) $dgo->getID();

        return $items_id > 0 && $dgo->can($items_id, UPDATE);
    }

    /**
     * Card do comentario do ativo, com auto-save.
     *
     * O formulario e' um POST completo e valido (action=save_dgo_comment,
     * tratado em MapController::handlePost). O JS apenas intercepta - se ele
     * nao carregar, o botao Salvar recarrega a pagina e grava do mesmo jeito.
     * Mesmo principio do painel da porta (bloco 4a).
     *
     * @param CommonDBTM $dgo
     * @param int        $locations_id
     * @param string     $edit_key
     * @param int        $floors_id
     * @return void
     */
    public static function displayCommentCard(
        CommonDBTM $dgo,
        int $locations_id,
        string $edit_key = '',
        int $floors_id = 0
    ): void {
        $items_id  = (int) $dgo->getID();
        $comment   = (string) ($dgo->fields['comment'] ?? '');
        $can_write = self::canWriteComment($dgo);

        echo "<div class='card'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-message-2'></i>" . htmlescape(__('Comentários', 'dgoplus')) . "</h3>";
        echo "</div>";

        echo "<div class='card-body'>";

        echo "<form method='post' action='" . htmlescape(MapController::getPublicPageUrl()) . "'"
            . " data-dgoplus-dgo-comment='1'"
            . " data-dgoplus-endpoint='" . htmlescape(self::getAjaxUrl('dgocomment.php')) . "'>";
        echo Html::hidden('action', ['value' => 'save_dgo_comment']);
        echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
        echo Html::hidden('items_id', ['value' => $items_id]);
        echo Html::hidden('locations_id', ['value' => $locations_id]);
        echo Html::hidden('floor', ['value' => $floors_id]);
        if ($edit_key !== '') {
            echo Html::hidden('edit', ['value' => $edit_key]);
        }

        echo "<textarea name='comment' class='form-control' rows='4' placeholder='"
            . htmlescape(__('Observações desta DGO: acesso, chave, reserva técnica…', 'dgoplus')) . "'"
            . ($can_write ? '' : ' readonly') . ">" . htmlescape($comment) . "</textarea>";

        echo "<div class='form-hint mt-1'>"
            . htmlescape(__('Este é o campo Comentários da ficha do ativo.', 'dgoplus'))
            . "</div>";

        if ($can_write) {
            echo "<div class='d-flex justify-content-end align-items-center gap-2 mt-2'>";
            // Comeca vazio: so o JS escreve aqui. A ausencia deste span e' o
            // sinal de "somente leitura" para o JS, exatamente como no 4a.
            echo "<span data-dgoplus-dgo-flag='1' class='small' aria-live='polite'></span>";
            echo Html::submit(_sx('button', 'Save'), [
                'name'  => 'update_dgo_comment',
                'class' => 'btn btn-sm btn-primary',
            ]);
            echo "</div>";
        } else {
            echo "<div class='text-muted small mt-2'>"
                . htmlescape(__('Você tem permissão apenas de leitura neste ativo.', 'dgoplus'))
                . "</div>";
        }

        Html::closeForm();

        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * Grava o comentario do ativo.
     *
     * Implementacao unica da regra, usada pelo POST classico e pelo endpoint
     * AJAX - o mesmo desenho do Port::applyInput, e pelo mesmo motivo: dois
     * caminhos de gravacao com regras proprias divergem em silencio (licao 47).
     *
     * NAO usa addMessageAfterRedirect: em caminho de AJAX a mensagem ficaria
     * guardada na sessao e apareceria fora de contexto na proxima navegacao.
     * Quem chama pelo POST e' que decide o que fazer com o retorno.
     *
     * @param array $input ['itemtype', 'items_id', 'comment']
     * @return array{ok: bool, error: string}
     */
    public static function applyComment(array $input): array
    {
        $itemtype = (string) ($input['itemtype'] ?? PassiveDCEquipment::class);
        $items_id = (int) ($input['items_id'] ?? 0);
        $comment  = (string) ($input['comment'] ?? '');

        if ($itemtype !== PassiveDCEquipment::class) {
            return ['ok' => false, 'error' => __('Tipo de ativo inválido.', 'dgoplus')];
        }

        $dgo = new PassiveDCEquipment();

        // Ativo tem que existir e ser LEGIVEL para este usuario, antes de
        // qualquer coisa: sem isso um items_id forjado no POST viraria
        // escrita em ativo de outra entidade.
        if ($items_id <= 0 || !$dgo->getFromDB($items_id) || !$dgo->can($items_id, READ)) {
            return ['ok' => false, 'error' => __('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus')];
        }

        if (!self::canWriteComment($dgo)) {
            return ['ok' => false, 'error' => __('Você não tem permissão para alterar este ativo.', 'dgoplus')];
        }

        // Nada mudou: nao gravar. update() com valor identico ainda assim
        // carimba date_mod e polui o Historico do ativo, e o auto-save dispara
        // no blur - bastaria clicar no campo e sair para sujar a ficha.
        if ((string) ($dgo->fields['comment'] ?? '') === $comment) {
            return ['ok' => true, 'error' => ''];
        }

        // Somente 'id' e 'comment' no input: campo que nao esta no array nao e'
        // tocado pelo update, e incluir campo a mais aqui apagaria dado da
        // ficha do ativo sem ninguem pedir (licao 44).
        $done = $dgo->update([
            'id'      => $items_id,
            'comment' => $comment,
        ]);

        if (!$done) {
            return ['ok' => false, 'error' => __('Não foi possível salvar o comentário.', 'dgoplus')];
        }

        return ['ok' => true, 'error' => ''];
    }
}
