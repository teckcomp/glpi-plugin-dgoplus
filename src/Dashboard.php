<?php

namespace GlpiPlugin\Dgoplus;

use Dropdown;
use Html;
use PassiveDCEquipment;

/**
 * Panorama geral dos elementos, exibido como tela inicial da pagina DGO+
 * (quando nenhuma localizacao esta selecionada).
 *
 * Toda a coleta usa CommonDBTM::find() (sem SQL manual) e agrega em PHP:
 * volume de piloto nao justifica GROUP BY, e o iterator do GLPI 11 tem a
 * armadilha conhecida de COUNT+GROUPBY descartarem os campos do SELECT.
 *
 * Bloco 4a-2: o painel passa a enxergar PAPEL. Ate aqui ele contava os tres
 * papeis juntos e chamava tudo de "DGO", porque so existia um papel de fato.
 * Agora cada contagem se abre por papel, e um filtro opcional restringe a tela
 * inteira a um deles.
 *
 * O papel vem sempre do Tipo nativo do ativo, via Setting - NUNCA do nome. E o
 * mapa Tipo->papel e montado UMA vez por carga de tela: Setting::getRoleForType()
 * releria a configuracao a cada ativo, e com N ativos isso seriam 3N leituras de
 * glpi_configs para responder uma pergunta que nao muda no meio da pagina.
 *
 * A tela NAO conhece a lista de papeis: ela pergunta Setting::getRoles(). Papel
 * novo entra no registro e as colunas, os cards e o filtro se ajustam sozinhos.
 * Acima de MAX_ROLE_COLUMNS papeis, a tabela colapsa numa coluna so - com seis
 * papeis, sete colunas espremeriam a barra de ocupacao contra a borda do card,
 * que e' o defeito que o bloco 3n ja pagou uma vez.
 *
 * Cores: a moldura (cartao, tabela, badge) usa classes nativas do tema, e
 * so as confirmadas em templates do core (bg-blue-lt, bg-green-lt,
 * bg-yellow-lt, bg-red-lt). O teal do prototipo entra como DESTAQUE, sempre
 * por cor explicita - nao depende de classe de paleta que o core nao usa.
 */
class Dashboard
{
    /** Destaque do projeto (teal do prototipo) */
    private const ACCENT = '#2FBFB0';

    /** Fundo suave do destaque, para pastilha de icone */
    private const ACCENT_SOFT = 'rgba(47,191,176,0.14)';

    /** Ocupacao media */
    private const WARN = '#E8B84B';

    /** Ocupacao alta (perto de lotar) */
    private const FULL = '#D6534A';

    /** Barra sem nada documentado */
    private const EMPTY_BAR = '#CBD5E1';

    /**
     * Quantas pendencias cabem no cartao do painel. Bloco 4d.
     *
     * O cartao e' radar, nao mesa de trabalho: mostra o topo da fila e manda o
     * resto para a pagina completa. Sem teto, uma fila grande esticaria a faixa
     * inferior e empurraria o resto do painel para fora da tela.
     */
    private const PENDING_ON_CARD = 5;

    /**
     * Largura dos cartoes da faixa inferior. Bloco 4d: eram dois a meia
     * largura, viraram tres.
     *
     * Em xl os tres ficam lado a lado; em lg, dois em cima e um embaixo; no
     * celular, empilhados. Escrito uma vez porque tres literais iguais em tres
     * lugares e' o comeco de uma faixa torta - basta alguem ajustar dois.
     */
    private const BOTTOM_COL = 'col-12 col-lg-6 col-xl-4';

    /**
     * A partir de quantos dias uma proposta em aberto muda de cor. Bloco 4d.
     */
    private const PENDING_OLD_DAYS = 7;

    /**
     * Cor por PAPEL, indexada pela posicao na hierarquia - nunca pela sigla.
     *
     * Prender cor a 'dio'/'dgo'/'cto' contrariaria a decisao fechada no 4a-1
     * (papeis vivem num registro de configuracao, nao no codigo): papel novo
     * entraria sem cor e ninguem lembraria por que. Indexado por posicao, o
     * quarto papel ja nasce com a quarta cor.
     *
     * Cadeia vazia = sem cor, herda o tema. E' o meio da hierarquia: com as
     * pontas coloridas e o miolo neutro, DIO e CTO se distinguem a relance sem
     * a linha inteira virar arco-iris.
     *
     * O azul da primeira posicao e' o mesmo `#206bc4` que o tema usa em
     * `bg-blue-lt` - o da pastilha do icone do proprio cartao. A terceira e' um
     * azul mais claro: o pedido foi "CTO azul tambem", e duas pontas no MESMO
     * azul deixariam de informar qual e' qual.
     *
     * @var array<int, string>
     */
    private const ROLE_COLORS = [
        0 => '#206BC4',
        1 => '',
        2 => '#0EA5E9',
        3 => '#7C4DBE',
    ];

    /**
     * Acima de quantos papeis a tabela colapsa as colunas por papel numa
     * coluna "Equip." unica.
     */
    private const MAX_ROLE_COLUMNS = 4;

    /**
     * Cor do papel na posicao dada, ou cadeia vazia (herda o tema).
     *
     * @param int $index
     * @return string
     */
    private static function roleColor(int $index): string
    {
        return self::ROLE_COLORS[$index] ?? '';
    }

    /**
     * Mesma cor em fundo suave, para a celula do papel.
     *
     * Converte o hexadecimal em rgba em vez de manter uma segunda constante:
     * duas listas de cor divergem no dia em que alguem mexe numa so.
     *
     * @param string $hex
     * @param float  $alpha
     * @return string
     */
    private static function softColor(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return 'transparent';
        }

