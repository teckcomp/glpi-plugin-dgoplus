<?php

use GlpiPlugin\Dgoplus\Port;

Session::checkRight(Port::$rightname, READ);

Html::header(Port::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'assets', 'GlpiPlugin\\Dgoplus\\Port');

Search::show(Port::class);

Html::footer();
