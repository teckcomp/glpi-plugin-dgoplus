<?php

/**
 * DGO+ - plugin GLPI 11
 * Bloco 1: schema + classes de dados + direito de perfil.
 * Bloco 3g: Piso em Configurar -> Intitulados.
 * Bloco 3h: Setor abandonado; escopo e' Localizacao (nativa) -> Piso.
 * Bloco 4a: auto-save do painel da porta (JS em public/).
 * Bloco 3j: DGO habilitavel na Analise de impacto.
 * Bloco 3k: atalho da ficha do ativo para o mapa.
 * Bloco 3l: configuracao de quais Tipos sao DGO.
 * Bloco 3q: limpeza de portas, paineis e historico na PURGA do ativo.
 * Bloco 4g: bump 1.3.0 e troca de isDgo() por isMapped().
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Dgoplus\Floor;
use GlpiPlugin\Dgoplus\MapController;
use GlpiPlugin\Dgoplus\MapPage;
use GlpiPlugin\Dgoplus\Port;
use GlpiPlugin\Dgoplus\ProfileTab;
use GlpiPlugin\Dgoplus\PurgeCleaner;
use GlpiPlugin\Dgoplus\Setting;

define('PLUGIN_DGOPLUS_VERSION', '1.3.13');
define('PLUGIN_DGOPLUS_MIN_GLPI', '11.0.0');
define('PLUGIN_DGOPLUS_MAX_GLPI', '11.9.99');

/**
 * Inicializacao do plugin.
 *
 * @return void
 */
