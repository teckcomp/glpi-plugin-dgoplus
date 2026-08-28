<?php

/**
 * DGO+ - bloco 3t: gravacao do comentario do ATIVO por AJAX.
 *
 * Mesmo desenho do ajax/port.php (bloco 4a):
 *
 *  - arquivos em ajax/ de plugin sao servidos direto pelo roteador do GLPI 11
 *    (RequestRouterTrait::getTargetFile, 11.0.6:87-97) e NAO precisam de
 *    bootstrap: sessao, autoload e $CFG_GLPI ja estao de pe (licao 3);
 *  - o token CSRF NAO e' validado aqui: o CheckCsrfListener do core
 *    (CheckCsrfListener.php:80) faz isso sozinho lendo o header
 *    X-Glpi-Csrf-Token. Chamar Session::checkCSRF na mao consumiria o token
 *    duas vezes;
 *  - a regra de gravacao mora em DgoIdentity::applyComment, para o POST
 *    classico e o AJAX nao poderem divergir.
 */

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Dgoplus\DgoIdentity;
use GlpiPlugin\Dgoplus\Port;

header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new BadRequestHttpException('Only POST is accepted.');
}

// Porta de entrada do plugin. O direito de ESCRITA e' o do ativo e esta
// checado dentro do applyComment, exatamente como no POST classico.
Session::checkRight(Port::$rightname, READ);

$result = DgoIdentity::applyComment([
    'itemtype' => $_POST['itemtype'] ?? PassiveDCEquipment::class,
    'items_id' => (int) ($_POST['items_id'] ?? 0),
    'comment'  => $_POST['comment'] ?? '',
]);

// Recusa de regra ou de permissao NAO vira erro de HTTP: o navegador precisa
// da frase para mostrar ao lado do campo, e a tela continua utilizavel.
//
// Bloco 5g-1b: 'denied' distingue as duas. Recusa de REGRA depende do que foi
// digitado e pode passar na tentativa seguinte; recusa de PERMISSAO e' da
// sessao e vai falhar sempre, entao o JS trava o auto-save em vez de reenviar
// a cada saida de campo. A decisao vem do ponto unico (applyComment), nao
// daqui - checar o direito neste arquivo criaria uma segunda sede da regra.
echo json_encode([
    'ok'      => $result['ok'],
    'denied'  => (bool) ($result['denied'] ?? false),
    'message' => $result['error'],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
