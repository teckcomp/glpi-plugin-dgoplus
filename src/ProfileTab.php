<?php

namespace GlpiPlugin\Dgoplus;

use CommonGLPI;
use Html;
use Profile;
use Session;

/**
 * Aba "DGO+" no formulario de Perfil, para conceder o direito
 * plugin_dgoplus_port.
 *
 * Nao estende \Profile de proposito: assinatura de showForm() da classe-pai
 * muda entre versoes e quebra em tempo de execucao.
 */
class ProfileTab extends CommonGLPI
{
    /** Quem pode ver esta aba: quem administra perfis */
    public static $rightname = 'profile';

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('DGO+', 'dgoplus');
    }

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            $item instanceof Profile
            && (int) $item->getID() > 0
            && ($item->fields['interface'] ?? '') !== 'helpdesk'
        ) {
            return self::createTabEntry(self::getTypeName(), 0, $item::class, 'ti ti-grid-dots');
        }

        return '';
    }

    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof Profile) || (int) $item->getID() <= 0) {
            return false;
        }

        $canedit = Session::haveRightsOr('profile', [CREATE, UPDATE, PURGE]);

        if ($canedit) {
            echo "<form method='post' action='" . htmlescape(Profile::getFormURL()) . "'>";
        }

        $item->displayRightsChoiceMatrix(
            [
                [
                    'itemtype' => Port::class,
                    'label'    => Port::getTypeName(2),
                    'field'    => Port::$rightname,
                ],
            ],
            [
                'title'   => self::getTypeName(),
                'canedit' => $canedit,
            ]
        );

        if ($canedit) {
            echo "<div class='text-center mt-2'>";
            echo Html::hidden('id', ['value' => $item->getID()]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }

        return true;
    }
}
