<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
use Dropdown;

/**
 * Ponto unico do rotulo de um elemento na tela.
 *
 * Bloco 5e-2a
 * -----------
 * O 5e resolveu a ambiguidade no SELETOR DE DESTINO, e la a desambiguacao e'
 * POR COLISAO: monta-se a lista de candidatos, conta-se rotulo repetido, e so'
 * o par repetido ganha sufixo.
 *
 * Essa regra NAO se reaproveita aqui, e o motivo e' estrutural, nao estetico:
 * colisao so' se detecta onde existe uma LISTA. Nos cards de vinculo ha' UM
 * destino e UMA origem - nao ha' com quem colidir, e comparar com o universo
 * inteiro custaria uma consulta a cada impressao. Logo, aqui a regra e' FIXA.
 *
 * O formato
 * ---------
 * `nome · localizacao · #id`, decisao do usuario em 28/08 (opcao B: o id
 * FECHA o rotulo, nao abre).
 *
 * - o NOME e' o que o operador reconhece;
 * - a LOCALIZACAO e' o que identifica de fato, porque o mesmo nome se repete
 *   entre unidades - e o elemento do outro lado de um vinculo pode estar em
 *   QUALQUER localizacao, ao contrario do seletor de destino, que o 5a ja
 *   recorta;
 * - o #id DESAMBIGUA o resto: dois homonimos na MESMA localizacao (o caso dos
 *   dois `CTO 01` da homologacao) empatariam ate' na localizacao.
 *
 * O #id sai sempre, e nao so' no empate, justamente porque nao ha' lista para
 * medir empate. Ele e' tambem a chave real da referencia: nome e' rotulo,
 * `itemtype` + `id` e' chave.
 *
 * Custo
 * -----
 * `forRow()` nao consulta nada: recebe a linha ja carregada. So' o nome da
 * localizacao exige leitura, e ela e' memorizada por id no cache estatico -
 * uma tela com quatro elementos em duas localizacoes faz DUAS leituras, nao
 * quatro.
 */
final class ItemLabel
{
    /** @var array<int,string> nome de localizacao ja resolvido nesta requisicao */
    private static array $loc_cache = [];

    /**
     * Rotulo a partir de uma linha JA CARREGADA.
     *
     * Existe separado do forItem() porque as telas em lote (pendentes,
     * trilha) ja tem a linha em maos: reler por getFromDB seria uma consulta
     * por item so' para repetir o que o find() trouxe.
     *
     * @param array $row      linha do elemento (espera 'name' e 'locations_id')
     * @param int   $items_id id do elemento
     * @return string
     */
    public static function forRow(array $row, int $items_id): string
    {
        $name = trim((string) ($row['name'] ?? ''));

        // Licao 16: estado vazio nao fica mudo. Nome vazio dizendo apenas o id
        // esconderia que o campo esta em branco - o que e' informacao.
        if ($name === '') {
            $name = __('sem nome', 'dgoplus');
        }

        return $name
            . ' · ' . self::locationName((int) ($row['locations_id'] ?? 0))
            . ' · #' . $items_id;
    }

    /**
     * Rotulo a partir do itemtype + id, lendo o elemento.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return string
     */
    public static function forItem(string $itemtype, int $items_id): string
    {
        if ($itemtype === '' || $items_id <= 0 || !class_exists($itemtype)) {
            return sprintf(__('elemento #%d', 'dgoplus'), $items_id);
        }

        $item = new $itemtype();
        if (!($item instanceof CommonDBTM) || !$item->getFromDB($items_id)) {
            // Elemento ausente: o rotulo completo mentiria ("sem nome · sem
            // localizacao") sugerindo um ativo existente e mal preenchido.
            // Mantido o texto que ja existia antes do 5e-2a.
            return sprintf(__('elemento #%d', 'dgoplus'), $items_id);
        }

        return self::forRow($item->fields, $items_id);
    }

    /**
     * Nome da localizacao, memorizado.
     *
     * Mesmo tratamento do Dashboard::locationLabel(), inclusive o '&nbsp;' que
     * o getDropdownName devolve quando o id nao existe mais.
     *
     * @param int $locations_id
     * @return string
     */
    private static function locationName(int $locations_id): string
    {
        if ($locations_id <= 0) {
            return __('sem localização', 'dgoplus');
        }

        if (isset(self::$loc_cache[$locations_id])) {
            return self::$loc_cache[$locations_id];
        }

        $name = Dropdown::getDropdownName('glpi_locations', $locations_id);
        $name = ($name !== '' && $name !== '&nbsp;') ? $name : ('#' . $locations_id);

        self::$loc_cache[$locations_id] = $name;

        return $name;
    }
}
