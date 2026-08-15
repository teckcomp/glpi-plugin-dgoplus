<?php

/**
 * DGO+ - plugin GLPI 11
 * Bloco 3q: limpeza dos dados do plugin quando o ativo da DGO e' PURGADO.
 *
 * O problema que esta classe resolve
 * ---------------------------------
 * Panel e Port sao CommonDBChild do ativo, mas moram em tabelas do plugin. O
 * core nao conhece essas tabelas: ao purgar o PassiveDCEquipment ele apaga a
 * linha do ativo e o proprio historico dele, e nada mais. As linhas de
 * _ports e _panels ficam apontando para um items_id que nao existe mais -
 * sem erro, sem aviso, sem lixeira. Aconteceu em producao em 30/07/2026
 * (8 portas + 1 painel de uma DGO de teste), e foi limpo por SQL na mao.
 *
 * Por que ITEM_PURGE e nao PRE_ITEM_PURGE
 * ---------------------------------------
 * Hooks::ITEM_PURGE dispara em CommonDBTM.php:2185, DEPOIS de
 * deleteFromDB(). Nesse ponto o ativo ja saiu do banco, mas $item->fields
 * continua populado em memoria - que e' tudo que precisamos (o id). Usar
 * PRE_ITEM_PURGE para BLOQUEAR a purga foi considerado e descartado:
 * a restricao de quem purga e' politica de perfil, nao regra de plugin.
 *
 * A ordem das operacoes importa
 * -----------------------------
 * glpi_logs do filho e' chaveado pelo ID DO FILHO (CommonDBChild nao
 * sobrescreve getLogTypeID(), entao CommonDBTM.php:704 devolve o proprio
 * tipo e o proprio id). Portanto os ids TEM que ser colhidos ANTES de as
 * linhas serem apagadas. Invertido, os ids somem e o historico fica orfao
 * do mesmo jeito - a correcao falharia em silencio, que e' exatamente a
 * assinatura do defeito que ela existe para corrigir.
 *
 * Nao ha filtro de is_deleted em lugar nenhum: porta na lixeira tambem e'
 * linha a limpar.
 *
 * O que o core ja faz sozinho, e nao repetimos aqui
 * ------------------------------------------------
 * CommonDBTM::cleanHistory() (CommonDBTM.php:886) apaga glpi_logs de
 * itemtype = 'PassiveDCEquipment'. Como CommonDBChild::post_addItem()
 * grava o historico LEGIVEL do filho sob o itemtype do PAI, as linhas do
 * tipo "Porta F2.10 adicionada" ja vao embora pelo core. O que sobra sob o
 * itemtype do filho e' alteracao de campo e criacao/restauracao - e e' isso
 * que limpamos.
 *
 * Rastro
 * ------
 * Como tudo o mais e' apagado, o unico registro de que a limpeza ocorreu vai
 * para glpi_events (Administracao -> Registro), que NAO e' tocado pela purga.
 */

namespace GlpiPlugin\Dgoplus;

use Glpi\Event;
use PassiveDCEquipment;

final class PurgeCleaner
{
    /**
     * Callback do hook Hooks::ITEM_PURGE, escopado para PassiveDCEquipment.
     *
     * Plugin::doHook() resolve o callback por get_class($param) exato
     * (Plugin.php:1795) e chama call_user_func($tab[$itemtype], $data)
     * (Plugin.php:1810), com $data sendo o proprio objeto.
     *
     * @param mixed $item
     * @return void
     */
    public static function onItemPurge($item): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Cinto e suspensorio: o hook ja e' escopado no setup.php, mas um
        // callback publico nao pode confiar em quem o chamou.
        if (!($item instanceof PassiveDCEquipment)) {
            return;
        }

        $items_id = (int) ($item->fields['id'] ?? 0);

        if ($items_id <= 0) {
            return;
        }

        // getType() em vez de literal: e' o mesmo valor que MapController
        // grava na coluna itemtype ao criar porta e painel.
        $itemtype = $item::getType();

        $ports_table  = Port::getTable();
        $panels_table = Panel::getTable();

        // ------------------------------------------------------------------
        // 1. Colher os ids ANTES de apagar. Ver o cabecalho desta classe.
        // ------------------------------------------------------------------
        $port_ids  = self::collectIds($ports_table, $itemtype, $items_id);
        $panel_ids = self::collectIds($panels_table, $itemtype, $items_id);

        // ------------------------------------------------------------------
        // 2. Historico dos filhos. Port e Panel sao apagados em consultas
        //    SEPARADAS de proposito: os dois conjuntos de id vivem em tabelas
        //    diferentes e colidem entre si (pode existir porta 12 e painel
        //    12). Uma consulta so, com a uniao dos ids, apagaria historico de
        //    porta alheia.
        // ------------------------------------------------------------------
        $logs_removed  = self::purgeLogs(Port::class, $port_ids);
        $logs_removed += self::purgeLogs(Panel::class, $panel_ids);

        // ------------------------------------------------------------------
        // 3. As linhas do plugin. Sem filtro de is_deleted.
        // ------------------------------------------------------------------
        if ($port_ids !== []) {
            $DB->delete($ports_table, ['id' => $port_ids]);
        }

        if ($panel_ids !== []) {
            $DB->delete($panels_table, ['id' => $panel_ids]);
        }

        // ------------------------------------------------------------------
        // 4. Rastro em glpi_events. Silencioso quando nao havia nada - purgar
        //    ativo passivo que nunca foi DGO e' rotina, e nao deve virar
        //    linha de registro.
        // ------------------------------------------------------------------
        if ($port_ids === [] && $panel_ids === []) {
            return;
        }

        Event::log(
            $items_id,
            PassiveDCEquipment::class,
            3,
            'dgoplus',
            sprintf(
                __('DGO+ removeu %1$d porta(s), %2$d painel(eis) e %3$d linha(s) de histórico da DGO purgada (id %4$d).', 'dgoplus'),
                count($port_ids),
                count($panel_ids),
                $logs_removed,
                $items_id
            )
        );
    }

    /**
     * Ids das linhas do plugin que pertencem ao ativo.
     *
     * @param string $table
     * @param string $itemtype
     * @param int    $items_id
     * @return int[]
     */
    private static function collectIds(string $table, string $itemtype, int $items_id): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!$DB->tableExists($table)) {
            return [];
        }

        $ids = [];

        $rows = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $table,
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ],
        ]);

        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    /**
     * Apaga glpi_logs de um itemtype de filho, para uma lista de ids.
     *
     * @param string $itemtype
     * @param int[]  $ids
     * @return int quantas linhas sairam
     */
    private static function purgeLogs(string $itemtype, array $ids): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($ids === []) {
            return 0;
        }

        $count = countElementsInTable('glpi_logs', [
            'itemtype' => $itemtype,
            'items_id' => $ids,
        ]);

        if ($count > 0) {
            $DB->delete('glpi_logs', [
                'itemtype' => $itemtype,
                'items_id' => $ids,
            ]);
        }

        return (int) $count;
    }
}