        return sprintf(
            'rgba(%d,%d,%d,%.3f)',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
            $alpha
        );
    }

    // -----------------------------------------------------------------
    // Filtro de papel
    // -----------------------------------------------------------------

    /**
     * Papel escolhido no filtro, lido da query string.
     *
     * Fica AQUI, e nao no MapController, para o bloco 4a-2 nao precisar tocar
     * num arquivo de 2000 linhas so para transportar um parametro. Quando o
     * 4a-3 puser o seletor na barra superior, ele apenas emite `role=` na URL
     * e este metodo ja o obedece - ou passa o papel explicitamente para
     * display(), que tem precedencia.
     *
     * Papel desconhecido vira null (= "Todos"), nunca lista vazia: URL colada a
     * mao com role=xpto tem que mostrar tudo, nao uma tela em branco.
     *
     * @return string|null
     */
    public static function currentRole(): ?string
    {
        $raw = $_GET['role'] ?? '';

        // ?role[]=x chega como array: o cast direto para string emitiria
        // "Array to string conversion" no log a cada carga da pagina, e o log
        // do GLPI e' onde se procura defeito de verdade. Nao e' so' higiene.
        if (!is_string($raw)) {
            return null;
        }

        $role = trim($raw);

        if ($role === '' || !Setting::isRole($role)) {
            return null;
        }

        return $role;
    }

    /**
     * Substantivo do que esta sendo contado, ja concordando com o filtro.
     *
     * Sem filtro: "elemento(s)". Com filtro: a sigla do papel. As siglas nao
     * passam por __() (sao iguais em qualquer idioma, decisao do 4a-1), mas o
     * plural do portugues e' aplicado aqui: "2 DGOs".
     *
     * @param string|null $role
     * @param int         $n
     * @return string
     */
    private static function roleNoun(?string $role, int $n): string
    {
        if ($role !== null) {
            return Setting::getRoleLabel($role) . ($n === 1 ? '' : 's');
        }

        return $n === 1
            ? __('elemento', 'dgoplus')
            : __('elementos', 'dgoplus');
    }

    // -----------------------------------------------------------------
    // Coleta
    // -----------------------------------------------------------------

    /**
     * Coleta tudo que a tela usa, numa passada.
     *
     * @param string|null $role papel do filtro; null = todos
     * @return array{
     *   role:string|null, roles:string[],
     *   total_items:int, trash_items:int, unmapped:int,
     *   by_role:array<string,int>, nodoc_by_role:array<string,int>,
     *   capacity:int, documented:int, free:int, occupancy:float,
     *   items_sem_doc:int, ports_trash:int,
     *   by_location:array<int,array{name:string,items:int,roles:array<string,int>,documented:int,capacity:int}>,
     *   top_items:array<int,array{id:int,name:string,role:string|null,locations_id:int,documented:int,capacity:int,pct:float}>,
     *   recent:array<int,array{position:string,edit_key:string,item_id:int,locations_id:int,item_name:string,code:string,date_mod:string}>
     * }
     */
    public static function collect(?string $role = null): array
    {
        if ($role !== null && !Setting::isRole($role)) {
            $role = null;
        }

        $roles = Setting::getRoles();

        // Mapa Tipo -> papel, montado uma vez (ver cabecalho da classe).
        $role_of_type = [];
        foreach ($roles as $r) {
            foreach (Setting::getTypesForRole($r) as $type_id) {
                $role_of_type[(int) $type_id] = $r;
            }
        }

        // --- Elementos (com restricao de entidade, igual ao mapa) ---
        $model    = new PassiveDCEquipment();
        $criteria = getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true);

        // Bloco 3l: o panorama tem que contar o MESMO conjunto que a tela
        // mostra. Sem este filtro o dashboard anunciaria elementos que o usuario
        // nao acha em lugar nenhum - e um numero que nao fecha com a tela custa
        // mais confianca do que um numero menor.
        //
        // Bloco 4a-2: com papel escolhido, o recorte e' roleCriteria(), que
        // devolve criterio IMPOSSIVEL para papel sem Tipo mapeado. Filtrar por
        // CTO sem CTO configurada tem de dar zero, nunca a base inteira.
        $scope = $role !== null
            ? Setting::roleCriteria($role)
            : Setting::typesCriteria();

        $items       = $model->find(['is_deleted' => 0] + $scope + $criteria);
        $trash_items = count($model->find(['is_deleted' => 1] + $scope + $criteria));

        $type_field = Setting::getTypeField();

        $item_ids      = [];
        $item_names    = [];
        $item_location = [];
        $item_role     = [];
        foreach ($items as $row) {
            $id                 = (int) $row['id'];
            $name               = (string) ($row['name'] ?? '');
            $item_ids[]         = $id;
            $item_names[$id]    = $name !== '' ? $name : ('#' . $id);
            $item_location[$id] = (int) ($row['locations_id'] ?? 0);
            $item_role[$id]     = $role_of_type[(int) ($row[$type_field] ?? 0)] ?? null;
        }

        // --- Elementos que ficaram fora de TODOS os papeis ---
        // Mesmo proposito do countFilteredOut() do mapa (licao 16): ativo que
        // some sem explicacao parece dado perdido. Aqui a conta e' sempre
        // global, independente do filtro de papel - por isso o comparativo e'
        // typesCriteria(), nunca $scope.
        $unmapped = 0;
        if (Setting::isTypeFilterEnabled()) {
            $total_any = count($model->find(['is_deleted' => 0] + $criteria));
            $total_map = count($model->find(['is_deleted' => 0] + Setting::typesCriteria() + $criteria));
            $unmapped  = max(0, $total_any - $total_map);
        }

        // --- Layouts (capacidade por elemento) ---
        // $widths sai do MESMO laco (bloco 3r): o rotulo continuo precisa da
        // largura por elemento, e uma segunda consulta aqui seria desperdicio -
        // as linhas ja estao em maos.
        $layouts = [];
        $widths  = array_fill_keys($item_ids, Panel::DEFAULT_FIBERS);
        if ($item_ids !== []) {
            $panel_model = new Panel();
            $panels      = $panel_model->find([
                'itemtype' => PassiveDCEquipment::class,
                'items_id' => $item_ids,
            ]);
            foreach ($panels as $row) {
                $fibers = Panel::sanitizeFibers((int) $row['fibers_per_tube']);
                $layouts[(int) $row['items_id']] =
                    Panel::sanitizeTubes((int) $row['tubes']) * $fibers;
                $widths[(int) $row['items_id']] = $fibers;
            }
        }
        $default_capacity = Panel::DEFAULT_TUBES * Panel::DEFAULT_FIBERS;

        // --- Portas ---
        $port_model  = new Port();
        $ports       = [];
        $ports_trash = 0;
        if ($item_ids !== []) {
            // Bloco 4d, parte C: gridCriteria() nas DUAS. Sem ele o painel
            // contava entrada como porta documentada, enquanto o mapa (que
            // sempre filtrou) contava so' a grade - duas telas com numeros
            // diferentes para o mesmo elemento.
            //
            // Passou em branco no 4b-1 porque naquele dia nao existia UMA
            // entrada no banco: filtro sem nada para filtrar nao falha. O 4c
            // criou a primeira e o defeito nasceu junto, inflando o numerador
            // de uma conta cujo denominador continua sendo so' a grade.
            // E' a licao 112 no lugar mais visivel do produto.
            $ports = $port_model->find([
                'itemtype'   => PassiveDCEquipment::class,
                'items_id'   => $item_ids,
                'is_deleted' => 0,
            ] + Port::gridCriteria());
            $ports_trash = count($port_model->find([
                'itemtype'   => PassiveDCEquipment::class,
                'items_id'   => $item_ids,
                'is_deleted' => 1,
            ] + Port::gridCriteria()));
        }

        // --- Agregacao por elemento ---
        $doc_by_item = array_fill_keys($item_ids, 0);
        foreach ($ports as $row) {
            $doc_by_item[(int) $row['items_id']]++;
        }

        $by_role       = array_fill_keys($roles, 0);
        $nodoc_by_role = array_fill_keys($roles, 0);

        $capacity   = 0;
        $documented = 0;
        $sem_doc    = 0;
        $per_item   = [];
        foreach ($item_ids as $id) {
            $cap = $layouts[$id] ?? $default_capacity;
            $doc = $doc_by_item[$id] ?? 0;
            $r   = $item_role[$id] ?? null;

            $capacity   += $cap;
            $documented += $doc;

            if ($r !== null && isset($by_role[$r])) {
                $by_role[$r]++;
            }

            if ($doc === 0) {
                $sem_doc++;
                if ($r !== null && isset($nodoc_by_role[$r])) {
                    $nodoc_by_role[$r]++;
                }
            }

            $per_item[$id] = [
                'id'           => $id,
                'name'         => $item_names[$id],
                'role'         => $r,
                'locations_id' => $item_location[$id] ?? 0,
                'documented'   => $doc,
                'capacity'     => $cap,
                'pct'          => $cap > 0 ? round($doc * 100 / $cap, 1) : 0.0,
            ];
        }

        // --- Agregacao por localizacao ---
        $by_location = [];
        foreach ($item_ids as $id) {
            $loc = $item_location[$id] ?? 0;
            if (!isset($by_location[$loc])) {
                $by_location[$loc] = [
                    'name'       => self::locationLabel($loc),
                    'items'      => 0,
                    'roles'      => array_fill_keys($roles, 0),
                    'documented' => 0,
                    'capacity'   => 0,
                ];
            }

            $r = $item_role[$id] ?? null;

            $by_location[$loc]['items']++;
            if ($r !== null && isset($by_location[$loc]['roles'][$r])) {
                $by_location[$loc]['roles'][$r]++;
            }
            $by_location[$loc]['documented'] += $doc_by_item[$id] ?? 0;
            $by_location[$loc]['capacity']   += $layouts[$id] ?? $default_capacity;
        }
        uasort($by_location, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        // --- Top 5 mais ocupados (so os que tem algo documentado) ---
        $top = array_filter($per_item, fn($d) => $d['documented'] > 0);
        usort($top, fn($a, $b) => $b['pct'] <=> $a['pct'] ?: $b['documented'] <=> $a['documented']);
        $top = array_slice($top, 0, 5);

        // --- Atividade recente (5 ultimas portas alteradas) ---
        usort($ports, fn($a, $b) => strcmp((string) ($b['date_mod'] ?? ''), (string) ($a['date_mod'] ?? '')));
        $recent = [];
        foreach (array_slice($ports, 0, 5) as $row) {
            $item_id  = (int) $row['items_id'];
            $recent[] = [
                'position'     => Port::formatPosition(
                    (int) $row['tube_num'],
                    (int) $row['fiber_num'],
                    $widths[$item_id] ?? Panel::DEFAULT_FIBERS
                ),
                'edit_key'     => ((int) $row['tube_num']) . '-' . ((int) $row['fiber_num']),
                'item_id'      => $item_id,
                'locations_id' => $item_location[$item_id] ?? 0,
                'item_name'    => $item_names[$item_id] ?? ('#' . $item_id),
                'code'         => (string) ($row['code'] ?? ''),
                'date_mod'     => (string) ($row['date_mod'] ?? ''),
            ];
        }

        // --- Pendencias de vinculo (bloco 4d) ---
        // A consulta vive no Link, ponto unico das duas telas. Aqui so' se
        // decide quantas cabem no cartao; o total sai da lista inteira, para o
        // contador do cabecalho nunca dizer 5 quando ha 12.
        $pending       = Link::pendingRows($role);
        $pending_total = count($pending);

        return [
            'role'          => $role,
            'roles'         => $roles,
            'pending'       => array_slice($pending, 0, self::PENDING_ON_CARD),
            'pending_total' => $pending_total,
            'total_items'   => count($item_ids),
            'trash_items'   => $trash_items,
            'unmapped'      => $unmapped,
            'by_role'       => $by_role,
            'nodoc_by_role' => $nodoc_by_role,
            'capacity'      => $capacity,
            'documented'    => $documented,
            'free'          => max(0, $capacity - $documented),
            'occupancy'     => $capacity > 0 ? round($documented * 100 / $capacity, 1) : 0.0,
            'items_sem_doc' => $sem_doc,
            'ports_trash'   => $ports_trash,
            'by_location'   => $by_location,
            'top_items'     => $top,
            'recent'        => $recent,
        ];
    }

    /**
     * @param int $locations_id
     * @return string
     */
    private static function locationLabel(int $locations_id): string
    {
        if ($locations_id <= 0) {
            return __('Sem localização', 'dgoplus');
        }

        $name = Dropdown::getDropdownName('glpi_locations', $locations_id);

        return ($name !== '' && $name !== '&nbsp;') ? $name : ('#' . $locations_id);
    }

    // -----------------------------------------------------------------
    // Renderizacao
    // -----------------------------------------------------------------

    /**
     * Renderiza o dashboard. $url_builder recebe um array de parametros e
     * devolve a URL da pagina do mapa (injetado pelo MapController para nao
     * duplicar a logica de root_doc).
     *
     * Layout em 3 faixas: cartoes / tabela por localizacao (largura total, a
     * unica que cresce com o piloto) / top 5 + atividade lado a lado.
     *
     * O segundo parametro e' OPCIONAL de proposito: o MapController de hoje
     * chama display($url_builder) e continua valendo, sem uma linha de mudanca
     * naquele arquivo. Quando o 4a-3 tiver o seletor na barra, passa o papel
     * aqui; ate la, currentRole() le da query string.
     *
     * @param callable(array):string $url_builder
     * @param string|null            $role
     * @return void
     */
    public static function display(callable $url_builder, ?string $role = null): void
    {
        $d = self::collect($role ?? self::currentRole());

        self::displayCards($d);
        self::displayByLocation($d, $url_builder);
        self::displayBottomRow($d, $url_builder);
    }

    /**
     * Faixa 1: os quatro cartoes de resumo.
     *
     * Bloco 4a-2: os cartoes 1 e 4 sao os que se abrem por papel, entao
     * crescem (col-xl-4) e ficam LADO A LADO - vem primeiro os dois que
     * carregam a leitura por papel, depois os dois numeros unicos, que
     * encolhem (col-xl-2). A ordem visual deixou de ser a ordem antiga.
     *
     * @param array $d
     * @return void
     */
    private static function displayCards(array $d): void
    {
        $role = $d['role'];

        echo "<div class='row row-cards g-3 mb-3'>";

        // --- 1. Elementos cadastrados (com abertura por papel) ---
        $subtitle = $d['trash_items'] > 0
            ? sprintf(__('%d na lixeira', 'dgoplus'), $d['trash_items'])
            : __('nenhum na lixeira', 'dgoplus');

        self::card(
            'col-12 col-lg-6 col-xl-4',
            sprintf(__('%s cadastrados', 'dgoplus'), self::ucfirstLabel(self::roleNoun($role, 2))),
            (string) $d['total_items'],
            $subtitle,
            'ti ti-server',
            'bg-blue-lt',
            [
                'aside'  => self::roleBreakdown($d['roles'], $d['by_role'], $role),
                'footer' => self::unmappedNote($d),
            ]
        );

        // --- 4. Sem documentacao (com abertura por papel) ---
        // Aqui o zero e' a boa noticia, entao os numeros diferentes de zero vao
        // na cor de alerta: num cartao de pendencia, o que salta e' o que falta
        // fazer, nao o que ja esta feito.
        self::card(
            'col-12 col-lg-6 col-xl-4',
            sprintf(__('%s sem documentação', 'dgoplus'), self::ucfirstLabel(self::roleNoun($role, 2))),
            (string) $d['items_sem_doc'],
            $d['items_sem_doc'] > 0
                ? __('sem nenhuma porta registrada ainda', 'dgoplus')
                : __('todos já têm alguma porta', 'dgoplus'),
            $d['items_sem_doc'] > 0 ? 'ti ti-alert-triangle' : 'ti ti-circle-check',
            $d['items_sem_doc'] > 0 ? 'bg-yellow-lt' : 'bg-green-lt',
            [
                'aside' => self::roleBreakdown($d['roles'], $d['nodoc_by_role'], $role, self::WARN),
            ]
        );

        // --- 2. Ocupacao geral (numero unico, encolhe) ---
        self::card(
            'col-6 col-lg-3 col-xl-2',
            __('Ocupação geral', 'dgoplus'),
            self::pctLabel((float) $d['occupancy']),
            sprintf(__('%d de %d portas', 'dgoplus'), $d['documented'], $d['capacity']),
            'ti ti-chart-donut',
            '',
            [
                'bar_pct'     => (float) $d['occupancy'],
                'value_color' => self::ACCENT,
                'compact'     => true,
            ]
        );

        // --- 3. Portas livres (numero unico, encolhe) ---
        self::card(
            'col-6 col-lg-3 col-xl-2',
            __('Portas livres', 'dgoplus'),
            (string) $d['free'],
            $d['ports_trash'] > 0
                ? sprintf(__('%d na lixeira', 'dgoplus'), $d['ports_trash'])
                : __('nenhuma na lixeira', 'dgoplus'),
            'ti ti-plug',
            'bg-green-lt',
            ['compact' => true]
        );

        echo "</div>";
    }

    /**
     * Abertura por papel, como bloco de DESTAQUE ao lado do numero grande.
     *
     * A primeira versao do 4a-2 punha isso como pastilhas pequenas no rodape
     * do cartao, e ficou errado por dois motivos: sobrava metade da largura
     * vazia, e a informacao que da' NOME ao cartao ("por papel") aparecia menor
     * do que o subtitulo. Aqui cada papel e' uma coluna propria, com sigla
     * acima e numero em corpo grande, separadas por filete - o mesmo peso
     * visual do total, que e' o que a leitura pede.
     *
     * Com filtro de papel ativo devolve cadeia vazia: repetir "DIO 1" ao lado
     * de um cartao que ja se chama "DIOs cadastrados" e' ruido.
     *
     * @param string[]           $roles
     * @param array<string,int>  $counts
     * @param string|null        $active_role
     * @param string             $accent_zero cor do numero quando e' zero
     * @return string
     */
    private static function roleBreakdown(
        array $roles,
        array $counts,
        ?string $active_role,
        string $accent_zero = ''
    ): string {
        if ($active_role !== null || $roles === []) {
            return '';
        }

        // flex:2 contra flex:1 do total: o bloco de papeis fica com o DOBRO da
        // largura, que e' o espaco que sobrava vazio. Cada papel e' flex:1 1 0
        // dentro dele, entao os tres se distribuem por igual em vez de se
        // amontoarem numa borda - e continuam distribuidos com quatro papeis.
        //
        // O fundo cinza do conjunto sai quando ha cor por papel: duas camadas
        // de fundo, uma dentro da outra, embarralhariam as celulas.
        $out = "<div class='dgoplus-rolebar d-flex flex-wrap align-items-end'"
            . " style='flex:2 1 210px;gap:6px'>";

        $index = 0;
        foreach ($roles as $role) {
            $n     = (int) ($counts[$role] ?? 0);
            $zero  = $n === 0;
            $color = self::roleColor($index);
            $index++;

            $cell = 'flex:1 1 0;min-width:56px;padding:5px 6px;text-align:center;border-radius:7px';
            $cell .= $color !== ''
                ? ';background:' . self::softColor($color, $zero ? 0.05 : 0.11)
                : ';background:rgba(128,128,128,' . ($zero ? '0.035' : '0.06') . ')';

            $label_style = 'font-size:0.7rem;font-weight:600;letter-spacing:0.09em';
            $label_class = 'text-muted';
            if ($color !== '' && !$zero) {
                $label_style .= ';color:' . $color;
                $label_class = '';
            }

            // O numero do cartao de pendencia fica na cor de alerta, e nao na
            // do papel: ali a pergunta e' "quanto falta", nao "qual papel". A
            // identidade do papel continua legivel pela sigla e pelo fundo.
            $num_style = 'font-size:1.75rem;line-height:1.15;font-weight:600';
            if ($zero) {
                $num_style .= ';opacity:0.32';
            } elseif ($accent_zero !== '') {
                $num_style .= ';color:' . $accent_zero;
            } elseif ($color !== '') {
                $num_style .= ';color:' . $color;
            }

            $out .= "<div style='" . $cell . "'>";
            $out .= "<div class='" . $label_class . "' style='" . $label_style . "'>"
                . htmlescape(Setting::getRoleLabel($role)) . "</div>";
            $out .= "<div style='" . $num_style . "'>" . $n . "</div>";
            $out .= "</div>";
        }

        $out .= "</div>";

        return $out;
    }

    /**
     * Nota discreta sobre elementos fora de todos os papeis.
     *
     * Mesmo padrao do aviso da barra do mapa (countFilteredOut), em versao
     * curta: aqui o numero e' global e nao ha o que clicar. Some quando zero.
     *
     * @param array $d
     * @return string
     */
    private static function unmappedNote(array $d): string
    {
        $n = (int) $d['unmapped'];

        if ($n <= 0) {
            return '';
        }

        return "<div class='small text-muted mt-1'>"
            . "<i class='ti ti-filter me-1'></i>"
            . htmlescape(sprintf(
                _n(
                    '%d elemento fora dos papéis configurados',
                    '%d elementos fora dos papéis configurados',
                    $n,
                    'dgoplus'
                ),
                $n
            ))
            . "</div>";
    }

    /**
     * Faixa 2: tabela por localizacao, em largura total.
     *
     * Largura total de proposito: e' a tabela mais larga e a unica que cresce
     * conforme o piloto ganha localizacoes. Espremida em 7/12 da tela, o
     * table-responsive cortava a coluna de ocupacao na borda do card.
     *
     * @param array                  $d
     * @param callable(array):string $url_builder
     * @return void
     */
    private static function displayByLocation(array $d, callable $url_builder): void
    {
        $columns = self::roleColumns($d);

        echo "<div class='row row-cards g-3 mb-3'>";
        echo "<div class='col-12'>";
        echo "<div class='card'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between'>";
        echo "<h3 class='card-title mb-0'><i class='ti ti-map-pin me-1'></i>"
            . htmlescape(sprintf(
                __('%s por localização', 'dgoplus'),
                self::ucfirstLabel(self::roleNoun($d['role'], 2))
            ))
            . "</h3>";
        if ($d['by_location'] !== []) {
            echo "<span class='badge bg-blue-lt'>"
                . sprintf(
                    _n('%d localização', '%d localizações', count($d['by_location']), 'dgoplus'),
                    count($d['by_location'])
                )
                . "</span>";
        }
        echo "</div>";

        if ($d['by_location'] === []) {
            echo "<div class='card-body text-muted'>"
                . __('Nenhum elemento cadastrado ainda. Escolha uma localização acima para começar.', 'dgoplus')
                . "</div>";
            echo "</div></div></div>";
            return;
        }

        $width = count($columns) > 2 ? '26%' : '34%';

        echo "<div class='table-responsive'>";
        echo "<table class='table table-vcenter card-table mb-0'>";
        echo "<thead><tr>";
        echo "<th>" . __('Localização', 'dgoplus') . "</th>";
        foreach ($columns as $col) {
            echo "<th class='text-end'>" . htmlescape($col['label']) . "</th>";
        }
        echo "<th class='text-end'>" . __('Documentadas', 'dgoplus') . "</th>";
        echo "<th class='text-end'>" . __('Livres', 'dgoplus') . "</th>";
        echo "<th style='width:" . $width . "'>" . __('Ocupação', 'dgoplus') . "</th>";
        echo "</tr></thead><tbody>";

        foreach ($d['by_location'] as $loc_id => $row) {
            $free = max(0, $row['capacity'] - $row['documented']);
            $pct  = $row['capacity'] > 0 ? round($row['documented'] * 100 / $row['capacity'], 1) : 0.0;
            $url  = $url_builder(['location' => $loc_id]);

            echo "<tr>";
            echo "<td><a href='" . htmlescape($url) . "' class='text-decoration-none'>"
                . "<i class='ti ti-map-pin me-1 text-muted'></i>" . htmlescape($row['name']) . "</a></td>";
            foreach ($columns as $col) {
                echo "<td class='text-end'>" . self::columnValue($col, $row) . "</td>";
            }
            echo "<td class='text-end'>" . (int) $row['documented'] . "</td>";
            echo "<td class='text-end'>" . $free . "</td>";
            echo "<td>" . self::gauge((float) $pct) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";

        if (count($d['by_location']) > 1) {
            $totals = [
                'items' => (int) $d['total_items'],
                'roles' => $d['by_role'],
            ];

            echo "<tfoot><tr class='fw-bold'>";
            echo "<td>" . __('Total', 'dgoplus') . "</td>";
            foreach ($columns as $col) {
                echo "<td class='text-end'>" . self::columnValue($col, $totals) . "</td>";
            }
            echo "<td class='text-end'>" . (int) $d['documented'] . "</td>";
            echo "<td class='text-end'>" . (int) $d['free'] . "</td>";
            echo "<td>" . self::gauge((float) $d['occupancy']) . "</td>";
            echo "</tr></tfoot>";
        }

        echo "</table></div>";
        echo "</div></div></div>";
    }

    /**
     * Quais colunas de contagem a tabela mostra.
     *
     * Tres casos, nesta ordem de precedencia:
     *  - filtro de papel ativo  -> uma coluna, a do papel;
     *  - papeis demais          -> uma coluna "Equip." com o total (o colapso);
     *  - caso normal            -> uma coluna por papel.
     *
     * @param array $d
     * @return array<int,array{key:string,label:string,role:string|null}>
     */
    private static function roleColumns(array $d): array
    {
        $roles = $d['roles'];

        if ($d['role'] !== null) {
            return [[
                'key'   => 'role',
                'label' => Setting::getRoleLabel($d['role']),
                'role'  => $d['role'],
            ]];
        }

        if (count($roles) > self::MAX_ROLE_COLUMNS) {
            return [[
                'key'   => 'total',
                'label' => __('Equip.', 'dgoplus'),
                'role'  => null,
            ]];
        }

        $columns = [];
        foreach ($roles as $role) {
            $columns[] = [
                'key'   => 'role',
                'label' => Setting::getRoleLabel($role),
                'role'  => $role,
            ];
        }

        return $columns;
    }

    /**
     * @param array{key:string,label:string,role:string|null} $col
     * @param array                                           $row
     * @return int
     */
    private static function columnValue(array $col, array $row): int
    {
        if ($col['role'] === null) {
            return (int) ($row['items'] ?? 0);
        }

        return (int) ($row['roles'][$col['role']] ?? 0);
    }

    /**
     * Faixa 3: top 5 e atividade recente, cada um no seu cartao.
     *
     * @param array                  $d
     * @param callable(array):string $url_builder
     * @return void
     */
    private static function displayBottomRow(array $d, callable $url_builder): void
    {
        echo "<div class='row row-cards g-3'>";

        // --- Top 5 ---
        echo "<div class='" . self::BOTTOM_COL . "'>";
        echo "<div class='card h-100'>";
        echo "<div class='card-header'><h3 class='card-title mb-0'>"
            . "<i class='ti ti-flame me-1'></i>" . __('Equipamentos mais ocupados', 'dgoplus') . "</h3></div>";

        if ($d['top_items'] === []) {
            echo "<div class='card-body text-muted'>" . __('Nada documentado ainda.', 'dgoplus') . "</div>";
        } else {
            echo "<div class='table-responsive'><table class='table table-vcenter card-table mb-0'><tbody>";
            $rank = 0;
            foreach ($d['top_items'] as $t) {
                $rank++;
                $url = $url_builder(['location' => (int) $t['locations_id'], 'dgo' => (int) $t['id']]);
                echo "<tr>";
                echo "<td style='width:28px' class='text-muted'>" . $rank . "</td>";
                echo "<td>";
                echo "<a href='" . htmlescape($url) . "' class='text-decoration-none'>"
                    . htmlescape($t['name']) . "</a>";
                // A sigla so aparece sem filtro: com filtro ativo, toda linha
                // teria a mesma pastilha e ela deixaria de informar.
                if ($d['role'] === null && $t['role'] !== null) {
                    echo " <span class='badge bg-secondary-lt'>"
                        . htmlescape(Setting::getRoleLabel($t['role'])) . "</span>";
                }
                echo "</td>";
                echo "<td class='text-end text-nowrap text-muted'>"
                    . (int) $t['documented'] . "/" . (int) $t['capacity'] . "</td>";
                echo "<td style='width:34%'>" . self::gauge((float) $t['pct']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
        }
        echo "</div></div>";

        // --- Vinculos pendentes (bloco 4d) ---
        self::displayPendingCard($d, $url_builder);

        // --- Atividade recente ---
        echo "<div class='" . self::BOTTOM_COL . "'>";
        echo "<div class='card h-100'>";
        echo "<div class='card-header'><h3 class='card-title mb-0'>"
            . "<i class='ti ti-history me-1'></i>" . __('Atividade recente', 'dgoplus') . "</h3></div>";

        if ($d['recent'] === []) {
            echo "<div class='card-body text-muted'>" . __('Nenhuma alteração registrada.', 'dgoplus') . "</div>";
        } else {
            echo "<div class='table-responsive'><table class='table table-vcenter card-table mb-0'><tbody>";
            foreach ($d['recent'] as $r) {
                $url = $url_builder([
                    'location' => (int) $r['locations_id'],
                    'dgo'      => (int) $r['item_id'],
                    'edit'     => $r['edit_key'],
                ]) . '#dgoplus-panel';

                echo "<tr>";
                echo "<td class='text-nowrap' style='width:70px'>"
                    . "<a href='" . htmlescape($url) . "' class='badge bg-blue-lt text-decoration-none'>"
                    . htmlescape($r['position']) . "</a></td>";
                echo "<td>" . htmlescape($r['item_name'])
                    . ($r['code'] !== '' ? " <span class='text-muted'>(" . htmlescape($r['code']) . ")</span>" : '')
                    . "</td>";
                echo "<td class='text-end text-muted text-nowrap'>" . htmlescape(self::dateLabel($r['date_mod'])) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
        }
        echo "</div></div>";

        echo "</div>";
    }

    /**
     * O cartao de vinculos pendentes. Bloco 4d.
     *
     * Cada linha leva ao card da entrada do elemento de DESTINO, com o slot ja
     * aberto: e' la' que confirmar e recusar vivem desde o 4c, com as guardas
     * de direito que ja foram validadas na tela. Reimplementar os botoes aqui
     * criaria um segundo lugar para a mesma regra de negocio - e' o defeito que
     * o ponto unico do Port e do Link existem para evitar.
     *
     * A linha nao distingue quem propos: autoconfirmacao e' permitida e fica
     * registrada (decisao do 4c), entao a fila nao tem por que discriminar.
     *
     * @param array                  $d
     * @param callable(array):string $url_builder
     * @return void
     */
    private static function displayPendingCard(array $d, callable $url_builder): void
    {
        $rows  = $d['pending'] ?? [];
        $total = (int) ($d['pending_total'] ?? 0);

        echo "<div class='" . self::BOTTOM_COL . "'>";
        echo "<div class='card h-100'>";

        echo "<div class='card-header d-flex align-items-center'>";
        echo "<h3 class='card-title mb-0'>"
            . "<i class='ti ti-git-pull-request me-1'></i>" . __('Vínculos pendentes', 'dgoplus') . "</h3>";
        if ($total > 0) {
            echo "<span class='badge bg-orange-lt ms-auto'>" . $total . "</span>";
        }
        echo "</div>";

        if ($rows === []) {
            echo "<div class='card-body text-muted'>" . __('Nenhuma proposta em aberto.', 'dgoplus') . "</div>";
            echo "</div></div>";
            return;
        }

        echo "<div class='table-responsive'><table class='table table-vcenter card-table mb-0'><tbody>";
        foreach ($rows as $r) {
            $url = $url_builder([
                'location' => (int) $r['dst_locations_id'],
                'dgo'      => (int) $r['dst_items_id'],
                'entry'    => (int) $r['dst_slot'],
            ]);

            echo "<tr>";
            echo "<td class='text-nowrap' style='width:70px'>"
                . "<a href='" . htmlescape($url) . "' class='badge bg-blue-lt text-decoration-none'>"
                . htmlescape($r['src_label']) . "</a></td>";

            // Cada nome em text-nowrap: sem isso a quebra cai DENTRO do nome
            // do elemento ("Teste / drop 001") em vez de cair na seta, e a
            // linha fica ilegivel no cartao estreito. Visto na renderizacao.
            echo "<td>";
            echo "<span class='text-nowrap'>" . htmlescape($r['src_item']) . "</span>"
                . " <i class='ti ti-arrow-right text-muted'></i> "
                . "<span class='text-nowrap'>" . htmlescape($r['dst_item'])
                . " <span class='text-muted'>" . htmlescape($r['dst_label']) . "</span></span>";
            echo "<div class='text-muted small'>"
                . htmlescape(sprintf(__('Proposto por %s', 'dgoplus'), $r['proposer']))
                . "</div>";
            echo "</td>";

            echo "<td class='text-end text-nowrap " . self::ageClass((int) $r['age_days']) . "'>"
                . htmlescape(self::ageLabel((int) $r['age_days'])) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";

        // Bloco 4g: o rodape aparece com QUALQUER pendencia, nao so' quando
        // sobra fila fora do cartao.
        //
        // Ate' o 4f a condicao era $total > count($rows), e com isso a pagina
        // de pendencias ficava inalcancavel na faixa de 1 a 5 - que e'
        // justamente a faixa do dia a dia. O cartao e' o unico caminho para
        // ela, entao "sem rodape" significava "sem porta".
        //
        // O texto continua respeitando a razao da trava original: "Ver todas
        // as 3" com as tres ja na tela seria mentira. Quando a fila cabe
        // inteira no cartao, o rodape anuncia o que a pagina ACRESCENTA - os
        // filtros por papel, elemento e idade - em vez de prometer mais linhas.
        if ($total > 0) {
            $params = $d['role'] !== null ? ['role' => (string) $d['role']] : [];
            $label  = $total > count($rows)
                ? sprintf(__('Ver todas as %d', 'dgoplus'), $total)
                : __('Abrir a fila completa', 'dgoplus');

            echo "<div class='card-footer text-center py-2'>";
            echo "<a href='" . htmlescape(Pending::getPageUrl($params)) . "'>"
                . htmlescape($label) . "</a>";
            echo "</div>";
        }

        echo "</div></div>";
    }

    /**
     * Idade em texto curto. Bloco 4d.
     *
     * @param int $days
     * @return string
     */
    public static function ageLabel(int $days): string
    {
        if ($days <= 0) {
            return __('hoje', 'dgoplus');
        }

        return sprintf(_n('%d dia', '%d dias', $days, 'dgoplus'), $days);
    }

    /**
     * Classe de cor da idade. Bloco 4d.
     *
     * O limite existe para separar "acabou de chegar" de "foi esquecido"; sem
     * ele a coluna de idade seria um numero cinza que ninguem le.
     *
     * @param int $days
     * @return string
     */
    public static function ageClass(int $days): string
    {
        return $days >= self::PENDING_OLD_DAYS ? 'text-orange' : 'text-muted';
    }

    /**
     * Um cartao de resumo. A pastilha do icone usa classe nativa do tema
     * ($pill_class) ou, quando vazia, o destaque teal por cor explicita.
     *
     * As opcionais vao num array em vez de virar o oitavo, nono e decimo
     * parametro posicional: ja eram quatro, e cada bloco novo acrescentaria
     * mais um. Com array, quem le a chamada ve o NOME do que esta ligando.
     *
     * Chaves de $opts:
     *   bar_pct     float|null  barra fina sob o valor (ausente = sem barra)
     *   value_color string      cor explicita do valor
     *   aside       string      HTML ja escapado, A DIREITA do valor
     *   footer      string      HTML ja escapado, abaixo de tudo
     *   compact     bool        cartao estreito: numero e pastilha menores
     *
     * @param string $col_class classe de coluna (largura do cartao)
     * @param string $title
     * @param string $value
     * @param string $subtitle
     * @param string $icon
     * @param string $pill_class classe de fundo nativa; '' = destaque teal
     * @param array  $opts
     * @return void
     */
    private static function card(
        string $col_class,
        string $title,
        string $value,
        string $subtitle,
        string $icon,
        string $pill_class,
        array $opts = []
    ): void {
        $bar_pct     = $opts['bar_pct'] ?? null;
        $value_color = (string) ($opts['value_color'] ?? '');
        $aside       = (string) ($opts['aside'] ?? '');
        $footer      = (string) ($opts['footer'] ?? '');
        $compact     = (bool) ($opts['compact'] ?? false);

        $pill_size  = $compact ? 30 : 38;
        $pill_style = 'width:' . $pill_size . 'px;height:' . $pill_size . 'px;border-radius:10px;'
            . 'display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;'
            . 'font-size:' . ($compact ? 15 : 18) . 'px';

        if ($pill_class === '') {
            $pill_style .= ';background:' . self::ACCENT_SOFT . ';color:' . self::ACCENT;
        }

        echo "<div class='" . htmlescape($col_class) . "'>";
        echo "<div class='card h-100'><div class='card-body d-flex flex-column'>";

        echo "<div class='d-flex align-items-center gap-2 mb-2'>";
        echo "<span class='" . htmlescape($pill_class) . "' style='" . $pill_style . "'>"
            . "<i class='" . htmlescape($icon) . "'></i></span>";
        echo "<span class='text-muted" . ($compact ? ' small' : '') . "'>" . htmlescape($title) . "</span>";
        echo "</div>";

        // O bloco lateral e' o que ocupa a largura que sobrava. align-items-end
        // alinha a base do numero grande com a base dos numeros por papel;
        // flex-wrap devolve o bloco para baixo em tela estreita, em vez de
        // espremer as colunas ate' o numero quebrar.
        $has_aside = $aside !== '';
        echo "<div class='d-flex flex-wrap align-items-end justify-content-between gap-2 mt-auto'>";

        echo "<div" . ($has_aside ? " style='flex:1 1 auto;min-width:96px'" : '') . ">";
        $style = $value_color !== '' ? " style='color:" . htmlescape($value_color) . "'" : '';
        echo "<div class='" . ($compact ? 'h2' : 'h1') . " mb-1'" . $style . ">" . htmlescape($value) . "</div>";

        if ($bar_pct !== null) {
            echo self::bar((float) $bar_pct);
        }

        echo "<div class='text-muted small mt-1'>" . htmlescape($subtitle) . "</div>";
        echo "</div>";

        // Ja vem escapado de roleBreakdown()/unmappedNote(); nunca recebe texto
        // do usuario.
        if ($has_aside) {
            echo $aside;
        }

        echo "</div>";

        echo $footer;

        echo "</div></div>";
        echo "</div>";
    }

    /**
     * ucfirst ciente de acento e de sigla.
     *
     * "elementos" -> "Elementos", mas "DIOs" continua "DIOs". O ucfirst() cru
     * do PHP e' byte a byte; aqui nao ha acento na primeira letra hoje, mas o
     * rotulo passa por __() e um idioma futuro pode ter.
     *
     * @param string $label
     * @return string
     */
    private static function ucfirstLabel(string $label): string
    {
        if ($label === '') {
            return $label;
        }

        return mb_strtoupper(mb_substr($label, 0, 1)) . mb_substr($label, 1);
    }

    /**
     * Barra de ocupacao + rotulo em porcentagem, para celula de tabela.
     *
     * @param float $pct
     * @return string
     */
    private static function gauge(float $pct): string
    {
        return "<div class='d-flex align-items-center gap-2'>"
            . "<div class='flex-fill'>" . self::bar($pct) . "</div>"
            . "<span class='text-muted small text-nowrap' style='min-width:46px;text-align:right'>"
            . htmlescape(self::pctLabel($pct)) . "</span>"
            . "</div>";
    }

    /**
     * Barra fina. Largura minima visivel de 2% quando ha algo documentado,
     * senao 1.4% viraria uma barra invisivel (o numero fica do lado, entao
     * nao ha risco de o usuario ler o valor errado na barra).
     *
     * @param float $pct
     * @return string
     */
    private static function bar(float $pct): string
    {
        $clamped = max(0.0, min(100.0, $pct));
        $width   = $clamped > 0 ? max(2.0, $clamped) : 0.0;

        return "<div class='progress' style='height:6px'>"
            . "<div class='progress-bar' role='progressbar'"
            . " style='width:" . sprintf('%.1f', $width) . "%;background-color:" . self::barColor($clamped) . "'"
            . " aria-valuenow='" . sprintf('%.1f', $clamped) . "' aria-valuemin='0' aria-valuemax='100'></div>"
            . "</div>";
    }

    /**
     * Faixa de cor da ocupacao: livre (destaque), enchendo (ambar), quase
     * cheia (vermelho). Ocupacao alta e' o alerta - significa elemento sem
     * folga.
     *
     * @param float $pct
     * @return string
     */
    private static function barColor(float $pct): string
    {
        if ($pct <= 0) {
            return self::EMPTY_BAR;
        }
        if ($pct >= 80) {
            return self::FULL;
        }
        if ($pct >= 50) {
            return self::WARN;
        }

        return self::ACCENT;
    }

    /**
     * @param float $pct
     * @return string
     */
    private static function pctLabel(float $pct): string
    {
        return sprintf('%.1f', $pct) . '%';
    }

    /**
     * Data no formato configurado no GLPI; string vazia nao vira "hoje".
     *
     * @param string $datetime
     * @return string
     */
    private static function dateLabel(string $datetime): string
    {
        if (trim($datetime) === '' || $datetime === 'NULL') {
            return '—';
        }

        return (string) Html::convDateTime($datetime);
    }
}
