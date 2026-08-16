<?php

namespace GlpiPlugin\Dgoplus;


/**
 * A pagina de vinculos pendentes. Bloco 4d.
 *
 * O cartao do painel e' o radar: cinco linhas e um contador. Esta pagina e' a
 * fila inteira, com filtro de papel, elemento e idade. As duas leem a MESMA
 * consulta (Link::pendingRows), por isso nao ha como uma contradizer a outra -
 * o que muda aqui e' so' o recorte depois da leitura.
 *
 * Sem entrada de menu de proposito
 * --------------------------------
 * Chega-se aqui pelo rodape do cartao. Registrar mais um item no menu Ativos
 * exigiria mexer no setup.php, que esta PROIBIDO na copia disco->repositorio
 * enquanto durar a divergencia de versao 1.2.0/1.2.1 (licao 105). O caminho de
 * navegacao existe e e' suficiente; o item de menu, se fizer falta, entra junto
 * com o bump da 1.3.0, quando o setup.php volta a ser copiavel.
 *
 * Nenhuma acao aqui
 * -----------------
 * Confirmar e recusar continuam sendo do card da entrada (bloco 4c), com as
 * guardas de direito ja validadas. Esta tela LE e NAVEGA. Um segundo lugar
 * escrevendo vinculo seria um segundo lugar para errar.
 */
class Pending
{
    /** Faixas do filtro de idade, em dias */
    private const AGE_BANDS = [7, 30];

    /**
     * URL publica desta pagina.
     *
     * Mesmo padrao do MapController::getPageUrl e pela mesma razao: no GLPI 11
     * os arquivos de front/ sao carregados por require() de dentro do front
     * controller do Symfony, entao $_SERVER['PHP_SELF'] vale o caminho do
     * roteador e todo formulario cairia na home. root_doc + caminho literal e'
     * o padrao do core.
     *
     * @param array $params
     * @return string
     */
    public static function getPageUrl(array $params = []): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $url = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/dgoplus/front/pending.php';

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Le a query string, aplica os filtros e desenha. Ponto de entrada de
     * front/pending.php.
     *
     * @return void
     */
    public static function processAndDisplay(): void
    {
        $role = Dashboard::currentRole();

        // A consulta ja recorta por papel: o filtro de papel desta tela e' o
        // MESMO parametro que o painel usa, e currentRole() e' o ponto unico
        // dele desde o 4a-3.
        $rows = Link::pendingRows($role);

        $item   = (int) ($_GET['item'] ?? 0);
        $age    = (int) ($_GET['age'] ?? 0);
        $total  = count($rows);

        // Os elementos do seletor saem da lista ANTES do recorte por elemento:
        // filtrar por um elemento nao pode fazer os outros sumirem do proprio
        // seletor - o usuario ficaria preso no filtro que acabou de escolher.
        $item_options = [];
        foreach ($rows as $r) {
            $item_options[(int) $r['dst_items_id']] = (string) $r['dst_item'];
        }
        asort($item_options, SORT_NATURAL | SORT_FLAG_CASE);

        $shown = [];
        foreach ($rows as $r) {
            if ($item > 0 && (int) $r['dst_items_id'] !== $item) {
                continue;
            }
            if ($age > 0 && (int) $r['age_days'] < $age) {
                continue;
            }
            $shown[] = $r;
        }

        self::displayHeader($total, count($shown), $role);
        self::displayFilters($role, $item, $age, $item_options);
        self::displayList($shown, $total);
    }

    /**
     * @param int         $total
     * @param int         $shown
     * @param string|null $role
     * @return void
     */
    private static function displayHeader(int $total, int $shown, ?string $role): void
    {
        echo "<div class='d-flex flex-wrap align-items-center gap-2 mb-3'>";

        echo "<h2 class='mb-0'><i class='ti ti-git-pull-request me-1'></i>"
            . __('Vínculos pendentes', 'dgoplus') . "</h2>";

        if ($total > 0) {
            $label = $shown === $total
                ? (string) $total
                : sprintf(__('%1$d de %2$d', 'dgoplus'), $shown, $total);
            echo "<span class='badge bg-orange-lt'>" . htmlescape($label) . "</span>";
        }

        echo "<a class='btn btn-outline-secondary ms-auto' href='"
            . htmlescape(MapController::getPublicPageUrl()) . "'>"
            . "<i class='ti ti-arrow-left me-1'></i>" . __('Voltar ao mapa', 'dgoplus') . "</a>";

        echo "</div>";
    }

