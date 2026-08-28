<?php

use GlpiPlugin\Dgoplus\MapController;
use GlpiPlugin\Dgoplus\Port;

Session::checkRight(Port::$rightname, READ);

Html::header(Port::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'assets', 'GlpiPlugin\\Dgoplus\\Port');

// Bloco PAINEL-1b: caminho de volta.
//
// Ate' o PAINEL-1a esta tela so' era alcancavel pelo botao do proprio mapa, e
// quem chegava sabia voltar pelo historico do navegador. Com o link novo do
// painel ela passou a ser destino de duas telas diferentes e virou beco: o
// menu Ativos > DGO+ nao aparece no rastro de navegacao daqui.
//
// A URL sai de getPublicPageUrl(), que ja e' o ponto unico exposto pelo
// controlador (licao 13) e que preserva o filtro de papel corrente.
echo "<div class='mb-2'>";
echo "<a class='btn btn-outline-secondary btn-sm' href='" . htmlescape(MapController::getPublicPageUrl()) . "'>"
    . "<i class='ti ti-arrow-left'></i>&nbsp;" . __('Voltar ao mapa DGO+', 'dgoplus') . "</a>";
echo "</div>";

Search::show(Port::class);

Html::footer();
