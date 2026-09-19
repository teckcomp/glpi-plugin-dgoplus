<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBChild;
use CommonDBTM;

/**
 * DGO+ - plugin GLPI 11
 * Bloco 6a: agrupamento de funcoes numa CAIXA FISICA (caixa composta).
 *
 * O problema que esta classe resolve
 * ---------------------------------
 * Em campo existe central de fibra que faz DIO, DGO e as vezes CTO na mesma
 * carcaca: bandejas de emenda em cima, splitter e distribuicao embaixo,
 * cordao ligando as metades. Uma etiqueta, um QR, tres funcoes.
 *
 * O modelo (decisao do usuario, 19/09/2026)
 * -----------------------------------------
 * Papel continua sendo o Tipo nativo do ativo - Setting::ROLES nao muda e
 * "composto" NAO e' papel. Cada funcao e' um PassiveDCEquipment proprio, com
 * #id, grade e entradas proprias, no papel dela. Esta tabela so' diz que a
 * funcao X (membro) pertence a caixa Y (hospedeira) e em que posicao ela
 * aparece na tela empilhada. A hospedeira e' a primeira funcao - a dona da
 * etiqueta e do QR - e NAO tem linha propria aqui: ela e' hospedeira por
 * aparecer em items_id_host de alguem.
 *
 * "Caixa composta" e' portanto um estado DERIVADO ("tem membros"), nunca um
 * campo. Nada em Link::hierarchyAllows, gridCriteria ou nos loops de
 * Setting::getRoles() precisa saber que esta tabela existe.
 *
 * Invariantes (garantidos em 6b, quando a tela passa a gravar)
 * ------------------------------------------------------------
 * - Uma funcao pertence a NO MAXIMO uma caixa (UNIQUE itemtype+items_id).
 * - Hospedeira nao e' membro de outra caixa, e membro nao hospeda (sem
 *   caixa dentro de caixa).
 * - Localizacao e piso do membro nascem herdados da hospedeira; divergencia
 *   acusa em tela, nunca fica muda.
 *
 * Este bloco (6a) entrega so' o schema, o modelo e a limpeza na purga. Nenhuma
 * tela grava aqui ainda.
 */
class Box extends CommonDBChild
{
    /** O filho e' a FUNCAO (membro): apagar/purgar a funcao leva a linha junto. */
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static $rightname = 'plugin_dgoplus_port';

    public static $checkParentRights = self::HAVE_VIEW_RIGHT_ON_ITEM;

    /** Toda mudanca de agrupamento vai ao historico da funcao. */
    public $dohistory = true;

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Função de caixa composta', 'Funções de caixa composta', $nb, 'dgoplus');
    }

    /**
     * A caixa hospedeira de uma funcao, ou null quando o elemento nao e'
     * membro de caixa nenhuma.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array{itemtype:string, items_id:int, position:int}|null
     */
    public static function hostOf(string $itemtype, int $items_id): ?array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($items_id <= 0) {
            return null;
        }

        $rows = $DB->request([
            'SELECT' => ['itemtype_host', 'items_id_host', 'position'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ],
            'LIMIT'  => 1,
        ]);

        foreach ($rows as $row) {
            return [
                'itemtype' => (string) $row['itemtype_host'],
                'items_id' => (int) $row['items_id_host'],
                'position' => (int) $row['position'],
            ];
        }

        return null;
    }

    /**
     * Membros de uma caixa, na ordem de exibicao. Lista vazia = elemento
     * simples (nao e' caixa composta).
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array<int, array{id:int, itemtype:string, items_id:int, position:int}>
     */
    public static function membersOf(string $itemtype, int $items_id): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($items_id <= 0) {
            return [];
        }

        $out = [];

        $rows = $DB->request([
            'SELECT' => ['id', 'itemtype', 'items_id', 'position'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype_host' => $itemtype,
                'items_id_host' => $items_id,
            ],
            'ORDER'  => ['position ASC', 'id ASC'],
        ]);

        foreach ($rows as $row) {
            $out[] = [
                'id'       => (int) $row['id'],
                'itemtype' => (string) $row['itemtype'],
                'items_id' => (int) $row['items_id'],
                'position' => (int) $row['position'],
            ];
        }

        return $out;
    }

    /**
     * O elemento e' caixa composta? (tem pelo menos um membro)
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public static function isHost(CommonDBTM $item): bool
    {
        return self::membersOf($item->getType(), (int) $item->getID()) !== [];
    }

    /**
     * Ids das linhas desta tabela que citam o elemento - como MEMBRO ou como
     * HOSPEDEIRA. Usado pelo PurgeCleaner: purgar a hospedeira solta os
     * membros (que continuam existindo, agora como elementos simples);
     * purgar um membro apaga so' a linha dele.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return int[]
     */
    public static function idsTouchingItem(string $itemtype, int $items_id): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($items_id <= 0 || !$DB->tableExists(self::getTable())) {
            return [];
        }

        $ids = [];

        $rows = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'OR' => [
                    ['itemtype' => $itemtype, 'items_id' => $items_id],
                    ['itemtype_host' => $itemtype, 'items_id_host' => $items_id],
                ],
            ],
        ]);

        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }
}
