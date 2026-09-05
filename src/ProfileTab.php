<?php

namespace GlpiPlugin\Dgoplus;

use CommonGLPI;
use Html;
use Profile;
use Session;

/**
 * Aba "DGO+" no formulario de Perfil, para conceder o direito
 * plugin_dgoplus_port.
 *
 * Nao estende \Profile de proposito: assinatura de showForm() da classe-pai
 * muda entre versoes e quebra em tempo de execucao.
 */
class ProfileTab extends CommonGLPI
{
    /** Quem pode ver esta aba: quem administra perfis */
    public static $rightname = 'profile';

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('DGO+', 'dgoplus');
    }

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            $item instanceof Profile
            && (int) $item->getID() > 0
            && ($item->fields['interface'] ?? '') !== 'helpdesk'
        ) {
            return self::createTabEntry(self::getTypeName(), 0, $item::class, 'ti ti-grid-dots');
        }

        return '';
    }

    /**
     * Nota explicativa ABAIXO da matriz de direitos. Bloco 5g-3, corrigido
     * pelo 2b.
     *
     * Este e' o destino de toda informacao de permissao que NAO cabe na tela
     * do tecnico (licao 153): a tela do mapa so' fala de direito para quem
     * esbarrou numa recusa, e o painel da porta nomeia o que falta. O que o
     * ADMINISTRADOR precisa saber antes de conceder mora aqui, que e'
     * exatamente a tela onde ele decide.
     *
     * Nada nesta funcao le ou grava direito: e' texto, e cada linha descreve
     * uma guarda que existe no codigo.
     *
     * Vem DEPOIS da matriz porque a matriz e' a acao e a nota e' a
     * referencia: quem abre a aba para marcar uma caixa nao deveria rolar por
     * cinco paragrafos ate' chegar nela.
     *
     * ⚠️ Usa 'card', nao 'alert': o .alert do tema renderizou o conteudo em
     * COLUNAS lado a lado (visto em tela, 28/08), enquanto o card e' o
     * recipiente que o resto do plugin ja usa e empilha como esperado
     * (licao 21 - so' classe cujo comportamento foi confirmado).
     *
     * @return void
     */
    private static function displayRightsNote(): void
    {
        echo "<div class='card mt-3'>";

        echo "<div class='card-header'><h3 class='card-title mb-0'>"
            . __('O que cada nível concede no DGO+', 'dgoplus') . "</h3></div>";

        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm card-table mb-0'><tbody>";

        $rows = [
            [
                __('Ler', 'dgoplus'),
                __('Ver o mapa, o painel, os relatórios, os comentários e a descrição das portas.', 'dgoplus'),
            ],
            [
                __('Atualizar', 'dgoplus'),
                __('Documentar portas, propor e confirmar vínculos, comentar o elemento, preencher a OBS e atribuir piso.', 'dgoplus'),
            ],
            [
                __('Criar', 'dgoplus'),
                __('Criar elementos pelo mapa, fileiras, colunas e pisos — ou seja, estrutura.', 'dgoplus'),
            ],
            [
                _x('button', 'Delete'),
                __('Esvaziar portas, esvaziar fileira ou coluna e desmontar vínculo já confirmado.', 'dgoplus'),
            ],
        ];

        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td class='text-nowrap fw-bold' style='width:110px'>" . htmlescape($row[0]) . "</td>";
            echo "<td>" . htmlescape($row[1]) . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
        echo "</div>";

        echo "<div class='card-body'>";

        echo "<p class='mb-2'>"
            . __('Quem cria elemento pelo mapa costuma precisar de Excluir junto: todo elemento novo nasce com a grade padrão (4 × 16 = 64 posições), e reduzi-la exige Excluir.', 'dgoplus')
            . "</p>";

        // Bloco 5i-2: o texto acompanhou o 5i. O formulario nativo (e o
        // "Data centers - Atualizar" que ele exigia) saiu de cena; anexar
        // passou a ser ato do plugin. Abrir continua com o porteiro do core.
        echo "<p class='mb-2'><strong>"
            . __('Anexar pelo mapa exige apenas o Atualizar do DGO+', 'dgoplus')
            . "</strong> — "
            . __('o formulário de anexo é do próprio plugin. Já abrir ou baixar um anexo exige Documentos com Ler (aba Gerência): sem esse direito a lista aparece, mas o arquivo fica com cadeado.', 'dgoplus')
            . "</p>";

        echo "<p class='mb-2'>"
            . __('Ficam fora do DGO+, por decisão: criar Localização (é lista suspensa de todo o GLPI) e excluir o ativo do elemento (é purga, do administrador).', 'dgoplus')
            . "</p>";

        echo "<p class='mb-0 text-muted'>"
            . __('Direito só entra na sessão no login: quem já estava conectado precisa sair e entrar. E o direito vale apenas nas entidades do perfil — quem tem Atualizar grava porta, vínculo e comentário nos elementos da sua entidade sem precisar de direito no ativo.', 'dgoplus')
            . "</p>";

        echo "</div>";

        echo "</div>";
    }

    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof Profile) || (int) $item->getID() <= 0) {
            return false;
        }

        $canedit = Session::haveRightsOr('profile', [CREATE, UPDATE, PURGE]);

        if ($canedit) {
            echo "<form method='post' action='" . htmlescape(Profile::getFormURL()) . "'>";
        }

        $item->displayRightsChoiceMatrix(
            [
                [
                    'itemtype' => Port::class,
                    'label'    => Port::getTypeName(2),
                    'field'    => Port::$rightname,
                ],
            ],
            [
                'title'   => self::getTypeName(),
                'canedit' => $canedit,
            ]
        );

        if ($canedit) {
            echo "<div class='text-center mt-2'>";
            echo Html::hidden('id', ['value' => $item->getID()]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }

        // Bloco 2b: a nota vem DEPOIS da matriz e do botao Salvar. Antes ela
        // empurrava as caixas de marcacao para fora da primeira tela, que e' o
        // que a pessoa veio fazer aqui.
        self::displayRightsNote();

        return true;
    }
}
