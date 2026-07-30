<?php

use GlpiPlugin\Dgoplus\MapController;
use GlpiPlugin\Dgoplus\Port;

Session::checkRight(Port::$rightname, READ);

Html::header(__('DGO+', 'dgoplus'), $_SERVER['PHP_SELF'], 'assets', 'GlpiPlugin\\Dgoplus\\MapPage');

MapController::processAndDisplay();

Html::footer();
