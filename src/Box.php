<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBChild;
use CommonDBTM;
use PassiveDCEquipment;

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
 * Bloco 6a: schema, modelo e limpeza na purga.
 * Bloco 6b-1: attach() passa a ser o PONTO UNICO de gravacao desta tabela -
 * a acao "Adicionar funcao" do mapa grava por aqui, e os invariantes acima
 * sao checados aqui, nunca na tela. hostRefusal() e' a mesma pergunta feita
 * ANTES de criar o elemento, para nao nascer funcao orfa.
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

    /**
     * Por que ESTE elemento nao pode receber funcao? Cadeia vazia = pode.
     *
     * Bloco 6b-1. Chamado pela acao ANTES de criar o elemento novo (senao a
     * recusa chegaria depois, com um elemento orfao ja gravado) e de novo
     * dentro de attach(), que e' o ponto unico de gravacao. Toda recusa tem
     * frase - hospedeira invalida nunca vira silencio.
     *
     * @param CommonDBTM $host ja carregado por getFromDB (ou vazio, se falhou)
     * @return string
     */
    public static function hostRefusal(CommonDBTM $host): string
    {
        if (!($host instanceof PassiveDCEquipment) || (int) $host->getID() <= 0) {
            return __('A caixa escolhida não existe mais.', 'dgoplus');
        }

        if ((int) ($host->fields['is_deleted'] ?? 0) === 1) {
            return __('A caixa escolhida está na lixeira — restaure-a antes de acrescentar funções.', 'dgoplus');
        }

        if (!Port::parentIsReachable($host)) {
            return __('A caixa escolhida está fora das entidades que você pode acessar.', 'dgoplus');
        }

        if ((int) ($host->fields['locations_id'] ?? 0) <= 0) {
            return __('A caixa escolhida não tem localização — a nova função não teria de onde herdá-la.', 'dgoplus');
        }

        $host_of_host = self::hostOf($host->getType(), (int) $host->getID());
        if ($host_of_host !== null) {
            return sprintf(
                __('Este elemento já é função da caixa #%d — funções se acrescentam na caixa, não dentro de outra função.', 'dgoplus'),
                $host_of_host['items_id']
            );
        }

        return '';
    }

    /**
     * Agrupa $member na caixa $host. PONTO UNICO de gravacao desta tabela.
     *
     * Devolve cadeia vazia no sucesso, a frase da recusa no fracasso. Checa:
     * hospedeira valida (hostRefusal), membro existente e diferente da
     * hospedeira, membro que ainda nao pertence a caixa nenhuma e que nao
     * hospeda ninguem (sem caixa dentro de caixa). A UNIQUE do 6a continua
     * como ultima barreira no banco.
     *
     * @param CommonDBTM $host
     * @param CommonDBTM $member
     * @return string
     */
    public static function attach(CommonDBTM $host, CommonDBTM $member): string
    {
        $refusal = self::hostRefusal($host);
        if ($refusal !== '') {
            return $refusal;
        }

        $member_id = (int) $member->getID();
        if (!($member instanceof PassiveDCEquipment) || $member_id <= 0) {
            return __('A nova função não foi encontrada no banco.', 'dgoplus');
        }

        if ($member->getType() === $host->getType() && $member_id === (int) $host->getID()) {
            return __('Um elemento não pode ser função de si mesmo.', 'dgoplus');
        }

        $current = self::hostOf($member->getType(), $member_id);
        if ($current !== null) {
            return sprintf(
                __('Este elemento já é função da caixa #%d.', 'dgoplus'),
                $current['items_id']
            );
        }

        if (self::membersOf($member->getType(), $member_id) !== []) {
            return __('Este elemento já é uma caixa com funções — caixa dentro de caixa não existe.', 'dgoplus');
        }

        $box = new self();
        $id  = $box->add([
            'itemtype'      => $member->getType(),
            'items_id'      => $member_id,
            'itemtype_host' => $host->getType(),
            'items_id_host' => (int) $host->getID(),
            'position'      => self::nextPosition($host->getType(), (int) $host->getID()),
            'entities_id'   => (int) ($member->fields['entities_id'] ?? 0),
            'is_recursive'  => 0,
        ]);

        if (!$id) {
            return __('O banco recusou o agrupamento — veja o php-errors.log.', 'dgoplus');
        }

        return '';
    }

    /**
     * Proxima posicao na pilha da caixa. A hospedeira e' a posicao 0
     * implicita (nao tem linha), entao o primeiro membro nasce na 1.
     *
     * @param string $itemtype_host
     * @param int    $items_id_host
     * @return int
     */
    public static function nextPosition(string $itemtype_host, int $items_id_host): int
    {
        $max = 0;
        foreach (self::membersOf($itemtype_host, $items_id_host) as $row) {
            $max = max($max, $row['position']);
        }

        return $max + 1;
    }

    /**
     * Papel sugerido para a nova funcao: o degrau LOGO ABAIXO do papel da
     * caixa, na ordem do registro (DIO -> DGO, DGO -> CTO...). Cadeia vazia
     * quando a caixa e' o ultimo degrau ou nao tem papel - ai o usuario
     * escolhe, sem padrao (mesma regra do 4a-3).
     *
     * E' so' SUGESTAO de tela: qualquer papel do registro e' aceito no POST.
     *
     * @param string|null $host_role
     * @return string
     */
    public static function suggestRole(?string $host_role): string
    {
        $roles = Setting::getRoles();
        $pos   = $host_role === null ? false : array_search($host_role, $roles, true);

        if ($pos === false || !isset($roles[$pos + 1])) {
            return '';
        }

        return $roles[$pos + 1];
    }

    /**
     * Nome sugerido para a funcao: o nome da caixa com a sigla do papel novo.
     *
     * "DIO Renner" + DGO -> "DGO Renner" (a sigla do papel da caixa no
     * comeco do nome, seguida de espaco, hifen ou sublinhado, e' TROCADA).
     * "Tecnicos" + DGO -> "DGO Tecnicos" (sem a sigla, ela e' PREFIXADA).
     * Nome vazio -> so' a sigla. Nunca vira regra de papel: papel e' Tipo.
     *
     * @param string      $host_name
     * @param string|null $host_role
     * @param string      $new_role
     * @return string
     */
    public static function suggestName(string $host_name, ?string $host_role, string $new_role): string
    {
        $label = Setting::getRoleLabel($new_role);
        $name  = trim($host_name);

        if ($name === '') {
            return $label;
        }

        if ($host_role !== null && $host_role !== '') {
            $pattern = '/^' . preg_quote(Setting::getRoleLabel($host_role), '/') . '[\\s_\\-]+/iu';
            if (preg_match($pattern, $name) === 1) {
                $rest = trim((string) preg_replace($pattern, '', $name, 1));
                return $rest === '' ? $label : $label . ' ' . $rest;
            }
        }

        return $label . ' ' . $name;
    }
}