    /**
     * Os tres seletores, num formulario GET so'.
     *
     * O papel viaja como campo deste formulario (nao como hidden) para o
     * usuario poder troca-lo aqui sem voltar ao mapa. Continua sendo lido por
     * Dashboard::currentRole(), que le $_GET.
     *
     * @param string|null       $role
     * @param int               $item
     * @param int               $age
     * @param array<int,string> $item_options
     * @return void
     */
    private static function displayFilters(?string $role, int $item, int $age, array $item_options): void
    {
        echo "<div class='card mb-3'><div class='card-body py-2'>";
        echo "<form method='get' action='" . htmlescape(self::getPageUrl()) . "' "
            . "class='d-flex flex-wrap align-items-center gap-2'>";

        // --- Papel ---
        echo "<label class='form-label mb-0 me-1'>" . __('Papel', 'dgoplus') . "</label>";
        echo "<select name='role' class='form-select w-auto' onchange='this.form.submit()'>";
        echo "<option value=''>" . __('Todos os papéis', 'dgoplus') . "</option>";
        foreach (Setting::getRoles() as $r) {
            echo "<option value='" . htmlescape($r) . "'" . ($role === $r ? ' selected' : '') . ">"
                . htmlescape(Setting::getRoleLabel($r)) . "</option>";
        }
        echo "</select>";

        // --- Elemento ---
        echo "<label class='form-label mb-0 me-1'>" . __('Elemento', 'dgoplus') . "</label>";
        echo "<select name='item' class='form-select w-auto' onchange='this.form.submit()'>";
        echo "<option value='0'>" . __('Todos os elementos', 'dgoplus') . "</option>";
        foreach ($item_options as $id => $name) {
            echo "<option value='" . (int) $id . "'" . ($item === (int) $id ? ' selected' : '') . ">"
                . htmlescape($name) . "</option>";
        }
        echo "</select>";

        // --- Idade ---
        echo "<label class='form-label mb-0 me-1'>" . __('Idade', 'dgoplus') . "</label>";
        echo "<select name='age' class='form-select w-auto' onchange='this.form.submit()'>";
        echo "<option value='0'>" . __('Qualquer idade', 'dgoplus') . "</option>";
        foreach (self::AGE_BANDS as $days) {
            echo "<option value='" . (int) $days . "'" . ($age === $days ? ' selected' : '') . ">"
                . htmlescape(sprintf(__('Mais de %d dias', 'dgoplus'), $days)) . "</option>";
        }
        echo "</select>";

        // Sem JS o onchange nao dispara; o botao e' o caminho que sempre existe.
        echo "<button type='submit' class='btn btn-outline-secondary'>"
            . __('Filtrar', 'dgoplus') . "</button>";

        if ($role !== null || $item > 0 || $age > 0) {
            echo "<a class='btn btn-outline-secondary' href='" . htmlescape(self::getPageUrl()) . "'>"
                . "<i class='ti ti-x'></i></a>";
        }

        echo "</form>";
        echo "</div></div>";
    }

    /**
     * @param array $rows
     * @param int   $total
     * @return void
     */
    private static function displayList(array $rows, int $total): void
    {
        echo "<div class='card'>";

        if ($rows === []) {
            // Duas frases diferentes de proposito: "nao ha nada" e "ha, mas o
            // seu filtro escondeu" mandam o usuario para lados opostos.
            echo "<div class='card-body text-muted'>"
                . ($total === 0
                    ? __('Nenhuma proposta em aberto.', 'dgoplus')
                    : __('Nenhuma proposta corresponde ao filtro.', 'dgoplus'))
                . "</div>";
            echo "</div>";
            return;
        }

        echo "<div class='table-responsive'><table class='table table-vcenter card-table mb-0'>";
        echo "<thead><tr>";
        echo "<th>" . __('Origem', 'dgoplus') . "</th>";
        echo "<th class='text-nowrap'>" . __('Destino', 'dgoplus') . "</th>";
        echo "<th>" . __('Proposto por', 'dgoplus') . "</th>";
        echo "<th class='text-end'>" . __('Idade', 'dgoplus') . "</th>";
        echo "</tr></thead><tbody>";

        foreach ($rows as $r) {
            $url = MapController::getEntryUrl(
                (int) $r['dst_locations_id'],
                (int) $r['dst_items_id'],
                (int) $r['dst_slot']
            );

            echo "<tr>";

            echo "<td class='text-nowrap'>"
                . "<span class='badge bg-blue-lt'>" . htmlescape($r['src_label']) . "</span> "
                . htmlescape($r['src_item']) . "</td>";

            // text-nowrap: a tabela ja rola na horizontal (table-responsive), e
            // rolar e' melhor do que picotar o nome do elemento uma palavra por
            // linha, que foi o que a renderizacao em 480 mostrou.
            echo "<td class='text-nowrap'><a href='" . htmlescape($url) . "' class='text-decoration-none'>"
                . htmlescape($r['dst_item'])
                . " <span class='text-muted'>" . htmlescape($r['dst_label']) . "</span></a></td>";

            echo "<td>" . htmlescape($r['proposer']) . "</td>";

            echo "<td class='text-end text-nowrap " . Dashboard::ageClass((int) $r['age_days']) . "'>"
                . htmlescape(Dashboard::ageLabel((int) $r['age_days'])) . "</td>";

            echo "</tr>";
        }

        echo "</tbody></table></div>";
        echo "</div>";
    }
}
