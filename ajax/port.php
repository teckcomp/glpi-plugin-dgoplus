<?php

/**
 * DGO+ - bloco 4a: gravacao de porta por AJAX.
 *
 * Arquivos em ajax/ de plugin sao servidos direto pelo roteador do GLPI 11
 * (RequestRouterTrait::getTargetFile, 11.0.6:87-97) e NAO precisam de
 * bootstrap: a sessao, o autoload e o $CFG_GLPI ja estao de pe (licao 3).
 *
 * O token CSRF NAO e' validado aqui: o CheckCsrfListener do core
 * (src/Glpi/Kernel/Listener/ControllerListener/CheckCsrfListener.php:80) faz
 * isso sozinho, lendo o header X-Glpi-Csrf-Token quando a requisicao e' AJAX.
 * Chamar Session::checkCSRF na mao consumiria o token duas vezes.
 *
 * A resposta devolve o HTML da celula e dos badges renderizado pelo PHP - o
 * JS nao monta markup, para nao existir uma segunda versao da regra de cor.
 */

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Dgoplus\MapController;
use GlpiPlugin\Dgoplus\Panel;
use GlpiPlugin\Dgoplus\Port;

header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new BadRequestHttpException('Only POST is accepted.');
}

// Porta de entrada do plugin. Os direitos finos (CREATE/UPDATE/DELETE) sao
// checados dentro de Port::applyInput, exatamente como no POST classico.
Session::checkRight(Port::$rightname, READ);

$itemtype     = (string) ($_POST['itemtype'] ?? PassiveDCEquipment::class);
$items_id     = (int) ($_POST['items_id'] ?? 0);
$tube_num     = (int) ($_POST['tube_num'] ?? 0);
$fiber_num    = (int) ($_POST['fiber_num'] ?? 0);
$locations_id = (int) ($_POST['locations_id'] ?? 0);
$floors_id    = (int) ($_POST['floor'] ?? 0);
$edit_key     = $tube_num . '-' . $fiber_num;

// O ativo pai tem que existir e ser visivel para este usuario: sem isso, um
// items_id forjado no POST faria o endpoint gravar porta em DGO de outra
// entidade.
$dgo = new $itemtype();
if (!($dgo instanceof CommonDBTM) || !$dgo->getFromDB($items_id) || !$dgo->can($items_id, READ)) {
    throw new BadRequestHttpException('Unknown or unreadable parent asset.');
}

$result = Port::applyInput([
    'itemtype'      => $itemtype,
    'items_id'      => $items_id,
    'tube_num'      => $tube_num,
    'fiber_num'     => $fiber_num,
    'code'          => $_POST['code'] ?? '',
    'comment'       => $_POST['comment'] ?? '',
    'is_no_coupler' => $_POST['is_no_coupler'] ?? 0,
]);

if (!$result['ok']) {
    // Recusa de regra (ex.: sem acoplador + numero de loja) NAO e' erro de
    // HTTP: o navegador precisa da mensagem para mostrar no painel, e a tela
    // continua utilizavel. Erro de permissao, esse sim, vira excecao no
    // applyInput e o core responde 403.
    echo json_encode([
        'ok'      => false,
        'message' => $result['error'],
    ]);
    return;
}

// Estado novo da porta, relido do banco: e' o mesmo caminho que a carga da
// pagina usa, entao a celula devolvida nao pode divergir da que apareceria
// num F5.
$port = new Port();
$row  = null;
if ($port->getFromDBByCrit([
    'itemtype'   => $itemtype,
    'items_id'   => $items_id,
    'tube_num'   => $tube_num,
    'fiber_num'  => $fiber_num,
    'is_deleted' => 0,
])) {
    $row = $port->fields;
}

$layout   = Panel::getLayoutForItem($dgo);
$capacity = $layout['tubes'] * $layout['fibers_per_tube'];
$stats    = Port::statsForDgo($itemtype, $items_id);

echo json_encode([
    'ok'          => true,
    'state'       => $result['state'],
    'cell_key'    => $edit_key,
    'cell_html'   => MapController::renderCell(
        $items_id,
        $locations_id,
        $floors_id,
        $tube_num,
        $fiber_num,
        $row,
        $edit_key,
        $layout['fibers_per_tube']
    ),
    'badges_html' => MapController::renderBadges(
        $stats['documented'],
        $capacity,
        $stats['no_coupler']
    ),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
