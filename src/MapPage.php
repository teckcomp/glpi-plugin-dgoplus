<?php

namespace GlpiPlugin\Dgoplus;

use Session;

/**
 * Nao e um CommonDBTM: existe so para aparecer no menu Ativos e apontar
 * para front/map.php (dropdown de localizacao + abas de DGO + grade).
 */
class MapPage
{
    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('DGO+', 'dgoplus');
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-grid-dots';
    }

    /**
     * @return bool
     */
    public static function canView()
    {
        return Session::haveRight(Port::$rightname, READ);
    }

    /**
     * Entrada no menu "Ativos". Sobrescrita completa (nao delega a
     * CommonGLPI::getMenuContent) porque esta classe nao e CommonDBTM.
     *
     * @return array|false
     */
    public static function getMenuContent()
    {
        if (!static::canView()) {
            return false;
        }

        $page = '/plugins/dgoplus/front/map.php';

        return [
            'title' => static::getTypeName(2),
            'page'  => $page,
            'icon'  => static::getIcon(),
            'links' => [
                'search' => $page,
            ],
        ];
    }
}
