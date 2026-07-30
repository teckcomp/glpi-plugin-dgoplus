<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
use Config;
use PassiveDCEquipmentType;

/**
 * Configuracao do plugin (bloco 3l).
 *
 * Guarda quais "Tipos de dispositivo passivo" representam uma DGO. Antes do 3l
 * o plugin assumia que TODO PassiveDCEquipment era DGO - premissa que caiu
 * quando o usuario criou um dispositivo passivo comum e ele apareceu como aba no
 * DGO+, com 64 portas para documentar.
 *
 * Nao tem tabela propria: usa o Config nativo do GLPI, que grava em
 * glpi_configs por contexto. Por isso este bloco NAO mexe no instalador.
 *
 * Guarda o ID do tipo, nunca o nome: renomear o intitulado depois nao pode
 * quebrar o reconhecimento das DGOs (licao 32).
 */
class Setting
{
    /** Contexto no glpi_configs */
    public const CONTEXT = 'plugin:dgoplus';

    /** Nome da chave que guarda os tipos de DGO */
    public const DGO_TYPES = 'dgo_types';

    /**
     * IDs dos tipos configurados como DGO.
     *
     * Lista vazia tem significado proprio: "filtro desligado", que e' o
     * comportamento anterior ao 3l. E' o que garante que aplicar o bloco nao
     * faca nenhuma DGO existente desaparecer sem o usuario pedir.
     *
     * @return int[]
     */
    public static function getDgoTypes(): array
    {
        $values = Config::getConfigurationValues(self::CONTEXT, [self::DGO_TYPES]);

        $raw = $values[self::DGO_TYPES] ?? '';
        if ($raw === '' || $raw === null) {
            return [];
        }

        $ids = importArrayFromDB($raw);
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

        return array_values(array_unique($ids));
    }

    /**
     * @param int[] $ids
     * @return void
     */
    public static function setDgoTypes(array $ids): void
    {
        $ids = array_map('intval', $ids);
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        $ids = array_values(array_unique($ids));

        Config::setConfigurationValues(self::CONTEXT, [
            self::DGO_TYPES => exportArrayToDB($ids),
        ]);
    }

    /**
     * Nenhum tipo escolhido = plugin se comporta como antes do 3l.
     *
     * @return bool
     */
    public static function isTypeFilterEnabled(): bool
    {
        return self::getDgoTypes() !== [];
    }

    /**
     * Nome da coluna do tipo, derivado do core em vez de escrito a mao
     * (licao 32): glpi_passivedcequipmenttypes -> passivedcequipmenttypes_id.
     *
     * @return string
     */
    public static function getTypeField(): string
    {
        return PassiveDCEquipmentType::getForeignKeyField();
    }

    /**
     * Criterio para somar (+) a um find() de PassiveDCEquipment.
     *
     * Devolve array vazio quando o filtro esta desligado, para o `+` nao
     * alterar nada - mesmo padrao de getEntitiesRestrictCriteria (licao 5).
     *
     * @return array
     */
    public static function dgoCriteria(): array
    {
        $types = self::getDgoTypes();

        if ($types === []) {
            return [];
        }

        return [self::getTypeField() => $types];
    }

    /**
     * O ativo conta como DGO?
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public static function isDgo(CommonDBTM $item): bool
    {
        $types = self::getDgoTypes();

        if ($types === []) {
            return true;
        }

        $field = self::getTypeField();

        return in_array((int) ($item->fields[$field] ?? 0), $types, true);
    }

    /**
     * Tipo a gravar numa DGO criada pela propria tela do DGO+.
     *
     * Sem isto a DGO nasceria sem tipo, o filtro a descartaria e ela
     * desapareceria no instante em que fosse criada - sem erro em log nenhum
     * (licao 14). Com mais de um tipo configurado, vale o primeiro.
     *
     * @return int 0 quando o filtro esta desligado
     */
    public static function getTypeForNewDgo(): int
    {
        $types = self::getDgoTypes();

        return $types === [] ? 0 : (int) $types[0];
    }

    /**
     * Tipos disponiveis para escolher na tela de configuracao.
     *
     * @return array<int, string> id => nome
     */
    public static function getAvailableTypes(): array
    {
        $type   = new PassiveDCEquipmentType();
        $result = [];

        foreach ($type->find([], ['name ASC']) as $row) {
            $result[(int) $row['id']] = (string) ($row['name'] ?? '');
        }

        return $result;
    }
}
