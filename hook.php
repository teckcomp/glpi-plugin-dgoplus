<?php

/**
 * DGO+ - hooks de instalacao / desinstalacao.
 */

use GlpiPlugin\Dgoplus\Install;

/**
 * @return bool
 */
function plugin_dgoplus_install()
{
    $migration = new Migration(PLUGIN_DGOPLUS_VERSION);

    $result = Install::install($migration);

    $migration->executeMigration();

    return $result;
}

/**
 * @return bool
 */
function plugin_dgoplus_uninstall()
{
    return Install::uninstall();
}