function plugin_init_dgoplus()
{
    /**
     * @var array $PLUGIN_HOOKS
     * @var array $CFG_GLPI
     */
    global $PLUGIN_HOOKS, $CFG_GLPI;

    // Declaracao obrigatoria: o core valida o token CSRF sozinho nos POST do plugin.
    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['dgoplus'] = true;

    if (!Plugin::isPluginActive('dgoplus')) {
        return;
    }

    // Aba "DGO+" dentro do formulario de Perfil (concessao do direito).
    Plugin::registerClass(ProfileTab::class, ['addtabon' => ['Profile']]);

    // Entrada "DGO+" no menu Ativos, apontando para front/map.php.
    $PLUGIN_HOOKS[Hooks::MENU_TOADD]['dgoplus'] = ['assets' => [MapPage::class]];

    // Bloco 4a: auto-save do painel da porta.
    // O arquivo mora em public/dgoplus.js, mas o caminho declarado aqui NAO
    // leva o prefixo public/: Html.php:6153 monta a URL como
    // /plugins/<chave>/<arquivo> e o roteador ja procura dentro de public/
    // (RequestRouterTrait::getTargetFile, 11.0.6:95). Declarar 'public/...'
    // gera aviso de deprecacao no log (mesmo trait, linha 161).
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['dgoplus'] = ['dgoplus.js'];

    // Bloco 3j: habilitar a DGO na Analise de impacto.
    //
    // PassiveDCEquipment ja esta em onze listas do CFG_GLPI, mas NAO em
    // impact_asset_types - e a tela Configurar -> Geral -> Analise de impacto
    // lista justamente esse array (Impact::showConfigForm, 11.0.6:1836), entao
    // sem esta injecao nao ha como habilitar pela interface.
    //
    // A cadeia inteira e' resolvida em tempo de execucao, por isso funciona a
    // partir daqui e sem nenhuma linha de banco:
    //   defineTabs -> addImpactTab (CommonGLPI:411) -> Impact::isEnabled
    //   -> getEnabledItemtypes (Impact:1799) -> filtra a config do banco
    //      contra $CFG_GLPI['impact_asset_types']
    //
    // O valor e' o caminho do icone (Impact::getImpactIcon, 11.0.6:1313) e
    // TEM que comecar com '/', senao o core registra Toolbox::deprecated
    // (mesma funcao, linha 1301). O arquivo do core vive em
    // public/pics/impact/, e o 'public/' nao entra na URL - mesma regra do JS
    // acima. Reaproveitar icone existente evita adicionar imagem ao plugin.
    //
    // Guarda de idempotencia: se um dia o core passar a incluir este itemtype,
    // a entrada dele (e o icone dele) prevalece.
    if (!isset($CFG_GLPI['impact_asset_types'][PassiveDCEquipment::class])) {
        $CFG_GLPI['impact_asset_types'][PassiveDCEquipment::class] = '/pics/impact/enclosure.png';
    }

    // Bloco 3k: atalho da ficha do ativo para o mapa no DGO+.
    //
    // Antes do 3k havia caminho do DGO+ para o ativo, mas nenhum de volta: quem
    // chegava a' DGO pela busca global, por um chamado, pela lista de
    // Dispositivos passivos ou pelo "Ir para" da Analise de impacto ficava sem
    // saida e tinha que reencontrar a localizacao e a DGO na mao pelo menu.
    //
    // O hook renderiza em templates/components/form/buttons.html.twig:40, logo
    // acima da barra de botoes do formulario. A funcao recebe
    // ['item' => ..., 'options' => ...] e IMPRIME (o template descarta o
    // retorno: PluginExtension::callPluginHook com $return_result = false).
    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['dgoplus'] = 'plugin_dgoplus_post_item_form';

    // Bloco 3l: botao "Configurar" do DGO+ em Configurar -> Plugins. O core
    // monta a URL como {root_doc}/plugins/dgoplus/<este valor>
    // (Plugin.php:2850), entao o caminho e' relativo a' raiz do plugin.
    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['dgoplus'] = 'front/config.form.php';

    // Bloco 3q: purgar o ativo tem que levar junto as portas, os paineis e o
    // historico deles.
    //
    // Sem esta linha, Plugin::doHook(Hooks::ITEM_PURGE) (CommonDBTM.php:2185)
    // nao encontra nada do DGO+ e as tabelas do plugin ficam com linhas
    // apontando para um items_id que nao existe mais - sem erro e sem lixeira.
    //
    // O formato de ARRAY POR ITEMTYPE nao e' decoracao: em Plugin.php:1806 o
    // core faz isset($tab[$itemtype]) com $itemtype = get_class($param)
    // (linha 1795). Declarar um callable solto aqui faria o callback rodar na
    // purga de QUALQUER item do GLPI - chamado, computador, usuario - e a
    // classe teria que se defender sozinha a cada purga do sistema inteiro.
    //
    // A comparacao e' de classe EXATA, nao instanceof: se um dia o DGO+
    // aceitar outro itemtype como DGO, ele precisa de sua propria entrada.
    $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['dgoplus'] = [
        PassiveDCEquipment::class => [PurgeCleaner::class, 'onItemPurge'],
    ];
}

/**
 * Botao "Abrir no mapa DGO+" na ficha do ativo (bloco 3k).
 *
 * Imprime; nao retorna. Sai calado em qualquer situacao que nao seja uma DGO
 * salva e visivel para quem esta olhando.
 *
 * @param array $params ['item' => CommonDBTM, 'options' => array]
 * @return void
 */
