<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
use Document;
use Document_Item;
use Dropdown;
use Html;
use Location;
use PassiveDCEquipment;
use Session;

/**
 * Sem estado em propriedades: cada metodo le $_GET/$_POST e devolve/imprime.
 * Isso evita qualquer vazamento de estado entre requisicoes (o hook do plugin
 * roda uma vez por request, mas objetos estaticos poderiam sobreviver a
 * chamadas de teste/CLI, entao trato tudo como local).
 */
class MapController
{
    /** Destaque do projeto (teal do prototipo) */
    private const ACCENT = '#2FBFB0';

    /** Fundo da celula documentada */
    private const CELL_DOC_BG = 'rgba(47,191,176,0.13)';

    /** Borda da celula documentada */
    private const CELL_DOC_BORDER = 'rgba(47,191,176,0.45)';

    /** Fundo da celula livre - cinza em alfa, para nao quebrar no tema escuro */
    private const CELL_FREE_BG = 'rgba(128,128,128,0.07)';

    /** Borda da celula livre */
    private const CELL_FREE_BORDER = 'rgba(128,128,128,0.28)';

    /** Fundo da celula sem acoplador - vermelho do projeto em alfa (licao 23) */
    private const CELL_NC_BG = 'rgba(214,83,74,0.13)';

    /** Borda da celula sem acoplador */
    private const CELL_NC_BORDER = 'rgba(214,83,74,0.45)';

    /** Contorno de resultado de busca (mesmo ambar do prototipo) */
    private const MATCH_COLOR = '#E8B84B';

    /**
     * Padrao de 12 cores de fibra (ABNT/EIA), como no prototipo.
     *
     * Fica aqui, e nao em Port::getFiberColor(), de proposito: a tela nao
     * pode depender de um metodo cuja existencia eu nao consiga provar no
     * arquivo implantado.
     */
    private const FIBER_COLORS = [
        '#2563EB', '#EA7C1F', '#22A559', '#8B5E34', '#94A3B8', '#B8BEC4',
        '#DC3545', '#3A3A3A', '#F2C230', '#8B2FA0', '#EC6BAF', '#2FBFB0',
    ];

    /** Acima disso, as abas de DGO viram um seletor com busca */
    private const MAX_TABS = 8;

    /**
     * Ponto de entrada chamado por front/map.php.
     *
     * @return void
     */
    public static function processAndDisplay(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::handlePost();
        }

        $locations_id = (int) ($_GET['location'] ?? 0);
        $dgo_id       = (int) ($_GET['dgo'] ?? 0);
        $edit_key     = $_GET['edit'] ?? '';
        $search       = trim((string) ($_GET['q'] ?? ''));
        $floors_id    = (int) ($_GET['floor'] ?? 0);
        $entry_slot   = (int) ($_GET['entry'] ?? 0);

        // Piso que nao pertence a localizacao escolhida e' descartado: e' o que
        // faz "trocar de localizacao limpa o piso" valer inclusive para URL
        // colada a mao ou favorito antigo.
        if ($floors_id > 0 && !array_key_exists($floors_id, Floor::getForLocation($locations_id))) {
            $floors_id = 0;
        }

        self::displayLocationPicker($locations_id, $dgo_id, $search, $floors_id);

        if ($search !== '') {
            self::displaySearchResults($search);
        }

        if ($locations_id <= 0) {
            Dashboard::display(
                static fn(array $params): string => self::getPageUrl($params)
            );
            return;
        }

        $dgos = self::getDgosAtLocation($locations_id, $floors_id);

        self::displayDgoTabs($locations_id, $dgos, $dgo_id, $floors_id);

        if ($dgos === []) {
            self::displayEmptyState(
                $floors_id > 0
                    ? __('Nenhum elemento neste piso. Troque o piso, ou abra um elemento e atribua o piso na grade.', 'dgoplus')
                    : __('Nenhum elemento cadastrado nesta localização ainda.', 'dgoplus')
            );
            return;
        }

        if ($dgo_id <= 0 || !isset($dgos[$dgo_id])) {
            self::displayEmptyState(
                __('Selecione um elemento acima para abrir a grade de portas.', 'dgoplus')
            );
            return;
        }

        $dgo = $dgos[$dgo_id];

