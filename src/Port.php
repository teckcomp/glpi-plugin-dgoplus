<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBChild;
use Location;
use Session;

/**
 * Uma porta (fibra) documentada de um DGO.
 *
 * Filho polimorfico: o pai e o ativo que representa o DGO
 * (por padrao PassiveDCEquipment, mas a tabela aceita qualquer itemtype).
 *
 * Posicao logica = tube_num (fileira / tubo) + fiber_num (coluna / fibra no tubo).
 */
class Port extends CommonDBChild
{
    /** Campo que guarda o itemtype do pai */
    public static $itemtype = 'itemtype';

    /** Campo que guarda o id do pai */
    public static $items_id = 'items_id';

    /** Direito proprio do plugin */
    public static $rightname = 'plugin_dgoplus_port';

    /**
     * Para mexer nas portas basta poder VER o ativo pai
     * (mais o direito plugin_dgoplus_port). Nao exige UPDATE no ativo.
     */
    public static $checkParentRights = self::HAVE_VIEW_RIGHT_ON_ITEM;

    /** Grava historico; como $logs_for_parent e true, aparece no Historico do DGO */
    public $dohistory = true;

    /**
     * Porta da GRADE: a matriz de fileiras x colunas do elemento. E' o unico
     * kind que existia ate' o 4b-1, e por isso e' o DEFAULT da coluna: toda
     * linha gravada antes deste bloco e' grade, e o ALTER as marca sozinho.
     */
    public const KIND_GRID = 'grade';

    /**
     * Porta de ENTRADA (E1-E4). Reservado no 4b-1, usado no 4b-2: a coluna
     * nasce aqui para que os pontos de leitura ja aprendam a filtrar ANTES de
     * existir linha de entrada para confundir o diagnostico.
     */
    public const KIND_ENTRY = 'entrada';

    /**
     * Fileira das entradas. ZERO nao e' "sem fileira": e' o que faz entrada e
     * grade nunca colidirem na chave unica, porque grade e' sempre >= 1. E' a
     * razao de kind ficar FORA da chave (licao 112).
     */
    public const ENTRY_TUBE = 0;

