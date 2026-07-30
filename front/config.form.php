<?php

/**
 * DGO+ - tela de configuracao (bloco 3l).
 *
 * Chegada por Configurar -> Plugins -> botao "Configurar" do DGO+, que o core
 * monta a partir do hook CONFIG_PAGE (Plugin.php:2847-2850).
 *
 * Nao precisa de bootstrap: front/*.php de plugin no GLPI 11 ja recebe sessao,
 * autoload e $CFG_GLPI de pe (licao 3).
 */

use GlpiPlugin\Dgoplus\Setting;

// Configurar o plugin e' ato de administracao, nao de uso: exige o direito de
// configuracao geral do GLPI, nao o direito das portas.
Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
    // O core valida o token CSRF sozinho por causa do CSRF_COMPLIANT
    // declarado no setup.php.
    $types = $_POST['dgo_types'] ?? [];

    if (!is_array($types)) {
        $types = [$types];
    }

    Setting::setDgoTypes($types);

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
$selected  = Setting::getDgoTypes();

echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h3 class='card-title d-flex align-items-center gap-2'>"
    . "<i class='ti ti-layout-grid'></i>" . htmlescape(__('Quais ativos são DGO', 'dgoplus'))
    . "</h3>";
echo "</div>";

echo "<div class='card-body'>";

echo "<p class='text-muted'>"
    . htmlescape(__(
        'O DGO+ trabalha sobre o ativo nativo "Dispositivo passivo", que também é usado para patch panels, calhas e outros equipamentos. Escolha abaixo quais Tipos representam uma DGO.',
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

    echo "<div class='mb-3'>";
    echo "<label class='form-label'>" . htmlescape(__('Tipos que são DGO', 'dgoplus')) . "</label>";
    Dropdown::showFromArray('dgo_types', $available, [
        'multiple' => true,
        'values'   => $selected,
        'width'    => '100%',
    ]);
    echo "</div>";

    echo "<div class='alert alert-warning'>";
    echo "<i class='ti ti-alert-triangle me-1'></i>";
    echo "<strong>" . htmlescape(__('Deixar em branco mantém o comportamento atual:', 'dgoplus')) . "</strong> ";
    echo htmlescape(__(
        'todos os dispositivos passivos são tratados como DGO. Ao escolher um ou mais Tipos, os ativos que não tiverem esses Tipos deixam de aparecer no DGO+ — inclusive os que já estão documentados. As portas não são apagadas: voltam a aparecer se o Tipo for ajustado.',
        'dgoplus'
    ));
    echo "</div>";

    echo "<p class='text-muted small'>";
    echo htmlescape(__(
        'Dica: para ajustar vários ativos de uma vez, use Ativos → Dispositivos passivos, selecione as DGOs e aplique a ação em massa de alteração do Tipo.',
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