        self::displayGrid($dgo, $locations_id, $edit_key, $search, $floors_id, $entry_slot);
        self::displayDocumentsManager($dgo, $locations_id, $edit_key, $floors_id);
        self::displayEditPanel($dgo, $locations_id, $edit_key, $floors_id);
    }

    /**
     * URL publica desta pagina.
     *
     * NAO usar $_SERVER['PHP_SELF']: no GLPI 11 os arquivos de front/ sao
     * carregados por require() de dentro do front controller do Symfony
     * (Glpi\Controller\LegacyFileLoadController), entao PHP_SELF vale o
     * caminho do roteador, nao o do plugin - e todo form cai na home.
     * O padrao do core e' root_doc + /plugins/<chave>/front/<arquivo>.php
     * (ver Toolbox::getItemTypeSearchURL). Plugin::getWebDir() esta
     * deprecado no 11.0.6 e polui o log a cada chamada.
     *
     * @param array $params
     * @return string
     */
    private static function getPageUrl(array $params = []): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $url = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/front/map.php';

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * URL de action= dos formularios POST desta pagina. Bloco 4a-3.
     *
     * Em POST o navegador PRESERVA a query string do action=, entao carregar
     * o papel aqui e' o que faz Dashboard::currentRole() continuar enxergando
     * o filtro DURANTE o tratamento do POST - sem isso, salvar uma porta
     * derrubaria o filtro de papel no redirect (mesma classe de defeito que o
     * scope() ja resolve para localizacao e piso, que viajam como hidden).
     * O papel nao vira hidden porque quem o le e' currentRole(), que le
     * $_GET de proposito: array forjado em $_POST nunca chega nele.
     *
     * @return string
     */
    private static function getPostUrl(): string
    {
        $role = Dashboard::currentRole();

        return self::getPageUrl($role !== null ? ['role' => $role] : []);
    }

    /**
     * URL desta pagina, sem parametros, para quem monta formulario de fora.
     *
     * Bloco 3t: DgoIdentity precisa do action= do formulario de comentario.
     * Expor ESTE metodo, sem parametros, em vez de tornar getPageUrl() publico
     * mantem a montagem de query string do DGO+ num lugar so (licao 13).
     *
     * Bloco 4a-3: passa pelo getPostUrl() para o formulario de comentario
     * tambem nao derrubar o filtro de papel ao salvar.
     *
     * @return string
     */
    public static function getPublicPageUrl(): string
    {
        return self::getPostUrl();
    }

    /**
     * URL do card de uma entrada aberto. Bloco 4d.
     *
     * As telas de pendencia precisam levar ao lugar onde confirmar e recusar
     * vivem desde o 4c. Elas NAO montam a query string por conta propria: o
     * nome dos parametros ('location', 'dgo', 'entry') e' assunto deste
     * controlador, e tres literais espalhados por dois arquivos e' o comeco de
     * um link que quebra em silencio quando um deles mudar (licao 13).
     *
     * @param int $locations_id
     * @param int $items_id
     * @param int $slot entrada E<n>
     * @return string
     */
    public static function getEntryUrl(int $locations_id, int $items_id, int $slot): string
    {
        return self::getPageUrl([
            'location' => $locations_id,
            'dgo'      => $items_id,
            'entry'    => $slot,
        ]);
    }

    /**
     * URL da tela de configuracao do plugin.
     *
     * Mesmo lugar que o core aponta pelo hook CONFIG_PAGE
     * (Plugin.php:2850 monta {root_doc}/plugins/{chave}/{valor do hook}).
     *
     * @return string
     */
    private static function getConfigUrl(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/front/config.form.php';
    }

    /**
     * URL de um endpoint ajax/ do plugin.
     *
     * Mesmo padrao do getPageUrl (root_doc + caminho literal): PHP_SELF esta
     * morto no GLPI 11 (licao 12) e Plugin::getWebDir() esta deprecado no
     * 11.0.6. Arquivos em ajax/ sao servidos direto pelo roteador, sem passar
     * por public/ (RequestRouterTrait::getTargetFile, 11.0.6:87-97).
     *
     * @param string $file
     * @return string
     */
    private static function getAjaxUrl(string $file): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/ajax/' . $file;
    }

    /**
     * URL de um estatico do plugin.
     *
     * O arquivo mora em public/, mas o 'public/' NAO entra na URL: o roteador
     * do GLPI 11 ja procura dentro dele (RequestRouterTrait::getTargetFile,
     * 11.0.6:87-97). E' a mesma regra que o setup.php aplica ao dgoplus.js
     * (licao 49).
     *
     * @param string $file
     * @return string
     */
    private static function getAssetUrl(string $file): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/' . $file;
    }

    /**
     * Assinatura visual do plugin na barra superior.
     *
     * A marca e' o mesmo desenho do logo.png que o GLPI serve no card de
     * Plugins (LogoController, /Plugin/dgoplus/Logo), so que em SVG para nao
     * borrar em tela de alta densidade. O texto fica em HTML, e nao dentro do
     * SVG, para herdar a cor do tema (claro/escuro) sem uma segunda versao do
     * arquivo.
     *
     * @return void
     */
    private static function displayBrand(): void
    {
        echo "<div class='d-flex align-items-center gap-2 pe-3 me-1' style='border-right:1px solid rgba(128,128,128,0.25)'>";
        echo "<img src='" . htmlescape(self::getAssetUrl('dgoplus-mark.svg')) . "'"
            . " alt='' aria-hidden='true' width='45' height='45' style='display:block'>";
        echo "<span class='d-flex flex-column lh-1'>";
        echo "<span style='font-size:1.15rem;font-weight:600;letter-spacing:-0.02em'>"
            . "DGO<span style='color:" . self::ACCENT . "'>+</span></span>";
        echo "<span class='text-muted' style='font-size:0.68rem;margin-top:2px'>"
            . htmlescape(__('Mapa de portas ópticas', 'dgoplus')) . "</span>";
        echo "</span>";
        echo "</div>";
    }

    /**
     * Parametros de navegacao que precisam sobreviver a todo clique e a todo
     * POST. Sem isso, salvar uma porta ou abrir um anexo joga o usuario de
     * volta para "todos os pisos" e o filtro parece nao funcionar.
     *
     * @param int   $locations_id
     * @param int   $floors_id
     * @param array $extra
     * @return array
     */
    private static function scope(int $locations_id, int $floors_id, array $extra = []): array
    {
        $params = [];

        if ($locations_id > 0) {
            $params['location'] = $locations_id;
        }
        if ($floors_id > 0) {
            $params['floor'] = $floors_id;
        }

        // Bloco 4a-3: o filtro de papel sobrevive a navegacao pelo mesmo
        // motivo que localizacao e piso. Le do ponto unico ja testado
        // (Dashboard::currentRole) em vez de ganhar parametro: mexer na
        // assinatura tocaria os ~20 pontos de chamada para propagar um valor
        // que ja e' global por natureza - ele vem da URL da requisicao.
        $role = Dashboard::currentRole();
        if ($role !== null) {
            $params['role'] = $role;
        }

        return $params + $extra;
    }

    /**
     * URL da pagina do DGO+ ja posicionada num ativo especifico.
     *
     * Publico porque o bloco 3k precisa dela fora do controlador: o botao
     * "Abrir no mapa DGO+" da ficha do ativo (hook POST_ITEM_FORM, em
     * setup.php) tem que apontar para ca. Expor ESTE metodo em vez de tornar
     * getPageUrl() publico mantem a montagem de URL num lugar so (licao 13) -
     * de fora, ninguem monta query string do DGO+ na mao.
     *
     * Leva o piso junto para a tela abrir com o filtro coerente com o ativo.
     * Piso incoerente com a localizacao ja e' descartado no processAndDisplay,
     * entao nao ha como esta URL levar a um estado invalido.
     *
     * @param CommonDBTM $item
     * @return string
     */
    public static function getUrlForDgo(CommonDBTM $item): string
    {
        $locations_id = (int) ($item->fields['locations_id'] ?? 0);
        $floors_id    = Panel::getFloorForItem($item);

        return self::getPageUrl(self::scope($locations_id, $floors_id, [
            'dgo' => (int) $item->getID(),
        ]));
    }

    /**
     * @return void
     */
    private static function handlePost(): void
    {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_dgo':
                self::actionCreateDgo();
                break;
            case 'set_floor':
                self::actionSetFloor();
                break;
            case 'add_tube':
                self::actionAddTube();
                break;
            case 'remove_tube':
                self::actionRemoveTube();
                break;
            case 'add_column':
                self::actionAddColumn();
                break;
            case 'remove_column':
                self::actionRemoveColumn();
                break;
            case 'save_port':
                self::actionSavePort();
                break;
            case 'delete_port':
                self::actionDeletePort();
                break;
            case 'save_dgo_comment':
                self::actionSaveDgoComment();
                break;
            case 'save_entry_obs':
                self::actionSaveEntryObs();
                break;
            case 'propose_link':
                self::actionProposeLink();
                break;
            case 'confirm_link':
                self::actionConfirmLink();
                break;
            case 'refuse_link':
                self::actionRefuseLink();
                break;
            case 'dismantle_link':
                self::actionDismantleLink();
                break;
        }
    }

    /**
     * @return void
     */
    private static function actionCreateDgo(): void
    {
        // Bloco 5f-2b: so o direito do PLUGIN. Exigir 'datacenter' CREATE aqui
        // devolvia ao tecnico o menu "Dispositivos passivos" inteiro - e com
        // ele o poder de criar ativo de qualquer tipo fora do mapa - so para
        // poder acrescentar uma CTO na localizacao onde ele ja documenta
        // portas. A trava que importa continua abaixo: o elemento nasce em
        // Session::getActiveEntity(), nunca em outra entidade.
        Session::checkRight(Port::$rightname, CREATE);

        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $name         = trim($_POST['name'] ?? '');

        // Bloco 4a-3: papel obrigatorio, validado contra o registro. Valor
        // forjado como array ou papel inexistente recebe o mesmo tratamento
        // do vazio - a recusa e' dura, como a do nome, sem "tem certeza?".
        $raw_role = $_POST['role'] ?? '';
        $role     = is_string($raw_role) ? trim($raw_role) : '';

        if ($locations_id <= 0 || $name === '') {
            Session::addMessageAfterRedirect(__('Preencha o nome do novo elemento.', 'dgoplus'), false, ERROR);
            self::redirectTo(self::scope($locations_id, $floors_id));
            return;
        }

        if (!Setting::isRole($role)) {
            Session::addMessageAfterRedirect(
                sprintf(__('Escolha o papel do novo elemento (%s).', 'dgoplus'), Setting::getRoleListLabel()),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $floors_id));
            return;
        }

        $input = [
            'name'         => $name,
            'locations_id' => $locations_id,
            'entities_id'  => Session::getActiveEntity(),
        ];

        // Bloco 3l, e este e' o ponto que nao pode faltar: com o filtro de Tipo
        // ligado, um elemento criado sem tipo seria descartado pelo proprio
        // filtro e desapareceria no instante em que fosse criado, sem uma linha
        // de erro em log nenhum (licao 14). Bloco 4a-3: o Tipo agora vem do
        // papel escolhido; papel sem Tipo mapeado, com o filtro ligado, RECUSA
        // em vez de criar um elemento que sumiria. Com o filtro desligado
        // (nenhum Tipo mapeado em papel nenhum) nao ha o que gravar e nada
        // some - comportamento identico ao anterior ao 3l.
        $type_id = Setting::getTypeForNewItem($role);
        if ($type_id > 0) {
            $input[Setting::getTypeField()] = $type_id;
        } elseif (Setting::isTypeFilterEnabled()) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __('O papel %s não tem nenhum Tipo configurado — revise a configuração do DGO+ antes de criar.', 'dgoplus'),
                    Setting::getRoleLabel($role)
                ),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $floors_id));
            return;
        }

        $item = new PassiveDCEquipment();
        $id   = $item->add($input);

        // Heranca do contexto: DGO criada com um piso filtrado nasce nele.
        if ($id && $floors_id > 0 && array_key_exists($floors_id, Floor::getForLocation($locations_id))) {
            Panel::setFloorForItem($item, $floors_id);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $id ?: 0]));
    }

    /**
     * Atribui (ou tira) o piso da DGO aberta.
     *
     * Exige UPDATE porque muda a classificacao do ativo, nao uma porta.
     *
     * @return void
     */
    private static function actionSetFloor(): void
    {
        Session::checkRight(Port::$rightname, UPDATE);

        $itemtype     = $_POST['itemtype'] ?? PassiveDCEquipment::class;
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['plugin_dgoplus_floors_id'] ?? 0);
        $filter       = (int) ($_POST['floor'] ?? 0);

        // Piso de OUTRA localizacao nunca entra, mesmo que o POST venha forjado.
        if ($floors_id > 0 && !array_key_exists($floors_id, Floor::getForLocation($locations_id))) {
            Session::addMessageAfterRedirect(
                __('Este piso não pertence à localização da DGO.', 'dgoplus'),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $filter, ['dgo' => $items_id]));
            return;
        }

        $item = self::getDgoItem($itemtype, $items_id);

        if ($item->getID() > 0) {
            Panel::setFloorForItem($item, $floors_id);
        }

        // Se havia filtro de piso ativo e a DGO saiu dele, seguir a DGO em vez
        // de devolver o usuario a uma lista onde ela nao aparece mais.
        if ($filter > 0 && $filter !== $floors_id) {
            $filter = $floors_id;
        }

        self::redirectTo(self::scope($locations_id, $filter, ['dgo' => $items_id]));
    }

    /**
     * @return void
     */
    private static function actionAddTube(): void
    {
        Session::checkRight(Port::$rightname, CREATE);

        $itemtype     = $_POST['itemtype'] ?? PassiveDCEquipment::class;
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        $panel  = new Panel();
        $layout = Panel::getLayoutForItem(self::getDgoItem($itemtype, $items_id));
        $tubes  = Panel::sanitizeTubes($layout['tubes'] + 1);

        if ($panel->getFromDBByCrit(['itemtype' => $itemtype, 'items_id' => $items_id])) {
            $panel->update(['id' => $panel->getID(), 'tubes' => $tubes]);
        } else {
            $panel->add([
                'itemtype'        => $itemtype,
                'items_id'        => $items_id,
                'tubes'           => $tubes,
                'fibers_per_tube' => $layout['fibers_per_tube'],
            ]);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * Remove a ultima fileira (tubo) da grade.
     *
     * Regras: nunca abaixo de 1 fileira; recusa se a fileira tiver alguma
     * porta documentada ativa (is_deleted = 0). Portas na lixeira nao
     * bloqueiam - se a fileira for recriada, elas voltam a ser
     * restauraveis pela logica de save_port.
     *
     * @return void
     */
    private static function actionRemoveTube(): void
    {
        Session::checkRight(Port::$rightname, DELETE);

        $itemtype     = $_POST['itemtype'] ?? PassiveDCEquipment::class;
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        $layout    = Panel::getLayoutForItem(self::getDgoItem($itemtype, $items_id));
        $last_tube = $layout['tubes'];

        if ($last_tube <= 1) {
            Session::addMessageAfterRedirect(
                __('A grade precisa ter pelo menos uma fileira.', 'dgoplus'),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
            return;
        }

        $port = new Port();
        $busy = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'tube_num'   => $last_tube,
            'is_deleted' => 0,
        ] + Port::gridCriteria());

        if ($busy !== []) {
            $positions = [];
            foreach ($busy as $row) {
                $positions[] = Port::formatPosition(
                    (int) $row['tube_num'],
                    (int) $row['fiber_num'],
                    $layout['fibers_per_tube']
                );
            }
            sort($positions);
            Session::addMessageAfterRedirect(
                sprintf(
                    __('A fileira F%d tem portas documentadas (%s). Limpe-as antes de remover a fileira.', 'dgoplus'),
                    $last_tube,
                    implode(', ', $positions)
                ),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
            return;
        }

        $panel = new Panel();
        if ($panel->getFromDBByCrit(['itemtype' => $itemtype, 'items_id' => $items_id])) {
            $panel->update(['id' => $panel->getID(), 'tubes' => $last_tube - 1]);
        } else {
            // Sem linha propria o layout era o padrao: cria uma ja com a
            // fileira a menos, senao a remocao "nao pega".
            $panel->add([
                'itemtype'        => $itemtype,
                'items_id'        => $items_id,
                'tubes'           => $last_tube - 1,
                'fibers_per_tube' => $layout['fibers_per_tube'],
            ]);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * Acrescenta uma coluna (fibra por tubo) a grade.
     *
     * @return void
     */
    /**
     * Recusa mudar a LARGURA da grade quando isso renumeraria portas ja
     * documentadas. Bloco 3r.
     *
     * O numero da porta e' continuo e derivado: `(fileira - 1) * largura +
     * coluna`. Mexer na largura mexe no multiplicador de toda fileira a partir
     * da 2, entao acrescentar ou remover uma coluna renumeraria portas que ja
     * tem etiqueta colada no equipamento - sem erro, sem historico, sem
     * lixeira. A recusa e' dura de proposito: um "tem certeza?" seria clicado
     * no automatico.
     *
     * A FILEIRA 1 NAO ENTRA na conta, e isso nao e' descuido: ali o numero
     * continuo e' igual ao numero da coluna qualquer que seja a largura
     * (`(1-1) * largura + coluna == coluna`). DGO com documentacao so na
     * primeira fileira continua podendo mudar de geometria.
     *
     * Quantidade de fileiras nao renumera nada e por isso os botoes de fileira
     * seguem livres - a fileira nova nasce depois de todas as outras.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @param int    $width        Largura vigente, para montar o rotulo atual
     * @param int    $locations_id
     * @param int    $floors_id
     * @return bool true se recusou (o chamador deve retornar imediatamente)
     */
    private static function refuseWidthChange(
        string $itemtype,
        int $items_id,
        int $width,
        int $locations_id,
        int $floors_id
    ): bool {
        $port = new Port();

        // is_deleted = 0: porta na lixeira nao tem etiqueta no mundo fisico.
        // "Sem acoplador" entra, sim - e' posicao descrita, nao posicao livre.
        $rows = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ] + Port::gridCriteria());

        // A fileira 1 sai FORA aqui, em PHP, e nao no criterio do find():
        // uma DGO tem no maximo 48x48 posicoes, entao trazer tudo e filtrar
        // custa nada, e evita depender de sintaxe de operador do iterator que
        // nenhuma outra consulta deste plugin exercita.
        //
        // Ordena pelos numeros, nao pelo rotulo: em grade larga o rotulo tem
        // tres digitos e "F2.100" viria antes de "F2.99" na ordem de string.
        $pairs = [];
        foreach ($rows as $row) {
            if ((int) $row['tube_num'] <= 1) {
                continue;
            }
            $pairs[] = [(int) $row['tube_num'], (int) $row['fiber_num']];
        }

        if ($pairs === []) {
            return false;
        }
        usort($pairs, fn($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);

        $total   = count($pairs);
        $shown   = array_slice($pairs, 0, 8);
        $labels  = [];
        foreach ($shown as $pair) {
            $labels[] = Port::formatPosition($pair[0], $pair[1], $width);
        }
        $list = implode(', ', $labels);
        if ($total > count($shown)) {
            $list .= sprintf(__(' e mais %d', 'dgoplus'), $total - count($shown));
        }

        Session::addMessageAfterRedirect(
            sprintf(
                __(
                    'Não é possível mudar o número de colunas: a numeração das portas é contínua e '
                    . 'mudaria para %d porta(s) já documentada(s) fora da primeira fileira (%s). '
                    . 'Defina a largura da grade antes de documentar, ou libere essas portas.',
                    'dgoplus'
                ),
                $total,
                $list
            ),
            false,
            ERROR
        );

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));

        return true;
    }

    private static function actionAddColumn(): void
    {
        Session::checkRight(Port::$rightname, CREATE);

        $itemtype     = $_POST['itemtype'] ?? PassiveDCEquipment::class;
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        $panel  = new Panel();
        $layout = Panel::getLayoutForItem(self::getDgoItem($itemtype, $items_id));
        $fibers = Panel::sanitizeFibers($layout['fibers_per_tube'] + 1);

        if (self::refuseWidthChange($itemtype, $items_id, $layout['fibers_per_tube'], $locations_id, $floors_id)) {
            return;
        }

        if ($panel->getFromDBByCrit(['itemtype' => $itemtype, 'items_id' => $items_id])) {
            $panel->update(['id' => $panel->getID(), 'fibers_per_tube' => $fibers]);
        } else {
            $panel->add([
                'itemtype'        => $itemtype,
                'items_id'        => $items_id,
                'tubes'           => $layout['tubes'],
                'fibers_per_tube' => $fibers,
            ]);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * Remove a ultima coluna da grade.
     *
     * Mesma regra da fileira: nunca abaixo de 1 coluna, e recusa se a ultima
     * coluna tiver alguma porta ativa - inclusive porta marcada como "sem
     * acoplador", que tambem e' registro a preservar.
     *
     * @return void
     */
    private static function actionRemoveColumn(): void
    {
        Session::checkRight(Port::$rightname, DELETE);

        $itemtype     = $_POST['itemtype'] ?? PassiveDCEquipment::class;
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        $layout      = Panel::getLayoutForItem(self::getDgoItem($itemtype, $items_id));
        $last_column = $layout['fibers_per_tube'];

        if ($last_column <= 1) {
            Session::addMessageAfterRedirect(
                __('A grade precisa ter pelo menos uma coluna.', 'dgoplus'),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
            return;
        }

        if (self::refuseWidthChange($itemtype, $items_id, $last_column, $locations_id, $floors_id)) {
            return;
        }

        $port = new Port();
        $busy = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'fiber_num'  => $last_column,
            'is_deleted' => 0,
        ] + Port::gridCriteria());

        if ($busy !== []) {
            $positions = [];
            foreach ($busy as $row) {
                $positions[] = Port::formatPosition(
                    (int) $row['tube_num'],
                    (int) $row['fiber_num'],
                    $last_column
                );
            }
            sort($positions);
            Session::addMessageAfterRedirect(
                sprintf(
                    __('A coluna %d tem portas registradas (%s). Limpe-as antes de remover a coluna.', 'dgoplus'),
                    $last_column,
                    implode(', ', $positions)
                ),
                false,
                ERROR
            );
            self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
            return;
        }

        $panel = new Panel();
        if ($panel->getFromDBByCrit(['itemtype' => $itemtype, 'items_id' => $items_id])) {
            $panel->update(['id' => $panel->getID(), 'fibers_per_tube' => $last_column - 1]);
        } else {
            // Sem linha propria o layout era o padrao: cria uma ja com a coluna
            // a menos, senao a remocao "nao pega".
            $panel->add([
                'itemtype'        => $itemtype,
                'items_id'        => $items_id,
                'tubes'           => $layout['tubes'],
                'fibers_per_tube' => $last_column - 1,
            ]);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * @return void
     */
    private static function actionSavePort(): void
    {
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $tube_num     = (int) ($_POST['tube_num'] ?? 0);
        $fiber_num    = (int) ($_POST['fiber_num'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        // A regra (tres estados exclusivos, restauracao da lixeira, checagem de
        // direito) vive em Port::applyInput desde o bloco 4a, para o POST e o
        // AJAX nao poderem divergir. Aqui fica so o que e' de POST: mensagem na
        // sessao + redirect.
        // 'name' continua fora do array de proposito (licao 44).
        $result = Port::applyInput([
            'itemtype'      => $_POST['itemtype'] ?? PassiveDCEquipment::class,
            'items_id'      => $items_id,
            'tube_num'      => $tube_num,
            'fiber_num'     => $fiber_num,
            'code'          => $_POST['code'] ?? '',
            'comment'       => $_POST['comment'] ?? '',
            'is_no_coupler' => $_POST['is_no_coupler'] ?? 0,
        ]);

        if (!$result['ok']) {
            Session::addMessageAfterRedirect($result['error'], false, ERROR);
            self::redirectTo(self::scope($locations_id, $floors_id, [
                'dgo'  => $items_id,
                'edit' => $tube_num . '-' . $fiber_num,
            ]));
            return;
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * Grava o comentario NATIVO do ativo pelo caminho de POST. Bloco 3t.
     *
     * A regra mora em DgoIdentity::applyComment, que e' a mesma que o endpoint
     * ajax/dgocomment.php usa - o POST e o AJAX nao podem divergir (licao 47).
     * Aqui fica so o que e' de POST: mensagem na sessao + redirect preservando
     * localizacao, piso, DGO e a porta aberta no painel.
     *
     * @return void
     */
    private static function actionSaveDgoComment(): void
    {
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $edit_key     = (string) ($_POST['edit'] ?? '');

        $result = DgoIdentity::applyComment([
            'itemtype' => $_POST['itemtype'] ?? PassiveDCEquipment::class,
            'items_id' => $items_id,
            'comment'  => $_POST['comment'] ?? '',
        ]);

        if (!$result['ok']) {
            Session::addMessageAfterRedirect($result['error'], false, ERROR);
        }

        $params = self::scope($locations_id, $floors_id, ['dgo' => $items_id]);
        if ($edit_key !== '') {
            $params['edit'] = $edit_key;
        }

        self::redirectTo($params);
    }

    /**
     * Grava a OBS do elemento (faixa das entradas). Bloco 4b-2.
     *
     * UPDATE e nao CREATE: a OBS descreve o elemento, nao cria porta. Mesmo
     * direito que o seletor de Piso exige, pela mesma razao.
     *
     * A trava de pai e' a mesma do 3m - existir e estar ao alcance deste
     * usuario -, e desde o bloco 5f-3b pelo mesmo metodo que as portas usam,
     * Port::parentIsReachable. Sem ela, um items_id forjado no POST escreveria
     * OBS em elemento de outra entidade, que e' exatamente o furo que o 3m
     * fechou nas portas.
     *
     * @return void
     */
    private static function actionSaveEntryObs(): void
    {
        Session::checkRight(Port::$rightname, UPDATE);

        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $edit_key     = (string) ($_POST['edit'] ?? '');
        $obs          = trim((string) ($_POST['obs'] ?? ''));

        $params = self::scope($locations_id, $floors_id, ['dgo' => $items_id]);
        if ($edit_key !== '') {
            $params['edit'] = $edit_key;
        }

        $item = new PassiveDCEquipment();

        if ($items_id <= 0 || !$item->getFromDB($items_id) || !Port::parentIsReachable($item)) {
            Session::addMessageAfterRedirect(
                __('Ativo não encontrado ou sem permissão de acesso.', 'dgoplus'),
                false,
                ERROR
            );
            self::redirectTo($params);
            return;
        }

        Panel::setCommentForItem($item, $obs);

        self::redirectTo($params);
    }

    /**
     * Propoe um vinculo a partir da celula aberta no painel. Bloco 4c.
     *
     * A regra inteira (hierarquia, limites da grade, lados ja ocupados) mora
     * em Link::propose - aqui fica so' o que e' de POST: mensagem na sessao e
     * redirect mantendo o painel aberto na MESMA celula, para o resultado (a
     * secao Alimenta redesenhada) aparecer onde o clique aconteceu.
     *
     * dst_itemtype nao vem do POST de proposito: o seletor so' lista
     * PassiveDCEquipment, e aceitar itemtype forjado abriria a porta que a
     * trava de pai fecha.
     *
     * @return void
     */
    private static function actionProposeLink(): void
    {
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $tube_num     = (int) ($_POST['tube_num'] ?? 0);
        $fiber_num    = (int) ($_POST['fiber_num'] ?? 0);

        $result = Link::propose([
            'itemtype'     => $_POST['itemtype'] ?? PassiveDCEquipment::class,
            'items_id'     => $items_id,
            'tube_num'     => $tube_num,
            'fiber_num'    => $fiber_num,
            'dst_itemtype' => PassiveDCEquipment::class,
            'dst_items_id' => $_POST['dst_items_id'] ?? 0,
            'dst_slot'     => $_POST['dst_slot'] ?? 0,
        ]);

        if (!$result['ok']) {
            Session::addMessageAfterRedirect($result['error'], false, ERROR);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, [
            'dgo'  => $items_id,
            'edit' => $tube_num . '-' . $fiber_num,
        ]));
    }

    /**
     * Confirma um vinculo pendente, a partir do card da entrada. Bloco 4c.
     *
     * O 'entry' volta na URL para o card continuar aberto mostrando o estado
     * novo (borda solida, quem confirmou).
     *
     * @return void
     */
    private static function actionConfirmLink(): void
    {
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $entry_slot   = (int) ($_POST['entry'] ?? 0);

        $result = Link::confirm((int) ($_POST['link_id'] ?? 0));

        if (!$result['ok']) {
            Session::addMessageAfterRedirect($result['error'], false, ERROR);
        }

        $params = self::scope($locations_id, $floors_id, ['dgo' => $items_id]);
        if ($entry_slot > 0) {
            $params['entry'] = $entry_slot;
        }

        self::redirectTo($params);
    }

    /**
     * Recusa um vinculo pendente, a partir do card da entrada. Bloco 4c.
     *
     * SEM 'entry' no redirect, de proposito: a recusa apaga a linha, o slot
     * volta a ser livre e caixa livre nao tem card para abrir.
     *
     * @return void
     */
    private static function actionRefuseLink(): void
    {
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        $result = Link::refuse((int) ($_POST['link_id'] ?? 0));

        if (!$result['ok']) {
            Session::addMessageAfterRedirect($result['error'], false, ERROR);
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * Desmonta um vinculo (pendente ou confirmado). Bloco 4c.
     *
     * Pode vir dos DOIS lados: do card da entrada (destino) e da secao
     * Alimenta do painel da porta (origem). O 'edit' preserva o painel aberto
     * quando o clique veio da origem; vindo do destino ele chega vazio.
     *
     * @return void
     */
    private static function actionDismantleLink(): void
    {
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $edit_key     = (string) ($_POST['edit'] ?? '');

        $result = Link::dismantle((int) ($_POST['link_id'] ?? 0));

        if (!$result['ok']) {
            Session::addMessageAfterRedirect($result['error'], false, ERROR);
        }

        $params = self::scope($locations_id, $floors_id, ['dgo' => $items_id]);
        if ($edit_key !== '') {
            $params['edit'] = $edit_key;
        }

        self::redirectTo($params);
    }

    /**
     * @return void
     */
    private static function actionDeletePort(): void
    {
        Session::checkRight(Port::$rightname, DELETE);

        $id           = (int) ($_POST['id'] ?? 0);
        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $items_id     = (int) ($_POST['items_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);

        $port = new Port();
        if ($port->getFromDB($id)) {
            // Bloco 4c: porta que participa de vinculo (qualquer lado) nao
            // vai para a lixeira - o vinculo apontaria para linha morta e o
            // mapa mentiria em silencio. O Port::pre_deleteItem ja bloquearia
            // o delete(), mas sem mensagem; aqui a tela EXPLICA (recusa dura,
            // como nos botoes de coluna).
            if (Link::idsTouchingPorts([$id]) !== []) {
                Session::addMessageAfterRedirect(
                    __('Esta porta participa de um vínculo. Desmonte o vínculo antes de excluir a porta.', 'dgoplus'),
                    false,
                    ERROR
                );
            } else {
                $port->delete(['id' => $id]);
            }
        }

        self::redirectTo(self::scope($locations_id, $floors_id, ['dgo' => $items_id]));
    }

    /**
     * @param array $params
     * @return void
     */
    private static function redirectTo(array $params): void
    {
        Html::redirect(self::getPageUrl($params));
    }

    /**
     * @param string $itemtype
     * @param int    $items_id
     * @return \CommonDBTM
     */
    private static function getDgoItem(string $itemtype, int $items_id): \CommonDBTM
    {
        $item = new $itemtype();
        $item->getFromDB($items_id);
        return $item;
    }

    /**
     * Caixa de aviso para os estados em que nao ha grade para desenhar.
     *
     * @param string $message
     * @return void
     */
    private static function displayEmptyState(string $message): void
    {
        echo "<div class='alert alert-info d-flex align-items-center gap-2' role='alert'>";
        echo "<i class='ti ti-info-circle'></i> ";
        echo htmlescape($message);
        echo "</div>";
    }

    /**
     * Cor da fibra pela coluna (posicao dentro do tubo).
     *
     * @param int $fiber_num
     * @return string
     */
    private static function fiberColor(int $fiber_num): string
    {
        if ($fiber_num < 1) {
            $fiber_num = 1;
        }

        return self::FIBER_COLORS[($fiber_num - 1) % count(self::FIBER_COLORS)];
    }

    /**
     * Barra superior: localizacao, busca global e atalho do relatorio.
     *
     * O grupo da esquerda ("dgoplus-scope") tem Localizacao e, quando a
     * localizacao tem piso cadastrado, tambem o seletor de Piso. Sao dois
     * formularios GET separados de proposito: o de Localizacao NAO carrega o
     * piso, e por isso trocar de localizacao limpa o nivel de baixo sozinho,
     * sem JavaScript.
     *
     * @param int    $locations_id
     * @param int    $dgo_id
     * @param string $search
     * @param int    $floors_id
     * @return void
     */
    private static function displayLocationPicker(int $locations_id, int $dgo_id = 0, string $search = '', int $floors_id = 0): void
    {
        echo "<div class='card mb-3'><div class='card-body py-2'>";
        echo "<div class='d-flex align-items-center gap-3 flex-wrap'>";

        self::displayBrand();

        echo "<div class='d-flex align-items-center gap-2 flex-wrap' id='dgoplus-scope'>";

        // Bloco 4a-3: filtro de papel, primeiro do grupo por ser o corte mais
        // largo (papel corta a base inteira; localizacao e piso cortam dentro
        // dele). Formulario GET proprio, como os outros dois seletores, e
        // carregando localizacao e piso escondidos - trocar o papel nao pode
        // limpar o resto do escopo. Sem localizacao escolhida o filtro vale
        // para o painel geral, que ja le o mesmo ?role= desde o 4a-2.
        $current_role = Dashboard::currentRole();
        $role_options = [];
        foreach (Setting::getRoles() as $role_key) {
            $role_options[$role_key] = Setting::getRoleLabel($role_key);
        }

        echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' id='dgoplus-role-form'>";
        echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
        if ($locations_id > 0) {
            echo Html::hidden('location', ['value' => $locations_id]);
        }
        if ($floors_id > 0) {
            echo Html::hidden('floor', ['value' => $floors_id]);
        }
        echo "<span class='text-muted d-flex align-items-center gap-1'>"
            . "<i class='ti ti-tags'></i> " . htmlescape(__('Papel', 'dgoplus')) . "</span>";
        Dropdown::showFromArray('role', $role_options, [
            'value'               => $current_role ?? 0,
            'width'               => '160px',
            'display_emptychoice' => true,
            'emptylabel'          => __('Todos os papéis', 'dgoplus'),
            'on_change'           => 'document.getElementById("dgoplus-role-form").submit();',
        ]);
        echo "</div>";
        echo "</form>";

        echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' id='dgoplus-location-form'>";
        echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
        // Papel e' ortogonal a localizacao: trocar de localizacao NAO limpa o
        // filtro de papel (ao contrario do piso, que pertence a localizacao).
        if ($current_role !== null) {
            echo Html::hidden('role', ['value' => $current_role]);
        }
        echo "<span class='text-muted d-flex align-items-center gap-1'>"
            . "<i class='ti ti-map-pin'></i> " . htmlescape(Location::getTypeName(1)) . "</span>";
        Dropdown::show('Location', [
            'name'      => 'location',
            'value'     => $locations_id,
            'on_change' => 'document.getElementById("dgoplus-location-form").submit();',
        ]);
        echo "</div>";
        echo "</form>";

        // Nivel 2, condicional: so aparece se a localizacao tiver piso
        // cadastrado em Configurar -> Listas suspensas -> Pisos.
        $floors = Floor::getForLocation($locations_id);

        if ($floors !== []) {
            echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' id='dgoplus-floor-form'>";
            echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
            echo Html::hidden('location', ['value' => $locations_id]);
            if ($current_role !== null) {
                echo Html::hidden('role', ['value' => $current_role]);
            }
            echo "<span class='text-muted d-flex align-items-center gap-1'>"
                . "<i class='ti ti-building'></i> " . htmlescape(Floor::getTypeName(1)) . "</span>";
            Dropdown::showFromArray('floor', $floors, [
                'value'               => $floors_id,
                'width'               => '180px',
                'display_emptychoice' => true,
                'emptylabel'          => __('Todos os pisos', 'dgoplus'),
                'on_change'           => 'document.getElementById("dgoplus-floor-form").submit();',
            ]);
            echo "</div>";
            echo "</form>";
        }

        echo "</div>";

        // Busca global: acha a porta em qualquer DGO/localizacao pelo
        // codigo, nome ou observacao. Preserva o contexto atual (location/
        // dgo) para a grade aberta continuar visivel, com destaque.
        echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' class='flex-grow-1' style='min-width:240px;max-width:420px'>";
        if ($locations_id > 0) {
            echo Html::hidden('location', ['value' => $locations_id]);
        }
        if ($floors_id > 0) {
            echo Html::hidden('floor', ['value' => $floors_id]);
        }
        if ($dgo_id > 0) {
            echo Html::hidden('dgo', ['value' => $dgo_id]);
        }
        if ($current_role !== null) {
            echo Html::hidden('role', ['value' => $current_role]);
        }
        echo "<div class='input-group input-group-sm'>";
        echo "<span class='input-group-text'><i class='ti ti-search'></i></span>";
        echo Html::input('q', [
            'value'       => $search,
            'placeholder' => __('Buscar porta, loja, splitter…', 'dgoplus'),
        ]);
        echo "<button type='submit' class='btn btn-outline-secondary'>" . __('Buscar', 'dgoplus') . "</button>";
        if ($search !== '') {
            $clear_params = self::scope($locations_id, $floors_id);
            if ($dgo_id > 0) {
                $clear_params['dgo'] = $dgo_id;
            }
            echo "<a class='btn btn-outline-secondary' href='" . htmlescape(self::getPageUrl($clear_params)) . "' title='" . __('Limpar busca', 'dgoplus') . "'><i class='ti ti-x'></i></a>";
        }
        echo "</div>";
        echo "</form>";

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $report_url = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/front/port.php';
        echo "<a class='btn btn-outline-secondary btn-sm ms-auto' href='" . htmlescape($report_url) . "'>"
            . "<i class='ti ti-report'></i>&nbsp;" . __('Relatório de portas', 'dgoplus') . "</a>";

        echo "</div>";
        echo "</div></div>";
    }

    /**
     * Resultados da busca global (todas as DGOs visiveis pela entidade).
     *
     * @param string $search
     * @return void
     */
    private static function displaySearchResults(string $search): void
    {
        // DGOs que o usuario pode ver (mesma restricao de entidade do resto)
        $dgo_model = new PassiveDCEquipment();
        $criteria  = getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true);
        $dgos      = $dgo_model->find(['is_deleted' => 0] + $criteria);

        $dgo_info = [];
        foreach ($dgos as $row) {
            $dgo_info[(int) $row['id']] = [
                'name'         => $row['name'] !== '' ? $row['name'] : ('#' . $row['id']),
                'locations_id' => (int) $row['locations_id'],
            ];
        }

        $matches = [];
        if ($dgo_info !== []) {
            // Curingas do LIKE escapados: busca literal pelo que foi digitado.
            $like = '%' . addcslashes($search, '%_\\') . '%';

            $port_model = new Port();
            $matches    = $port_model->find([
                'itemtype'   => PassiveDCEquipment::class,
                'items_id'   => array_keys($dgo_info),
                'is_deleted' => 0,
                'kind'       => Port::KIND_GRID,
                'OR'         => [
                    'code'    => ['LIKE', $like],
                    'name'    => ['LIKE', $like],
                    'comment' => ['LIKE', $like],
                ],
            ]);
        }

        echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title mb-0'>"
            . "<i class='ti ti-search me-1'></i>"
            . sprintf(__('Resultados para "%s"', 'dgoplus'), htmlescape($search))
            . " <span class='badge bg-blue-lt ms-1'>" . count($matches) . "</span></h3></div>";

        if ($matches === []) {
            echo "<div class='card-body text-muted'>"
                . __('Nenhuma porta encontrada com esse termo.', 'dgoplus')
                . "</div>";
            echo "</div>";
            return;
        }

        // Bloco 3r: a posicao exibida e' continua, e o continuo depende da
        // largura de CADA DGO. Uma consulta em lote, nao uma por linha.
        $widths = Panel::getWidthsForItems(
            PassiveDCEquipment::class,
            array_keys($dgo_info)
        );

        $shown = 0;
        echo "<div class='table-responsive'><table class='table table-vcenter card-table mb-0'>";
        echo "<thead><tr>"
            . "<th>" . __('Posição', 'dgoplus') . "</th>"
            . "<th>" . __('DGO', 'dgoplus') . "</th>"
            . "<th>" . __('Nome / Número (Loja)', 'dgoplus') . "</th>"
            . "<th>" . __('Observações', 'dgoplus') . "</th>"
            . "</tr></thead><tbody>";
        foreach ($matches as $row) {
            if (++$shown > 50) {
                break;
            }
            $items_id = (int) $row['items_id'];
            $info     = $dgo_info[$items_id];
            $pos      = Port::formatPosition(
                (int) $row['tube_num'],
                (int) $row['fiber_num'],
                $widths[$items_id] ?? Panel::DEFAULT_FIBERS
            );
            $url      = self::getPageUrl([
                'location' => $info['locations_id'],
                'dgo'      => $items_id,
                'edit'     => $row['tube_num'] . '-' . $row['fiber_num'],
                'q'        => $search,
            ]) . '#dgoplus-panel';

            echo "<tr>";
            echo "<td class='text-nowrap'><a class='badge bg-blue-lt text-decoration-none' href='" . htmlescape($url) . "'>"
                . htmlescape($pos) . "</a></td>";
            echo "<td>" . htmlescape($info['name']) . "</td>";
            $code_cell = (string) ($row['code'] ?? '');
            if ((int) ($row['is_no_coupler'] ?? 0) === 1) {
                $code_cell = __('sem acoplador', 'dgoplus');
            }
            echo "<td>" . htmlescape($code_cell) . "</td>";
            echo "<td class='text-muted'>" . htmlescape((string) ($row['comment'] ?? '')) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";

        if (count($matches) > 50) {
            echo "<div class='card-body text-muted small'>"
                . sprintf(__('Mostrando 50 de %d resultados — refine o termo.', 'dgoplus'), count($matches))
                . "</div>";
        }

        echo "</div>";
    }

    /**
     * DGOs da localizacao, opcionalmente restritas a um piso.
     *
     * @param int $locations_id
     * @param int $floors_id 0 = todos os pisos
     * @return array<int, PassiveDCEquipment> chave = id do ativo
     */
    private static function getDgosAtLocation(int $locations_id, int $floors_id = 0): array
    {
        $dgo       = new PassiveDCEquipment();
        $criteria  = getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true);
        $result    = [];

        $where = [
            'locations_id' => $locations_id,
            'is_deleted'   => 0,
        ];

        if ($floors_id > 0) {
            $ids = Panel::getItemsInFloor(PassiveDCEquipment::class, $floors_id);
            // Filtro nunca pode "sumir": piso sem nenhuma DGO tem que dar zero
            // resultado, nao a lista inteira. Lista vazia vira [0].
            $where['id'] = $ids !== [] ? $ids : [0];
        }

        // Bloco 3l: so os Tipos configurados. Devolve array vazio quando nada
        // foi configurado, e nesse caso o `+` nao altera o where - e' o que
        // mantem o comportamento anterior ao 3l intacto.
        //
        // Bloco 4a-3: com filtro de papel ativo, so os Tipos DAQUELE papel.
        // Papel sem Tipo mapeado devolve criterio impossivel (zero linhas),
        // nunca a base inteira - regra do roleCriteria(), fechada no 4a-1.
        $role = Dashboard::currentRole();
        $type_criteria = $role !== null
            ? Setting::roleCriteria($role)
            : Setting::typesCriteria();

        $rows = $dgo->find($where + $type_criteria + $criteria);

        foreach ($rows as $row) {
            $item = new PassiveDCEquipment();
            $item->getFromResultSet($row);
            $result[(int) $row['id']] = $item;
        }

        return $result;
    }

    /**
     * Quantos dispositivos passivos da localizacao ficaram de fora por nao ter
     * papel configurado (o Tipo deles nao esta em papel nenhum).
     *
     * Existe para a tela poder DIZER que filtrou, em vez de simplesmente
     * mostrar menos coisa: ativo que desaparece sem explicacao parece dado
     * perdido (licao 16). Devolve 0 quando o filtro esta desligado.
     *
     * NAO conta o filtro de papel da barra: quem filtra por DGO escolheu
     * esconder DIO e CTO, e avisar disso seria ruido. A conta e' sempre
     * contra o CONJUNTO dos papeis (typesCriteria), com ou sem filtro ativo.
     *
     * @param int $locations_id
     * @return int
     */
    private static function countFilteredOut(int $locations_id): int
    {
        if (!Setting::isTypeFilterEnabled()) {
            return 0;
        }

        $dgo      = new PassiveDCEquipment();
        $criteria = getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true);

        $where = [
            'locations_id' => $locations_id,
            'is_deleted'   => 0,
        ];

        $total = count($dgo->find($where + $criteria));
        $dgos  = count($dgo->find($where + Setting::typesCriteria() + $criteria));

        return max(0, $total - $dgos);
    }

    /**
     * Seletor de elemento da localizacao + criacao de elemento novo.
     *
     * Ate MAX_TABS elementos sao abas de verdade (um clique), agrupadas por
     * papel na ordem do registro (bloco 4a-3). Acima disso vira um seletor
     * unico com busca - o select2 que o core aplica em todo dropdown -, senao
     * um andar com 20 elementos viraria uma parede de botoes.
     *
     * @param int                           $locations_id
     * @param array<int,PassiveDCEquipment> $dgos
     * @param int                           $active_id
     * @param int                           $floors_id
     * @return void
     */
    private static function displayDgoTabs(int $locations_id, array $dgos, int $active_id, int $floors_id = 0): void
    {
        // Bloco 5f-2b: espelha exatamente a trava do POST (actionCreateDgo).
        // Tela e ponto de gravacao perguntando coisas diferentes e' como se
        // produz botao que existe e recusa, ou acao possivel sem botao.
        $can_create = Session::haveRight(Port::$rightname, CREATE);

        $filter_on = Setting::isTypeFilterEnabled();

        // Bloco 3l: quando o filtro de Tipo esconde ativos, a tela DIZ isso.
        // Sem este aviso, alguem que documentou um elemento e depois configurou
        // o Tipo errado concluiria que o plugin perdeu o trabalho dele.
        // Bloco 4a-3: o texto fala de papel, nao mais de "Tipo de DGO".
        $filtered = self::countFilteredOut($locations_id);
        if ($filtered > 0) {
            echo "<div class='alert alert-info d-flex align-items-center gap-2'>";
            echo "<i class='ti ti-info-circle'></i>";
            echo "<span>";
            echo htmlescape(sprintf(
                _n(
                    '%d dispositivo passivo desta localização não está listado porque seu Tipo não tem papel configurado.',
                    '%d dispositivos passivos desta localização não estão listados porque seus Tipos não têm papel configurado.',
                    $filtered,
                    'dgoplus'
                ),
                $filtered
            ));
            if (Session::haveRight('config', UPDATE)) {
                echo " <a href='" . htmlescape(self::getConfigUrl()) . "'>"
                    . htmlescape(__('Revisar a configuração', 'dgoplus')) . "</a>";
            }
            echo "</span>";
            echo "</div>";
        }

        echo "<div class='d-flex align-items-center gap-2 flex-wrap justify-content-between mb-3'>";

        if ($dgos !== [] && count($dgos) <= self::MAX_TABS) {
            // Bloco 4a-3: abas agrupadas por papel, na ordem do registro (que
            // e' a hierarquia fisica DIO -> DGO -> CTO, decisao do 4a-1). O
            // rotulo do grupo e' um nav-link desabilitado - classe do core,
            // nada inventado - com a contagem de elementos do grupo. Com o
            // filtro de Tipo desligado ninguem tem papel, e ai as abas ficam
            // exatamente como sempre foram, sem rotulos de grupo.
            $groups = [];
            if ($filter_on) {
                foreach (Setting::getRoles() as $role_key) {
                    $groups[$role_key] = [];
                }
                foreach ($dgos as $id => $item) {
                    // Papel nulo nao acontece aqui (a consulta so deixa passar
                    // Tipo mapeado), mas custa nada nao quebrar se acontecer.
                    $groups[Setting::getRoleOfItem($item) ?? ''][$id] = $item;
                }
            } else {
                $groups[''] = $dgos;
            }

            echo "<ul class='nav nav-tabs flex-grow-1' role='tablist'>";
            foreach ($groups as $group_role => $items) {
                if ($items === []) {
                    continue;
                }

                if ($group_role !== '') {
                    echo "<li class='nav-item d-flex align-items-center'>";
                    echo "<span class='nav-link disabled d-flex align-items-center gap-1 px-2 text-muted'"
                        . " style='font-size:0.72rem;font-weight:600;letter-spacing:0.04em'>"
                        . htmlescape(Setting::getRoleLabel((string) $group_role))
                        . "<span class='badge bg-secondary-lt'>" . count($items) . "</span>"
                        . "</span>";
                    echo "</li>";
                }

                foreach ($items as $id => $item) {
                    $count  = self::countDocumentedPorts(PassiveDCEquipment::class, $id);
                    $url    = self::getPageUrl(self::scope($locations_id, $floors_id, ['dgo' => $id]));
                    $active = ($id === $active_id);
                    $class  = 'nav-link d-flex align-items-center gap-2' . ($active ? ' active' : '');
                    $badge  = $active ? 'bg-blue-lt' : 'bg-secondary-lt';

                    echo "<li class='nav-item'>";
                    echo "<a class='" . $class . "' href='" . htmlescape($url) . "'>";
                    echo "<i class='ti ti-server'></i>";
                    echo htmlescape($item->fields['name'] ?: ('#' . $id));
                    echo "<span class='badge " . $badge . "'>" . $count . "</span>";
                    echo "</a>";
                    echo "</li>";
                }
            }
            echo "</ul>";
        } elseif ($dgos !== []) {
            $elements = [];
            foreach ($dgos as $id => $item) {
                $count = self::countDocumentedPorts(PassiveDCEquipment::class, $id);

                // Bloco 4a-3: a sigla do papel entra na frente do nome, para o
                // seletor unico nao perder o agrupamento que as abas ganharam.
                $prefix = '';
                if ($filter_on) {
                    $item_role = Setting::getRoleOfItem($item);
                    if ($item_role !== null) {
                        $prefix = Setting::getRoleLabel($item_role) . ' · ';
                    }
                }

                $elements[$id] = sprintf(
                    '%s%s — %d %s',
                    $prefix,
                    $item->fields['name'] ?: ('#' . $id),
                    $count,
                    __('documentadas', 'dgoplus')
                );
            }

            echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' id='dgoplus-dgo-form'>";
            echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
            echo Html::hidden('location', ['value' => $locations_id]);
            if ($floors_id > 0) {
                echo Html::hidden('floor', ['value' => $floors_id]);
            }
            $selector_role = Dashboard::currentRole();
            if ($selector_role !== null) {
                echo Html::hidden('role', ['value' => $selector_role]);
            }
            echo "<span class='text-muted d-flex align-items-center gap-1'>"
                . "<i class='ti ti-server'></i> " . sprintf(__('%d elementos nesta localização', 'dgoplus'), count($dgos))
                . "</span>";
            Dropdown::showFromArray('dgo', $elements, [
                'value'               => $active_id,
                'width'               => '340px',
                'display_emptychoice' => true,
                'on_change'           => 'document.getElementById("dgoplus-dgo-form").submit();',
            ]);
            echo "</div>";
            echo "</form>";
        }

        if ($can_create) {
            // Bloco 4a-3: elemento novo nasce com papel ESCOLHIDO, sem padrao.
            // Sem escolher, o POST recusa - mesma dureza do nome vazio. O papel
            // decide o Tipo gravado (Setting::getTypeForNewItem), e e' o Tipo
            // que decide em qual grupo de abas o elemento aparece.
            $role_choices = [];
            foreach (Setting::getRoles() as $role_key) {
                $role_choices[$role_key] = Setting::getRoleLabel($role_key);
            }

            echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "' style='min-width:300px;max-width:420px'>";
            echo Html::hidden('action', ['value' => 'create_dgo']);
            echo Html::hidden('locations_id', ['value' => $locations_id]);
            echo Html::hidden('floor', ['value' => $floors_id]);
            echo "<div class='d-flex align-items-center gap-1 flex-wrap'>";
            Dropdown::showFromArray('role', $role_choices, [
                'width'               => '110px',
                'display_emptychoice' => true,
                'emptylabel'          => __('Papel', 'dgoplus'),
            ]);
            echo "<div class='input-group input-group-sm' style='flex:1 1 180px'>";
            echo Html::input('name', ['placeholder' => __('Nome do novo elemento', 'dgoplus')]);
            echo "<button type='submit' name='submit_create_dgo' class='btn btn-outline-primary'>"
                . "<i class='ti ti-plus'></i>&nbsp;" . __('Novo elemento', 'dgoplus') . "</button>";
            echo "</div>";
            echo "</div>";
            Html::closeForm();
        }

        echo "</div>";
    }

    /**
     * @param string $itemtype
     * @param int    $items_id
     * @return int
     */
    private static function countDocumentedPorts(string $itemtype, int $items_id): int
    {
        $port = new Port();
        $rows = $port->find([
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'is_deleted'    => 0,
            'is_no_coupler' => 0,
        ] + Port::gridCriteria());

        return count($rows);
    }

    /**
     * Legenda das cores da grade.
     *
     * @return string
     */
    private static function gridLegend(): string
    {
        $swatch = static function (string $bg, string $border, bool $dashed = false): string {
            return "<span style='display:inline-block;width:10px;height:10px;border-radius:3px;"
                . "background:" . $bg . ";border:1px " . ($dashed ? 'dashed' : 'solid') . " " . $border . "'></span>";
        };

        return "<div class='d-flex align-items-center gap-3 flex-wrap text-muted small'>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_DOC_BG, self::CELL_DOC_BORDER) . ' ' . __('Documentada', 'dgoplus') . "</span>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_FREE_BG, self::CELL_FREE_BORDER) . ' ' . __('Livre', 'dgoplus') . "</span>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_NC_BG, self::CELL_NC_BORDER) . ' ' . __('Sem acoplador', 'dgoplus') . "</span>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_DOC_BG, self::CELL_DOC_BORDER, true) . ' ' . __('Vínculo pendente', 'dgoplus') . "</span>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch('transparent', self::MATCH_COLOR) . ' ' . __('Resultado da busca', 'dgoplus') . "</span>"
            . "</div>";
    }

    /**
     * HTML de UMA celula da grade.
     *
     * Extraido do displayGrid no bloco 4a e publico de proposito: o endpoint
     * ajax/port.php devolve o HTML desta mesma funcao para o navegador
     * substituir a celula. Se o JS montasse o markup por conta propria, a
     * regra de cor/estado passaria a existir em dois lugares e o AJAX
     * divergiria do recarregamento de pagina (licao 48).
     *
     * @param int         $items_id
     * @param int         $locations_id
     * @param int         $floors_id
     * @param int         $t         Fileira (tubo)
     * @param int         $f         Coluna (fibra)
     * @param array|null  $row       Linha da porta, ou null se a posicao esta livre
     * @param string      $edit_key  Posicao aberta no painel ("t-f")
     * @param array|null  $link      Vinculo que SAI desta porta (bloco 4c), ou null
     * @param string      $search    Termo da busca, para o destaque ambar
     * @return string
     */
    public static function renderCell(
        int $items_id,
        int $locations_id,
        int $floors_id,
        int $t,
        int $f,
        ?array $row,
        string $edit_key,
        int $fibers_per_tube,
        ?array $link = null,
        string $search = ''
    ): string {
        $key    = $t . '-' . $f;
        $exists = $row !== null;
        $no_cpl = $exists && (int) ($row['is_no_coupler'] ?? 0) === 1;
        $filled = $exists && !$no_cpl;
        $active = ($key === $edit_key);

        // Bloco 4c: pendente = tracejada, mesma linguagem visual da faixa
        // E1-E4 (decisao fechada da Fase 4). Confirmado fica com a borda
        // solida de documentada - o vinculo em si mora no title e na linha do
        // codigo quando ela esta vazia.
        $pending_link = $link !== null
            && (string) ($link['status'] ?? '') !== Link::STATUS_CONFIRMED;

        $dst = $link !== null
            ? Link::describeDestination((int) ($link['plugin_dgoplus_ports_id_dst'] ?? 0))
            : null;

        $is_match = false;
        if ($search !== '' && $exists) {
            // 'name' entra na busca de proposito: o campo saiu da tela,
            // mas o que ja foi digitado nele continua encontravel.
            $haystack = ($row['code'] ?? '') . ' '
                . ($row['name'] ?? '') . ' '
                . ($row['comment'] ?? '');
            $is_match = mb_stripos($haystack, $search) !== false;
        }

        if ($no_cpl) {
            $bg     = self::CELL_NC_BG;
            $border = '1px solid ' . self::CELL_NC_BORDER;
        } elseif ($filled) {
            $bg     = self::CELL_DOC_BG;
            $border = ($pending_link ? '1px dashed ' : '1px solid ') . self::CELL_DOC_BORDER;
        } else {
            $bg     = self::CELL_FREE_BG;
            $border = '1px solid ' . self::CELL_FREE_BORDER;
        }
        // Destaque por box-shadow INSET, nunca outline: o outline e'
        // desenhado FORA da caixa e o container com overflow-x:auto
        // corta o contorno da primeira coluna (a celula aparece
        // "mordida" na borda esquerda). Inset fica dentro da caixa e
        // nao depende de folga nenhuma no container.
        $extra  = '';
        if ($is_match) {
            $extra = 'box-shadow:inset 0 0 0 2px ' . self::MATCH_COLOR . ';';
        }
        if ($active) {
            $extra = 'box-shadow:inset 0 0 0 2px ' . self::ACCENT . ';';
        }

        $url = self::getPageUrl(self::scope($locations_id, $floors_id, [
            'dgo'  => $items_id,
            'edit' => $key,
        ])) . '#dgoplus-panel';

        $title = Port::formatPosition($t, $f, $fibers_per_tube);
        if ($no_cpl) {
            $title .= ' — ' . __('sem acoplador', 'dgoplus');
            if (($row['comment'] ?? '') !== '') {
                $title .= ': ' . $row['comment'];
            }
        } elseif ($filled) {
            // Bloco 4c: porta vinculada pode ter os campos vazios, e ai o
            // trim devolve '' - o travessao nao entra pendurado no nada.
            $detail = trim(($row['code'] ?? '') . ' ' . ($row['comment'] ?? ''));
            if ($detail !== '') {
                $title .= ' — ' . $detail;
            }
        }

        // Bloco 4c: quem esta celula alimenta, e em que pe o vinculo esta.
        if ($dst !== null && $dst['ok']) {
            $title .= ' — ' . sprintf(
                $pending_link
                    ? __('proposta: alimentar %1$s de %2$s (aguardando confirmação)', 'dgoplus')
                    : __('alimenta %1$s de %2$s', 'dgoplus'),
                $dst['label'],
                $dst['item']
            );
        }

        // data-dgoplus-cell e' a ancora do JS: e' por ele que a resposta do
        // AJAX encontra a celula a substituir. Sem JS, e' atributo inerte.
        $html = "<a href='" . htmlescape($url) . "' class='text-decoration-none' title='" . htmlescape($title) . "'"
            . " data-dgoplus-cell='" . htmlescape($key) . "'"
            . " style='display:block;width:64px;flex:0 0 auto;padding:4px 5px;border-radius:6px;color:inherit;"
            . "background:" . $bg . ";border:" . $border . ";" . $extra . "'>";

        $html .= "<span class='d-flex align-items-center gap-1'>";
        $html .= "<span style='display:inline-block;width:6px;height:6px;border-radius:50%;flex:0 0 auto;"
            . "background:" . self::fiberColor($f) . "'></span>";
        $html .= "<span class='text-muted' style='font-size:9.5px'>" . Port::formatPosition($t, $f, $fibers_per_tube) . "</span>";
        $html .= "</span>";

        // Sempre tres linhas, para a altura da celula nao mudar de
        // estado para estado (o layout de duas colunas foi aprovado
        // com esta altura). A terceira linha agora mostra a observacao,
        // que e' onde o sinal da porta passa a ser descrito.
        if ($no_cpl) {
            $html .= "<span class='d-block text-truncate' style='font-size:10px'>"
                . "<i class='ti ti-plug-off'></i> " . __('s/ acopl.', 'dgoplus') . "</span>";
            $html .= "<span class='d-block text-truncate text-muted' style='font-size:9.5px'>"
                . htmlescape((string) ($row['comment'] ?? '')) . "&nbsp;</span>";
        } elseif ($filled) {
            // Bloco 4c: porta vinculada sem numero de loja mostra PARA ONDE
            // vai ("→ E1") em vez do traco de vazio - e' o vinculo que a
            // documenta.
            $code_line = $row['code'] ?: (
                $dst !== null && $dst['ok'] ? '→ ' . $dst['label'] : '—'
            );
            $html .= "<span class='d-block text-truncate' style='font-size:11px'><strong>"
                . htmlescape($code_line) . "</strong></span>";
            $html .= "<span class='d-block text-truncate text-muted' style='font-size:9.5px'>"
                . htmlescape((string) ($row['comment'] ?? '')) . "&nbsp;</span>";
        } else {
            $html .= "<span class='d-block text-muted' style='font-size:10px'>" . __('livre', 'dgoplus') . "</span>";
            $html .= "<span class='d-block' style='font-size:9.5px'>&nbsp;</span>";
        }

        $html .= "</a>";

        return $html;
    }

    /**
     * Badges de contagem do cabecalho da grade.
     *
     * Tambem publico e tambem por causa do AJAX: depois de salvar, o contador
     * "N de 64 documentadas" tem que mudar sem recarregar a pagina.
     *
     * @param int $documented
     * @param int $capacity
     * @param int $no_coupler
     * @return string
     */
    public static function renderBadges(int $documented, int $capacity, int $no_coupler): string
    {
        $html = "<span class='badge bg-blue-lt'>"
            . sprintf(__('%d de %d documentadas', 'dgoplus'), $documented, $capacity)
            . "</span>";

        if ($no_coupler > 0) {
            $html .= "<span class='badge bg-red-lt'>"
                . sprintf(__('%d sem acoplador', 'dgoplus'), $no_coupler)
                . "</span>";
        }

        return $html;
    }

    /**
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param string             $edit_key
     * @param string             $search
     * @param int                $floors_id
     * @return void
     */
    private static function displayGrid(PassiveDCEquipment $dgo, int $locations_id, string $edit_key, string $search = '', int $floors_id = 0, int $entry_slot = 0): void
    {
        $items_id = (int) $dgo->getID();
        $layout   = Panel::getLayoutForItem($dgo);

        $port = new Port();
        $rows = $port->find([
            'itemtype'   => PassiveDCEquipment::class,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ] + Port::gridCriteria());

        $byKey = [];
        foreach ($rows as $row) {
            $byKey[$row['tube_num'] . '-' . $row['fiber_num']] = $row;
        }

        // Bloco 4c: vinculos que SAEM das portas desta grade, numa consulta
        // so' - nunca uma por celula. A celula de origem ganha a marca
        // (tracejada em pendente) e o title diz quem ela alimenta.
        $row_ids = [];
        foreach ($rows as $row) {
            $row_ids[] = (int) $row['id'];
        }

        $origin_links = Link::findByOrigins($row_ids);

        $capacity = $layout['tubes'] * $layout['fibers_per_tube'];

        // Contagem centralizada em Port::statsForDgo desde o 4a, para o badge
        // que o AJAX reescreve ser calculado do mesmo jeito que o da carga
        // inicial da pagina.
        $stats = Port::statsForDgo(PassiveDCEquipment::class, $items_id);

        echo "<div class='card mb-3'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between flex-wrap gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-grid-dots'></i>" . htmlescape($dgo->fields['name'] ?: ('#' . $items_id))
            // O span de id fixo e' o alvo do AJAX; o conteudo vem do mesmo
            // renderBadges que o endpoint usa.
            . "<span id='dgoplus-badges' class='d-flex align-items-center gap-2'>"
            . self::renderBadges($stats['documented'], $capacity, $stats['no_coupler'])
            . "</span>";
        echo "</h3>";
        echo self::gridLegend();
        echo "</div>";

        echo "<div class='card-body'>";

        // Duas colunas: grade a esquerda, anexos da DGO a direita (aquela faixa
        // vazia que sobrava ao lado das colunas). flex-wrap derruba os anexos
        // para baixo em tela estreita.
        echo "<div class='d-flex flex-wrap align-items-start gap-3'>";

        // min-width:0 e' obrigatorio: sem ele o filho flex nao encolhe e o
        // overflow-x:auto de dentro nunca entra em acao (a grade empurra a
        // coluna de anexos para fora do card).
        echo "<div style='flex:1 1 520px;min-width:0'>";

        // Bloco 4b-2: Piso e entradas dividem a MESMA linha (desenho do
        // usuario). align-items:flex-end alinha o seletor com as caixas, que
        // sao mais altas; flex-wrap derruba a faixa para baixo do Piso quando
        // nao couber - a quebra planejada dos 1000px.
        echo "<div class='d-flex align-items-end gap-3 flex-wrap mb-3'>";
        self::displayFloorAssignment($dgo, $locations_id, $floors_id);
        self::displayEntryStrip($dgo, $locations_id, $floors_id, $edit_key, $entry_slot);
        echo "</div>";

        // Bloco 4c: card da entrada aberta (?entry=N), LOGO ABAIXO da faixa -
        // posicao literal do desenho aprovado. Sem slot aberto, nao imprime nada.
        self::displayEntryCard($dgo, $locations_id, $floors_id, $edit_key, $entry_slot);

        echo "<div style='overflow-x:auto'>";

        for ($t = 1; $t <= $layout['tubes']; $t++) {
            echo "<div class='d-flex align-items-stretch gap-1 mb-1'>";
            echo "<div class='d-flex align-items-center justify-content-center text-muted small' style='width:34px;flex:0 0 auto'>"
                . "<strong>F" . $t . "</strong></div>";

            for ($f = 1; $f <= $layout['fibers_per_tube']; $f++) {
                $key    = $t . '-' . $f;
                $row_at = $byKey[$key] ?? null;

                echo self::renderCell(
                    $items_id,
                    $locations_id,
                    $floors_id,
                    $t,
                    $f,
                    $row_at,
                    $edit_key,
                    $layout['fibers_per_tube'],
                    $row_at !== null ? ($origin_links[(int) $row_at['id']] ?? null) : null,
                    $search
                );
            }
            echo "</div>";
        }

        echo "</div>";

        $can_add       = Session::haveRight(Port::$rightname, CREATE);
        $can_remove    = Session::haveRight(Port::$rightname, DELETE) && $layout['tubes'] > 1;
        $can_add_col   = $can_add && $layout['fibers_per_tube'] < Panel::MAX_FIBERS;
        $can_rm_col    = Session::haveRight(Port::$rightname, DELETE) && $layout['fibers_per_tube'] > 1;

        if ($can_add || $can_remove || $can_add_col || $can_rm_col) {
            echo "<div class='d-flex gap-2 mt-3 flex-wrap'>";

            if ($can_add) {
                echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
                echo Html::hidden('action', ['value' => 'add_tube']);
                echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::submit(sprintf(__('+ Nova fileira (F%d)', 'dgoplus'), $layout['tubes'] + 1), ['class' => 'btn btn-sm btn-outline-primary']);
                Html::closeForm();
            }

            if ($can_remove) {
                echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
                echo Html::hidden('action', ['value' => 'remove_tube']);
                echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::submit(sprintf(__('− Remover fileira (F%d)', 'dgoplus'), $layout['tubes']), ['class' => 'btn btn-sm btn-outline-danger']);
                Html::closeForm();
            }

            if ($can_add_col) {
                echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
                echo Html::hidden('action', ['value' => 'add_column']);
                echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::submit(
                    sprintf(__('+ Nova coluna (%d)', 'dgoplus'), $layout['fibers_per_tube'] + 1),
                    ['class' => 'btn btn-sm btn-outline-primary']
                );
                Html::closeForm();
            }

            if ($can_rm_col) {
                echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
                echo Html::hidden('action', ['value' => 'remove_column']);
                echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::submit(
                    sprintf(__('− Remover coluna (%d)', 'dgoplus'), $layout['fibers_per_tube']),
                    ['class' => 'btn btn-sm btn-outline-danger']
                );
                Html::closeForm();
            }

            echo "</div>";
        }

        echo "</div>"; // coluna da grade

        // Bloco 3t: a coluna da direita passou a ter tres cards - QR de
        // identidade, anexos e comentario do ativo. A <div> da coluna e'
        // aberta AQUI, e nao mais dentro do displayAttachmentsSidebar, para os
        // tres empilharem juntos.
        echo "<div style='flex:1 1 280px;min-width:0;max-width:420px'>";
        DgoIdentity::displayQrCard($dgo);
        // Bloco 4e (ajuste na validacao): anexos ACIMA do "Alimenta" - ordem
        // apontada pelo usuario na propria captura. O card "Alimenta" fecha a
        // parte de topologia da coluna, antes dos comentarios.
        self::displayAttachmentsSidebar($dgo, $locations_id, $edit_key, $floors_id);
        self::displayFeedsCard($dgo);
        DgoIdentity::displayCommentCard($dgo, $locations_id, $edit_key, $floors_id);
        echo "</div>"; // coluna da direita

        echo "</div>"; // linha de duas colunas
        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * Faixa de atribuicao do piso da DGO aberta.
     *
     * E' aqui que a DGO entra num piso: o cadastro em Listas suspensas cria o
     * piso, mas sem este seletor nao existe nenhum caminho na interface para
     * dizer em qual piso a DGO esta - e o filtro da barra ficaria sempre vazio.
     *
     * Quando a localizacao nao tem piso cadastrado, mostra uma dica em vez de
     * um seletor vazio (licao 16: estado vazio mudo parece defeito).
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param int                $floors_id filtro em vigor na barra
     * @return void
     */
    private static function displayFloorAssignment(PassiveDCEquipment $dgo, int $locations_id, int $floors_id): void
    {
        $items_id = (int) $dgo->getID();
        $floors   = Floor::getForLocation($locations_id);
        $current  = Panel::getFloorForItem($dgo);

        // mb-3 removido no 4b-2: o espacamento agora e' do wrapper que abraca
        // este seletor e a faixa de entradas. Mantido aqui, empurraria a faixa
        // para fora do alinhamento por 1rem.
        echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
        echo "<span class='text-muted d-flex align-items-center gap-1'>"
            . "<i class='ti ti-building'></i> " . htmlescape(Floor::getTypeName(1)) . "</span>";

        if ($floors === []) {
            echo "<span class='text-muted small'>"
                . htmlescape(__('Nenhum piso cadastrado nesta localização — cadastre em Configurar > Listas suspensas > Pisos.', 'dgoplus'))
                . "</span>";
            echo "</div>";
            return;
        }

        if (!Session::haveRight(Port::$rightname, UPDATE)) {
            echo "<span class='badge bg-blue-lt'>"
                . htmlescape($current > 0 ? ($floors[$current] ?? ('#' . $current)) : __('não atribuído', 'dgoplus'))
                . "</span>";
            echo "</div>";
            return;
        }

        echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "' id='dgoplus-setfloor-form'>";
        echo Html::hidden('action', ['value' => 'set_floor']);
        echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
        echo Html::hidden('items_id', ['value' => $items_id]);
        echo Html::hidden('locations_id', ['value' => $locations_id]);
        echo Html::hidden('floor', ['value' => $floors_id]);
        Dropdown::showFromArray('plugin_dgoplus_floors_id', $floors, [
            'value'               => $current,
            'width'               => '180px',
            'display_emptychoice' => true,
            'emptylabel'          => __('não atribuído', 'dgoplus'),
            'on_change'           => 'document.getElementById("dgoplus-setfloor-form").submit();',
        ]);
        Html::closeForm();

        echo "</div>";
    }

    /**
     * Faixa das entradas E1-E4 mais o campo de OBS, na linha do Piso.
     *
     * Bloco 4b-2, e o desenho e' do usuario: quatro caixas pequenas e um campo
     * largo ao lado, ANTES da grade - nao um card acima dela.
     *
     * So' em papel que RECEBE alimentacao - todo papel abaixo do primeiro do
     * registro (Setting::roleReceivesFeed(), ponto unico desde o 4h). O topo
     * da hierarquia (DIO) fica de fora: ninguem alimenta um DIO, entao quatro
     * caixas eternamente livres seriam ruido permanente na tela dele.
     * Elemento sem papel mapeado tambem fica de fora - sem papel nao ha como
     * afirmar que ele recebe alimentacao.
     *
     * As quatro caixas aparecem SEMPRE, mesmo sem linha no banco: a faixa e' um
     * mapa de slots fisicos, e slot livre e' informacao, nao ausencia dela. Por
     * isso elas ficam FORA do contador de ocupacao - entrada nao e' porta
     * alugavel, e' por onde a fibra chega.
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param int                $floors_id
     * @param string             $edit_key porta aberta no painel, preservada no redirect
     * @return void
     */
    private static function displayEntryStrip(
        PassiveDCEquipment $dgo,
        int $locations_id,
        int $floors_id,
        string $edit_key,
        int $entry_slot = 0
    ): void {
        $role = Setting::getRoleOfItem($dgo);

        if (!Setting::roleReceivesFeed($role)) {
            return;
        }

        $items_id = (int) $dgo->getID();

        // Uma consulta para as entradas e uma para os vinculos das quatro -
        // nunca uma por caixa.
        $entries = Port::entriesForItem(PassiveDCEquipment::class, $items_id);

        $entry_ids = [];
        foreach ($entries as $row) {
            $entry_ids[] = (int) $row['id'];
        }

        $links = Link::findByDestinations($entry_ids);

        echo "<div class='d-flex align-items-center gap-2 flex-wrap' style='flex:1 1 auto;min-width:0'>";

        for ($slot = 1; $slot <= Port::MAX_ENTRIES; $slot++) {
            $entry = $entries[$slot] ?? null;
            $link  = $entry !== null ? ($links[(int) $entry['id']] ?? null) : null;

            // Bloco 4c-2: a caixa ALTERNA o card. Aberta, o link dela aponta
            // para a URL SEM o 'entry' - clicar de novo fecha. Sem isto o card
            // ficava fixo na tela e nao havia caminho de volta pela interface.
            $box_url = '';
            if ($link !== null) {
                $extra = ['dgo' => $items_id];
                if ($slot !== $entry_slot) {
                    $extra['entry'] = $slot;
                }
                if ($edit_key !== '') {
                    $extra['edit'] = $edit_key;
                }
                $box_url = self::getPageUrl(self::scope($locations_id, $floors_id, $extra));
            }

            echo self::renderEntryBox($slot, $entry, $links, $box_url, $slot === $entry_slot);
        }

        self::displayEntryObs($dgo, $locations_id, $floors_id, $edit_key);

        echo "</div>";
    }

    /**
     * Uma caixa da faixa de entradas.
     *
     * Mostra so' E<n> e o estado. QUEM alimenta vai no title (hover): o nome do
     * elemento de origem nao cabe numa caixa desta largura, e cortado com
     * reticencias seria pior que ausente.
     *
     * Vinculo pendente usa BORDA TRACEJADA, nao uma quinta cor - decisao
     * fechada da Fase 4. Pendente ja ocupa a entrada, entao pintar de "livre"
     * seria mentira e pintar de "ocupada" esconderia que falta confirmar.
     *
     * Caixa com vinculo e' um LINK para o card (?entry=N) - bloco 4c; caixa
     * livre continua um div inerte. A caixa aberta ganha a mesma marca de
     * "aberto" das celulas da grade: box-shadow INSET, nunca outline
     * (licao 27 - o outline seria cortado pelo overflow do container).
     *
     * @param int        $slot
     * @param array|null $entry   linha da porta de entrada, ou null se nem existe
     * @param array      $links   vinculos indexados por porta de destino
     * @param string     $box_url URL do card; vazia = caixa nao clicavel
     * @param bool       $open    e' a entrada aberta no card?
     * @return string
     */
    private static function renderEntryBox(
        int $slot,
        ?array $entry,
        array $links,
        string $box_url = '',
        bool $open = false
    ): string {
        $label = Port::formatEntryLabel($slot);

        $state   = __('livre', 'dgoplus');
        $title   = sprintf(__('Entrada %s — livre', 'dgoplus'), $label);
        $bg      = self::CELL_FREE_BG;
        $border  = '1px solid ' . self::CELL_FREE_BORDER;
        $muted   = true;

        $link = $entry !== null ? ($links[(int) $entry['id']] ?? null) : null;

        if ($link !== null) {
            $origin  = Link::describeOrigin((int) $link['plugin_dgoplus_ports_id_src']);
            $pending = (string) ($link['status'] ?? '') !== Link::STATUS_CONFIRMED;

            $state = $origin['ok'] ? $origin['label'] : __('origem removida', 'dgoplus');
            $muted = !$origin['ok'];

            $title = $origin['ok']
                ? sprintf(
                    $pending
                        ? __('Entrada %1$s — proposta de %2$s (%3$s), aguardando confirmação', 'dgoplus')
                        : __('Entrada %1$s — alimentada por %2$s (%3$s)', 'dgoplus'),
                    $label,
                    $origin['label'],
                    $origin['item']
                )
                : sprintf(__('Entrada %s — o vínculo aponta para uma porta que não existe mais', 'dgoplus'), $label);

            $bg     = self::CELL_DOC_BG;
            $border = $pending
                ? '1px dashed ' . self::CELL_DOC_BORDER
                : '1px solid ' . self::CELL_DOC_BORDER;
        }

        $style = "min-width:56px;text-align:center;padding:3px 6px;border-radius:6px;"
            . "background:" . $bg . ";border:" . $border . ";line-height:1.25";

        if ($open) {
            $style .= ";box-shadow:inset 0 0 0 2px " . self::ACCENT;
        }

        $inner  = "<div style='font-size:11.5px;font-weight:500'>" . htmlescape($label) . "</div>";
        $inner .= "<div class='" . ($muted ? 'text-muted' : '') . "' style='font-size:10.5px'>"
            . htmlescape($state) . "</div>";

        if ($box_url !== '') {
            return "<a href='" . htmlescape($box_url) . "#dgoplus-entry-card' class='text-decoration-none'"
                . " style='" . $style . ";color:inherit;display:block' title='" . htmlescape($title) . "'>"
                . $inner . "</a>";
        }

        return "<div style='" . $style . "' title='" . htmlescape($title) . "'>" . $inner . "</div>";
    }

    /**
     * Campo de OBS do elemento, ao lado das quatro entradas.
     *
     * Um campo de texto livre por elemento: splitagem, fibra redundante, numero
     * de fusao - o que for. Foi ele que resolveu o C1 (redundancia se registra
     * em texto, nao em coluna), e por isso ele nasce junto com as entradas e
     * nao num bloco depois.
     *
     * Mora em Panel::comment. Nao confundir com o comentario do ativo nativo
     * (bloco 3t), que fica no card da coluna da direita.
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param int                $floors_id
     * @param string             $edit_key
     * @return void
     */
    private static function displayEntryObs(
        PassiveDCEquipment $dgo,
        int $locations_id,
        int $floors_id,
        string $edit_key
    ): void {
        $items_id = (int) $dgo->getID();
        $current  = Panel::getCommentForItem($dgo);

        if (!Session::haveRight(Port::$rightname, UPDATE)) {
            if ($current === '') {
                return;
            }

            echo "<span class='text-muted' style='font-size:12px'>"
                . "<i class='ti ti-note'></i> " . htmlescape($current) . "</span>";

            return;
        }

        echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'"
            . " class='d-flex align-items-center gap-2' style='flex:1 1 220px;min-width:180px'>";
        echo Html::hidden('action', ['value' => 'save_entry_obs']);
        echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
        echo Html::hidden('items_id', ['value' => $items_id]);
        echo Html::hidden('locations_id', ['value' => $locations_id]);
        echo Html::hidden('floor', ['value' => $floors_id]);

        if ($edit_key !== '') {
            echo Html::hidden('edit', ['value' => $edit_key]);
        }

        echo Html::input('obs', [
            'value'       => $current,
            'class'       => 'form-control form-control-sm',
            'style'       => 'flex:1 1 auto;min-width:0',
            'maxlength'   => 255,
            'placeholder' => __('OBS: splitter, fibra reserva, fusão…', 'dgoplus'),
        ]);

        echo Html::submit(__('Salvar', 'dgoplus'), ['class' => 'btn btn-sm btn-outline-primary']);
        Html::closeForm();
    }

    /**
     * Card da entrada aberta (?entry=N): quem alimenta, quem propos e os
     * botoes do fluxo. Bloco 4c.
     *
     * Renderiza NADA quando o slot nao tem vinculo: caixa livre nao e'
     * clicavel, entao chegar aqui com slot livre e' URL editada a mao - e
     * responder em silencio e' melhor que inventar um card vazio.
     *
     * Confirmar e Recusar pedem UPDATE (as duas metades da mesma resposta ao
     * proponente); Desmontar pede DELETE (destroi documentacao estabelecida).
     * Autoconfirmacao e' permitida: o botao nao distingue quem propos - o
     * registro de QUEM confirmou (users_id_confirmer) e' a auditoria.
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param int                $floors_id
     * @param string             $edit_key
     * @param int                $entry_slot
     * @return void
     */
    private static function displayEntryCard(
        PassiveDCEquipment $dgo,
        int $locations_id,
        int $floors_id,
        string $edit_key,
        int $entry_slot
    ): void {
        if ($entry_slot < 1 || $entry_slot > Port::MAX_ENTRIES) {
            return;
        }

        $role = Setting::getRoleOfItem($dgo);
        if (!Setting::roleReceivesFeed($role)) {
            return;
        }

        $items_id = (int) $dgo->getID();
        $entries  = Port::entriesForItem(PassiveDCEquipment::class, $items_id);
        $entry    = $entries[$entry_slot] ?? null;

        if ($entry === null) {
            return;
        }

        $links = Link::findByDestinations([(int) $entry['id']]);
        $link  = $links[(int) $entry['id']] ?? null;

        if ($link === null) {
            return;
        }

        $pending = (string) ($link['status'] ?? '') !== Link::STATUS_CONFIRMED;
        $origin  = Link::describeOrigin((int) $link['plugin_dgoplus_ports_id_src']);
        $label   = Port::formatEntryLabel($entry_slot);

        // Link para abrir o elemento de ORIGEM no mapa: quem confirma quer
        // conferir de onde vem antes de clicar. getUrlForDgo ja e' o ponto
        // unico dessa URL (licao 13).
        $origin_url = '';
        $src_port   = new Port();
        if ($origin['ok'] && $src_port->getFromDB((int) $link['plugin_dgoplus_ports_id_src'])) {
            $src_item = new PassiveDCEquipment();
            if ($src_item->getFromDB((int) ($src_port->fields['items_id'] ?? 0))) {
                $origin_url = self::getUrlForDgo($src_item);
            }
        }

        echo "<div id='dgoplus-entry-card' class='card mb-3' style='max-width:520px'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between gap-2'>";
        echo "<h3 class='card-title mb-0'>" . sprintf(__('Entrada %s', 'dgoplus'), htmlescape($label)) . "</h3>";
        echo "<span class='d-flex align-items-center gap-2'>";
        echo $pending
            ? "<span class='badge bg-yellow-lt'>" . __('pendente', 'dgoplus') . "</span>"
            : "<span class='badge bg-green-lt'>" . __('confirmado', 'dgoplus') . "</span>";

        // Bloco 4c-2: fechar o card. Mesma URL da tela sem o 'entry' - quem
        // rolou a pagina nao precisa voltar ate a faixa para recolher.
        $close_extra = ['dgo' => $items_id];
        if ($edit_key !== '') {
            $close_extra['edit'] = $edit_key;
        }
        echo "<a href='" . htmlescape(self::getPageUrl(self::scope($locations_id, $floors_id, $close_extra)))
            . "' class='text-muted text-decoration-none' title='" . htmlescape(__('Fechar', 'dgoplus')) . "'"
            . " aria-label='" . htmlescape(__('Fechar', 'dgoplus')) . "'>&times;</a>";
        echo "</span>";
        echo "</div>";

        echo "<div class='card-body py-2'>";

        echo "<div class='d-flex justify-content-between gap-3 py-1'>";
        echo "<span class='text-muted'>" . __('Alimentada por', 'dgoplus') . "</span>";
        if ($origin['ok']) {
            $item_html = $origin_url !== ''
                ? "<a href='" . htmlescape($origin_url) . "'>" . htmlescape($origin['item']) . "</a>"
                : htmlescape($origin['item']);
            echo "<span>" . $item_html . " · " . htmlescape($origin['label']) . "</span>";
        } else {
            echo "<span class='text-muted'>" . __('origem removida', 'dgoplus') . "</span>";
        }
        echo "</div>";

        // Bloco 4e: a trilha completa ate o topo, entre "Alimentada por" e
        // "Proposto por" - posicao literal do desenho aprovado. So' vinculo
        // CONFIRMADO sobe (decisao do 4e); sem nenhum nivel confirmado acima,
        // a linha nem aparece - trilha de um elemento so' nao informa nada.
        $levels = Link::upstreamLevels(PassiveDCEquipment::class, $items_id);

        if ($levels !== []) {
            echo "<div class='py-2 my-1 border-top border-bottom'>";
            echo "<div class='text-muted' style='font-size:12px'>" . __('Trilha', 'dgoplus') . "</div>";
            echo "<div class='d-flex align-items-center gap-1 flex-wrap mt-1'>";

            // Os niveis chegam de baixo para cima (0 = pais diretos) e a
            // trilha desenha da esquerda para a direita a partir do TOPO -
            // por isso o reverse. Nivel com mais de um pai mostra todos lado
            // a lado (decisao do 4e).
            foreach (array_reverse($levels) as $level) {
                foreach ($level as $i => $node) {
                    // O "+" separa pais do MESMO nivel; sem ele, dois chips
                    // adjacentes leem como um degrau da trilha (visto na
                    // renderizacao, nao deduzido - licao 104).
                    if ($i > 0) {
                        echo "<span class='text-muted'>+</span>";
                    }
                    echo self::renderTrailChip($node['row'], $node['role']);
                }
                echo "<i class='ti ti-arrow-right text-muted'></i>";
            }

            echo "<span class='badge bg-blue-lt'>"
                . htmlescape(sprintf(__('%s · aqui', 'dgoplus'), $label)) . "</span>";
            echo "</div>";
            echo "<div class='form-hint mt-1'>"
                . htmlescape(__('Só vínculos confirmados sobem na trilha.', 'dgoplus')) . "</div>";
            echo "</div>";
        }

        echo "<div class='d-flex justify-content-between gap-3 py-1'>";
        echo "<span class='text-muted'>" . __('Proposto por', 'dgoplus') . "</span>";
        echo "<span>" . htmlescape((string) getUserName((int) ($link['users_id_proposer'] ?? 0)))
            . " <span class='text-muted'>· " . htmlescape(Html::convDateTime($link['date_creation'] ?? null)) . "</span></span>";
        echo "</div>";

        if (!$pending) {
            echo "<div class='d-flex justify-content-between gap-3 py-1'>";
            echo "<span class='text-muted'>" . __('Confirmado por', 'dgoplus') . "</span>";
            echo "<span>" . htmlescape((string) getUserName((int) ($link['users_id_confirmer'] ?? 0))) . "</span>";
            echo "</div>";
        }

        $can_respond = Session::haveRight(Port::$rightname, UPDATE);
        $can_break   = Session::haveRight(Port::$rightname, DELETE);

        if ($pending && $can_respond) {
            echo "<div class='d-flex gap-2 mt-2'>";

            echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
            echo Html::hidden('action', ['value' => 'confirm_link']);
            echo Html::hidden('link_id', ['value' => (int) $link['id']]);
            echo Html::hidden('items_id', ['value' => $items_id]);
            echo Html::hidden('locations_id', ['value' => $locations_id]);
            echo Html::hidden('floor', ['value' => $floors_id]);
            echo Html::hidden('entry', ['value' => $entry_slot]);
            echo Html::submit(__('Confirmar', 'dgoplus'), ['class' => 'btn btn-sm btn-primary']);
            Html::closeForm();

            echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
            echo Html::hidden('action', ['value' => 'refuse_link']);
            echo Html::hidden('link_id', ['value' => (int) $link['id']]);
            echo Html::hidden('items_id', ['value' => $items_id]);
            echo Html::hidden('locations_id', ['value' => $locations_id]);
            echo Html::hidden('floor', ['value' => $floors_id]);
            echo Html::submit(__('Recusar', 'dgoplus'), ['class' => 'btn btn-sm btn-outline-danger']);
            Html::closeForm();

            echo "</div>";
            echo "<div class='form-hint mt-1'>"
                . htmlescape(__('Recusar apaga a proposta e libera a entrada e a porta de origem (se vazia).', 'dgoplus'))
                . "</div>";
        }

        if (!$pending && $can_break) {
            echo "<div class='d-flex mt-2'>";
            echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'>";
            echo Html::hidden('action', ['value' => 'dismantle_link']);
            echo Html::hidden('link_id', ['value' => (int) $link['id']]);
            echo Html::hidden('items_id', ['value' => $items_id]);
            echo Html::hidden('locations_id', ['value' => $locations_id]);
            echo Html::hidden('floor', ['value' => $floors_id]);
            echo Html::submit(__('Desmontar vínculo', 'dgoplus'), ['class' => 'btn btn-sm btn-outline-danger']);
            Html::closeForm();
            echo "</div>";
        }

        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * Documentos ligados a DGO, ja com restricao de entidade e sem os que
     * estao na lixeira. Usa o criterio nativo (Document_Item, 11.0.6:896)
     * para nao reimplementar o JOIN nem a regra de entidade.
     *
     * @param PassiveDCEquipment $dgo
     * @return array<int, array>
     */
    private static function getDocumentsForDgo(PassiveDCEquipment $dgo): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!Document::canView()) {
            return [];
        }

        $criteria = Document_Item::getDocumentForItemRequest($dgo, ['assocdate DESC']);

        // Chave distinta das que o core ja pos no WHERE - nao sobrescreve nada.
        $criteria['WHERE']['glpi_documents.is_deleted'] = 0;

        $out = [];
        foreach ($DB->request($criteria) as $row) {
            $out[] = $row;
        }

        return $out;
    }

    /**
     * URL de download / miniatura de um documento.
     *
     * itemtype e items_id vao junto de proposito: com eles a checagem nativa
     * Document::canViewFile() libera quem tem direito no ativo, mesmo sem
     * direito global de Documento.
     *
     * @param int $documents_id
     * @param int $items_id
     * @return string
     */
    private static function documentUrl(int $documents_id, int $items_id): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return ($CFG_GLPI['root_doc'] ?? '') . '/front/document.send.php?' . http_build_query([
            'docid'    => $documents_id,
            'itemtype' => PassiveDCEquipment::class,
            'items_id' => $items_id,
        ]);
    }

    /**
     * Card compacto de anexos, na coluna da direita.
     *
     * So miniatura + nome + contador. O componente nativo de anexo
     * (Document_Item::showForItem) NAO cabe aqui: sao dois cards lado a lado
     * mais uma tabela de 8 colunas - em coluna estreita a ultima coluna some
     * sem avisar (licao 20). Ele vai em largura total, em displayDocumentsManager().
     *
     * Bloco 3t: este metodo NAO abre mais a <div> da coluna. Quem abre e' o
     * displayGrid, porque a coluna agora tem tres cards (QR, anexos,
     * comentario) e nao so este.
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param string             $edit_key
     * @return void
     */
    /**
     * Um elemento da trilha, como pastilha clicavel. Bloco 4e.
     *
     * Recebe a LINHA do find() (nao um objeto carregado) e hidrata um
     * PassiveDCEquipment so' para a URL: getUrlForDgo le locations_id dos
     * fields e getID()/getType() do proprio objeto, entao a linha completa
     * basta - e recarregar do banco seria uma consulta por pastilha, a
     * leitura em laco que o 4d aboliu.
     *
     * A sigla do papel entra na frente do nome, a mesma linguagem que o 4a-3
     * pos no seletor ("DIO · nome"). Papel nulo (Tipo nao mapeado) mostra so'
     * o nome.
     *
     * @param array       $row  linha de glpi_passivedcequipments
     * @param string|null $role
     * @return string
     */
    private static function renderTrailChip(array $row, ?string $role): string
    {
        $el         = new PassiveDCEquipment();
        $el->fields = $row;

        $name = (string) ($row['name'] ?? '');
        if ($name === '') {
            $name = '#' . (int) ($row['id'] ?? 0);
        }

        $text = ($role !== null ? Setting::getRoleLabel($role) . ' · ' : '') . $name;

        return "<a href='" . htmlescape(self::getUrlForDgo($el)) . "'"
            . " class='badge bg-secondary-lt text-decoration-none'>"
            . htmlescape($text) . "</a>";
    }

    /**
     * Card "Alimenta" da coluna da direita: quem este elemento alimenta,
     * agrupado por elemento de destino. Bloco 4e.
     *
     * SO' LE E NAVEGA (decisao da Fase 4, a mesma das telas de pendencia):
     * propor, confirmar, recusar e desmontar continuam no card da entrada do
     * elemento de destino e no painel da porta de origem. Um segundo lugar
     * escrevendo vinculo seria um segundo lugar para errar.
     *
     * So' aparece em papel que tem nivel abaixo (mesma regra do
     * displayFeedSection): CTO nem renderiza o card, porque CTO nao propoe.
     * Pendente entra na lista com selo amarelo (decisao do 4e).
     *
     * Nome do destino em text-nowrap com reticencias: sem isso a quebra cai
     * dentro do nome, o defeito que o 4d viu na renderizacao ("Teste /
     * drop 001").
     *
     * @param PassiveDCEquipment $dgo
     * @return void
     */
    private static function displayFeedsCard(PassiveDCEquipment $dgo): void
    {
        $role  = Setting::getRoleOfItem($dgo);
        $roles = Setting::getRoles();
        $pos   = $role !== null ? array_search($role, $roles, true) : false;

        if ($pos === false || array_slice($roles, (int) $pos + 1) === []) {
            return;
        }

        $groups = Link::downstreamOf(PassiveDCEquipment::class, (int) $dgo->getID());

        echo "<div class='card mb-3'>";

        echo "<div class='card-header d-flex align-items-center gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-plug'></i>" . __('Alimenta', 'dgoplus') . "</h3>";
        echo "<span class='badge bg-secondary-lt ms-auto'>" . count($groups) . "</span>";
        echo "</div>";

        echo "<div class='card-body py-2'>";

        if ($groups === []) {
            // Estado vazio explicito (licao 16: vazio mudo parece defeito).
            echo "<span class='text-muted'>" . __('Nenhum vínculo de saída.', 'dgoplus') . "</span>";
        }

        foreach ($groups as $i => $group) {
            if ($i > 0) {
                echo "<hr class='my-2'>";
            }

            $el         = new PassiveDCEquipment();
            $el->fields = $group['row'];

            $name = (string) ($group['row']['name'] ?? '');
            if ($name === '') {
                $name = '#' . (int) ($group['row']['id'] ?? 0);
            }

            $text = ($group['role'] !== null ? Setting::getRoleLabel((string) $group['role']) . ' · ' : '')
                . $name;

            echo "<div class='text-nowrap' style='overflow:hidden;text-overflow:ellipsis'>";
            echo "<a href='" . htmlescape(self::getUrlForDgo($el)) . "'>" . htmlescape($text) . "</a>";
            echo "</div>";

            foreach ($group['links'] as $pair) {
                echo "<div class='d-flex align-items-center gap-2 mt-1'>";
                echo "<span class='font-monospace text-secondary' style='font-size:12.5px'>"
                    . htmlescape($pair['src_label'])
                    . " <i class='ti ti-arrow-right'></i> "
                    . htmlescape($pair['dst_label'])
                    . "</span>";
                echo $pair['pending']
                    ? "<span class='badge bg-yellow-lt'>" . __('pendente', 'dgoplus') . "</span>"
                    : "<span class='badge bg-green-lt'>" . __('confirmado', 'dgoplus') . "</span>";
                echo "</div>";
            }
        }

        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    private static function displayAttachmentsSidebar(PassiveDCEquipment $dgo, int $locations_id, string $edit_key, int $floors_id = 0): void
    {
        $items_id = (int) $dgo->getID();

        echo "<div class='card mb-3'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-files'></i>" . __('Anexos do elemento', 'dgoplus') . "</h3>";

        if (!Document::canView()) {
            echo "</div><div class='card-body'>";
            echo "<div class='text-muted small'>"
                . htmlescape(__('Seu perfil não tem direito de ver Documentos.', 'dgoplus'))
                . "</div>";
            echo "</div></div>";
            return;
        }

        $documents = self::getDocumentsForDgo($dgo);

        echo "<span class='badge bg-blue-lt'>" . count($documents) . "</span>";
        echo "</div>";

        echo "<div class='card-body'>";

        if ($documents === []) {
            // Estado vazio mudo parece defeito (licao 16).
            echo "<div class='text-muted small mb-3'>"
                . htmlescape(__('Nenhuma foto ou documento anexado a esta DGO.', 'dgoplus'))
                . "</div>";
        } else {
            echo "<div class='d-flex flex-wrap gap-2 mb-3'>";

            foreach ($documents as $doc) {
                $doc_id = (int) $doc['id'];
                $mime   = (string) ($doc['mime'] ?? '');
                $label  = (string) ($doc['name'] ?: ($doc['filename'] ?? '#' . $doc_id));
                $url    = self::documentUrl($doc_id, $items_id);

                echo "<a href='" . htmlescape($url) . "' target='_blank' rel='noopener'"
                    . " class='text-decoration-none text-muted' title='" . htmlescape($label) . "'"
                    . " style='display:block;width:74px'>";

                echo "<span style='display:flex;align-items:center;justify-content:center;"
                    . "width:74px;height:60px;border-radius:6px;overflow:hidden;"
                    . "background:" . self::CELL_FREE_BG . ";border:1px solid " . self::CELL_FREE_BORDER . "'>";

                if (str_starts_with($mime, 'image/')) {
                    echo "<img src='" . htmlescape($url) . "' alt='" . htmlescape($label) . "'"
                        . " style='width:100%;height:100%;object-fit:cover'>";
                } else {
                    $icon = ($mime === 'application/pdf') ? 'ti-file-type-pdf' : 'ti-file';
                    echo "<i class='ti " . $icon . "' style='font-size:22px'></i>";
                }

                echo "</span>";
                echo "<span class='d-block text-truncate' style='font-size:9.5px'>" . htmlescape($label) . "</span>";
                echo "</a>";
            }

            echo "</div>";
        }

        $params = self::scope($locations_id, $floors_id, ['dgo' => $items_id]);
        if ($edit_key !== '') {
            $params['edit'] = $edit_key;
        }
        $params['docs'] = 1;

        echo "<a class='btn btn-sm btn-outline-primary' href='"
            . htmlescape(self::getPageUrl($params) . '#dgoplus-docs') . "'>"
            . __('Gerenciar anexos', 'dgoplus') . "</a>";

        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * Bloco nativo de anexo, em largura total, abaixo da grade.
     *
     * Document_Item::showForItem() = formulario de envio + "associar existente"
     * + tabela completa. O envio posta em front/document.form.php levando
     * itemtype/items_id escondidos, e por isso o core termina em Html::back()
     * (11.0.6, front/document.form.php:83) - o usuario volta exatamente para
     * esta pagina, com localizacao e DGO ainda selecionadas.
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param string             $edit_key
     * @return void
     */
    private static function displayDocumentsManager(PassiveDCEquipment $dgo, int $locations_id, string $edit_key, int $floors_id = 0): void
    {
        echo "<div id='dgoplus-docs'></div>";

        if ((int) ($_GET['docs'] ?? 0) !== 1) {
            return;
        }

        $items_id = (int) $dgo->getID();

        $back = self::scope($locations_id, $floors_id, ['dgo' => $items_id]);
        if ($edit_key !== '') {
            $back['edit'] = $edit_key;
        }

        echo "<div class='card mb-3'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between flex-wrap gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-files'></i>"
            . htmlescape($dgo->fields['name'] ?: ('#' . $items_id))
            . "<span class='text-muted'>" . __('Anexos', 'dgoplus') . "</span></h3>";
        echo "<a class='btn btn-sm btn-outline-secondary' href='"
            . htmlescape(self::getPageUrl($back)) . "'>" . __('Fechar anexos', 'dgoplus') . "</a>";
        echo "</div>";

        echo "<div class='card-body'>";

        // Devolve false sem imprimir nada se faltar direito de Documento ou de
        // leitura no ativo - sem este aviso a area ficaria em branco (licao 16).
        if (!Document_Item::showForItem($dgo)) {
            echo "<div class='alert alert-info py-2' role='alert'>"
                . htmlescape(__('Seu perfil não permite ver ou anexar Documentos neste ativo.', 'dgoplus'))
                . "</div>";
        }

        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param string             $edit_key
     * @return void
     */
    private static function displayEditPanel(PassiveDCEquipment $dgo, int $locations_id, string $edit_key, int $floors_id = 0): void
    {
        echo "<div id='dgoplus-panel'></div>";

        if ($edit_key === '' || !str_contains($edit_key, '-')) {
            self::displayEmptyState(
                __('Clique em uma posição da grade para documentar a porta.', 'dgoplus')
            );
            return;
        }

        [$tube_num, $fiber_num] = array_map('intval', explode('-', $edit_key, 2));
        $items_id               = (int) $dgo->getID();

        // is_deleted = 0 aqui de proposito: uma porta na lixeira aparece como
        // "livre" na grade, entao o painel tem que mostrar campos vazios tambem.
        $port  = new Port();
        $found = $port->getFromDBByCrit([
            'itemtype'   => PassiveDCEquipment::class,
            'items_id'   => $items_id,
            'tube_num'   => $tube_num,
            'fiber_num'  => $fiber_num,
            'is_deleted' => 0,
        ]);

        $code       = $found ? $port->fields['code'] : '';
        $comment    = $found ? $port->fields['comment'] : '';
        $no_coupler = $found && (int) ($port->fields['is_no_coupler'] ?? 0) === 1;

        // Bloco 5f-1a: a trava da tela tem que perguntar EXATAMENTE o que
        // Port::applyInput vai exigir - se divergirem, o campo aparece
        // editavel e o Salvar bate em 403 (ou o contrario: campo bloqueado
        // para quem podia gravar). Antes era "$found ? UPDATE : CREATE".
        $can_write = Session::haveRight(Port::$rightname, UPDATE);

        // Html::formatAttribute() renderiza QUALQUER valor como atributo,
        // inclusive false -> readonly="" (que em HTML e' readonly ativo).
        // Por isso a chave so entra no array quando e' para bloquear mesmo.
        $input_opts = [];
        if (!$can_write) {
            $input_opts['readonly'] = 'readonly';
        }

        echo "<div class='card'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between flex-wrap gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>";
        echo "<span style='display:inline-block;width:8px;height:8px;border-radius:50%;"
            . "background:" . self::fiberColor($fiber_num) . "'></span>";
        echo htmlescape($dgo->fields['name'] ?: ('#' . $items_id));
        echo "<span class='text-muted'>"
            . sprintf(__('Fileira %d, coluna %d', 'dgoplus'), $tube_num, $fiber_num)
            . "</span></h3>";
        echo "<span class='badge bg-blue-lt'>" . htmlescape(Port::formatPosition(
            $tube_num,
            $fiber_num,
            Panel::getLayoutForItem($dgo)['fibers_per_tube']
        )) . "</span>";
        echo "</div>";

        echo "<div class='card-body'>";

        if (!$can_write) {
            echo "<div class='alert alert-info py-2' role='alert'>"
                . htmlescape(__('Você tem permissão apenas de leitura nesta porta.', 'dgoplus'))
                . "</div>";
        }

        // data-dgoplus-port-form: o JS do bloco 4a se prende a este formulario
        // para salvar por AJAX. O form continua sendo um POST normal e completo
        // - se o JS nao carregar, o botao Salvar funciona como antes.
        // Bloco 4a-3: o endpoint leva o papel na query para as celulas que o
        // AJAX redesenha manterem o filtro nos links (renderCell -> scope le
        // o $_GET da requisicao AJAX, nao o da pagina).
        $ajax_url = self::getAjaxUrl('port.php');
        $role     = Dashboard::currentRole();
        if ($role !== null) {
            $ajax_url .= '?role=' . $role;
        }

        echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "'"
            . " data-dgoplus-port-form='1'"
            . " data-dgoplus-endpoint='" . htmlescape($ajax_url) . "'"
            . " data-dgoplus-cell-key='" . htmlescape($tube_num . '-' . $fiber_num) . "'>";
        echo Html::hidden('action', ['value' => 'save_port']);
        echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
        echo Html::hidden('items_id', ['value' => $items_id]);
        echo Html::hidden('tube_num', ['value' => $tube_num]);
        echo Html::hidden('fiber_num', ['value' => $fiber_num]);
        echo Html::hidden('locations_id', ['value' => $locations_id]);
        echo Html::hidden('floor', ['value' => $floors_id]);

        echo "<div class='row g-3'>";
        echo "<div class='col-12 col-md-6'>";
        echo "<label class='form-label'>" . __('Nome / Número (Loja)', 'dgoplus') . "</label>";
        echo Html::input('code', ['value' => $code, 'placeholder' => __('ex.: 0155 — Loja L2', 'dgoplus')] + $input_opts);
        echo "</div>";

        // Terceiro estado, no espaco que o campo aposentado liberou.
        // 'form-check form-switch' e' o interruptor que o core usa em 17
        // templates do 11.0.6 - nada de classe inventada (licao 21).
        echo "<div class='col-12 col-md-6'>";
        echo "<label class='form-label'>" . __('Estado da porta', 'dgoplus') . "</label>";
        echo "<label class='form-check form-switch'>";
        echo "<input type='hidden' name='is_no_coupler' value='0'>";
        echo "<input class='form-check-input' type='checkbox' name='is_no_coupler' value='1'"
            . ($no_coupler ? " checked='checked'" : '')
            . ($can_write ? '' : " disabled='disabled'") . ">";
        echo "<span class='form-check-label'>"
            . "<i class='ti ti-plug-off'></i> " . __('Sem acoplador', 'dgoplus') . "</span>";
        echo "</label>";
        echo "<div class='form-hint'>"
            . htmlescape(__('Porta sem acoplador instalado: não pode ser usada e não conta como documentada.', 'dgoplus'))
            . "</div>";
        echo "</div>";
        echo "</div>";

        echo "<div class='mt-3'>";
        echo "<label class='form-label'>" . __('Observações', 'dgoplus') . "</label>";
        echo "<textarea name='comment' class='form-control' rows='2' placeholder='"
            . htmlescape(__('Descreva o sinal da porta caso esteja vaga…', 'dgoplus')) . "'"
            . ($can_write ? '' : ' readonly') . ">" . htmlescape($comment) . "</textarea>";
        echo "</div>";

        if ($can_write) {
            echo "<div class='d-flex justify-content-end align-items-center gap-2 mt-3'>";
            // Aviso de estado do auto-save. Comeca vazio e so o JS escreve
            // aqui; sem JS o espaco fica invisivel e o botao e' o caminho.
            echo "<span data-dgoplus-flag='1' class='small' aria-live='polite'></span>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
        }

        Html::closeForm();

        // Bloco 4c: secao "Alimenta" - decisao aprovada: a proposta nasce
        // AQUI, no painel da porta de origem.
        self::displayFeedSection(
            $dgo,
            $locations_id,
            $floors_id,
            $tube_num,
            $fiber_num,
            $found ? (int) $port->getID() : 0,
            $no_coupler
        );

        if ($found && Session::haveRight(Port::$rightname, DELETE)) {
            echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "' class='d-flex justify-content-end'>";
            echo Html::hidden('action', ['value' => 'delete_port']);
            echo Html::hidden('id', ['value' => $port->getID()]);
            echo Html::hidden('items_id', ['value' => $items_id]);
            echo Html::hidden('locations_id', ['value' => $locations_id]);
            echo Html::hidden('floor', ['value' => $floors_id]);
            echo Html::submit(
                __('Excluir porta', 'dgoplus'),
                ['name' => 'delete', 'class' => 'btn btn-outline-danger btn-sm']
            );
            Html::closeForm();
        }

        echo "</div>"; // card-body
        echo "</div>"; // card
    }

    /**
     * Secao "Alimenta" do painel da porta. Bloco 4c.
     *
     * So' aparece quando o elemento tem papel E existe papel ABAIXO dele na
     * hierarquia: DIO e DGO alimentam; CTO e' a ponta - o que sai dela vai
     * para o cliente, fora do escopo do mapa. Papel novo no registro
     * (Setting::ROLES) entra aqui sozinho, sem papel escrito a mao.
     *
     * Tres estados:
     *  - a porta ja alimenta alguem: destino + status + Desmontar;
     *  - sem vinculo e usuario com UPDATE (bloco 5f-1b): formulario de proposta
     *    (elemento -> entrada, decisao aprovada dos dois campos);
     *  - sem direito ou porta sem acoplador: uma linha explicando o porque -
     *    secao que some sem explicacao parece defeito (licao 16).
     *
     * O seletor de entrada imprime E1-E4 SEMPRE habilitadas: quem desabilita
     * as ocupadas e' o JS (dgoplus.js), lendo o JSON embutido abaixo. Sem JS
     * o formulario continua valido - o servidor recusa entrada ocupada com
     * mensagem amigavel (Link::propose), que e' a unica validacao que conta.
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param int                $floors_id
     * @param int                $tube_num
     * @param int                $fiber_num
     * @param int                $port_id  id da linha da porta, ou 0 se a celula esta livre
     * @param bool               $no_coupler
     * @return void
     */
    private static function displayFeedSection(
        PassiveDCEquipment $dgo,
        int $locations_id,
        int $floors_id,
        int $tube_num,
        int $fiber_num,
        int $port_id,
        bool $no_coupler
    ): void {
        $role  = Setting::getRoleOfItem($dgo);
        $roles = Setting::getRoles();
        $pos   = $role !== null ? array_search($role, $roles, true) : false;

        if ($pos === false) {
            return;
        }

        $below = array_slice($roles, (int) $pos + 1);
        if ($below === []) {
            return;
        }

        $items_id = (int) $dgo->getID();
        $edit_key = $tube_num . '-' . $fiber_num;

        $link = null;
        if ($port_id > 0) {
            $links = Link::findByOrigins([$port_id]);
            $link  = $links[$port_id] ?? null;
        }

        echo "<div class='mt-3 pt-3 border-top'>";
        echo "<div class='d-flex align-items-center gap-2 mb-2'>";
        echo "<i class='ti ti-plug'></i><strong>" . __('Alimenta', 'dgoplus') . "</strong>";
        echo "</div>";

        if ($link !== null) {
            $pending = (string) ($link['status'] ?? '') !== Link::STATUS_CONFIRMED;
            $dst     = Link::describeDestination((int) ($link['plugin_dgoplus_ports_id_dst'] ?? 0));

            echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
            echo "<span>" . sprintf(
                __('%1$s de %2$s', 'dgoplus'),
                htmlescape($dst['ok'] ? $dst['label'] : '?'),
                htmlescape($dst['ok'] ? $dst['item'] : __('destino removido', 'dgoplus'))
            ) . "</span>";
            echo $pending
                ? "<span class='badge bg-yellow-lt'>" . __('pendente', 'dgoplus') . "</span>"
                : "<span class='badge bg-green-lt'>" . __('confirmado', 'dgoplus') . "</span>";

            if (Session::haveRight(Port::$rightname, DELETE)) {
                echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "' class='ms-auto'>";
                echo Html::hidden('action', ['value' => 'dismantle_link']);
                echo Html::hidden('link_id', ['value' => (int) $link['id']]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::hidden('edit', ['value' => $edit_key]);
                echo Html::submit(__('Desmontar vínculo', 'dgoplus'), ['class' => 'btn btn-sm btn-outline-danger']);
                Html::closeForm();
            }

            echo "</div>";
            echo "<div class='form-hint mt-1'>"
                . htmlescape($pending
                    ? __('Aguardando confirmação no elemento de destino. A porta já conta como ocupada.', 'dgoplus')
                    : __('Vínculo confirmado. Desmontar remove o vínculo dos dois lados.', 'dgoplus'))
                . "</div>";
            echo "</div>";
            return;
        }

        // Bloco 5f-1b: a trava da tela pergunta a MESMA coisa que o ponto
        // unico (Link::propose). Divergir aqui deixaria o formulario visivel
        // com o Salvar em 403, ou escondido para quem pode gravar.
        //
        // A mensagem nomeia o direito que falta e onde ele mora (licao 119):
        // "exige permissao de criacao" nao dizia a quem pedir nem o que pedir.
        if (!Session::haveRight(Port::$rightname, UPDATE)) {
            echo "<div class='form-hint'>"
                . htmlescape(__('Sem vínculo. Propor um vínculo exige a permissão "Atualizar" em "Portas de DGO" (Administração → Perfis → aba DGO+).', 'dgoplus'))
                . "</div>";
            echo "</div>";
            return;
        }

        if ($no_coupler) {
            echo "<div class='form-hint'>"
                . htmlescape(__('Porta sem acoplador não pode alimentar. Desmarque a opção para propor um vínculo.', 'dgoplus'))
                . "</div>";
            echo "</div>";
            return;
        }

        // Candidatos: elementos com papel ABAIXO deste, na entidade, fora da
        // lixeira. Papel sem Tipo mapeado nao contribui (getTypesForRole
        // devolve []). A uniao e' por id de Tipo, nunca por nome (licao 32).
        $type_ids = [];
        foreach ($below as $below_role) {
            foreach (Setting::getTypesForRole($below_role) as $tid) {
                $type_ids[] = (int) $tid;
            }
        }
        $type_ids = array_values(array_unique($type_ids));

        $candidates = [];
        if ($type_ids !== []) {
            $element    = new PassiveDCEquipment();
            $criteria   = getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true);
            $candidates = $element->find([
                'is_deleted'             => 0,
                Setting::getTypeField()  => $type_ids,
            ] + $criteria, ['name']);
        }

        if ($candidates === []) {
            echo "<div class='form-hint'>"
                . htmlescape(__('Nenhum elemento de papel abaixo na hierarquia está cadastrado para receber o vínculo.', 'dgoplus'))
                . "</div>";
            echo "</div>";
            return;
        }

        // Slots ja ocupados por elemento (pendente OCUPA, decisao fechada):
        // uma consulta para as entradas de todos os candidatos e uma para os
        // vinculos delas - nunca uma por elemento.
        $entry_port = new Port();
        $entry_rows = $entry_port->find([
            'itemtype'   => PassiveDCEquipment::class,
            'items_id'   => array_keys($candidates),
            'is_deleted' => 0,
        ] + Port::entryCriteria());

        $entry_ids = [];
        foreach ($entry_rows as $er) {
            $entry_ids[] = (int) $er['id'];
        }

        $dst_links = Link::findByDestinations($entry_ids);

        $occupied = [];
        foreach ($entry_rows as $er) {
            if (isset($dst_links[(int) $er['id']])) {
                $occupied[(int) $er['items_id']][] = (int) $er['fiber_num'];
            }
        }

        echo "<form method='post' action='" . htmlescape(self::getPostUrl()) . "' data-dgoplus-link-form='1'>";
        echo Html::hidden('action', ['value' => 'propose_link']);
        echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
        echo Html::hidden('items_id', ['value' => $items_id]);
        echo Html::hidden('tube_num', ['value' => $tube_num]);
        echo Html::hidden('fiber_num', ['value' => $fiber_num]);
        echo Html::hidden('locations_id', ['value' => $locations_id]);
        echo Html::hidden('floor', ['value' => $floors_id]);

        // Bloco 5a: escopo do destino - Localizacao > Piso > Elemento.
        //
        // O recorte usa o `locations_id` NATIVO do elemento, a mesma coluna
        // que o getDgosAtLocation() usa para as abas do mapa: e' o campo que
        // esta preenchido na base (medido em 22/08: 31 de 31 elementos), ao
        // contrario do piso, que mora na _panels e quase nunca esta atribuido.
        // Por isso o piso e' refinamento OPCIONAL, nunca corte obrigatorio.
        //
        // Os dois seletores NAO tem atributo `name`: sao controles de tela, o
        // POST continua sendo exatamente o do 4c (dst_items_id + dst_slot).
        // PROGRESSIVO, como o 4c: sem o JS os tres selects aparecem completos
        // e o formulario posta igual - o filtro e' conveniencia, nao validacao.
        $cand_floors = Panel::getFloorsForItems(PassiveDCEquipment::class, array_keys($candidates));

        $cand_scope = [];
        $loc_ids    = [];
        foreach ($candidates as $cid => $cand) {
            $cloc = (int) ($cand['locations_id'] ?? 0);
            $cand_scope[(int) $cid] = [
                'loc'   => $cloc,
                'floor' => (int) ($cand_floors[(int) $cid] ?? 0),
            ];
            if ($cloc > 0) {
                $loc_ids[$cloc] = $cloc;
            }
        }

        // So as localizacoes que TEM candidato entram na lista. Oferecer uma
        // localizacao vazia so' produz um seletor de elemento vazio, sem
        // dizer por que (licao 16: nada some sem explicacao).
        $loc_options = [];
        if ($loc_ids !== []) {
            $loc_item = new Location();
            $loc_rows = $loc_item->find(['id' => array_values($loc_ids)], ['completename']);
            foreach ($loc_rows as $lrow) {
                $loc_options[(int) $lrow['id']] = (string) ($lrow['completename'] ?: ($lrow['name'] ?? ''));
            }
        }

        // Pisos de todas essas localizacoes, com o dono de cada um, para o JS
        // poder podar o seletor de piso quando a localizacao muda.
        $floor_options = [];
        $floor_owner   = [];
        foreach ($loc_options as $lid => $lname) {
            foreach (Floor::getForLocation($lid) as $fid => $fname) {
                $floor_options[(int) $fid] = (string) $fname;
                $floor_owner[(int) $fid]   = (int) $lid;
            }
        }

        // Valor inicial: a localizacao onde o usuario JA esta. Vazio significa
        // "todas" - vinculo entre localizacoes diferentes continua possivel.
        $dst_loc_initial = array_key_exists($locations_id, $loc_options) ? $locations_id : 0;

        echo "<div class='row g-2 align-items-end'>";

        // Select nativo escrito a mao, como o dst_items_id e o dst_slot do 4c.
        // Dropdown::showFromArray renderiza select2, que esconde o <select>
        // real - opcao podada por JS continuaria visivel na caixa do select2.
        echo "<div class='col-12 col-md-6'>";
        echo "<label class='form-label mb-1'>" . htmlescape(Location::getTypeName(1)) . "</label>";
        echo "<select class='form-select form-select-sm' data-dgoplus-link-loc='1'>";
        echo "<option value='0'>" . htmlescape(__('Todas as localizações', 'dgoplus')) . "</option>";
        foreach ($loc_options as $lid => $lname) {
            echo "<option value='" . (int) $lid . "'"
                . ((int) $lid === $dst_loc_initial ? " selected='selected'" : '')
                . ">" . htmlescape($lname) . "</option>";
        }
        echo "</select>";
        echo "</div>";

        echo "<div class='col-12 col-md-6'>";
        echo "<label class='form-label mb-1'>" . htmlescape(Floor::getTypeName(1)) . "</label>";
        echo "<select class='form-select form-select-sm' data-dgoplus-link-floor='1'>";
        echo "<option value='0'>" . htmlescape(__('Todos os pisos', 'dgoplus')) . "</option>";
        foreach ($floor_options as $fid => $fname) {
            echo "<option value='" . (int) $fid . "'>" . htmlescape($fname) . "</option>";
        }
        echo "</select>";
        echo "</div>";

        echo "</div>";

        echo "<div class='row g-2 align-items-end mt-1'>";

        echo "<div class='col-12 col-md-5'>";
        echo "<label class='form-label mb-1'>" . __('Elemento de destino', 'dgoplus') . "</label>";
        echo "<select name='dst_items_id' class='form-select form-select-sm' data-dgoplus-link-dst='1'>";
        foreach ($candidates as $cid => $cand) {
            $cand_role  = Setting::getRoleForType((int) ($cand[Setting::getTypeField()] ?? 0));
            $cand_label = ($cand['name'] ?: ('#' . $cid))
                . ($cand_role !== null ? ' (' . Setting::getRoleLabel($cand_role) . ')' : '');
            echo "<option value='" . (int) $cid . "'>" . htmlescape($cand_label) . "</option>";
        }
        echo "</select>";
        echo "</div>";

        echo "<div class='col-6 col-md-3'>";
        echo "<label class='form-label mb-1'>" . __('Entrada', 'dgoplus') . "</label>";
        echo "<select name='dst_slot' class='form-select form-select-sm' data-dgoplus-link-slot='1'>";
        for ($slot = 1; $slot <= Port::MAX_ENTRIES; $slot++) {
            echo "<option value='" . $slot . "'>" . htmlescape(Port::formatEntryLabel($slot)) . "</option>";
        }
        echo "</select>";
        echo "</div>";

        echo "<div class='col-6 col-md-4'>";
        echo Html::submit(__('Propor vínculo', 'dgoplus'), ['class' => 'btn btn-sm btn-outline-primary w-100']);
        echo "</div>";

        echo "</div>";

        // JSON para o JS desabilitar as entradas ocupadas. HEX flags
        // obrigatorias: nome de elemento contendo </script> quebraria a
        // pagina inteira (mesma regra dos endpoints AJAX).
        echo "<script type='application/json' data-dgoplus-link-occupied='1'>"
            . json_encode($occupied, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . "</script>";

        // Bloco 5a: escopo de cada candidato e o dono de cada piso. Mesmas HEX
        // flags - nome de localizacao com </script> quebraria a pagina.
        echo "<script type='application/json' data-dgoplus-link-scope='1'>"
            . json_encode(
                ['items' => $cand_scope, 'floors' => $floor_owner],
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_FORCE_OBJECT
            )
            . "</script>";

        Html::closeForm();
        echo "</div>";
    }
}