    /**
     * Quatro entradas por elemento, E1 a E4.
     *
     * Elas cobrem `n` filamentos - representam a chegada do splitter, nao
     * quatro fibras nem quatro origens redundantes (decisao do usuario,
     * 15/08). O invariante antigo de "uma entrada por elemento" esta revogado.
     */
    public const MAX_ENTRIES = 4;

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Porta de DGO', 'Portas de DGO', $nb, 'dgoplus');
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-grid-dots';
    }

    /**
     * Matriz de 4 niveis: Ler, Criar, Editar, Deletar (envio para lixeira).
     * DELETE aqui e exclusao suave (fica em "Item na lixeira"); Purge
     * (exclusao definitiva) fica de fora de proposito - ninguem perde
     * documentacao de fibra sem passar pela lixeira primeiro.
     *
     * @param string $interface
     * @return array
     */
    public function getRights($interface = 'central')
    {
        return [
            READ   => __('Read'),
            CREATE => __('Create'),
            UPDATE => __('Update'),
            DELETE => _x('button', 'Delete'),
        ];
    }

    /**
     * Opcoes de busca nativas - alimentam a lista/relatorio em
     * front/port.php (Search::show), com filtro, ordenacao e exportacao
     * (CSV/PDF/Excel) iguais aos da guia de Ativos.
     *
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'code',
            'name'          => __('Nome / Número (Loja)', 'dgoplus'),
            'datatype'      => 'itemlink',
            'itemlink_type' => self::class,
        ];

        // Campo aposentado no bloco 3i-b: saiu do formulario e da grade, mas
        // segue no relatorio para o que ja foi digitado continuar consultavel e
        // exportavel. Nada e' escrito nele a partir de agora.
        $tab[] = [
            'id'       => 2,
            'table'    => $this->getTable(),
            'field'    => 'name',
            'name'     => __('Nome da porta / splitter (histórico)', 'dgoplus'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => 3,
            'table'    => $this->getTable(),
            'field'    => 'itemtype',
            'name'     => __('Tipo do ativo (DGO)', 'dgoplus'),
            'datatype' => 'itemtypename',
        ];

        $tab[] = [
            'id'       => 5,
            'table'    => $this->getTable(),
            'field'    => 'tube_num',
            'name'     => __('Fileira (tubo)', 'dgoplus'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id'       => 6,
            'table'    => $this->getTable(),
            'field'    => 'fiber_num',
            'name'     => __('Fibra', 'dgoplus'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id'       => 7,
            'table'    => $this->getTable(),
            'field'    => 'comment',
            'name'     => __('Observacoes', 'dgoplus'),
            'datatype' => 'text',
        ];

        // Bloco 4b-2: sem esta opcao o relatorio lista entrada e porta de
        // grade misturadas, sem coluna que as distinga e sem filtro para
        // separar - e a contagem exportada em CSV passaria a mentir no dia em
        // que a primeira entrada fosse criada.
        $tab[] = [
            'id'       => 10,
            'table'    => $this->getTable(),
            'field'    => 'kind',
            'name'     => __('Tipo da porta (grade / entrada)', 'dgoplus'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => 9,
            'table'    => $this->getTable(),
            'field'    => 'is_no_coupler',
            'name'     => __('Sem acoplador', 'dgoplus'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'            => 8,
            'table'         => 'glpi_locations',
            'field'         => 'completename',
            'name'          => Location::getTypeName(1),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
            'joinparams'    => [
                'beforejoin' => [
                    'table'      => $this->getTable(),
                    'joinparams' => ['jointype' => 'empty'],
                ],
            ],
            'forcegroupby'  => true,
            'nosearch'      => true,
        ];

        $tab[] = [
            'id'            => 19,
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Ultima atualizacao'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 121,
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Data de criacao'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * Grava (ou apaga) uma porta a partir dos campos do painel.
     *
     * Bloco 4a: esta e' a UNICA implementacao da regra de gravacao. O POST
     * classico (MapController::actionSavePort) e o endpoint AJAX
     * (ajax/port.php) chamam este metodo - se a regra vivesse em dois lugares,
     * um dos dois divergiria na primeira manutencao, e a guarda de contradicao
     * e' justamente a parte que nao pode divergir.
     *
     * Nao chama Session::addMessageAfterRedirect: em AJAX nao ha redirect, e a
     * mensagem ficaria presa na sessao para aparecer fora de contexto na
     * proxima navegacao (licao 47). Quem chama decide como exibir 'error'.
     *
     * O campo 'name' NAO entra no $input em nenhum caminho: saiu da tela no
     * 3i-b e ler de $_POST daria '' apagando em silencio o historico (licao 44).
     *
     * @param array $params itemtype, items_id, tube_num, fiber_num, code,
     *                      comment, is_no_coupler
     * @return array{ok:bool, error:string, state:string, code:string,
     *               comment:string, id:int}
     */
    public static function applyInput(array $params): array
    {
        $itemtype   = (string) ($params['itemtype'] ?? '');
        $items_id   = (int) ($params['items_id'] ?? 0);
        $tube_num   = (int) ($params['tube_num'] ?? 0);
        $fiber_num  = (int) ($params['fiber_num'] ?? 0);
        $code       = trim((string) ($params['code'] ?? ''));
        $comment    = trim((string) ($params['comment'] ?? ''));
        $no_coupler = (int) ($params['is_no_coupler'] ?? 0) === 1;

        $fail = static function (string $error): array {
            return [
                'ok'      => false,
                'error'   => $error,
                'state'   => '',
                'code'    => '',
                'comment' => '',
                'id'      => 0,
            ];
        };

        if ($itemtype === '' || $items_id <= 0 || $tube_num <= 0 || $fiber_num <= 0) {
            return $fail(__('Posição inválida.', 'dgoplus'));
        }

        // Bloco 3m: o ativo pai tem que existir e ser visivel para este usuario.
        //
        // Esta trava existia so no ajax/port.php desde o 4a, e por isso o
        // caminho MAIS FACIL era o MENOS protegido: pelo botao Salvar (POST
        // classico) um items_id forjado gravava porta em DGO de outra entidade.
        // Aqui ela vale para os dois caminhos, que e' o que o 4a prometeu
        // ("uma regra, dois caminhos") e havia cumprido pela metade.
        //
        // $checkParentRights = HAVE_VIEW_RIGHT_ON_ITEM esta declarado nesta
        // classe e NAO substitui isto: ele so e' exercido por can()/check(), e
        // CommonDBTM::add/update/delete/restore nao checam direito nenhum
        // (11.0.6: :1286, :1638, :2114, :2328 - zero chamadas a can* em todos
        // os quatro). Declarar a propriedade nunca protegeu esta gravacao.
        //
        // can($id, READ) e' o mesmo par que canConnexityItem usaria com
        // HAVE_VIEW_RIGHT_ON_ITEM (CommonDBConnexity.php): direito global do
        // itemtype pai (datacenter, no caso do PassiveDCEquipment) + direito na
        // instancia (entidade). Mensagem unica de proposito para os tres casos:
        // dizer "existe mas voce nao pode ver" ja e' informacao demais.
        $parent = new $itemtype();
        if (
            !($parent instanceof \CommonDBTM)
            || !$parent->getFromDB($items_id)
            || !$parent->can($items_id, READ)
        ) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $port = new self();

        // Sem filtro de is_deleted de proposito: a chave unica continua ocupada
        // por linha na lixeira, entao a posicao e' reaproveitada (restaurada) em
        // vez de tentar um INSERT que bateria em 1062.
        $found = $port->getFromDBByCrit([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'tube_num'  => $tube_num,
            'fiber_num' => $fiber_num,
        ]);

        $is_deleted = $found && (int) $port->fields['is_deleted'] === 1;

        // Os tres estados sao exclusivos: uma porta sem acoplador nao tem
        // numero de loja. Recusar e' melhor que gravar uma celula que a grade
        // nao saberia pintar.
        if ($no_coupler && $code !== '') {
            return $fail(__('Uma porta sem acoplador não pode ter nome/número de loja. Limpe o campo ou desmarque a opção.', 'dgoplus'));
        }

        // Nada preenchido E sem a marca de "sem acoplador" = a porta voltou a
        // ser livre. Com a marca, a linha tem que existir para guardar o estado.
        if ($code === '' && $comment === '' && !$no_coupler) {
            if ($found && !$is_deleted) {
                Session::checkRight(self::$rightname, DELETE);
                $port->delete(['id' => $port->getID()]);
            }

            return [
                'ok'      => true,
                'error'   => '',
                'state'   => 'free',
                'code'    => '',
                'comment' => '',
                'id'      => 0,
            ];
        }

        // 'kind' entra na GRAVACAO, mas NAO no getFromDBByCrit acima. A chave
        // unica e' (itemtype, items_id, tube_num, fiber_num) e nao inclui kind:
        // procurar com kind faria uma posicao ocupada por linha de outro kind
        // devolver "nao achei", o INSERT seguinte bateria em 1062 e o usuario
        // veria "erro inesperado". Mesma armadilha que o comentario do
        // is_deleted descreve, por baixo da mesma chave.
        $input = [
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'tube_num'      => $tube_num,
            'fiber_num'     => $fiber_num,
            'kind'          => self::KIND_GRID,
            'code'          => $code,
            'comment'       => $comment,
            'is_no_coupler' => $no_coupler ? 1 : 0,
        ];

        if ($found) {
            if ($is_deleted) {
                // Posicao estava na lixeira: exige CREATE (esta voltando a existir).
                Session::checkRight(self::$rightname, CREATE);
                $port->restore(['id' => $port->getID()]);
            }
            Session::checkRight(self::$rightname, UPDATE);
            $input['id'] = $port->getID();
            $port->update($input);
            $id = $port->getID();
        } else {
            Session::checkRight(self::$rightname, CREATE);
            $id = (int) $port->add($input);
        }

        return [
            'ok'      => true,
            'error'   => '',
            'state'   => $no_coupler ? 'no_coupler' : 'documented',
            'code'    => $code,
            'comment' => $comment,
            'id'      => (int) $id,
        ];
    }

    /**
     * Criterio para somar (+) a um find() que deve enxergar SO' a grade.
     *
     * Ponto unico de proposito, mesma razao de Dashboard::currentRole() ser o
     * ponto unico do papel: sao OITO consultas que precisam deste filtro
     * (contador, badge, grade, busca, trava de fileira, trava de coluna, trava
     * de largura e painel de edicao). Escrito a mao em oito lugares, o nono
     * ponto que alguem acrescentar vai esquecer - e esquecer aqui nao da erro,
     * da numero errado em silencio, que e' o defeito mais caro deste projeto
     * (licao 14).
     *
     * @return array
     */
    public static function gridCriteria(): array
    {
        return ['kind' => self::KIND_GRID];
    }

    /**
     * Criterio para somar (+) a um find() que deve enxergar SO' as entradas.
     *
     * Par simetrico do gridCriteria(), pela mesma razao: filtro escrito a mao
     * em cada tela e' filtro que alguem esquece, e esquecer aqui nao da erro -
     * da numero errado em silencio.
     *
     * @return array
     */
    public static function entryCriteria(): array
    {
        return ['kind' => self::KIND_ENTRY];
    }

    /**
     * As entradas documentadas de um elemento, indexadas por E<n>.
     *
     * Devolve SO' o que existe no banco. A faixa da tela desenha as quatro
     * caixas sempre; slot sem linha aqui e' slot livre - nao ha linha vazia
     * para representar entrada livre, e nao deve haver: linha de porta so
     * nasce quando ha vinculo para pendurar nela.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array<int, array> fiber_num (1-4) => linha
     */
    public static function entriesForItem(string $itemtype, int $items_id): array
    {
        if ($itemtype === '' || $items_id <= 0) {
            return [];
        }

        $port = new self();
        $rows = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ] + self::entryCriteria());

        $by_slot = [];
        foreach ($rows as $row) {
            $slot = (int) ($row['fiber_num'] ?? 0);
            if ($slot >= 1 && $slot <= self::MAX_ENTRIES) {
                $by_slot[$slot] = $row;
            }
        }

        ksort($by_slot);

        return $by_slot;
    }

    /**
     * Garante que a linha da entrada E<n> exista, e devolve o id dela.
     *
     * PONTO UNICO de criacao de entrada, e de proposito FORA do applyInput.
     * O applyInput e' o caminho do painel de edicao da GRADE (POST e AJAX), e
     * a regra dele - tres estados exclusivos, "vazio apaga a linha" - nao vale
     * para entrada: entrada nao tem numero de loja, nao tem acoplador e nao
     * pode sumir por estar "vazia", porque o que a mantem viva e' o VINCULO
     * pendurado nela, nao o conteudo dos campos. Passar entrada pelo applyInput
     * faria a primeira gravacao apagar a linha e derrubar o vinculo junto.
     *
     * Sem filtro de is_deleted no getFromDBByCrit, e sem filtro de kind: a
     * chave unica e' (itemtype, items_id, tube_num, fiber_num) e a licao 112
     * vale igual aqui - busca pela chave unica nunca filtra kind, senao o
     * INSERT seguinte bate em 1062 e a tela diz "erro inesperado".
     *
     * @param string $itemtype
     * @param int    $items_id
     * @param int    $slot fiber_num da entrada, 1 a 4
     * @return array{ok:bool, error:string, id:int}
     */
    public static function ensureEntry(string $itemtype, int $items_id, int $slot): array
    {
        $fail = static function (string $error): array {
            return ['ok' => false, 'error' => $error, 'id' => 0];
        };

        if ($itemtype === '' || $items_id <= 0) {
            return $fail(__('Elemento inválido.', 'dgoplus'));
        }

        if ($slot < 1 || $slot > self::MAX_ENTRIES) {
            return $fail(
                sprintf(__('Entrada inválida: são %d entradas por elemento.', 'dgoplus'), self::MAX_ENTRIES)
            );
        }

        // Mesma trava de pai do applyInput (bloco 3m): existir, ser CommonDBTM
        // e ser visivel para este usuario. add() do core nao checa direito
        // nenhum, entao a checagem tem que ser explicita aqui.
        if (!class_exists($itemtype)) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $parent = new $itemtype();
        if (
            !($parent instanceof \CommonDBTM)
            || !$parent->getFromDB($items_id)
            || !$parent->can($items_id, READ)
        ) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $port  = new self();
        $found = $port->getFromDBByCrit([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'tube_num'  => self::ENTRY_TUBE,
            'fiber_num' => $slot,
        ]);

        if ($found) {
            if ((int) $port->fields['is_deleted'] === 1) {
                Session::checkRight(self::$rightname, CREATE);
                $port->restore(['id' => $port->getID()]);
            }

            return ['ok' => true, 'error' => '', 'id' => (int) $port->getID()];
        }

        Session::checkRight(self::$rightname, CREATE);

        $id = (int) $port->add([
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'tube_num'      => self::ENTRY_TUBE,
            'fiber_num'     => $slot,
            'kind'          => self::KIND_ENTRY,
            'code'          => '',
            'comment'       => '',
            'is_no_coupler' => 0,
        ]);

        if ($id <= 0) {
            return $fail(__('Não foi possível criar a entrada.', 'dgoplus'));
        }

        return ['ok' => true, 'error' => '', 'id' => $id];
    }

    /**
     * Contagem por estado de um DGO, para os badges do cabecalho da grade.
     *
     * "Documentadas" NAO conta as sem acoplador: elas nao sao ocupacao, sao
     * indisponibilidade.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array{documented:int, no_coupler:int, total:int}
     */
    public static function statsForDgo(string $itemtype, int $items_id): array
    {
        $port = new self();
        $rows = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ] + self::gridCriteria());

        $no_coupler = 0;
        foreach ($rows as $row) {
            if ((int) ($row['is_no_coupler'] ?? 0) === 1) {
                $no_coupler++;
            }
        }

        return [
            'documented' => count($rows) - $no_coupler,
            'no_coupler' => $no_coupler,
            'total'      => count($rows),
        ];
    }

    /**
     * Nome legivel de uma porta: posicao, mais o nome/numero da loja se houver.
     *
     * Sobrescrito no bloco 3j por causa de um efeito colateral do 3i-b: o
     * padrao do core devolve o campo `name` (CommonDBTM::computeFriendlyName,
     * 11.0.6:5934), e nada escreve em `name` desde que ele saiu da tela. Com o
     * campo vazio, getName() cai no fallback NOT_AVAILABLE (11.0.6:3724) e o
     * Historico do ativo passou a registrar "N/A (4)" a cada porta criada ou
     * excluida - o log dizia que algo aconteceu sem dizer ONDE.
     *
     * CommonDBChild::getHistoryNameForItem (11.0.6:518) chama getNameID(), que
     * passa por aqui, entao corrigir este metodo conserta o historico sem
     * ressuscitar o campo aposentado.
     *
     * @return string
     */
    protected function computeFriendlyName()
    {
        // Bloco 4b-2: entrada nao tem fileira, entao nao tem rotulo de
        // posicao. Sem este ramo, formatPosition(0, 1, ...) devolveria "F0.01"
        // - proibido pela especificacao - e o valor vazaria para o Historico do
        // ativo, que e' justamente o lugar onde ninguem olha ate' precisar.
        // O ramo e' por KIND, nao por tube_num == 0: kind e' o que a linha
        // declara ser, e formatPosition continua sendo o ponto unico do rotulo
        // da GRADE, sem ganhar um caso especial que so ela conheceria.
        if ((string) ($this->fields['kind'] ?? self::KIND_GRID) === self::KIND_ENTRY) {
            return self::formatEntryLabel((int) ($this->fields['fiber_num'] ?? 0));
        }

        // Bloco 3r: o rotulo continuo depende da largura da grade, entao o
        // Historico precisa dela tambem - senao o log registraria "F2.01" para
        // uma porta que a tela chama de "F2.17".
        $position = self::formatPosition(
            (int) ($this->fields['tube_num'] ?? 0),
            (int) ($this->fields['fiber_num'] ?? 0),
            Panel::getWidthForItemId(
                (string) ($this->fields['itemtype'] ?? ''),
                (int) ($this->fields['items_id'] ?? 0)
            )
        );

        $code = trim((string) ($this->fields['code'] ?? ''));
        if ($code !== '') {
            return $position . ' — ' . $code;
        }

        if ((int) ($this->fields['is_no_coupler'] ?? 0) === 1) {
            return $position . ' — ' . __('sem acoplador', 'dgoplus');
        }

        return $position;
    }

    /**
     * Rotulo curto da posicao, ex.: F2.17
     *
     * Bloco 3r: o numero depois do ponto e' CONTINUO na DGO inteira, como a
     * serigrafia de fabrica de um DIO/DGO real - a fileira 2 de uma grade de
     * 16 colunas comeca em 17, nao em 1. Antes do 3r cada fileira reiniciava
     * em 01, e o rotulo nao correspondia a etiqueta colada no equipamento.
     *
     * O numero e' DERIVADO, nunca gravado: `tube_num` e `fiber_num` continuam
     * sendo a verdade no banco. Em compensacao ele depende da largura, e por
     * isso mudar `fibers_per_tube` com porta documentada renumera tudo - o que
     * as guardas de MapController::actionAddColumn/actionRemoveColumn impedem.
     *
     * $fibers_per_tube e' obrigatorio de proposito. Um default silencioso
     * (0 = "usa o padrao") daria rotulo errado em qualquer ponto de chamada
     * esquecido, sem erro nenhum; sem default, o PHP levanta ArgumentCountError
     * e o problema aparece (licao 14: falha silenciosa custa mais que falha
     * barulhenta).
     *
     * @param int $tube_num
     * @param int $fiber_num
     * @param int $fibers_per_tube Largura da grade (colunas por fileira)
     * @return string
     */
    public static function formatPosition(int $tube_num, int $fiber_num, int $fibers_per_tube): string
    {
        // Posicao invalida sai crua: transformar lixo em numero plausivel
        // esconderia o defeito em vez de mostra-lo.
        if ($tube_num < 1 || $fiber_num < 1) {
            return sprintf('F%d.%02d', $tube_num, $fiber_num);
        }

        $width = $fibers_per_tube > 0 ? $fibers_per_tube : Panel::DEFAULT_FIBERS;

        return sprintf('F%d.%02d', $tube_num, ($tube_num - 1) * $width + $fiber_num);
    }

    /**
     * Rotulo curto de uma entrada: E1 a E4.
     *
     * Ponto unico, como o formatPosition e' o da grade. Slot fora da faixa sai
     * cru ("E0", "E9") em vez de virar E1 por conveniencia: numero plausivel
     * escondendo dado invalido e' o defeito mais caro deste projeto (licao 14).
     *
     * @param int $slot fiber_num da entrada
     * @return string
     */
    public static function formatEntryLabel(int $slot): string
    {
        return 'E' . $slot;
    }

    /**
     * Cor padrao da fibra pela posicao dentro do tubo (padrao ABNT/EIA de 12 cores).
     * Indice 1 = primeira fibra do tubo.
     *
     * @param int $fiber_num
     * @return string Codigo hexadecimal
     */
    public static function getFiberColor(int $fiber_num): string
    {
        $colors = [
            '#2563EB', '#EA7C1F', '#22A559', '#8B5E34', '#94A3B8', '#F1F5F1',
            '#DC3545', '#3A3A3A', '#F2C230', '#8B2FA0', '#EC6BAF', '#2FBFB0',
        ];

        if ($fiber_num < 1) {
            $fiber_num = 1;
        }

        return $colors[($fiber_num - 1) % 12];
    }
}
