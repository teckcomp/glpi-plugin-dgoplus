<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
use PassiveDCEquipment;
use Session;

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
     * Para onde vai este vinculo, em texto legivel. Bloco 4c.
     *
     * Par simetrico do describeOrigin, para a secao "Alimenta" do painel da
     * porta: rotulo E<n> + nome do elemento alimentado. Nao ha largura de
     * grade envolvida - entrada nao tem posicao continua.
     *
     * @param int $dst_ports_id
     * @return array{ok:bool, label:string, item:string}
     */
    public static function describeDestination(int $dst_ports_id): array
    {
        $empty = ['ok' => false, 'label' => '', 'item' => ''];

        if ($dst_ports_id <= 0) {
            return $empty;
        }

        $port = new Port();
        if (!$port->getFromDB($dst_ports_id)) {
            return $empty;
        }

        return [
            'ok'    => true,
            'label' => Port::formatEntryLabel((int) ($port->fields['fiber_num'] ?? 0)),
            'item'  => self::itemNameOf(
                (string) ($port->fields['itemtype'] ?? ''),
                (int) ($port->fields['items_id'] ?? 0)
            ),
        ];
    }

    /**
     * A hierarquia permite que src alimente dst? Bloco 4c.
     *
     * PERMISSIVA: pode pular nivel (DIO alimenta CTO direto). O que ela nunca
     * permite e' SUBIR nem ficar no MESMO nivel - e "mesmo nivel" ja cobre a
     * autorreferencia, porque um elemento tem o mesmo papel que ele proprio.
     * Como todo vinculo desce um degrau, ciclo e' impossivel por construcao.
     *
     * A ordem vem do registro de papeis (Setting::ROLES), que E' a hierarquia
     * fisica - decisao do 4a-1. Papel nulo (elemento sem Tipo mapeado) nunca
     * participa: sem papel nao ha degrau para comparar.
     *
     * @param string|null $src_role
     * @param string|null $dst_role
     * @return bool
     */
    public static function hierarchyAllows(?string $src_role, ?string $dst_role): bool
    {
        if ($src_role === null || $dst_role === null) {
            return false;
        }

        $order = array_flip(Setting::getRoles());

        if (!isset($order[$src_role]) || !isset($order[$dst_role])) {
            return false;
        }

        return $order[$src_role] < $order[$dst_role];
    }

    /**
     * Propoe um vinculo: porta de grade da origem -> entrada do destino.
     *
     * PONTO UNICO de criacao de vinculo (bloco 4c), pela mesma razao de
     * Port::applyInput ser o ponto unico da gravacao de porta: a regra
     * (hierarquia, limites da grade, lados ja ocupados) nao pode existir em
     * dois lugares. O POST classico chama este metodo; um endpoint AJAX
     * futuro chamara o mesmo.
     *
     * A linha nasce PENDENTE e a porta ja conta como ocupada nas duas pontas
     * (decisao fechada da Fase 4). As pre-checagens de "ja ocupada" sao de
     * cortesia - a palavra final e' das UNIQUE do banco (unicity_src e
     * unicity_dst, provadas com 1062 no 4b-2); bater nelas viraria "erro
     * inesperado" na tela, e por isso elas nao sao a primeira linha de defesa.
     *
     * Se o INSERT falhar DEPOIS de um ensureEntry que criou a linha da
     * entrada, a linha fica - sem vinculo, a caixa E<n> continua "livre" na
     * tela e a proxima proposta a reaproveita pelo proprio ensureEntry. E' o
     * unico ponto em que o invariante "linha de entrada so' existe com
     * vinculo" afrouxa, e e' invisivel de proposito.
     *
     * @param array $params itemtype, items_id, tube_num, fiber_num (origem);
     *                      dst_itemtype, dst_items_id, dst_slot (destino)
     * @return array{ok:bool, error:string, id:int}
     */
    public static function propose(array $params): array
    {
        $itemtype     = (string) ($params['itemtype'] ?? '');
        $items_id     = (int) ($params['items_id'] ?? 0);
        $tube_num     = (int) ($params['tube_num'] ?? 0);
        $fiber_num    = (int) ($params['fiber_num'] ?? 0);
        $dst_itemtype = (string) ($params['dst_itemtype'] ?? '');
        $dst_items_id = (int) ($params['dst_items_id'] ?? 0);
        $dst_slot     = (int) ($params['dst_slot'] ?? 0);

        $fail = static function (string $error): array {
            return ['ok' => false, 'error' => $error, 'id' => 0];
        };

        if ($itemtype === '' || $items_id <= 0 || $tube_num < 1 || $fiber_num < 1) {
            return $fail(__('Posição de origem inválida.', 'dgoplus'));
        }

        if ($dst_itemtype === '' || $dst_items_id <= 0) {
            return $fail(__('Escolha o elemento de destino.', 'dgoplus'));
        }

        if ($dst_slot < 1 || $dst_slot > Port::MAX_ENTRIES) {
            return $fail(
                sprintf(__('Entrada inválida: são %d entradas por elemento.', 'dgoplus'), Port::MAX_ENTRIES)
            );
        }

        // Origem e destino com a MESMA trava de pai do 3m: existir, ser
        // CommonDBTM e ser visivel para este usuario (direito + entidade).
        $origin = self::loadVisibleItem($itemtype, $items_id);
        if ($origin === null) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $dest = self::loadVisibleItem($dst_itemtype, $dst_items_id);
        if ($dest === null) {
            return $fail(__('Elemento de destino não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        // Papel vem do Tipo nativo, nunca do nome (regra dura do 4a).
        if (!self::hierarchyAllows(Setting::getRoleOfItem($origin), Setting::getRoleOfItem($dest))) {
            return $fail(sprintf(
                __('A hierarquia não permite este vínculo: a origem precisa estar acima do destino (%s), nunca no mesmo nível nem abaixo.', 'dgoplus'),
                Setting::getRoleChainLabel()
            ));
        }

        // Limite da grade da ORIGEM: uma celula forjada fora do layout
        // criaria porta fantasma que a grade nunca desenharia (licao 14).
        $layout = Panel::getLayoutForItem($origin);
        if ($tube_num > (int) $layout['tubes'] || $fiber_num > (int) $layout['fibers_per_tube']) {
            return $fail(__('Posição de origem fora da grade do elemento.', 'dgoplus'));
        }

        // Bloco 4c-2: TODAS as recusas acontecem em leitura, ANTES de criar ou
        // restaurar qualquer linha. Antes desta ordem, ensureGrid restaurava a
        // porta da lixeira e so' depois a recusa aparecia - acao recusada
        // deixava rastro no banco. Regra: nada e' materializado enquanto uma
        // recusa ainda for possivel.
        //
        // A leitura NAO filtra is_deleted, pela licao 112: a chave unica e'
        // (itemtype, items_id, tube_num, fiber_num) e linha na lixeira ocupa a
        // posicao. Mas o que ela guarda NAO vale como estado atual - porta na
        // lixeira e' posicao livre para quem olha a tela, entao o
        // is_no_coupler dela nao pode recusar proposta nenhuma.
        $probe = new Port();
        $existing = $probe->getFromDBByCrit([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'tube_num'  => $tube_num,
            'fiber_num' => $fiber_num,
        ]);

        if ($existing && (int) $probe->fields['is_deleted'] === 0) {
            if ((int) ($probe->fields['is_no_coupler'] ?? 0) === 1) {
                return $fail(__('Uma porta sem acoplador não pode alimentar. Desmarque a opção antes de propor o vínculo.', 'dgoplus'));
            }

            $already = self::findByOrigins([(int) $probe->getID()]);
            if (isset($already[(int) $probe->getID()])) {
                return $fail(__('Esta porta já alimenta um destino. Desmonte o vínculo atual antes de propor outro.', 'dgoplus'));
            }
        }

        // Entrada de destino: mesma leitura previa. Entrada na lixeira tambem
        // e' slot livre - o vinculo que a ocupava ja foi embora (a faxina do
        // removeAndClean e' quem a lixeirou).
        $dst_probe = new Port();
        $dst_exists = $dst_probe->getFromDBByCrit([
            'itemtype'  => $dst_itemtype,
            'items_id'  => $dst_items_id,
            'tube_num'  => Port::ENTRY_TUBE,
            'fiber_num' => $dst_slot,
        ]);

        if ($dst_exists && (int) $dst_probe->fields['is_deleted'] === 0) {
            $taken = self::findByDestinations([(int) $dst_probe->getID()]);
            if (isset($taken[(int) $dst_probe->getID()])) {
                return $fail(
                    sprintf(__('A entrada %s deste elemento já está ocupada. Escolha outra.', 'dgoplus'), Port::formatEntryLabel($dst_slot))
                );
            }
        }

        // Daqui em diante nenhuma recusa de regra e' possivel: pode materializar.
        $grid = Port::ensureGrid($itemtype, $items_id, $tube_num, $fiber_num);
        if (!$grid['ok']) {
            return $fail($grid['error']);
        }

        $entry = Port::ensureEntry($dst_itemtype, $dst_items_id, $dst_slot);
        if (!$entry['ok']) {
            return $fail($entry['error']);
        }

        // ensureGrid/ensureEntry so' exigem CREATE quando CRIAM linha; com as
        // duas linhas pre-existentes nenhuma checagem teria rodado ate aqui.
        Session::checkRight(self::$rightname, CREATE);

        // No 11.0.6, INSERT que falha nao devolve false: DBmysql::doQuery
        // LANCA RuntimeException (DBmysql.php:415-424, mysqli_report OFF na
        // conexao). Sem o catch, uma corrida na UNIQUE (dois usuarios
        // propondo o mesmo lado entre a pre-checagem e o INSERT) viraria
        // tela 500 - e a mensagem amigavel abaixo seria codigo morto.
        $link = new self();
        try {
            $id = (int) $link->add([
                'entities_id'                 => (int) ($origin->fields['entities_id'] ?? 0),
                'is_recursive'                => (int) ($origin->fields['is_recursive'] ?? 0),
                'plugin_dgoplus_ports_id_src' => $grid['id'],
                'plugin_dgoplus_ports_id_dst' => $entry['id'],
                'status'                      => self::STATUS_PENDING,
                'users_id_proposer'           => (int) Session::getLoginUserID(),
                'users_id_confirmer'          => 0,
                'comment'                     => '',
            ]);
        } catch (\RuntimeException $e) {
            $id = 0;
        }

        if ($id <= 0) {
            // Corrida real: outra proposta ocupou um dos lados entre a
            // pre-checagem e o INSERT, e a UNIQUE recusou.
            return $fail(__('Não foi possível criar o vínculo — a porta ou a entrada pode ter sido ocupada agora. Recarregue a página e tente de novo.', 'dgoplus'));
        }

        return ['ok' => true, 'error' => '', 'id' => $id];
    }

    /**
     * Confirma um vinculo pendente. Bloco 4c.
     *
     * Quem confirma fica REGISTRADO em users_id_confirmer - inclusive quando
     * e' o proprio proponente (autoconfirmacao permitida e registrada,
     * decisao fechada da Fase 4). Confirmar de novo um vinculo ja confirmado
     * e' idempotente: nao regrava o confirmador nem gera historico.
     *
     * @param int $id
     * @return array{ok:bool, error:string, id:int}
     */
    public static function confirm(int $id): array
    {
        Session::checkRight(self::$rightname, UPDATE);

        $fail = static function (string $error): array {
            return ['ok' => false, 'error' => $error, 'id' => 0];
        };

        $link = new self();
        if ($id <= 0 || !$link->getFromDB($id)) {
            return $fail(__('Vínculo não encontrado.', 'dgoplus'));
        }

        if (!self::touchesVisibleItem($link)) {
            return $fail(__('Vínculo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        if ((string) $link->fields['status'] === self::STATUS_CONFIRMED) {
            return ['ok' => true, 'error' => '', 'id' => $id];
        }

        $link->update([
            'id'                 => $id,
            'status'             => self::STATUS_CONFIRMED,
            'users_id_confirmer' => (int) Session::getLoginUserID(),
        ]);

        return ['ok' => true, 'error' => '', 'id' => $id];
    }

    /**
     * Recusa um vinculo pendente: APAGA a linha. Bloco 4c.
     *
     * Recusa e confirmacao sao as duas metades da mesma resposta ao
     * proponente, e por isso pedem o MESMO direito (UPDATE) - exigir DELETE
     * para recusar deixaria um perfil capaz de aceitar mas incapaz de dizer
     * nao. Vinculo ja confirmado nao se recusa: desmonta-se (DELETE), porque
     * ai e' documentacao estabelecida sendo destruida.
     *
     * @param int $id
     * @return array{ok:bool, error:string, id:int}
     */
    public static function refuse(int $id): array
    {
        Session::checkRight(self::$rightname, UPDATE);

        $fail = static function (string $error): array {
            return ['ok' => false, 'error' => $error, 'id' => 0];
        };

        $link = new self();
        if ($id <= 0 || !$link->getFromDB($id)) {
            return $fail(__('Vínculo não encontrado.', 'dgoplus'));
        }

        if (!self::touchesVisibleItem($link)) {
            return $fail(__('Vínculo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        if ((string) $link->fields['status'] === self::STATUS_CONFIRMED) {
            return $fail(__('Este vínculo já foi confirmado. Use Desmontar.', 'dgoplus'));
        }

        if (!self::removeAndClean($link)) {
            return $fail(__('Não foi possível recusar o vínculo.', 'dgoplus'));
        }

        return ['ok' => true, 'error' => '', 'id' => $id];
    }

    /**
     * Desmonta um vinculo (pendente ou confirmado): APAGA a linha. Bloco 4c.
     *
     * Desmontagem e' DIRETA - nao tem segunda confirmacao nem lixeira
     * (decisao fechada da Fase 4). Pede DELETE porque destroi documentacao;
     * o botao aparece nos dois lados (secao Alimenta da origem e card da
     * entrada no destino).
     *
     * @param int $id
     * @return array{ok:bool, error:string, id:int}
     */
    public static function dismantle(int $id): array
    {
        Session::checkRight(self::$rightname, DELETE);

        $fail = static function (string $error): array {
            return ['ok' => false, 'error' => $error, 'id' => 0];
        };

        $link = new self();
        if ($id <= 0 || !$link->getFromDB($id)) {
            return $fail(__('Vínculo não encontrado.', 'dgoplus'));
        }

        if (!self::touchesVisibleItem($link)) {
            return $fail(__('Vínculo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        if (!self::removeAndClean($link)) {
            return $fail(__('Não foi possível desmontar o vínculo.', 'dgoplus'));
        }

        return ['ok' => true, 'error' => '', 'id' => $id];
    }

    /**
     * Apaga a linha do vinculo e faz a faxina das portas que so' existiam por
     * causa dele.
     *
     * Sem is_deleted na tabela, o delete() do core APAGA de verdade - e' o
     * comportamento decidido (recusa apaga; desmontagem e' direta). O
     * historico proprio (dohistory) registra a remocao em glpi_logs.
     *
     * A faxina honra o invariante "linha de porta so' existe para sustentar
     * conteudo ou vinculo":
     *  - a ENTRADA de destino fica sem vinculo (unicity_dst garante que este
     *    era o unico) e vai para a lixeira - o ensureEntry a restaura se um
     *    vinculo novo chegar;
     *  - a porta de ORIGEM so' vai para a lixeira se estiver VAZIA (sem
     *    codigo, sem OBS, sem marca de acoplador): era uma linha criada pela
     *    propria proposta. Porta com conteudo fica - o conteudo a sustenta.
     *
     * SEM Session::checkRight aqui, de proposito: o direito exercido foi o do
     * VINCULO (UPDATE na recusa, DELETE na desmontagem); estas exclusoes
     * suaves sao consequencia mecanica do invariante, nao acao do usuario
     * sobre portas. O pre_deleteItem do Port nao bloqueia: o vinculo ja saiu
     * do banco quando as portas sao lixeiradas.
     *
     * @param self $link carregado (getFromDB ja feito)
     * @return bool
     */
    private static function removeAndClean(self $link): bool
    {
        $src_id = (int) $link->fields['plugin_dgoplus_ports_id_src'];
        $dst_id = (int) $link->fields['plugin_dgoplus_ports_id_dst'];

        if (!$link->delete(['id' => $link->getID()], 1)) {
            return false;
        }

        $dst = new Port();
        if ($dst->getFromDB($dst_id) && (int) $dst->fields['is_deleted'] === 0) {
            $dst->delete(['id' => $dst_id]);
        }

        $src = new Port();
        if (
            $src->getFromDB($src_id)
            && (int) $src->fields['is_deleted'] === 0
            && trim((string) ($src->fields['code'] ?? '')) === ''
            && trim((string) ($src->fields['comment'] ?? '')) === ''
            && (int) ($src->fields['is_no_coupler'] ?? 0) === 0
        ) {
            $src->delete(['id' => $src_id]);
        }

        return true;
    }

    /**
     * O vinculo toca algum elemento visivel para este usuario?
     *
     * Confirmar, recusar e desmontar valem para quem enxerga QUALQUER um dos
     * dois lados: o fluxo e' de duas partes, e cada parte age da sua tela.
     * O que isto barra e' agir sobre vinculo inteiramente fora da entidade
     * do usuario - a mesma classe de furo que a trava de pai do 3m fechou.
     *
     * @param self $link carregado
     * @return bool
     */
    private static function touchesVisibleItem(self $link): bool
    {
        foreach (['plugin_dgoplus_ports_id_src', 'plugin_dgoplus_ports_id_dst'] as $fk) {
            $port = new Port();

            if (!$port->getFromDB((int) ($link->fields[$fk] ?? 0))) {
                continue;
            }

            $item = self::loadVisibleItem(
                (string) ($port->fields['itemtype'] ?? ''),
                (int) ($port->fields['items_id'] ?? 0)
            );

            if ($item !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Carrega um ativo que exista e seja visivel para este usuario, ou null.
     *
     * Mesmo par de checagens da trava de pai do 3m (Port::applyInput):
     * can(id, READ) cobre o direito global do itemtype e a entidade da
     * instancia. Mensagem generica fica a cargo de quem chama - dizer "existe
     * mas voce nao pode ver" ja e' informacao demais.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return CommonDBTM|null
     */
    private static function loadVisibleItem(string $itemtype, int $items_id): ?CommonDBTM
    {
        if ($itemtype === '' || $items_id <= 0 || !class_exists($itemtype)) {
            return null;
        }

        $item = new $itemtype();

        if (
            !($item instanceof CommonDBTM)
            || !$item->getFromDB($items_id)
            || !$item->can($items_id, READ)
        ) {
            return null;
        }

        return $item;
    }

    /**
     * Nome legivel de um ativo, com o fallback "elemento #N".
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return string
     */
    /**
     * A cadeia de alimentacao ACIMA de um elemento, nivel a nivel. Bloco 4e.
     *
     * Nivel 0 = pais diretos, nivel 1 = avos, e assim por diante. Cada nivel
     * traz TODOS os pais lado a lado (decisao do 4e): um elemento com duas
     * entradas confirmadas de origens diferentes tem dois pais no mesmo nivel,
     * e esconder um deles seria o mapa mentindo por omissao.
     *
     * SO' vinculo CONFIRMADO sobe na trilha (decisao do 4e): pendente e'
     * proposta, nao topologia - ele aparece nas listas com selo amarelo, nunca
     * na cadeia.
     *
     * Em LOTE por nivel, nunca por elemento: entradas dos elementos do nivel
     * numa consulta, vinculos noutra, portas de origem noutra, elementos-pai
     * noutra. A trilha percorre varios niveis e o custo por elemento
     * multiplicaria (regra do 4d, mesma do pendingRows).
     *
     * Teto de profundidade = numero de papeis registrados. A hierarquia
     * permissiva desce um degrau por vinculo, entao uma cadeia legitima nunca
     * tem mais niveis do que papeis - e o teto, junto com o conjunto de
     * elementos ja visitados, garante parada mesmo com dado corrompido no
     * banco (um ciclo que o propose nunca criaria, mas SQL na mao poderia).
     *
     * Restricao de entidade igual a do pendingRows: elemento-pai que o usuario
     * nao enxerga nao entra no nivel - a trilha para nele, em vez de vazar
     * nome de ativo de outra entidade.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array<int, array<int, array{row: array, role: string|null}>>
     */
    public static function upstreamLevels(string $itemtype, int $items_id): array
    {
        if ($itemtype !== PassiveDCEquipment::class || $items_id <= 0) {
            return [];
        }

        $type_field = Setting::getTypeField();
        $max_levels = count(Setting::getRoles());

        $levels  = [];
        $seen    = [$items_id => true];
        $current = [$items_id];

        for ($depth = 0; $depth < $max_levels && $current !== []; $depth++) {
            // --- Entradas dos elementos deste nivel, numa consulta ---
            $port_model = new Port();
            $entries    = $port_model->find([
                'itemtype'   => PassiveDCEquipment::class,
                'items_id'   => $current,
                'is_deleted' => 0,
            ] + Port::entryCriteria());

            $entry_ids = [];
            foreach ($entries as $row) {
                $entry_ids[] = (int) $row['id'];
            }

            if ($entry_ids === []) {
                break;
            }

            // --- Vinculos confirmados que chegam nessas entradas ---
            $src_ids = [];
            foreach (self::findByDestinations($entry_ids) as $link) {
                if ((string) ($link['status'] ?? '') === self::STATUS_CONFIRMED) {
                    $src_ids[] = (int) $link['plugin_dgoplus_ports_id_src'];
                }
            }
            $src_ids = self::cleanIds($src_ids);

            if ($src_ids === []) {
                break;
            }

            // --- Portas de origem -> elementos-pai (sem os ja visitados) ---
            $src_model  = new Port();
            $parent_ids = [];
            foreach ($src_model->find(['id' => $src_ids]) as $row) {
                if ((string) ($row['itemtype'] ?? '') !== PassiveDCEquipment::class) {
                    continue;
                }
                $pid = (int) $row['items_id'];
                if ($pid > 0 && !isset($seen[$pid])) {
                    $parent_ids[$pid] = $pid;
                }
            }

            if ($parent_ids === []) {
                break;
            }

            // --- Elementos-pai visiveis para este usuario ---
            $model   = new PassiveDCEquipment();
            $parents = $model->find(
                ['id' => array_values($parent_ids)]
                + getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true)
            );

            if ($parents === []) {
                break;
            }

            $level = [];
            foreach ($parents as $row) {
                $level[] = [
                    'row'  => $row,
                    'role' => Setting::getRoleForType((int) ($row[$type_field] ?? 0)),
                ];
            }

            // Ordem estavel: nome, desempate por id - a trilha nao pode dancar
            // entre duas aberturas da mesma tela (mesma razao do pendingRows).
            usort(
                $level,
                fn(array $a, array $b): int =>
                    strcmp((string) ($a['row']['name'] ?? ''), (string) ($b['row']['name'] ?? ''))
                        ?: ((int) $a['row']['id'] <=> (int) $b['row']['id'])
            );

            $levels[] = $level;

            $current = [];
            foreach ($level as $node) {
                $pid          = (int) $node['row']['id'];
                $seen[$pid]   = true;
                $current[]    = $pid;
            }
        }

        return $levels;
    }

    /**
     * Quem este elemento alimenta, agrupado por elemento de destino. Bloco 4e.
     *
     * A consulta reversa da trilha: portas de grade do elemento -> vinculos
     * que saem delas -> entradas de destino -> elementos alimentados. Traz
     * PENDENTES tambem (decisao do 4e): na lista eles aparecem com selo, so'
     * na trilha e' que nao sobem.
     *
     * Em LOTE: grade numa consulta, vinculos noutra, entradas noutra,
     * elementos noutra - nunca describe* em laco (regra do 4d).
     *
     * Restricao de entidade tira o GRUPO inteiro: destino que o usuario nao
     * enxerga nao aparece nem anonimo (mesma regra do pendingRows).
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array<int, array{
     *   row: array, role: string|null,
     *   links: array<int, array{src_label:string, dst_label:string, pending:bool,
     *                           tube_num:int, fiber_num:int}>
     * }>
     */
    public static function downstreamOf(string $itemtype, int $items_id): array
    {
        if ($itemtype !== PassiveDCEquipment::class || $items_id <= 0) {
            return [];
        }

        // --- Portas de grade do elemento ---
        $port_model = new Port();
        $grid_rows  = $port_model->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ] + Port::gridCriteria());

        if ($grid_rows === []) {
            return [];
        }

        $grid_by_id = [];
        foreach ($grid_rows as $row) {
            $grid_by_id[(int) $row['id']] = $row;
        }

        // --- Vinculos que saem dessas portas ---
        $links = self::findByOrigins(array_keys($grid_by_id));

        if ($links === []) {
            return [];
        }

        // --- Entradas de destino, numa consulta ---
        $dst_ids = [];
        foreach ($links as $link) {
            $dst_ids[] = (int) $link['plugin_dgoplus_ports_id_dst'];
        }
        $dst_ids = self::cleanIds($dst_ids);

        $dst_model = new Port();
        $dst_ports = [];
        foreach ($dst_model->find(['id' => $dst_ids]) as $row) {
            $dst_ports[(int) $row['id']] = $row;
        }

        // --- Elementos de destino visiveis, numa consulta ---
        $dst_item_ids = [];
        foreach ($dst_ports as $row) {
            if ((string) ($row['itemtype'] ?? '') === PassiveDCEquipment::class) {
                $dst_item_ids[] = (int) $row['items_id'];
            }
        }
        $dst_item_ids = self::cleanIds($dst_item_ids);

        $items = [];
        if ($dst_item_ids !== []) {
            $model = new PassiveDCEquipment();
            $found = $model->find(
                ['id' => $dst_item_ids]
                + getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true)
            );
            foreach ($found as $row) {
                $items[(int) $row['id']] = $row;
            }
        }

        if ($items === []) {
            return [];
        }

        // Largura da grade DESTE elemento, para o rotulo continuo da origem.
        $width = Panel::getWidthForItemId($itemtype, $items_id);

        $type_field = Setting::getTypeField();

        $groups = [];
        foreach ($links as $src_port_id => $link) {
            $src = $grid_by_id[$src_port_id] ?? null;
            $dst = $dst_ports[(int) $link['plugin_dgoplus_ports_id_dst']] ?? null;

            if ($src === null || $dst === null) {
                continue;
            }

            $dst_item_id = (int) $dst['items_id'];
            if (!isset($items[$dst_item_id])) {
                continue;
            }

            if (!isset($groups[$dst_item_id])) {
                $groups[$dst_item_id] = [
                    'row'   => $items[$dst_item_id],
                    'role'  => Setting::getRoleForType((int) ($items[$dst_item_id][$type_field] ?? 0)),
                    'links' => [],
                ];
            }

            $groups[$dst_item_id]['links'][] = [
                'src_label' => Port::formatPosition(
                    (int) $src['tube_num'],
                    (int) $src['fiber_num'],
                    $width
                ),
                'dst_label' => Port::formatEntryLabel((int) $dst['fiber_num']),
                'pending'   => (string) ($link['status'] ?? '') !== self::STATUS_CONFIRMED,
                'tube_num'  => (int) $src['tube_num'],
                'fiber_num' => (int) $src['fiber_num'],
            ];
        }

        // Linhas do grupo em ordem de posicao na grade; grupos por nome, com
        // desempate por id - mesma estabilidade da trilha.
        foreach ($groups as &$group) {
            usort(
                $group['links'],
                fn(array $a, array $b): int =>
                    ($a['tube_num'] <=> $b['tube_num']) ?: ($a['fiber_num'] <=> $b['fiber_num'])
            );
        }
        unset($group);

        uasort(
            $groups,
            fn(array $a, array $b): int =>
                strcmp((string) ($a['row']['name'] ?? ''), (string) ($b['row']['name'] ?? ''))
                    ?: ((int) $a['row']['id'] <=> (int) $b['row']['id'])
        );

        return array_values($groups);
    }

    private static function itemNameOf(string $itemtype, int $items_id): string
    {
        if ($itemtype !== '' && $items_id > 0 && class_exists($itemtype)) {
            $item = new $itemtype();
            if ($item instanceof CommonDBTM && $item->getFromDB($items_id)) {
                $name = (string) ($item->fields['name'] ?? '');
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return sprintf(__('elemento #%d', 'dgoplus'), $items_id);
    }

    /**
     * As propostas em aberto, prontas para desenhar. Bloco 4d.
     *
     * PONTO UNICO das duas telas de pendencia (o cartao do painel e a pagina
     * completa). Duas consultas que divergissem por uma virgula dariam numeros
     * diferentes na mesma sessao, e numero que nao fecha com a tela custa mais
     * confianca do que numero menor (a mesma razao do 3l).
     *
     * Em LOTE de proposito: a leitura ingenua chamaria describeOrigin() e
     * describeDestination() por linha, e cada um faz getFromDB de porta mais
     * getFromDB de elemento - quatro consultas por pendencia, mais a largura da
     * grade. Aqui sao quatro consultas no total, independente do numero de
     * linhas.
     *
     * O papel filtra pelo elemento de DESTINO: pendencia e' de quem tem de
     * responder, e quem responde e' o lado alimentado. Filtrar pela origem
     * mostraria ao operador de DIO uma fila que nao e' dele.
     *
     * Restricao de entidade igual a do painel e a do mapa: vinculo cujo destino
     * o usuario nao enxerga nao entra na fila dele.
     *
     * @param string|null $role papel do elemento de destino; null = todos
     * @return array<int, array{
     *   id:int, date_creation:string, age_days:int, proposer:string,
     *   src_item:string, src_label:string,
     *   dst_item:string, dst_label:string, dst_items_id:int,
     *   dst_locations_id:int, dst_slot:int, dst_role:string|null
     * }>
     */
    public static function pendingRows(?string $role = null): array
    {
        if ($role !== null && !Setting::isRole($role)) {
            $role = null;
        }

        $link = new self();
        $rows = $link->find(['status' => self::STATUS_PENDING]);

        if ($rows === []) {
            return [];
        }

        // --- Portas das duas pontas, numa consulta ---
        $port_ids = [];
        foreach ($rows as $row) {
            $port_ids[] = (int) $row['plugin_dgoplus_ports_id_src'];
            $port_ids[] = (int) $row['plugin_dgoplus_ports_id_dst'];
        }
        $port_ids = self::cleanIds($port_ids);

        if ($port_ids === []) {
            return [];
        }

        $port_model = new Port();
        $ports      = [];
        foreach ($port_model->find(['id' => $port_ids]) as $row) {
            $ports[(int) $row['id']] = $row;
        }

        // --- Elementos citados por qualquer ponta, com restricao de entidade ---
        $item_ids = [];
        foreach ($ports as $row) {
            if ((string) ($row['itemtype'] ?? '') === PassiveDCEquipment::class) {
                $item_ids[] = (int) $row['items_id'];
            }
        }
        $item_ids = self::cleanIds($item_ids);

        $items = [];
        if ($item_ids !== []) {
            $model = new PassiveDCEquipment();
            $found = $model->find(
                ['id' => $item_ids]
                + getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true)
            );
            foreach ($found as $row) {
                $items[(int) $row['id']] = $row;
            }
        }

        // --- Larguras de grade, para o rotulo continuo da origem ---
        $widths = [];
        if ($items !== []) {
            $panel_model = new Panel();
            $panels      = $panel_model->find([
                'itemtype' => PassiveDCEquipment::class,
                'items_id' => array_keys($items),
            ]);
            foreach ($panels as $row) {
                $widths[(int) $row['items_id']] = Panel::sanitizeFibers((int) $row['fibers_per_tube']);
            }
        }

        $type_field = Setting::getTypeField();
        // Parenteses obrigatorios: o cast liga mais forte que o ??, e sem eles
        // a leitura de chave ausente vira aviso no log a cada abertura da tela.
        $now = strtotime((string) ($_SESSION['glpi_currenttime'] ?? '')) ?: time();

        $out = [];
        foreach ($rows as $row) {
            $src = $ports[(int) $row['plugin_dgoplus_ports_id_src']] ?? null;
            $dst = $ports[(int) $row['plugin_dgoplus_ports_id_dst']] ?? null;

            if ($src === null || $dst === null) {
                continue;
            }

            $src_id = (int) $src['items_id'];
            $dst_id = (int) $dst['items_id'];

            // Elemento fora da entidade do usuario (ou apagado): a linha inteira
            // sai. Mostrar meia pendencia, com um lado anonimo, seria vazamento
            // de nome de ativo pela porta dos fundos.
            if (!isset($items[$src_id]) || !isset($items[$dst_id])) {
                continue;
            }

            $dst_role = Setting::getRoleForType((int) ($items[$dst_id][$type_field] ?? 0));

            if ($role !== null && $dst_role !== $role) {
                continue;
            }

            $created = (string) ($row['date_creation'] ?? '');
            $stamp   = $created !== '' ? strtotime($created) : false;

            $out[] = [
                'id'            => (int) $row['id'],
                'date_creation' => $created,
                'age_days'      => $stamp !== false ? max(0, (int) floor(($now - $stamp) / 86400)) : 0,
                'proposer'      => (string) getUserName((int) ($row['users_id_proposer'] ?? 0)),
                'src_item'      => self::displayNameOf($items[$src_id], $src_id),
                'src_label'     => Port::formatPosition(
                    (int) $src['tube_num'],
                    (int) $src['fiber_num'],
                    $widths[$src_id] ?? Panel::DEFAULT_FIBERS
                ),
                'dst_item'         => self::displayNameOf($items[$dst_id], $dst_id),
                'dst_label'        => Port::formatEntryLabel((int) $dst['fiber_num']),
                'dst_items_id'     => $dst_id,
                'dst_locations_id' => (int) ($items[$dst_id]['locations_id'] ?? 0),
                'dst_slot'         => (int) $dst['fiber_num'],
                'dst_role'         => $dst_role,
            ];
        }

        // Mais VELHA primeiro: a fila existe para mostrar o que foi esquecido,
        // nao o que acabou de chegar. Empate desempata por id, para a ordem nao
        // dancar entre duas aberturas da mesma tela.
        usort(
            $out,
            fn(array $a, array $b): int => strcmp($a['date_creation'], $b['date_creation']) ?: ($a['id'] <=> $b['id'])
        );

        return $out;
    }

    /**
     * Nome de exibicao de uma linha de elemento ja carregada.
     *
     * Nao usa itemNameOf(): aquele faz getFromDB, e aqui a linha ja esta em
     * maos - seria uma consulta por pendencia so para reler o que o find() ja
     * trouxe.
     *
     * @param array $row
     * @param int   $items_id
     * @return string
     */
    private static function displayNameOf(array $row, int $items_id): string
    {
        $name = trim((string) ($row['name'] ?? ''));

        return $name !== '' ? $name : sprintf(__('elemento #%d', 'dgoplus'), $items_id);
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
