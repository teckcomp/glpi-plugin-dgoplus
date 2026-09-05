<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBChild;
use Location;
use PassiveDCEquipment;
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
     * Bloco 3s: o carimbo de documentacao NAO gera linha de historico.
     *
     * A linha que o core ja grava para a alteracao do codigo tem o autor
     * dentro dela (glpi_logs.user_name); registrar "Documentado por: fulano"
     * ao lado seria a mesma informacao duas vezes, e dobraria o volume do
     * Historico do ativo a cada gravacao. CommonDBTM:1736 e :1750 leem esta
     * propriedade para decidir.
     */
    public $history_blacklist = ['users_id_documenter', 'date_documented'];

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
     * URL do relatorio de portas (front/port.php, que e' um Search::show).
     *
     * Existe para a montagem da URL ficar num lugar so' (licao 13): ate' o
     * bloco PAINEL-1a ela estava escrita a mao dentro do MapController, e o
     * link novo do painel seria a segunda copia da mesma string.
     *
     * Mesmo padrao do getPageUrl do MapController: root_doc + caminho
     * literal. PHP_SELF esta morto no GLPI 11 (licao 12) e
     * Plugin::getWebDir() esta deprecado no 11.0.6.
     *
     * Os parametros aceitos sao os do motor de busca do core - 'sort',
     * 'order' e 'criteria' como ARRAY. O escalar (sort=19) tambem funciona,
     * mas o proprio core o marca como compatibilidade com links anteriores ao
     * 10.0 (SearchEngine::prepareDataForSearch, 11.0.6:346-367), entao aqui
     * se usa a forma corrente.
     *
     * @param array $params
     * @return string
     */
    public static function getReportUrl(array $params = []): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $url = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/front/port.php';

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
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

        // Bloco 5h: o join da Localizacao passa pela tabela do ativo.
        //
        // O que estava aqui declarava 'beforejoin' apontando para a PROPRIA
        // tabela de portas, com jointype 'empty' - jointype que o core nao
        // conhece: SQLProvider::getLeftJoinCriteria cai no 'default' e monta
        // join padrao. Como tabela de referencia e tabela do beforejoin eram a
        // mesma, o intermediario era descartado ("auto link") e o join final
        // saia como glpi_plugin_dgoplus_ports.locations_id = glpi_locations.id
        // - coluna que nao existe nesta tabela. Erro 1054 (licao 121).
        //
        // A porta e' polimorfica (itemtype / items_id) e quem carrega
        // locations_id e' o ativo. O jointype do core para "a MINHA tabela
        // aponta para a tabela nova por itemtype/items_id" e'
        // 'itemtype_item_revert'. O 'specific_itemtype' e' obrigatorio: sem
        // ele o core usa o itemtype da busca (PluginDgoplusPort) na condicao
        // do ON, e a coluna voltaria vazia em todas as linhas.
        $tab[] = [
            'id'            => 8,
            'table'         => 'glpi_locations',
            'field'         => 'completename',
            'name'          => Location::getTypeName(1),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
            'joinparams'    => [
                'beforejoin' => [
                    'table'      => 'glpi_passivedcequipments',
                    'joinparams' => [
                        'jointype'          => 'itemtype_item_revert',
                        'specific_itemtype' => PassiveDCEquipment::class,
                    ],
                ],
            ],
            'forcegroupby'  => true,
            'nosearch'      => true,
        ];

        // Bloco 3s: as duas unicas opcoes que o relatorio nao tinha e que a
        // operacao pedia - QUEM documentou e QUANDO. Nada mais mudou aqui: o
        // relatorio inteiro continua como estava, estas entram como
        // complemento das opcoes que ja existiam.
        //
        // right => 'all' de proposito. O default do datatype dropdown sobre
        // glpi_users e' 'interface', que lista apenas quem tem acesso a
        // interface central - e quem documenta porta pode ter perfil so de
        // self-service. Com o default, o filtro nao ofereceria justamente o
        // usuario procurado.
        $tab[] = [
            'id'            => 11,
            'table'         => 'glpi_users',
            'field'         => 'name',
            'linkfield'     => 'users_id_documenter',
            'name'          => __('Documentado por', 'dgoplus'),
            'datatype'      => 'dropdown',
            'right'         => 'all',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 12,
            'table'         => $this->getTable(),
            'field'         => 'date_documented',
            'name'          => __('Data da documentação', 'dgoplus'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
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
    /**
     * Carimbo do ato de documentacao: quem esta gravando, e quando. Bloco 3s.
     *
     * PONTO UNICO das duas colunas, pela mesma razao de gridCriteria() ser o
     * ponto unico do filtro de kind: sao TRES pontos de escrita em _ports
     * (applyInput, ensureGrid, ensureEntry) e quem acrescentar um quarto
     * precisa achar uma linha, nao lembrar de dois nomes de coluna. Esquecer
     * aqui nao da erro - da coluna vazia em silencio.
     *
     * $_SESSION['glpi_currenttime'] e' o relogio da requisicao no GLPI, o
     * mesmo que o core usa para date_mod: sem ele, duas gravacoes da mesma
     * requisicao poderiam diferir em um segundo. O fallback cobre chamada fora
     * de sessao (CLI), onde getLoginUserID() ja devolve 0.
     *
     * @return array{users_id_documenter:int, date_documented:string}
     */
    public static function documentStamp(): array
    {
        return [
            'users_id_documenter' => (int) Session::getLoginUserID(),
            'date_documented'     => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        ];
    }

    /**
     * O ativo pai existe e esta ao alcance deste usuario?
     *
     * PONTO UNICO da regra de visibilidade do pai. Nasceu no bloco 5f-3a
     * porque a mesma pergunta era feita em quatro lugares (applyInput,
     * ensureEntry, ensureGrid e ajax/port.php) - e regra copiada em quatro
     * lugares diverge em silencio no dia em que um deles for editado.
     *
     * O que mudou em relacao ao can($id, READ) que estava aqui: can() somava
     * o direito GLOBAL no itemtype pai ('datacenter', no PassiveDCEquipment)
     * com o acesso a' instancia. O direito global era o acoplamento que devolvia
     * ao tecnico o menu "Dispositivos passivos" inteiro (licao 117). O acesso a'
     * entidade - a protecao que o bloco 3m realmente queria - continua igual,
     * agora perguntado direto:
     *
     *   - getEntityID() devolve -1 quando o itemtype nao e' entity-assign
     *     (CommonDBTM.php:3197), e haveAccessToEntity(-1) e' false: falha
     *     fechado, nunca aberto;
     *   - isRecursive() cobre o ativo publicado numa entidade pai e visto de
     *     baixo (glpi_passivedcequipments TEM is_recursive).
     *
     * @param mixed $parent ativo pai ja carregado por getFromDB
     * @return bool
     */
    public static function parentIsReachable($parent): bool
    {
        if (!($parent instanceof \CommonDBTM) || (int) $parent->getID() <= 0) {
            return false;
        }

        return Session::haveAccessToEntity($parent->getEntityID(), $parent->isRecursive());
    }

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
        // Bloco 5f-3a: a pergunta deixou de ser can($id, READ) e passou a ser
        // parentIsReachable(). can() somava duas coisas - direito global no
        // itemtype pai (datacenter) + entidade -, e era a primeira metade que
        // devolvia ao tecnico o menu "Dispositivos passivos" inteiro so para
        // ele poder documentar uma porta. A entidade, que e' a protecao que o
        // 3m queria, continua exatamente igual. Mensagem unica de proposito
        // para os tres casos: dizer "existe mas voce nao pode ver" ja e'
        // informacao demais.
        $parent = new $itemtype();
        if (
            !($parent instanceof \CommonDBTM)
            || !$parent->getFromDB($items_id)
            || !self::parentIsReachable($parent)
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

        // Bloco 3s: codigo que estava gravado ANTES desta chamada, lido aqui
        // porque restore()/update() mais abaixo substituem $port->fields.
        //
        // Linha na lixeira conta como codigo vazio: para quem olha a tela, a
        // posicao estava LIVRE, e quem escrever um codigo nela esta
        // documentando, nao corrigindo o que outro documentou.
        $old_code = ($found && !$is_deleted) ? trim((string) ($port->fields['code'] ?? '')) : '';

        // Bloco 4c: se esta porta ALIMENTA alguem, duas regras abaixo mudam.
        // A consulta so' roda quando a linha existe - porta sem linha nao tem
        // id para o vinculo apontar.
        $link_row = null;
        if ($found) {
            $links    = Link::findByOrigins([(int) $port->getID()]);
            $link_row = $links[(int) $port->getID()] ?? null;
        }

        // Os tres estados sao exclusivos: uma porta sem acoplador nao tem
        // numero de loja. Recusar e' melhor que gravar uma celula que a grade
        // nao saberia pintar.
        if ($no_coupler && $code !== '') {
            return $fail(__('Uma porta sem acoplador não pode ter nome/número de loja. Limpe o campo ou desmarque a opção.', 'dgoplus'));
        }

        // Bloco 4c: porta que alimenta um destino nao pode virar "sem
        // acoplador" - sem acoplador nao passa sinal, e o vinculo diria o
        // contrario. Recusa dura, como a do codigo + sem acoplador acima:
        // gravar a contradicao faria o mapa mentir em silencio (licao 14).
        if ($no_coupler && $link_row !== null) {
            return $fail(__('Esta porta alimenta outro elemento e não pode ficar sem acoplador. Desmonte o vínculo primeiro.', 'dgoplus'));
        }

        // Nada preenchido E sem a marca de "sem acoplador" = a porta voltou a
        // ser livre. Com a marca, a linha tem que existir para guardar o estado.
        //
        // Bloco 4c: EXCETO quando ha vinculo pendurado nela. O que mantem a
        // linha viva passa a ser o vinculo, nao o conteudo dos campos
        // (documentada = tem codigo OU tem vinculo) - entao esvaziar os campos
        // de uma porta que alimenta alguem NAO a apaga: cai no caminho de
        // gravacao abaixo e a linha fica, vazia, sustentada pelo vinculo.
        if ($code === '' && $comment === '' && !$no_coupler && $link_row === null) {
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

        // Bloco 3s: carimba o ato de documentar, e SO ele.
        //
        // A condicao e' mudanca de VALOR do codigo, nao "gravacao com codigo
        // preenchido": corrigir uma virgula na OBS de uma porta ja documentada
        // roubaria a autoria de quem a documentou de verdade. Pelo mesmo
        // motivo, apagar o codigo nao carimba - o carimbo antigo fica, por
        // decisao do usuario (16/08), e uma porta livre pode exibir quem a
        // documentou por ultimo.
        //
        // Marcar "sem acoplador" tambem nao carimba: e' o oposto de
        // documentar, e a regra de exclusao acima ja garante que ela vem sem
        // codigo.
        if ($code !== '' && $code !== $old_code) {
            $input += self::documentStamp();
        }

        // Bloco 5f-1a: DOCUMENTAR PORTA E' ATUALIZAR, sempre.
        //
        // Ate o 1.3.2 a exigencia dependia de a linha existir no banco: celula
        // ja documentada pedia UPDATE, celula em branco pedia CREATE. Isso
        // vazava um detalhe de implementacao para o usuario - a grade de 1665
        // posicoes e' desenhada inteira na tela, mas so' vira linha quando
        // alguem escreve nela, entao o tecnico com UPDATE via a grade toda e
        // so' conseguia editar as posicoes que outra pessoa ja tinha tocado.
        // Foi a causa real do "o tecnico nao consegue documentar" (licao 118).
        //
        // A semantica da Fase 5: CRIAR e' criar ESTRUTURA (fileira, coluna,
        // piso, elemento); preencher celula da grade e' ATUALIZAR. Vale para o
        // INSERT e para a restauracao da lixeira - nos dois casos o ato do
        // usuario e' o mesmo: documentar uma posicao que a tela ja mostrava.
        if ($found) {
            Session::checkRight(self::$rightname, UPDATE);
            if ($is_deleted) {
                $port->restore(['id' => $port->getID()]);
            }
            $input['id'] = $port->getID();
            $port->update($input);
            $id = $port->getID();
        } else {
            Session::checkRight(self::$rightname, UPDATE);
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

        // Mesma trava de pai do applyInput (bloco 3m, revisto pelo 5f-3a):
        // existir, ser CommonDBTM e estar ao alcance deste usuario. add() do
        // core nao checa direito nenhum, entao a checagem tem que ser
        // explicita aqui.
        if (!class_exists($itemtype)) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $parent = new $itemtype();
        if (
            !($parent instanceof \CommonDBTM)
            || !$parent->getFromDB($items_id)
            || !self::parentIsReachable($parent)
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
            // Bloco 3s: lido ANTES do restore, que substitui $port->fields.
            $has_code = trim((string) ($port->fields['code'] ?? '')) !== '';

            if ((int) $port->fields['is_deleted'] === 1) {
                // Bloco 5f-1b: ATUALIZAR, nao CRIAR - ver o comentario longo
                // logo abaixo, no INSERT desta mesma funcao.
                Session::checkRight(self::$rightname, UPDATE);
                $port->restore(['id' => $port->getID()]);
            }

            // Bloco 3s: a entrada esta sendo documentada AGORA, por um vinculo,
            // e quem propoe e' quem carimba. So nao carimba se a linha ja
            // tivesse codigo - carimbo de quem escreveu codigo e' mais antigo e
            // mais verdadeiro que o de quem apenas ligou a fibra nela.
            //
            // Na pratica entrada nunca tem codigo (nasce vazia por
            // construcao), mas a condicao fica escrita: e' a MESMA do
            // ensureGrid, e uma regra so' nos dois lados nao divergem.
            if (!$has_code) {
                $port->update(
                    ['id' => $port->getID()] + self::documentStamp()
                );
            }

            return ['ok' => true, 'error' => '', 'id' => (int) $port->getID()];
        }

        // Bloco 5f-1b: PROPOR VINCULO E' ATUALIZAR, nao criar.
        //
        // Par do 5f-1a e pela mesma razao. A linha da entrada nasce aqui
        // porque alguem pendurou um VINCULO nela, nao porque alguem criou
        // estrutura: E1-E4 ja aparecem na tela por construcao (MAX_ENTRIES),
        // exatamente como a grade ja e' desenhada inteira. Exigir CREATE aqui
        // fazia o tecnico com ATUALIZAR propor vinculo para um elemento cuja
        // entrada ainda nao tinha sido usada e levar 403 no Salvar - o mesmo
        // vazamento de detalhe de implementacao da licao 118.
        //
        // CRIAR continua sendo criar ESTRUTURA: fileira, coluna, piso,
        // elemento. Ligar duas posicoes que a tela ja mostra e' ATUALIZAR.
        Session::checkRight(self::$rightname, UPDATE);

        $id = (int) $port->add([
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'tube_num'      => self::ENTRY_TUBE,
            'fiber_num'     => $slot,
            'kind'          => self::KIND_ENTRY,
            'code'          => '',
            'comment'       => '',
            'is_no_coupler' => 0,
        ] + self::documentStamp());

        if ($id <= 0) {
            return $fail(__('Não foi possível criar a entrada.', 'dgoplus'));
        }

        return ['ok' => true, 'error' => '', 'id' => $id];
    }

    /**
     * Garante que a linha da porta de GRADE exista, e devolve o id dela.
     *
     * Existe para a PROPOSTA de vinculo (bloco 4c): a origem de um vinculo e'
     * sempre uma porta de grade, e a celula pode estar LIVRE - sem linha no
     * banco - na hora de propor. O applyInput nao serve para isso: a regra
     * dele e' "campo vazio apaga a linha", e a linha criada aqui nasce vazia
     * de proposito - o que a mantem viva e' o vinculo que sera pendurado nela
     * (documentada = tem codigo OU tem vinculo).
     *
     * NAO valida os limites da grade (fileira <= tubes, coluna <= largura):
     * quem conhece o layout e' quem propoe, e a checagem mora em
     * Link::propose. Aqui ficam as travas de mecanica, iguais as do
     * ensureEntry: posicao positiva, pai visivel, restauracao da lixeira e
     * criacao idempotente.
     *
     * tube_num >= 1 tambem garante que a linha achada pela chave unica nunca
     * e' uma entrada (entrada e' sempre tube_num = 0) - e por isso nao ha
     * filtro de kind no getFromDBByCrit, exatamente como manda a licao 112.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @param int    $tube_num
     * @param int    $fiber_num
     * @return array{ok:bool, error:string, id:int, is_no_coupler:bool}
     */
    public static function ensureGrid(string $itemtype, int $items_id, int $tube_num, int $fiber_num): array
    {
        $fail = static function (string $error): array {
            return ['ok' => false, 'error' => $error, 'id' => 0, 'is_no_coupler' => false];
        };

        if ($itemtype === '' || $items_id <= 0 || $tube_num < 1 || $fiber_num < 1) {
            return $fail(__('Posição inválida.', 'dgoplus'));
        }

        // Mesma trava de pai do applyInput e do ensureEntry (bloco 3m,
        // revisto pelo 5f-3a): existir, ser CommonDBTM e estar ao alcance
        // deste usuario.
        if (!class_exists($itemtype)) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $parent = new $itemtype();
        if (
            !($parent instanceof \CommonDBTM)
            || !$parent->getFromDB($items_id)
            || !self::parentIsReachable($parent)
        ) {
            return $fail(__('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'));
        }

        $port  = new self();
        $found = $port->getFromDBByCrit([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'tube_num'  => $tube_num,
            'fiber_num' => $fiber_num,
        ]);

        if ($found) {
            if ((int) $port->fields['is_deleted'] === 1) {
                // Bloco 4c-2: posicao na lixeira e' posicao LIVRE para a tela,
                // entao ela volta LIMPA. Restaurar com o conteudo antigo faria
                // ressuscitar codigo e marca de acoplador que o usuario ja
                // tinha apagado - e a porta reapareceria vermelha na grade por
                // causa de uma proposta que ele acabou de fazer.
                // Bloco 5f-1b: ATUALIZAR, nao CRIAR - mesma razao do
                // ensureEntry, e a posicao ja estava desenhada na grade.
                Session::checkRight(self::$rightname, UPDATE);
                $port->restore(['id' => $port->getID()]);
                $port->update([
                    'id'            => $port->getID(),
                    'kind'          => self::KIND_GRID,
                    'code'          => '',
                    'comment'       => '',
                    'is_no_coupler' => 0,
                ] + self::documentStamp());

                return ['ok' => true, 'error' => '', 'id' => (int) $port->getID(), 'is_no_coupler' => false];
            }

            // Bloco 3s: linha VIVA. Carimba so' quando ela nao tem codigo -
            // nesse caso quem a torna documentada e' o vinculo, e o autor e'
            // quem propoe. Com codigo gravado, o carimbo de quem documentou
            // fica: propor vinculo sobre porta ja documentada nao transfere
            // autoria.
            if (trim((string) ($port->fields['code'] ?? '')) === '') {
                $port->update(
                    ['id' => $port->getID()] + self::documentStamp()
                );
            }

            return [
                'ok'            => true,
                'error'         => '',
                'id'            => (int) $port->getID(),
                'is_no_coupler' => (int) ($port->fields['is_no_coupler'] ?? 0) === 1,
            ];
        }

        // Bloco 5f-1b: ATUALIZAR, nao CRIAR. A celula de grade ja aparecia na
        // tela (o layout do painel a desenha), e virar linha no banco e' o
        // mesmo ato do 5f-1a: documentar uma posicao que ja existia.
        Session::checkRight(self::$rightname, UPDATE);

        $id = (int) $port->add([
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'tube_num'      => $tube_num,
            'fiber_num'     => $fiber_num,
            'kind'          => self::KIND_GRID,
            'code'          => '',
            'comment'       => '',
            'is_no_coupler' => 0,
        ] + self::documentStamp());

        if ($id <= 0) {
            return $fail(__('Não foi possível criar a porta de origem.', 'dgoplus'));
        }

        return ['ok' => true, 'error' => '', 'id' => $id, 'is_no_coupler' => false];
    }

    /**
     * Bloqueia a lixeira de uma porta que participa de vinculo. Bloco 4c.
     *
     * E' o unico gancho que cobre TODOS os caminhos de exclusao suave - o
     * botao "Excluir porta" do painel, a acao em massa da lista nativa
     * (front/port.php) e qualquer chamador futuro de delete(). O core chama
     * pre_deleteItem() dentro de CommonDBTM::delete (11.0.6:2176) e false
     * ABORTA a exclusao - sem mensagem, e por isso o caminho da tela
     * (MapController::actionDeletePort) checa antes e explica; aqui e' a rede
     * de seguranca dos caminhos sem tela.
     *
     * NAO interfere na purga do elemento: o PurgeCleaner apaga por SQL direto
     * (nunca por delete()), e apaga os vinculos ANTES das portas.
     * NAO interfere na faxina de Link::removeAndClean: la o vinculo ja saiu
     * do banco quando as portas sao lixeiradas, e idsTouchingPorts devolve [].
     *
     * @return bool
     */
    public function pre_deleteItem()
    {
        return Link::idsTouchingPorts([(int) $this->getID()]) === [];
    }

    /**
     * Contagem por estado de um DGO, para os badges do cabecalho da grade.
     *
     * "Documentadas" NAO conta as sem acoplador: elas nao sao ocupacao, sao
     * indisponibilidade.
     *
     * Bloco BADGE-C: tambem conta as entradas ocupadas, na MESMA definicao
     * da faixa E1-E4 (renderEntryBox): entrada ocupada = linha de entrada
     * viva com vinculo apontando para ela - pendente ocupa igual (Fase 4).
     * O denominador e' fixo em MAX_ENTRIES: a faixa desenha E1-E4 por
     * construcao, exista ou nao a linha no banco. Este metodo e' o PONTO
     * UNICO das duas contagens - badge do cabecalho e cartao do painel
     * consomem daqui, nunca de consulta propria.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return array{documented:int, no_coupler:int, total:int,
     *               entries_occupied:int, entries_total:int}
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

        $entry_rows = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ] + self::entryCriteria());

        $entry_ids = [];
        foreach ($entry_rows as $erow) {
            $entry_ids[] = (int) $erow['id'];
        }

        return [
            'documented'       => count($rows) - $no_coupler,
            'no_coupler'       => $no_coupler,
            'total'            => count($rows),
            'entries_occupied' => count(Link::findByDestinations($entry_ids)),
            'entries_total'    => self::MAX_ENTRIES,
        ];
    }

    /**
     * Campo que o core trata como "nome" da porta. Bloco 3u.
     *
     * O default do core devolve `name` - campo aposentado no 3i-b, vazio em
     * tudo que foi documentado desde entao. O efeito visivel estava no
     * relatorio: getDefaultToView() (SearchOption.php:742, 11.0.6) forca como
     * primeira coluna a opcao de busca cujo campo e' o getNameField() do
     * itemtype, fora do circuito de preferencias - e a engrenagem nao consegue
     * oculta-la. A operacao ficava com uma primeira coluna quase toda vazia.
     *
     * Devolver `code` faz a coluna forcada virar a opcao 1 (Nome / Numero da
     * Loja), que e' dado vivo, e a opcao 2 (historico) virar coluna comum,
     * ocultavel. Nao ha efeito colateral no Historico nem nos rotulos: o
     * computeFriendlyName() logo abaixo ja e' sobrescrito desde o 3j e nao
     * passa por getNameField(). O SearchEngine deduplica a coluna forcada
     * contra as preferencias (laco "Clean and reorder toview"), entao a
     * preferencia global 11, 12, 1 continua valida como esta.
     *
     * @return string
     */
    public static function getNameField()
    {
        return 'code';
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
