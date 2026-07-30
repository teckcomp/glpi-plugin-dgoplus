<?php

namespace GlpiPlugin\Dgoplus;

use CommonDropdown;
use Location;
use Session;

/**
 * Piso — nivel 2 e ultimo do escopo (Localizacao -> Piso).
 *
 * Pendura numa Localizacao nativa. E' um CommonDropdown para ganhar de graca
 * a tela de Configurar -> Intitulados, o historico e as opcoes de busca.
 *
 * NAO existe front/floor.php nem front/floor.form.php de proposito: no GLPI 11
 * o LegacyItemtypeRouteListener resolve /plugins/dgoplus/front/floor[.form].php
 * sozinho para esta classe. Criar os arquivos so traria aviso de deprecacao
 * (licao 34).
 */
class Floor extends CommonDropdown
{
    /** Direito proprio do plugin — o mesmo do resto do DGO+ */
    public static $rightname = 'plugin_dgoplus_port';

    /** Grava historico das mudancas */
    public $dohistory = true;

    /** Sem traducao de intitulado: o plugin nao publica catalogo de traducao */
    public $can_be_translated = false;

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Piso', 'Pisos', $nb, 'dgoplus');
    }

    /**
     * Mesmo icone que o core usa em DCRoom (src/DCRoom.php:464).
     *
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-building';
    }

    /**
     * A matriz do plugin tem 4 niveis (Ler|Criar|Editar|Deletar = 15) e nao
     * inclui PURGE. Intitulado nao tem lixeira: excluir e' purgar. Sem este
     * mapeamento ninguem conseguiria apagar um piso.
     *
     * @return bool
     */
    public static function canPurge(): bool
    {
        return Session::haveRight(static::$rightname, DELETE);
    }

    /**
     * Campo extra no formulario: a localizacao a que o piso pertence.
     * O tipo 'dropdownValue' resolve o itemtype por getItemtypeForForeignKeyField().
     *
     * @return array
     */
    public function getAdditionalFields()
    {
        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'locations_id',
                    'label' => Location::getTypeName(1),
                    'type'  => 'dropdownValue',
                ],
            ]
        );
    }

    /**
     * O id 4 esta livre: o CommonDropdown usa 1, 2, 3, 16, 19, 80, 86, 121,
     * 137 e 138.
     *
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'       => '4',
            'table'    => 'glpi_locations',
            'field'    => 'completename',
            'name'     => Location::getTypeName(1),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    /**
     * @param array $input
     * @return array|false
     */
    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        if (!is_array($input)) {
            return false;
        }

        return $this->prepareInputCommon($input, 0);
    }

    /**
     * @param array $input
     * @return array|false
     */
    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);

        if (!is_array($input)) {
            return false;
        }

        return $this->prepareInputCommon($input, (int) ($this->fields['id'] ?? 0));
    }

    /**
     * Valida os obrigatorios, barra nome repetido na mesma localizacao (a chave
     * unica daria 1062 na cara do usuario) e herda o escopo de entidade da
     * localizacao — inclusive quando a localizacao muda numa edicao, que o
     * CommonDropdown nao trata.
     *
     * @param array $input
     * @param int   $current_id 0 na criacao
     * @return array|false
     */
    private function prepareInputCommon(array $input, int $current_id)
    {
        $locations_id = array_key_exists('locations_id', $input)
            ? (int) $input['locations_id']
            : (int) ($this->fields['locations_id'] ?? 0);

        if ($locations_id <= 0) {
            Session::addMessageAfterRedirect(
                __s('Escolha a localizacao do piso.', 'dgoplus'),
                false,
                ERROR
            );
            return false;
        }

        $name = array_key_exists('name', $input)
            ? trim((string) $input['name'])
            : (string) ($this->fields['name'] ?? '');

        if ($name === '') {
            Session::addMessageAfterRedirect(
                __s('Informe o nome do piso.', 'dgoplus'),
                false,
                ERROR
            );
            return false;
        }

        if (self::existsAtLocation($locations_id, $name, $current_id)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('Ja existe o piso "%s" nesta localizacao.', 'dgoplus'),
                    htmlescape($name)
                ),
                false,
                ERROR
            );
            return false;
        }

        $input['name'] = $name;

        $location = new Location();
        if ($location->getFromDB($locations_id)) {
            $input['entities_id']  = (int) $location->fields['entities_id'];
            $input['is_recursive'] = (int) $location->fields['is_recursive'];
        }

        return $input;
    }

    /**
     * @param int    $locations_id
     * @param string $name
     * @param int    $except_id
     * @return bool
     */
    private static function existsAtLocation(int $locations_id, string $name, int $except_id): bool
    {
        $criteria = [
            'locations_id' => $locations_id,
            'name'         => $name,
        ];

        if ($except_id > 0) {
            $criteria['NOT'] = ['id' => $except_id];
        }

        return countElementsInTable(self::getTable(), $criteria) > 0;
    }

    /**
     * Usado pelo confirm form nativo quando o core acha que o valor esta em uso.
     *
     * @return bool
     */
    public function haveChildren()
    {
        return self::countPanels((int) $this->getID()) > 0;
    }

    /**
     * A guarda de verdade mora aqui: o DropdownFormController so chama o
     * confirm form quando isUsed() e' verdadeiro, e isUsed() le getDbRelations(),
     * que nao conhece tabela de plugin. pre_deleteItem() o CommonDBTM::delete()
     * chama sempre, inclusive no purge forcado (licao 35).
     *
     * @return bool
     */
    public function pre_deleteItem()
    {
        if (!parent::pre_deleteItem()) {
            return false;
        }

        $dgos = self::countPanels((int) $this->getID());

        if ($dgos > 0) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('O piso "%1$s" nao pode ser excluido: %2$d DGO(s) vinculada(s).', 'dgoplus'),
                    htmlescape((string) ($this->fields['name'] ?? '')),
                    $dgos
                ),
                false,
                ERROR
            );
            return false;
        }

        return true;
    }

    /**
     * Pisos de uma localizacao, no formato id => nome, prontos para
     * Dropdown::showFromArray. Ordenado por nome.
     *
     * @param int $locations_id
     * @return array<int,string>
     */
    public static function getForLocation(int $locations_id): array
    {
        if ($locations_id <= 0) {
            return [];
        }

        $floor = new self();

        // getEntitiesRestrictCriteria devolve array para ser SOMADO (licao 5);
        // o quarto argumento true faz respeitar is_recursive.
        $rows = $floor->find(
            ['locations_id' => $locations_id]
                + getEntitiesRestrictCriteria(self::getTable(), '', '', true),
            ['name ASC']
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = (string) ($row['name'] ?? '');
        }

        return $result;
    }

    /**
     * DGOs classificadas neste piso (o vinculo mora na linha de layout).
     *
     * @param int $floors_id
     * @return int
     */
    public static function countPanels(int $floors_id): int
    {
        if ($floors_id <= 0) {
            return 0;
        }

        return (int) countElementsInTable(
            Panel::getTable(),
            ['plugin_dgoplus_floors_id' => $floors_id]
        );
    }
}
