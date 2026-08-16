<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;

/**
 * Um vinculo de topologia: a porta de grade de um elemento alimenta a porta de
 * ENTRADA (E1-E4) de outro.
 *
 * Uma linha, dois lados
 * ---------------------
 * O vinculo NAO e' um par de linhas espelhadas. E' uma linha so, com duas
 * chaves para a mesma tabela de portas: origem e destino. As duas telas (a do
 * elemento que alimenta e a do elemento alimentado) leem a MESMA linha por
 * lados diferentes. Duas linhas espelhadas divergiriam na primeira manutencao,
 * e divergencia aqui significa mapa mentindo em silencio.
 *
 * Nomes das colunas
 * -----------------
 * `plugin_dgoplus_ports_id_src` e `plugin_dgoplus_ports_id_dst`. A convencao do
 * GLPI (getForeignKeyFieldForTable) produz UM nome so para a tabela de portas,
 * e aqui sao duas chaves para ela. O core resolve isso com SUFIXO depois do
 * nome-base (glpi_tickets tem users_id_tech e users_id_recipient), e
 * getTableNameForForeignKeyField descarta o sufixo ao resolver a tabela - por
 * isso o sufixo preserva a convencao, enquanto `src_ports_id` a quebraria
 * (o GLPI procuraria uma tabela `glpi_src_ports`). A propria _ports ja usa esse
 * padrao em `items_id_link`.
 *
 * Sem is_deleted de proposito
 * ---------------------------
 * Vinculo nao tem lixeira: recusa APAGA a linha, desmontagem e' direta
 * (decisao fechada na Fase 4). Uma coluna de exclusao suave aqui criaria um
 * estado "vinculo que existe mas nao vale" que nenhuma tela saberia desenhar,
 * e a chave unica ficaria ocupada por linha invisivel - exatamente a armadilha
 * que a _ports ja tem e que o applyInput precisa contornar.
 */
class Link extends CommonDBTM
{
    /** Mesmo direito das portas: quem documenta porta documenta vinculo */
    public static $rightname = 'plugin_dgoplus_port';

    /** Grava historico proprio (a purga limpa, ver PurgeCleaner) */
    public $dohistory = true;

    /**
     * Proposto por um lado, ainda nao confirmado pelo outro. A porta ja conta
     * como ocupada nas duas pontas - por isso a chave unica vale desde aqui.
     */
    public const STATUS_PENDING = 'pendente';

    /** Confirmado pelos dois lados (ou autoconfirmado, o que fica registrado) */
    public const STATUS_CONFIRMED = 'confirmado';

    /**
     * varchar(16) e nao ENUM, mesma razao do Port::$kind: status novo seria
     * ALTER de tipo com dado dentro.
     *
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Vínculo', 'Vínculos', $nb, 'dgoplus');
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-link';
    }

    /**
     * Vinculos que CHEGAM nestas portas de entrada.
     *
     * Uma consulta so para as quatro entradas do elemento: a faixa E1-E4
     * desenha as quatro caixas em toda abertura de grade, e uma consulta por
     * caixa seria quatro consultas por tela.
     *
     * @param int[] $ports_ids ids de portas de destino (entradas)
     * @return array<int, array> ports_id do destino => linha do vinculo
     */
    public static function findByDestinations(array $ports_ids): array
    {
        $ids = self::cleanIds($ports_ids);

        if ($ids === []) {
            return [];
        }

        $link = new self();
        $rows = $link->find(['plugin_dgoplus_ports_id_dst' => $ids]);

        $by_dst = [];
        foreach ($rows as $row) {
            $by_dst[(int) $row['plugin_dgoplus_ports_id_dst']] = $row;
        }

        return $by_dst;
    }

    /**
     * Vinculos que SAEM destas portas de grade.
     *
     * Ainda nao tem chamador de tela (a marca na celula de origem e' do 4c),
     * mas e' o par simetrico do findByDestinations e o PurgeCleaner precisa
     * enxergar os dois lados.
     *
     * @param int[] $ports_ids ids de portas de origem (grade)
     * @return array<int, array> ports_id da origem => linha do vinculo
     */
    public static function findByOrigins(array $ports_ids): array
    {
        $ids = self::cleanIds($ports_ids);

        if ($ids === []) {
            return [];
        }

        $link = new self();
        $rows = $link->find(['plugin_dgoplus_ports_id_src' => $ids]);

        $by_src = [];
        foreach ($rows as $row) {
            $by_src[(int) $row['plugin_dgoplus_ports_id_src']] = $row;
        }

        return $by_src;
    }

    /**
     * Ids de vinculo que tocam estas portas por QUALQUER lado.
     *
     * E' o que a purga precisa: apagar o elemento apaga as portas dele, e todo
     * vinculo que citava qualquer uma das duas pontas vira lixo apontando para
     * porta inexistente - o mesmo defeito que o 3q corrigiu para porta e
     * painel, um nivel acima.
     *
     * @param int[] $ports_ids
     * @return int[]
     */
    public static function idsTouchingPorts(array $ports_ids): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ids = self::cleanIds($ports_ids);

        if ($ids === [] || !$DB->tableExists(self::getTable())) {
            return [];
        }

        $rows = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'OR' => [
                    ['plugin_dgoplus_ports_id_src' => $ids],
                    ['plugin_dgoplus_ports_id_dst' => $ids],
                ],
            ],
        ]);

        $found = [];
        foreach ($rows as $row) {
            $found[] = (int) $row['id'];
        }

        return $found;
    }

    /**
     * De onde vem este vinculo, em texto legivel.
     *
     * O rotulo da porta de origem depende da LARGURA da grade do elemento de
     * origem (bloco 3r), que nao e' a largura do elemento que esta na tela -
     * por isso a largura e' lida a partir do dono da porta de origem, nunca do
     * elemento aberto.
     *
     * @param int $src_ports_id
     * @return array{ok:bool, label:string, item:string}
     */
    public static function describeOrigin(int $src_ports_id): array
    {
        $empty = ['ok' => false, 'label' => '', 'item' => ''];

        if ($src_ports_id <= 0) {
            return $empty;
        }

        $port = new Port();
        if (!$port->getFromDB($src_ports_id)) {
            return $empty;
        }

        $itemtype = (string) ($port->fields['itemtype'] ?? '');
        $items_id = (int) ($port->fields['items_id'] ?? 0);

        $label = Port::formatPosition(
            (int) ($port->fields['tube_num'] ?? 0),
            (int) ($port->fields['fiber_num'] ?? 0),
            Panel::getWidthForItemId($itemtype, $items_id)
        );

        $item_name = '';
        if ($itemtype !== '' && $items_id > 0 && class_exists($itemtype)) {
            $item = new $itemtype();
            if ($item instanceof CommonDBTM && $item->getFromDB($items_id)) {
                $item_name = (string) ($item->fields['name'] ?? '');
            }
        }

        if ($item_name === '') {
            $item_name = sprintf(__('elemento #%d', 'dgoplus'), $items_id);
        }

        return ['ok' => true, 'label' => $label, 'item' => $item_name];
    }

    /**
     * @param array $ids
     * @return int[]
     */
    private static function cleanIds(array $ids): array
    {
        $clean = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[$id] = $id;
            }
        }

        return array_values($clean);
    }
}