function plugin_dgoplus_post_item_form($params = [])
{
    $item = $params['item'] ?? null;

    // So no ativo que representa a DGO.
    if (!($item instanceof PassiveDCEquipment)) {
        return;
    }

    // Formulario de criacao e formulario de modelo nao tem para onde ir.
    if ((int) $item->getID() <= 0 || $item->isNewItem()) {
        return;
    }

    if (!empty($params['options']['withtemplate'])) {
        return;
    }

    // Quem nao pode abrir a tela nao ve o botao. Nao e' so estetica: um botao
    // que leva a "acesso negado" e' pior que botao nenhum.
    if (!Session::haveRight(Port::$rightname, READ)) {
        return;
    }

    // Bloco 3l: dispositivo passivo que o plugin nao reconhece nao ganha botao
    // de DGO. Com o filtro de Tipo desligado, isMapped() devolve true para
    // todos e o comportamento e' o do 3k.
    //
    // Bloco 4g: era isDgo(), que desde o 4a nao passava de um apelido de
    // isMapped(). A troca so' pode acontecer aqui, e por isso esperou o bump:
    // enquanto a versao do disco divergia da do repositorio, este arquivo
    // estava proibido na copia disco->repositorio (licao 105). O nome novo
    // tambem e' o correto: o criterio nunca foi "e' uma DGO", e sim "tem algum
    // papel do plugin" - um DIO e uma CTO passam por aqui e precisam do botao.
    if (!Setting::isMapped($item)) {
        return;
    }

    $locations_id = (int) ($item->fields['locations_id'] ?? 0);

    echo "<div class='card-body border-top d-flex flex-wrap align-items-center gap-2'>";

    if ($locations_id > 0) {
        echo "<a class='btn btn-outline-primary' href='"
            . htmlescape(MapController::getUrlForDgo($item)) . "'>"
            . "<i class='ti ti-layout-grid me-1'></i>"
            . htmlescape(__('Abrir no mapa DGO+', 'dgoplus'))
            . "</a>";
    } else {
        // Decisao do usuario em 29/07/2026: botao desabilitado com o motivo, em
        // vez de escondido. O DGO+ parte da localizacao para listar as DGOs,
        // entao sem localizacao nao ha tela a abrir - e um botao que
        // simplesmente desaparece pareceria defeito (licao 16).
        echo "<button type='button' class='btn btn-outline-secondary' disabled>"
            . "<i class='ti ti-layout-grid me-1'></i>"
            . htmlescape(__('Abrir no mapa DGO+', 'dgoplus'))
            . "</button>";
        echo "<span class='text-muted small'>"
            . htmlescape(__('Defina a localização deste ativo para abri-lo no mapa.', 'dgoplus'))
            . "</span>";
    }

    echo "</div>";
}

/**
 * Hook Hooks::AUTO_GET_DROPDOWN (src/Glpi/Plugin/Hooks.php:1123).
 *
 * Nao e' entrada de $PLUGIN_HOOKS: o core chama a FUNCAO
 * plugin_dgoplus_getDropdown() por Plugin::doOneHook() (src/Plugin.php:1920) e
 * agrupa o retorno sob o nome do plugin em Configurar -> Intitulados.
 * Fica aqui, e nao no hook.php, porque setup.php de plugin ativo ja esta
 * carregado quando Dropdown::getStandardDropdownItemTypes() roda.
 *
 * Valor null: o core preenche sozinho com getTypeName() no plural
 * (src/Dropdown.php:1444).
 *
 * @return array
 */
function plugin_dgoplus_getDropdown()
{
    if (!Plugin::isPluginActive('dgoplus')) {
        return [];
    }

    return [
        Floor::class => null,
    ];
}

/**
 * Informacoes do plugin exibidas na lista de plugins.
 *
 * @return array
 */
function plugin_version_dgoplus()
{
    return [
        'name'         => 'DGO+',
        'version'      => PLUGIN_DGOPLUS_VERSION,
        'author'       => 'Teckcomp',
        'license'      => 'GPLv3+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_DGOPLUS_MIN_GLPI,
                'max' => PLUGIN_DGOPLUS_MAX_GLPI,
            ],
            'php'  => [
                'min' => '8.2',
            ],
        ],
    ];
}

/**
 * Pre-requisitos verificados antes da instalacao.
 *
 * @return bool
 */
function plugin_dgoplus_check_prerequisites()
{
    return true;
}

/**
 * Verificacao de configuracao pos-instalacao.
 *
 * @param bool $verbose
 * @return bool
 */
function plugin_dgoplus_check_config($verbose = false)
{
    return true;
}
