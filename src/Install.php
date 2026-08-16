<?php

namespace GlpiPlugin\Dgoplus;

use DBConnection;
use Migration;

/**
 * Criacao do schema e dos direitos. Tudo aqui precisa ser idempotente:
 * rodar duas vezes nao pode quebrar nem apagar dado.
 */
class Install
{
    /**
     * @param Migration $migration
     * @return bool
     */
    public static function install(Migration $migration): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        $charset   = DBConnection::getDefaultCharset();
        $collation = DBConnection::getDefaultCollation();
        $sign      = DBConnection::getDefaultPrimaryKeySignOption();

        $ports_table  = Port::getTable();
        $panels_table = Panel::getTable();
        $floors_table = Floor::getTable();

        // Tabela e itemtype do Setor, ABANDONADOS no bloco 3h. Escritos por
        // extenso de proposito: a classe Sector nao existe mais, entao nao ha
        // getTable() para derivar. Valor historico e fixo.
        $sectors_table_legacy    = 'glpi_plugin_dgoplus_sectors';
        $sectors_itemtype_legacy = 'GlpiPlugin\\Dgoplus\\Sector';

        if (!$DB->tableExists($ports_table)) {
            $migration->displayMessage("Criando $ports_table");

            $query = "CREATE TABLE `$ports_table` (
                `id` int $sign NOT NULL AUTO_INCREMENT,
                `entities_id` int $sign NOT NULL DEFAULT '0',
                `is_recursive` tinyint NOT NULL DEFAULT '0',
                `itemtype` varchar(255) DEFAULT NULL,
                `items_id` int $sign NOT NULL DEFAULT '0',
                `tube_num` int NOT NULL DEFAULT '1',
                `fiber_num` int NOT NULL DEFAULT '1',
                `kind` varchar(16) NOT NULL DEFAULT '" . Port::KIND_GRID . "',
                `code` varchar(64) DEFAULT NULL,
                `name` varchar(255) DEFAULT NULL,
                `comment` text,
                `itemtype_link` varchar(255) DEFAULT NULL,
                `items_id_link` int $sign NOT NULL DEFAULT '0',
                `is_no_coupler` tinyint NOT NULL DEFAULT '0',
                `is_deleted` tinyint NOT NULL DEFAULT '0',
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`itemtype`,`items_id`,`tube_num`,`fiber_num`),
                KEY `item` (`itemtype`,`items_id`),
                KEY `link` (`itemtype_link`,`items_id_link`),
                KEY `entities_id` (`entities_id`),
                KEY `is_recursive` (`is_recursive`),
                KEY `is_no_coupler` (`is_no_coupler`),
                KEY `is_deleted` (`is_deleted`),
                KEY `kind` (`kind`),
                KEY `code` (`code`),
                KEY `name` (`name`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC";

            $DB->doQuery($query);
        }

        // Coluna is_deleted adicionada depois da 0.1.0: idempotente para quem
        // ja tinha a tabela sem ela (habilita exclusao suave / lixeira nativa).
        if ($DB->tableExists($ports_table) && !$DB->fieldExists($ports_table, 'is_deleted')) {
            $migration->displayMessage("Adicionando is_deleted em $ports_table");
            $DB->doQuery("ALTER TABLE `$ports_table` ADD `is_deleted` tinyint NOT NULL DEFAULT '0' AFTER `items_id_link`");
            $DB->doQuery("ALTER TABLE `$ports_table` ADD KEY `is_deleted` (`is_deleted`)");
        }

        // Terceiro estado da porta (bloco 3h): "sem acoplador". Livre e ocupada
        // continuam derivados do conteudo; este e' o unico estado que precisa de
        // coluna, porque uma porta sem acoplador nao tem nome nem numero.
        if ($DB->tableExists($ports_table) && !$DB->fieldExists($ports_table, 'is_no_coupler')) {
            $migration->displayMessage("Adicionando is_no_coupler em $ports_table");
            $DB->doQuery("ALTER TABLE `$ports_table` ADD `is_no_coupler` tinyint NOT NULL DEFAULT '0' AFTER `items_id_link`");
            $DB->doQuery("ALTER TABLE `$ports_table` ADD KEY `is_no_coupler` (`is_no_coupler`)");
        }

