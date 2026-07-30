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
                    ? __('Nenhuma DGO neste piso. Troque o piso, ou abra uma DGO e atribua o piso na grade.', 'dgoplus')
                    : __('Nenhuma DGO cadastrada nesta localização ainda.', 'dgoplus')
            );
            return;
        }

        if ($dgo_id <= 0 || !isset($dgos[$dgo_id])) {
            self::displayEmptyState(
                __('Selecione uma DGO acima para abrir a grade de portas.', 'dgoplus')
            );
            return;
        }

        $dgo = $dgos[$dgo_id];

        self::displayGrid($dgo, $locations_id, $edit_key, $search, $floors_id);
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
        }
    }

    /**
     * @return void
     */
    private static function actionCreateDgo(): void
    {
        Session::checkRight(Port::$rightname, CREATE);
        Session::checkRight(PassiveDCEquipment::$rightname, CREATE);

        $locations_id = (int) ($_POST['locations_id'] ?? 0);
        $floors_id    = (int) ($_POST['floor'] ?? 0);
        $name         = trim($_POST['name'] ?? '');

        if ($locations_id <= 0 || $name === '') {
            Session::addMessageAfterRedirect(__('Preencha o nome da DGO.', 'dgoplus'), false, ERROR);
            self::redirectTo(self::scope($locations_id, $floors_id));
            return;
        }

        $input = [
            'name'         => $name,
            'locations_id' => $locations_id,
            'entities_id'  => Session::getActiveEntity(),
        ];

        // Bloco 3l, e este e' o ponto que nao pode faltar: com o filtro de Tipo
        // ligado, uma DGO criada sem tipo seria descartada pelo proprio filtro e
        // desapareceria no instante em que fosse criada - o usuario clicaria em
        // "+ Nova DGO", a tela recarregaria e nada apareceria, sem uma linha de
        // erro em log nenhum (licao 14). Com o filtro desligado o metodo devolve
        // 0 e a chave nem entra no input, preservando o comportamento anterior.
        $type_id = Setting::getTypeForNewDgo();
        if ($type_id > 0) {
            $input[Setting::getTypeField()] = $type_id;
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
        ]);

        if ($busy !== []) {
            $positions = [];
            foreach ($busy as $row) {
                $positions[] = Port::formatPosition((int) $row['tube_num'], (int) $row['fiber_num']);
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

        $port = new Port();
        $busy = $port->find([
            'itemtype'   => $itemtype,
            'items_id'   => $items_id,
            'fiber_num'  => $last_column,
            'is_deleted' => 0,
        ]);

        if ($busy !== []) {
            $positions = [];
            foreach ($busy as $row) {
                $positions[] = Port::formatPosition((int) $row['tube_num'], (int) $row['fiber_num']);
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
            $port->delete(['id' => $id]);
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
        echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' id='dgoplus-location-form'>";
        echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
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
            $pos      = Port::formatPosition((int) $row['tube_num'], (int) $row['fiber_num']);
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

        // Bloco 3l: so os Tipos configurados como DGO. Devolve array vazio
        // quando nada foi configurado, e nesse caso o `+` nao altera o where -
        // e' o que mantem o comportamento anterior ao 3l intacto.
        $rows = $dgo->find($where + Setting::dgoCriteria() + $criteria);

        foreach ($rows as $row) {
            $item = new PassiveDCEquipment();
            $item->getFromResultSet($row);
            $result[(int) $row['id']] = $item;
        }

        return $result;
    }

    /**
     * Quantos dispositivos passivos da localizacao ficaram de fora por nao ter
     * um Tipo de DGO configurado.
     *
     * Existe para a tela poder DIZER que filtrou, em vez de simplesmente
     * mostrar menos coisa: ativo que desaparece sem explicacao parece dado
     * perdido (licao 16). Devolve 0 quando o filtro esta desligado.
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
        $dgos  = count($dgo->find($where + Setting::dgoCriteria() + $criteria));

        return max(0, $total - $dgos);
    }

    /**
     * Seletor de DGO da localizacao + criacao de DGO nova.
     *
     * Ate MAX_TABS DGOs sao abas de verdade (um clique). Acima disso vira um
     * seletor unico com busca - o select2 que o core aplica em todo dropdown
     * -, senao um andar com 20 DGOs viraria uma parede de botoes.
     *
     * @param int                           $locations_id
     * @param array<int,PassiveDCEquipment> $dgos
     * @param int                           $active_id
     * @param int                           $floors_id
     * @return void
     */
    private static function displayDgoTabs(int $locations_id, array $dgos, int $active_id, int $floors_id = 0): void
    {
        $can_create = Session::haveRight(Port::$rightname, CREATE)
            && Session::haveRight(PassiveDCEquipment::$rightname, CREATE);

        // Bloco 3l: quando o filtro de Tipo esconde ativos, a tela DIZ isso.
        // Sem este aviso, alguem que documentou uma DGO e depois configurou o
        // Tipo errado concluiria que o plugin perdeu o trabalho dele.
        $filtered = self::countFilteredOut($locations_id);
        if ($filtered > 0) {
            echo "<div class='alert alert-info d-flex align-items-center gap-2'>";
            echo "<i class='ti ti-info-circle'></i>";
            echo "<span>";
            echo htmlescape(sprintf(
                _n(
                    '%d dispositivo passivo desta localização não está listado porque seu Tipo não é de DGO.',
                    '%d dispositivos passivos desta localização não estão listados porque seus Tipos não são de DGO.',
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
            echo "<ul class='nav nav-tabs flex-grow-1' role='tablist'>";
            foreach ($dgos as $id => $item) {
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
            echo "</ul>";
        } elseif ($dgos !== []) {
            $elements = [];
            foreach ($dgos as $id => $item) {
                $count         = self::countDocumentedPorts(PassiveDCEquipment::class, $id);
                $elements[$id] = sprintf(
                    '%s — %d %s',
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
            echo "<span class='text-muted d-flex align-items-center gap-1'>"
                . "<i class='ti ti-server'></i> " . sprintf(__('%d DGOs nesta localização', 'dgoplus'), count($dgos))
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
            echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "' style='min-width:260px;max-width:340px'>";
            echo Html::hidden('action', ['value' => 'create_dgo']);
            echo Html::hidden('locations_id', ['value' => $locations_id]);
            echo Html::hidden('floor', ['value' => $floors_id]);
            echo "<div class='input-group input-group-sm'>";
            echo Html::input('name', ['placeholder' => __('Nome da nova DGO', 'dgoplus')]);
            echo "<button type='submit' name='submit_create_dgo' class='btn btn-outline-primary'>"
                . "<i class='ti ti-plus'></i>&nbsp;" . __('Nova DGO', 'dgoplus') . "</button>";
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
        ]);

        return count($rows);
    }

    /**
     * Legenda das cores da grade.
     *
     * @return string
     */
    private static function gridLegend(): string
    {
        $swatch = static function (string $bg, string $border): string {
            return "<span style='display:inline-block;width:10px;height:10px;border-radius:3px;"
                . "background:" . $bg . ";border:1px solid " . $border . "'></span>";
        };

        return "<div class='d-flex align-items-center gap-3 flex-wrap text-muted small'>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_DOC_BG, self::CELL_DOC_BORDER) . ' ' . __('Documentada', 'dgoplus') . "</span>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_FREE_BG, self::CELL_FREE_BORDER) . ' ' . __('Livre', 'dgoplus') . "</span>"
            . "<span class='d-flex align-items-center gap-1'>"
            . $swatch(self::CELL_NC_BG, self::CELL_NC_BORDER) . ' ' . __('Sem acoplador', 'dgoplus') . "</span>"
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
        string $search = ''
    ): string {
        $key    = $t . '-' . $f;
        $exists = $row !== null;
        $no_cpl = $exists && (int) ($row['is_no_coupler'] ?? 0) === 1;
        $filled = $exists && !$no_cpl;
        $active = ($key === $edit_key);

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
            $border = '1px solid ' . self::CELL_DOC_BORDER;
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

        $title = Port::formatPosition($t, $f);
        if ($no_cpl) {
            $title .= ' — ' . __('sem acoplador', 'dgoplus');
            if (($row['comment'] ?? '') !== '') {
                $title .= ': ' . $row['comment'];
            }
        } elseif ($filled) {
            $title .= ' — ' . trim(
                ($row['code'] ?? '') . ' ' . ($row['comment'] ?? '')
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
        $html .= "<span class='text-muted' style='font-size:9.5px'>" . Port::formatPosition($t, $f) . "</span>";
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
            $html .= "<span class='d-block text-truncate' style='font-size:11px'><strong>"
                . htmlescape($row['code'] ?: '—') . "</strong></span>";
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
    private static function displayGrid(PassiveDCEquipment $dgo, int $locations_id, string $edit_key, string $search = '', int $floors_id = 0): void
    {
        $items_id = (int) $dgo->getID();
        $layout   = Panel::getLayoutForItem($dgo);

        $port = new Port();
        $rows = $port->find([
            'itemtype'   => PassiveDCEquipment::class,
            'items_id'   => $items_id,
            'is_deleted' => 0,
        ]);

        $byKey = [];
        foreach ($rows as $row) {
            $byKey[$row['tube_num'] . '-' . $row['fiber_num']] = $row;
        }

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

        self::displayFloorAssignment($dgo, $locations_id, $floors_id);

        echo "<div style='overflow-x:auto'>";

        for ($t = 1; $t <= $layout['tubes']; $t++) {
            echo "<div class='d-flex align-items-stretch gap-1 mb-1'>";
            echo "<div class='d-flex align-items-center justify-content-center text-muted small' style='width:34px;flex:0 0 auto'>"
                . "<strong>F" . $t . "</strong></div>";

            for ($f = 1; $f <= $layout['fibers_per_tube']; $f++) {
                $key = $t . '-' . $f;

                echo self::renderCell(
                    $items_id,
                    $locations_id,
                    $floors_id,
                    $t,
                    $f,
                    $byKey[$key] ?? null,
                    $edit_key,
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
                echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "'>";
                echo Html::hidden('action', ['value' => 'add_tube']);
                echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::submit(sprintf(__('+ Nova fileira (F%d)', 'dgoplus'), $layout['tubes'] + 1), ['class' => 'btn btn-sm btn-outline-primary']);
                Html::closeForm();
            }

            if ($can_remove) {
                echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "'>";
                echo Html::hidden('action', ['value' => 'remove_tube']);
                echo Html::hidden('itemtype', ['value' => PassiveDCEquipment::class]);
                echo Html::hidden('items_id', ['value' => $items_id]);
                echo Html::hidden('locations_id', ['value' => $locations_id]);
                echo Html::hidden('floor', ['value' => $floors_id]);
                echo Html::submit(sprintf(__('− Remover fileira (F%d)', 'dgoplus'), $layout['tubes']), ['class' => 'btn btn-sm btn-outline-danger']);
                Html::closeForm();
            }

            if ($can_add_col) {
                echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "'>";
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
                echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "'>";
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

        self::displayAttachmentsSidebar($dgo, $locations_id, $edit_key, $floors_id);

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

        echo "<div class='d-flex align-items-center gap-2 flex-wrap mb-3'>";
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

        echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "' id='dgoplus-setfloor-form'>";
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
     * Coluna compacta de anexos, ao lado da grade.
     *
     * So miniatura + nome + contador. O componente nativo de anexo
     * (Document_Item::showForItem) NAO cabe aqui: sao dois cards lado a lado
     * mais uma tabela de 8 colunas - em coluna estreita a ultima coluna some
     * sem avisar (licao 20). Ele vai em largura total, em displayDocumentsManager().
     *
     * @param PassiveDCEquipment $dgo
     * @param int                $locations_id
     * @param string             $edit_key
     * @return void
     */
    private static function displayAttachmentsSidebar(PassiveDCEquipment $dgo, int $locations_id, string $edit_key, int $floors_id = 0): void
    {
        $items_id = (int) $dgo->getID();

        echo "<div style='flex:1 1 280px;min-width:0;max-width:420px'>";
        echo "<div class='card'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between gap-2'>";
        echo "<h3 class='card-title mb-0 d-flex align-items-center gap-2'>"
            . "<i class='ti ti-files'></i>" . __('Anexos da DGO', 'dgoplus') . "</h3>";

        if (!Document::canView()) {
            echo "</div><div class='card-body'>";
            echo "<div class='text-muted small'>"
                . htmlescape(__('Seu perfil não tem direito de ver Documentos.', 'dgoplus'))
                . "</div>";
            echo "</div></div></div>";
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
        echo "</div>"; // coluna
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

        $can_write = Session::haveRight(Port::$rightname, $found ? UPDATE : CREATE);

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
        echo "<span class='badge bg-blue-lt'>" . htmlescape(Port::formatPosition($tube_num, $fiber_num)) . "</span>";
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
        echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "'"
            . " data-dgoplus-port-form='1'"
            . " data-dgoplus-endpoint='" . htmlescape(self::getAjaxUrl('port.php')) . "'"
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

        if ($found && Session::haveRight(Port::$rightname, DELETE)) {
            echo "<form method='post' action='" . htmlescape(self::getPageUrl()) . "' class='d-flex justify-content-end'>";
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
}
