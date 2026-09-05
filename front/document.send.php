<?php

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Dgoplus\Port;

// Bloco 5i-2: download de anexo pelo PLUGIN.
//
// Razao de existir: decisao de produto - ver um anexo no mapa nao exige
// NENHUM direito nativo (Gerencia > Documentos). O porteiro e' o direito do
// DGO+ (Ler), o mesmo que abre o mapa. O front/document.send.php do CORE
// continua existindo com as regras dele; este endpoint e' uma porta
// adicional, restrita ao escopo do plugin: so' serve anexo VINCULADO a um
// PassiveDCEquipment alcancavel (parentIsReachable, trava do 5f-3b). Um
// docid sozinho nao abre nada - sem o vinculo com o elemento pedido, a
// resposta e' recusa falada, nunca muda.
//
// Mecanismo do retorno: o mesmo do send.php do core - script legado que
// devolve uma Response (Document::getAsResponse) e o kernel a entrega
// (11.0.6, src/Glpi/Controller/LegacyFileLoadController.php: o retorno do
// require, quando e' Response, vira a resposta HTTP).

Session::checkRight(Port::$rightname, READ);

$docid    = (int) ($_GET['docid'] ?? 0);
$items_id = (int) ($_GET['items_id'] ?? 0);

$item = new PassiveDCEquipment();

if ($docid <= 0 || $items_id <= 0 || !$item->getFromDB($items_id) || !Port::parentIsReachable($item)) {
    $exception = new AccessDeniedHttpException();
    $exception->setMessageToDisplay(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
    throw $exception;
}

$doc_item = new Document_Item();

if (
    !$doc_item->getFromDBByCrit([
        'documents_id' => $docid,
        'itemtype'     => PassiveDCEquipment::class,
        'items_id'     => $items_id,
    ])
) {
    $exception = new AccessDeniedHttpException();
    $exception->setMessageToDisplay(__('O anexo não pertence a este elemento.', 'dgoplus'));
    throw $exception;
}

$document = new Document();

if (!$document->getFromDB($docid)) {
    $exception = new NotFoundHttpException();
    $exception->setMessageToDisplay(__('Anexo não encontrado.', 'dgoplus'));
    throw $exception;
}

return $document->getAsResponse();
