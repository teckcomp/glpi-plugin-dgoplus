<?php

namespace GlpiPlugin\Dgoplus;

use Dropdown;
use Html;
use PassiveDCEquipment;

/**
 * Panorama geral das DGOs, exibido como tela inicial da pagina DGO+
 * (quando nenhuma localizacao esta selecionada).
 *
 * Toda a coleta usa CommonDBTM::find() (sem SQL manual) e agrega em PHP:
 * volume de piloto nao justifica GROUP BY, e o iterator do GLPI 11 tem a
 * armadilha conhecida de COUNT+GROUPBY descartarem os campos do SELECT.
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
     * Coleta tudo que a tela usa, numa passada.
     *
     * @return array{
     *   total_dgos:int, trash_dgos:int,
     *   capacity:int, documented:int, free:int, occupancy:float,
     *   dgos_sem_doc:int, ports_trash:int,
     *   by_location:array<int,array{name:string,dgos:int,documented:int,capacity:int}>,
     *   top_dgos:array<int,array{id:int,name:string,locations_id:int,documented:int,capacity:int,pct:float}>,
     *   recent:array<int,array{position:string,edit_key:string,dgo_id:int,locations_id:int,dgo_name:string,code:string,date_mod:string}>
     * }
     */
    public static function collect(): array
    {
        // --- DGOs (com restricao de entidade, igual ao mapa) ---
        $dgo_model = new PassiveDCEquipment();
        $criteria  = getEntitiesRestrictCriteria('glpi_passivedcequipments', '', '', true);

        // Bloco 3l: o panorama tem que contar o MESMO conjunto que a tela
        // mostra. Sem este filtro o dashboard anunciaria DGOs que o usuario nao
        // acha em lugar nenhum - e um numero que nao fecha com a tela custa mais
        // confianca do que um numero menor.
        $dgo_criteria = Setting::dgoCriteria();

        $dgos       = $dgo_model->find(['is_deleted' => 0] + $dgo_criteria + $criteria);
        $trash_dgos = count($dgo_model->find(['is_deleted' => 1] + $dgo_criteria + $criteria));

        $dgo_ids       = array_map('intval', array_column($dgos, 'id'));
        $dgo_names     = [];
        $dgo_locations = [];
        foreach ($dgos as $row) {
            $dgo_names[(int) $row['id']]     = $row['name'] !== '' ? $row['name'] : ('#' . $row['id']);
            $dgo_locations[(int) $row['id']] = (int) ($row['locations_id'] ?? 0);
        }

        // --- Layouts (capacidade por DGO) ---
        $layouts = [];
        if ($dgo_ids !== []) {
            $panel_model = new Panel();
            $panels      = $panel_model->find([
                'itemtype' => PassiveDCEquipment::class,
                'items_id' => $dgo_ids,
            ]);
            foreach ($panels as $row) {
                $layouts[(int) $row['items_id']] =
                    Panel::sanitizeTubes((int) $row['tubes'])
                    * Panel::sanitizeFibers((int) $row['fibers_per_tube']);
            }
        }
        $default_capacity = Panel::DEFAULT_TUBES * Panel::DEFAULT_FIBERS;

        // --- Portas ---
        $port_model  = new Port();
        $ports       = [];
        $ports_trash = 0;
        if ($dgo_ids !== []) {
            $ports = $port_model->find([
                'itemtype'   => PassiveDCEquipment::class,
                'items_id'   => $dgo_ids,
                'is_deleted' => 0,
            ]);
            $ports_trash = count($port_model->find([
                'itemtype'   => PassiveDCEquipment::class,
                'items_id'   => $dgo_ids,
                'is_deleted' => 1,
            ]));
        }

        // --- Agregacao por DGO ---
        $doc_by_dgo = array_fill_keys($dgo_ids, 0);
        foreach ($ports as $row) {
            $doc_by_dgo[(int) $row['items_id']]++;
        }

        $capacity   = 0;
        $documented = 0;
        $sem_doc    = 0;
        $per_dgo    = [];
        foreach ($dgo_ids as $id) {
            $cap = $layouts[$id] ?? $default_capacity;
            $doc = $doc_by_dgo[$id] ?? 0;
            $capacity   += $cap;
            $documented += $doc;
            if ($doc === 0) {
                $sem_doc++;
            }
            $per_dgo[$id] = [
                'id'           => $id,
                'name'         => $dgo_names[$id],
                'locations_id' => $dgo_locations[$id] ?? 0,
                'documented'   => $doc,
                'capacity'     => $cap,
                'pct'          => $cap > 0 ? round($doc * 100 / $cap, 1) : 0.0,
            ];
        }

        // --- Agregacao por localizacao ---
        $by_location = [];
        foreach ($dgos as $row) {
            $loc = (int) ($row['locations_id'] ?? 0);
            if (!isset($by_location[$loc])) {
                $by_location[$loc] = [
                    'name'       => self::locationLabel($loc),
                    'dgos'       => 0,
                    'documented' => 0,
                    'capacity'   => 0,
                ];
            }
            $id = (int) $row['id'];
            $by_location[$loc]['dgos']++;
            $by_location[$loc]['documented'] += $doc_by_dgo[$id] ?? 0;
            $by_location[$loc]['capacity']   += $layouts[$id] ?? $default_capacity;
        }
        uasort($by_location, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        // --- Top 5 DGOs mais ocupadas (so as que tem algo documentado) ---
        $top = array_filter($per_dgo, fn($d) => $d['documented'] > 0);
        usort($top, fn($a, $b) => $b['pct'] <=> $a['pct'] ?: $b['documented'] <=> $a['documented']);
        $top = array_slice($top, 0, 5);

        // --- Atividade recente (5 ultimas portas alteradas) ---
        usort($ports, fn($a, $b) => strcmp((string) ($b['date_mod'] ?? ''), (string) ($a['date_mod'] ?? '')));
        $recent = [];
        foreach (array_slice($ports, 0, 5) as $row) {
            $dgo_id = (int) $row['items_id'];
            $recent[] = [
                'position'     => Port::formatPosition((int) $row['tube_num'], (int) $row['fiber_num']),
                'edit_key'     => ((int) $row['tube_num']) . '-' . ((int) $row['fiber_num']),
                'dgo_id'       => $dgo_id,
                'locations_id' => $dgo_locations[$dgo_id] ?? 0,
                'dgo_name'     => $dgo_names[$dgo_id] ?? ('#' . $dgo_id),
                'code'         => (string) ($row['code'] ?? ''),
                'date_mod'     => (string) ($row['date_mod'] ?? ''),
            ];
        }

        return [
            'total_dgos'   => count($dgo_ids),
            'trash_dgos'   => $trash_dgos,
            'capacity'     => $capacity,
            'documented'   => $documented,
            'free'         => max(0, $capacity - $documented),
            'occupancy'    => $capacity > 0 ? round($documented * 100 / $capacity, 1) : 0.0,
            'dgos_sem_doc' => $sem_doc,
            'ports_trash'  => $ports_trash,
            'by_location'  => $by_location,
            'top_dgos'     => $top,
            'recent'       => $recent,
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

    /**
     * Renderiza o dashboard. $url_builder recebe um array de parametros e
     * devolve a URL da pagina do mapa (injetado pelo MapController para nao
     * duplicar a logica de root_doc).
     *
     * Layout em 3 faixas: cartoes / tabela por localizacao (largura total, a
     * unica que cresce com o piloto) / top 5 + atividade lado a lado.
     *
     * @param callable(array):string $url_builder
     * @return void
     */
    public static function display(callable $url_builder): void
    {
        $d = self::collect();

        self::displayCards($d);
        self::displayByLocation($d, $url_builder);
        self::displayBottomRow($d, $url_builder);
    }

    /**
     * Faixa 1: os quatro cartoes de resumo.
     *
     * @param array $d
     * @return void
     */
    private static function displayCards(array $d): void
    {
        echo "<div class='row row-cards g-3 mb-3'>";

        self::card(
            __('DGOs cadastradas', 'dgoplus'),
            (string) $d['total_dgos'],
            $d['trash_dgos'] > 0
                ? sprintf(__('%d na lixeira', 'dgoplus'), $d['trash_dgos'])
                : __('nenhuma na lixeira', 'dgoplus'),
            'ti ti-server',
            'bg-blue-lt'
        );

        self::card(
            __('Ocupação geral', 'dgoplus'),
            self::pctLabel((float) $d['occupancy']),
            sprintf(__('%d de %d portas documentadas', 'dgoplus'), $d['documented'], $d['capacity']),
            'ti ti-chart-donut',
            '',
            (float) $d['occupancy'],
            self::ACCENT
        );

        self::card(
            __('Portas livres', 'dgoplus'),
            (string) $d['free'],
            $d['ports_trash'] > 0
                ? sprintf(__('%d portas na lixeira', 'dgoplus'), $d['ports_trash'])
                : __('nenhuma porta na lixeira', 'dgoplus'),
            'ti ti-plug',
            'bg-green-lt'
        );

        self::card(
            __('DGOs sem documentação', 'dgoplus'),
            (string) $d['dgos_sem_doc'],
            $d['dgos_sem_doc'] > 0
                ? __('sem nenhuma porta registrada ainda', 'dgoplus')
                : __('todas as DGOs já têm alguma porta', 'dgoplus'),
            $d['dgos_sem_doc'] > 0 ? 'ti ti-alert-triangle' : 'ti ti-circle-check',
            $d['dgos_sem_doc'] > 0 ? 'bg-yellow-lt' : 'bg-green-lt'
        );

        echo "</div>";
    }

    /**
     * Faixa 2: tabela por localizacao, em largura total.
     *
     * Largura total de proposito: e' a unica tabela com 5 colunas e a unica
     * que cresce conforme o piloto ganha localizacoes. Espremida em 7/12 da
     * tela, o table-responsive cortava a coluna de ocupacao na borda do card.
     *
     * @param array                  $d
     * @param callable(array):string $url_builder
     * @return void
     */
    private static function displayByLocation(array $d, callable $url_builder): void
    {
        echo "<div class='row row-cards g-3 mb-3'>";
        echo "<div class='col-12'>";
        echo "<div class='card'>";

        echo "<div class='card-header d-flex align-items-center justify-content-between'>";
        echo "<h3 class='card-title mb-0'><i class='ti ti-map-pin me-1'></i>"
            . __('DGOs por localização', 'dgoplus') . "</h3>";
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
                . __('Nenhuma DGO cadastrada ainda. Escolha uma localização acima e use "+ Nova DGO".', 'dgoplus')
                . "</div>";
            echo "</div></div></div>";
            return;
        }

        echo "<div class='table-responsive'>";
        echo "<table class='table table-vcenter card-table mb-0'>";
        echo "<thead><tr>"
            . "<th>" . __('Localização', 'dgoplus') . "</th>"
            . "<th class='text-end'>" . __('DGOs', 'dgoplus') . "</th>"
            . "<th class='text-end'>" . __('Documentadas', 'dgoplus') . "</th>"
            . "<th class='text-end'>" . __('Livres', 'dgoplus') . "</th>"
            . "<th style='width:34%'>" . __('Ocupação', 'dgoplus') . "</th>"
            . "</tr></thead><tbody>";

        foreach ($d['by_location'] as $loc_id => $row) {
            $free = max(0, $row['capacity'] - $row['documented']);
            $pct  = $row['capacity'] > 0 ? round($row['documented'] * 100 / $row['capacity'], 1) : 0.0;
            $url  = $url_builder(['location' => $loc_id]);

            echo "<tr>";
            echo "<td><a href='" . htmlescape($url) . "' class='text-decoration-none'>"
                . "<i class='ti ti-map-pin me-1 text-muted'></i>" . htmlescape($row['name']) . "</a></td>";
            echo "<td class='text-end'>" . (int) $row['dgos'] . "</td>";
            echo "<td class='text-end'>" . (int) $row['documented'] . "</td>";
            echo "<td class='text-end'>" . $free . "</td>";
            echo "<td>" . self::gauge((float) $pct) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";

        if (count($d['by_location']) > 1) {
            echo "<tfoot><tr class='fw-bold'>";
            echo "<td>" . __('Total', 'dgoplus') . "</td>";
            echo "<td class='text-end'>" . (int) $d['total_dgos'] . "</td>";
            echo "<td class='text-end'>" . (int) $d['documented'] . "</td>";
            echo "<td class='text-end'>" . (int) $d['free'] . "</td>";
            echo "<td>" . self::gauge((float) $d['occupancy']) . "</td>";
            echo "</tr></tfoot>";
        }

        echo "</table></div>";
        echo "</div></div></div>";
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
        echo "<div class='col-12 col-lg-6'>";
        echo "<div class='card h-100'>";
        echo "<div class='card-header'><h3 class='card-title mb-0'>"
            . "<i class='ti ti-flame me-1'></i>" . __('DGOs mais ocupadas', 'dgoplus') . "</h3></div>";

        if ($d['top_dgos'] === []) {
            echo "<div class='card-body text-muted'>" . __('Nada documentado ainda.', 'dgoplus') . "</div>";
        } else {
            echo "<div class='table-responsive'><table class='table table-vcenter card-table mb-0'><tbody>";
            $rank = 0;
            foreach ($d['top_dgos'] as $t) {
                $rank++;
                $url = $url_builder(['location' => (int) $t['locations_id'], 'dgo' => (int) $t['id']]);
                echo "<tr>";
                echo "<td style='width:28px' class='text-muted'>" . $rank . "</td>";
                echo "<td><a href='" . htmlescape($url) . "' class='text-decoration-none'>"
                    . htmlescape($t['name']) . "</a></td>";
                echo "<td class='text-end text-nowrap text-muted'>"
                    . (int) $t['documented'] . "/" . (int) $t['capacity'] . "</td>";
                echo "<td style='width:38%'>" . self::gauge((float) $t['pct']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
        }
        echo "</div></div>";

        // --- Atividade recente ---
        echo "<div class='col-12 col-lg-6'>";
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
                    'dgo'      => (int) $r['dgo_id'],
                    'edit'     => $r['edit_key'],
                ]) . '#dgoplus-panel';

                echo "<tr>";
                echo "<td class='text-nowrap' style='width:70px'>"
                    . "<a href='" . htmlescape($url) . "' class='badge bg-blue-lt text-decoration-none'>"
                    . htmlescape($r['position']) . "</a></td>";
                echo "<td>" . htmlescape($r['dgo_name'])
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
     * Um cartao de resumo. A pastilha do icone usa classe nativa do tema
     * ($pill_class) ou, quando vazia, o destaque teal por cor explicita.
     *
     * @param string      $title
     * @param string      $value
     * @param string      $subtitle
     * @param string      $icon
     * @param string      $pill_class  classe de fundo nativa; '' = destaque teal
     * @param float|null  $bar_pct     barra fina sob o valor (null = sem barra)
     * @param string      $value_color cor explicita do valor (vazio = padrao do tema)
     * @return void
     */
    private static function card(
        string $title,
        string $value,
        string $subtitle,
        string $icon,
        string $pill_class,
        ?float $bar_pct = null,
        string $value_color = ''
    ): void {
        $pill_style = 'width:38px;height:38px;border-radius:10px;display:inline-flex;'
            . 'align-items:center;justify-content:center;flex:0 0 auto;font-size:18px';

        if ($pill_class === '') {
            $pill_style .= ';background:' . self::ACCENT_SOFT . ';color:' . self::ACCENT;
        }

        echo "<div class='col-12 col-sm-6 col-xl-3'>";
        echo "<div class='card h-100'><div class='card-body'>";

        echo "<div class='d-flex align-items-center gap-2 mb-2'>";
        echo "<span class='" . htmlescape($pill_class) . "' style='" . $pill_style . "'>"
            . "<i class='" . htmlescape($icon) . "'></i></span>";
        echo "<span class='text-muted'>" . htmlescape($title) . "</span>";
        echo "</div>";

        $style = $value_color !== '' ? " style='color:" . htmlescape($value_color) . "'" : '';
        echo "<div class='h1 mb-1'" . $style . ">" . htmlescape($value) . "</div>";

        if ($bar_pct !== null) {
            echo self::bar($bar_pct);
        }

        echo "<div class='text-muted small mt-1'>" . htmlescape($subtitle) . "</div>";

        echo "</div></div>";
        echo "</div>";
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
     * cheia (vermelho). Ocupacao alta e' o alerta - significa DGO sem folga.
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
