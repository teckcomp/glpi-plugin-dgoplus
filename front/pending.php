<?php

use GlpiPlugin\Dgoplus\Pending;
use GlpiPlugin\Dgoplus\Port;

Session::checkRight(Port::$rightname, READ);

// O quarto parametro ancora a pagina no MESMO item de menu do mapa: nao ha
// entrada propria no menu (ver cabecalho de Pending), e sem a ancora o GLPI
// desenharia a migalha sem destaque nenhum em Ativos.
Html::header(__('DGO+', 'dgoplus'), $_SERVER['PHP_SELF'], 'assets', 'GlpiPlugin\\Dgoplus\\MapPage');

Pending::processAndDisplay();

Html::footer();
