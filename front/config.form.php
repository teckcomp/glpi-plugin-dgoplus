<?php
/**
 * DGO+ - tela de configuracao (bloco 3l, ampliada no 4a).
 *
 * Chegada por Configurar -> Plugins -> botao "Configurar" do DGO+, que o core
 * monta a partir do hook CONFIG_PAGE (Plugin.php:2847-2850).
 *
 * Nao precisa de bootstrap: front/*.php de plugin no GLPI 11 ja recebe sessao,
 * autoload e $CFG_GLPI de pe (licao 3).
 *
 * As listas sao geradas a partir de Setting::getRoles(): papel novo no
 * registro aparece aqui sozinho, sem editar esta tela.
 */
use GlpiPlugin\Dgoplus\Setting;
// Configurar o plugin e' ato de administracao, nao de uso: exige o direito de
// configuracao geral do GLPI, nao o direito das portas.
Session::checkRight('config', UPDATE);
if (isset($_POST['update'])) {
    // O core valida o token CSRF sozinho por causa do CSRF_COMPLIANT
    // declarado no setup.php.
    $posted = [];
    foreach (Setting::getRoles() as $role) {
        $value = $_POST[Setting::getRoleKey($role)] ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }
        $posted[$role] = $value;
    }

    // O mesmo Tipo em dois papeis faz contagem, filtro e cadastro divergirem
    // em silencio. Recusa, nao aviso: nada e' gravado ate' o conflito sair.
    $conflicts = Setting::findConflicts($posted);

    if ($conflicts !== []) {
        $names  = Setting::getAvailableTypes();
        $detail = [];

        foreach ($conflicts as $types_id => $roles) {
            $labels = array_map([Setting::class, 'getRoleLabel'], $roles);
            $detail[] = sprintf(
                '%s (%s)',
                $names[$types_id] ?? ('#' . $types_id),
                implode(', ', $labels)
            );
        }

        Session::addMessageAfterRedirect(
            sprintf(
                __('Nada foi salvo: o mesmo Tipo não pode estar em dois papéis — %s.', 'dgoplus'),
                implode('; ', $detail)
            ),
            false,
            ERROR
        );
        Html::back();
    }

    foreach ($posted as $role => $ids) {
        Setting::setTypesForRole($role, $ids);
    }

    Session::addMessageAfterRedirect(__('Configuração salva.', 'dgoplus'), false, INFO);
    Html::back();
}
// Mesmos argumentos que o core usa em front/plugin.php:48 -- setor "config",
// item "plugin" (singular). Errar isso deixa a pagina sem o menu ativo.
Html::header(
    __('DGO+', 'dgoplus'),
    $_SERVER['REQUEST_URI'] ?? '',
    'config',
    'plugin'
);
$available = Setting::getAvailableTypes();
$by_role   = Setting::getTypesByRole();
echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h3 class='card-title d-flex align-items-center gap-2'>"
    . "<i class='ti ti-layout-grid'></i>" . htmlescape(__('Papel de cada Tipo de ativo', 'dgoplus'))
    . "</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<p class='text-muted'>"
    . htmlescape(__(
        'O DGO+ trabalha sobre o ativo nativo "Dispositivo passivo", que também é usado para patch panels, calhas e outros equipamentos. Escolha abaixo quais Tipos representam cada papel. O papel vem sempre do Tipo do ativo, nunca do nome dele.',
        'dgoplus'
    ))
    . "</p>";
if ($available === []) {
    // Estado vazio nunca fica mudo (licao 16).
    echo "<div class='alert alert-info'>";
    echo "<i class='ti ti-info-circle me-1'></i>";
    echo htmlescape(__(
        'Nenhum "Tipo de dispositivo passivo" cadastrado ainda. Cadastre em Configurar → Listas suspensas → Tipo de dispositivo passivo e volte aqui.',
        'dgoplus'
    ));
    echo "</div>";
} else {
    echo "<form method='post' action='" . htmlescape($_SERVER['REQUEST_URI'] ?? '') . "'>";
    foreach (Setting::getRoles() as $role) {
        $key = Setting::getRoleKey($role);
        echo "<div class='mb-3'>";
        echo "<label class='form-label'>"
            . htmlescape(sprintf(__('Tipos que são %s', 'dgoplus'), Setting::getRoleLabel($role)))
            . "</label>";
        Dropdown::showFromArray($key, $available, [
            'multiple' => true,
            'values'   => $by_role[$role] ?? [],
            'width'    => '100%',
        ]);
        echo "</div>";
    }
    echo "<div class='alert alert-warning'>";
    echo "<i class='ti ti-alert-triangle me-1'></i>";
    echo "<strong>" . htmlescape(__('Deixar todos em branco mantém o comportamento anterior:', 'dgoplus')) . "</strong> ";
    echo htmlescape(__(
        'todos os dispositivos passivos aparecem no DGO+. Ao mapear qualquer Tipo, os ativos cujo Tipo não estiver em nenhum papel deixam de aparecer — inclusive os que já estão documentados. As portas não são apagadas: voltam a aparecer se o Tipo for ajustado. O mesmo Tipo não pode estar em dois papéis.',
        'dgoplus'
    ));
    echo "</div>";
    echo "<p class='text-muted small'>";
    echo htmlescape(__(
        'Dica: para ajustar vários ativos de uma vez, use Ativos → Dispositivos passivos, selecione os elementos e aplique a ação em massa de alteração do Tipo.',
        'dgoplus'
    ));
    echo "</p>";
    echo "<div class='d-flex justify-content-end'>";
    echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
    echo "</div>";
    Html::closeForm();
}
echo "</div>";
echo "</div>";
Html::footer();