        // Bloco 4b-1: classificacao da linha de porta.
        //
        // DEFAULT 'grade' nao e' comodidade: e' a migracao. Toda linha que
        // existe hoje E' porta de grade, entao o ALTER as marca corretamente
        // sozinho, sem UPDATE de dado e sem janela em que a coluna esteja vazia.
        //
        // NAO entra na chave unica de proposito. A unicity e'
        // (itemtype, items_id, tube_num, fiber_num) e assim continua: entrada
        // usa tube_num = 0 e grade usa tube_num >= 1, entao os dois conjuntos
        // ja nao colidem. Acrescentar kind a chave permitiria duas linhas na
        // mesma posicao fisica, que e' justamente o que a chave existe para
        // impedir.
        //
        // varchar(16) e nao ENUM: kind novo (splitter, no dia em que voltar)
        // seria ALTER de tipo em ENUM, e ALTER de tipo com dado dentro e'
        // exatamente o que este projeto evita.
        if ($DB->tableExists($ports_table) && !$DB->fieldExists($ports_table, 'kind')) {
            $migration->displayMessage("Adicionando kind em $ports_table");
            $DB->doQuery("ALTER TABLE `$ports_table` ADD `kind` varchar(16) NOT NULL DEFAULT '" . Port::KIND_GRID . "' AFTER `fiber_num`");
            $DB->doQuery("ALTER TABLE `$ports_table` ADD KEY `kind` (`kind`)");
        }

        if (!$DB->tableExists($panels_table)) {
            $migration->displayMessage("Criando $panels_table");

            $default_tubes  = Panel::DEFAULT_TUBES;
            $default_fibers = Panel::DEFAULT_FIBERS;

            $query = "CREATE TABLE `$panels_table` (
                `id` int $sign NOT NULL AUTO_INCREMENT,
                `entities_id` int $sign NOT NULL DEFAULT '0',
                `is_recursive` tinyint NOT NULL DEFAULT '0',
                `itemtype` varchar(255) DEFAULT NULL,
                `items_id` int $sign NOT NULL DEFAULT '0',
                `plugin_dgoplus_floors_id` int $sign NOT NULL DEFAULT '0',
                `tubes` int NOT NULL DEFAULT '$default_tubes',
                `fibers_per_tube` int NOT NULL DEFAULT '$default_fibers',
                `comment` text,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`itemtype`,`items_id`),
                KEY `entities_id` (`entities_id`),
                KEY `is_recursive` (`is_recursive`),
                KEY `plugin_dgoplus_floors_id` (`plugin_dgoplus_floors_id`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC";

            $DB->doQuery($query);
        }

        // ------------------------------------------------------------------
        // Bloco 3g: escopo Localizacao -> Piso -> Setor.
        // Nivel 1 e' a arvore nativa de Localizacao; niveis 2 e 3 sao do plugin.
        // ------------------------------------------------------------------

        if (!$DB->tableExists($floors_table)) {
            $migration->displayMessage("Criando $floors_table");

            $query = "CREATE TABLE `$floors_table` (
                `id` int $sign NOT NULL AUTO_INCREMENT,
                `entities_id` int $sign NOT NULL DEFAULT '0',
                `is_recursive` tinyint NOT NULL DEFAULT '0',
                `name` varchar(255) DEFAULT NULL,
                `locations_id` int $sign NOT NULL DEFAULT '0',
                `comment` text,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`locations_id`,`name`),
                KEY `name` (`name`),
                KEY `entities_id` (`entities_id`),
                KEY `is_recursive` (`is_recursive`),
                KEY `locations_id` (`locations_id`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC";

            $DB->doQuery($query);
        }

