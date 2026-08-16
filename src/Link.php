<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
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
            return $fail(__('A hierarquia não permite este vínculo: a origem precisa estar acima do destino (DIO → DGO → CTO), nunca no mesmo nível nem abaixo.', 'dgoplus'));
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
