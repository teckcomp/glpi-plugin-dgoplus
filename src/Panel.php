<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBChild;
use CommonDBTM;

/**
 * Layout da grade de um DGO: quantos tubos (fileiras) e quantas fibras por tubo (colunas).
 *
 * Se o ativo nao tiver linha propria, valem os valores padrao desta classe.
 */
class Panel extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public static $rightname = 'plugin_dgoplus_port';

    public static $checkParentRights = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public $dohistory = true;

    /** Tubos (fileiras) por padrao */
    public const DEFAULT_TUBES = 4;

    /** Fibras por tubo (colunas) por padrao */
    public const DEFAULT_FIBERS = 16;

    /** Limites de sanidade para o que a interface aceita */
    public const MAX_TUBES  = 48;
    public const MAX_FIBERS = 48;

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Grade de DGO', 'Grades de DGO', $nb, 'dgoplus');
    }

    /**
     * Layout de um ativo, com fallback para o padrao.
     *
     * @param CommonDBTM $item
     * @return array{tubes:int, fibers_per_tube:int}
     */
    public static function getLayoutForItem(CommonDBTM $item): array
    {
        $panel = new self();

        $found = $panel->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        if ($found) {
            return [
                'tubes'           => self::sanitizeTubes((int) $panel->fields['tubes']),
                'fibers_per_tube' => self::sanitizeFibers((int) $panel->fields['fibers_per_tube']),
            ];
        }

        return [
            'tubes'           => self::DEFAULT_TUBES,
            'fibers_per_tube' => self::DEFAULT_FIBERS,
        ];
    }

    /**
     * Piso a que o ativo esta vinculado, 0 se nenhum.
     *
     * @param CommonDBTM $item
     * @return int
     */
    public static function getFloorForItem(CommonDBTM $item): int
    {
        $panel = new self();

        $found = $panel->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        if (!$found) {
            return 0;
        }

        return (int) ($panel->fields['plugin_dgoplus_floors_id'] ?? 0);
    }

    /**
     * Vincula (ou desvincula, com 0) o ativo a um piso.
     *
     * Efeito colateral conhecido e aceito: se o ativo ainda nao tinha linha de
     * layout, ela e' criada aqui com o layout vigente. A partir dai a DGO passa
     * a ter layout gravado e nao acompanha mais mudanca futura das constantes
     * DEFAULT_TUBES / DEFAULT_FIBERS.
     *
     * @param CommonDBTM $item
     * @param int        $floors_id
     * @return bool
     */
    public static function setFloorForItem(CommonDBTM $item, int $floors_id): bool
    {
        if ($floors_id < 0) {
            $floors_id = 0;
        }

        $panel = new self();

        $found = $panel->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        if ($found) {
            return (bool) $panel->update([
                'id'                       => $panel->getID(),
                'plugin_dgoplus_floors_id' => $floors_id,
            ]);
        }

        $layout = self::getLayoutForItem($item);

        return (bool) $panel->add([
            'itemtype'                 => $item->getType(),
            'items_id'                 => $item->getID(),
            'plugin_dgoplus_floors_id' => $floors_id,
            'tubes'                    => $layout['tubes'],
            'fibers_per_tube'          => $layout['fibers_per_tube'],
        ]);
    }

    /**
     * IDs dos ativos de um itemtype vinculados a um piso.
     *
     * @param string $itemtype
     * @param int    $floors_id
     * @return int[]
     */
    public static function getItemsInFloor(string $itemtype, int $floors_id): array
    {
        if ($floors_id <= 0) {
            return [];
        }

        $panel = new self();

        $rows = $panel->find([
            'itemtype'                 => $itemtype,
            'plugin_dgoplus_floors_id' => $floors_id,
        ]);

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['items_id'];
        }

        return $ids;
    }

    /**
     * @param int $tubes
     * @return int
     */
    public static function sanitizeTubes(int $tubes): int
    {
        if ($tubes < 1) {
            return self::DEFAULT_TUBES;
        }

        return min($tubes, self::MAX_TUBES);
    }

    /**
     * @param int $fibers
     * @return int
     */
    public static function sanitizeFibers(int $fibers): int
    {
        if ($fibers < 1) {
            return self::DEFAULT_FIBERS;
        }

        return min($fibers, self::MAX_FIBERS);
    }
}