        // Vinculo da DGO com o piso. Toda coluna sob fieldExists: o
        // plugin:install --force reexecuta este metodo inteiro, e ALTER ADD
        // repetido sem guarda da 1060 (licao 31).
        if ($DB->tableExists($panels_table) && !$DB->fieldExists($panels_table, 'plugin_dgoplus_floors_id')) {
            $migration->displayMessage("Adicionando plugin_dgoplus_floors_id em $panels_table");
            $DB->doQuery("ALTER TABLE `$panels_table` ADD `plugin_dgoplus_floors_id` int $sign NOT NULL DEFAULT '0' AFTER `items_id`");
            $DB->doQuery("ALTER TABLE `$panels_table` ADD KEY `plugin_dgoplus_floors_id` (`plugin_dgoplus_floors_id`)");
        }

        // Idempotente: so insere em perfis que ainda nao tem o direito.
        // Matriz de 4 niveis (Ler=1, Criar=4, Editar=2, Deletar=8 => 15).
        // Perfis sem config READ|UPDATE ficam com o direito em 0 (desmarcado) -
        // e' assim que perfis se diferenciam de verdade.
        $migration->addRight(Port::$rightname, READ | CREATE | UPDATE | DELETE, ['config' => READ | UPDATE]);

        // Coluna do vinculo visivel por padrao na lista de Intitulados: sem
        // isso, Piso vira uma lista de "Terreo" repetido, sem contexto nenhum.
        // updateDisplayPrefs checa existencia antes de inserir.
        $migration->updateDisplayPrefs([
            Floor::class => [4],
        ]);

        // ------------------------------------------------------------------
        // Bloco 3h: o Setor foi abandonado (decisao do usuario, 29/07/2026).
        // O escopo ficou Localizacao (nativa) -> Piso (plugin), sem terceiro
        // nivel. Esta limpeza e' idempotente e nao toca em dado de porta:
        // nenhuma tela jamais escreveu na tabela nem na coluna de setor.
        //
        // dropTable do Migration e' imediato; dropField NAO e' (fica para o
        // executeMigration), por isso a coluna sai por doQuery (licao 39).
        // ------------------------------------------------------------------

        if ($DB->tableExists($sectors_table_legacy)) {
            $migration->displayMessage("Removendo tabela obsoleta $sectors_table_legacy");
            $migration->dropTable($sectors_table_legacy);
        }

        if ($DB->tableExists($panels_table) && $DB->fieldExists($panels_table, 'plugin_dgoplus_sectors_id')) {
            $migration->displayMessage("Removendo coluna obsoleta plugin_dgoplus_sectors_id");
            $DB->doQuery("ALTER TABLE `$panels_table` DROP COLUMN `plugin_dgoplus_sectors_id`");
        }

        $DB->delete('glpi_displaypreferences', ['itemtype' => $sectors_itemtype_legacy]);
        $DB->delete('glpi_logs', ['itemtype' => $sectors_itemtype_legacy]);

        return true;
    }

    /**
     * @return bool
     */
    public static function uninstall(): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        // 'glpi_plugin_dgoplus_sectors' por extenso: classe abandonada no 3h,
        // mas a tabela pode existir em instalacao que passou pelo bloco 3g.
        $tables = [
            Port::getTable(),
            Panel::getTable(),
            Floor::getTable(),
            'glpi_plugin_dgoplus_sectors',
        ];

        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $DB->doQuery("DROP TABLE `$table`");
            }
        }

        $DB->delete('glpi_profilerights', ['name' => Port::$rightname]);

        foreach ([Port::class, Floor::class, 'GlpiPlugin\\Dgoplus\\Sector'] as $itemtype) {
            $DB->delete('glpi_displaypreferences', ['itemtype' => $itemtype]);
            $DB->delete('glpi_logs', ['itemtype' => $itemtype]);
        }

        // A configuracao do 3l (quais Tipos sao DGO) vive em glpi_configs sob o
        // contexto 'plugin:dgoplus'. Sem esta linha ela sobrevive ao
        // desinstalar e volta a valer numa reinstalacao futura, apontando IDs
        // de PassiveDCEquipmentType que podem nem existir mais.
        $DB->delete('glpi_configs', ['context' => Setting::CONTEXT]);

        return true;
    }
}
