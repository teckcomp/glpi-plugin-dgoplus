<?php

namespace GlpiPlugin\Dgoplus;

use CommonDBTM;
use Config;
use PassiveDCEquipmentType;

/**
 * Configuracao do plugin (bloco 3l, ampliado no 4a).
 *
 * Guarda quais "Tipos de dispositivo passivo" o DGO+ reconhece e QUAL PAPEL
 * cada Tipo representa: DIO, DGO ou CTO.
 *
 * Ate o 3l existia um papel so ("e' DGO ou nao e'"), numa chave unica
 * dgo_types. O 4a transforma isso num REGISTRO DE PAPEIS: cada papel tem sua
 * propria chave em glpi_configs, e a lista de papeis mora numa constante so
 * (self::ROLES). Papel novo - Splitter, por exemplo - entra ali e as telas se
 * ajustam sozinhas; nao ha papel escrito a mao em cinco lugares diferentes.
 *
 * REGRA DURA: o papel vem do Tipo nativo, NUNCA do nome do ativo. Produção
 * cadastra CTO com nome "DG08/CTO15" e isso funciona so' porque alguem digitou
 * certo. Nome muda; no dia em que mudar, o mapa se reorganizaria sozinho sem
 * ninguem pedir.
 *
 * Nao tem tabela propria: usa o Config nativo do GLPI, que grava em
 * glpi_configs por contexto. Por isso este bloco NAO mexe no instalador.
 *
 * Guarda o ID do tipo, nunca o nome: renomear o intitulado depois nao pode
 * quebrar o reconhecimento dos elementos (licao 32).
 */
class Setting
{
    /** Contexto no glpi_configs */
    public const CONTEXT = 'plugin:dgoplus';

    /** Chaves de configuracao, uma por papel */
    public const DIO_TYPES = 'dio_types';
    public const DGO_TYPES = 'dgo_types';
    public const CTO_TYPES = 'cto_types';
    public const PTO_TYPES = 'pto_types';

    /** Identificadores de papel */
    public const ROLE_DIO = 'dio';
    public const ROLE_DGO = 'dgo';
    public const ROLE_CTO = 'cto';
    public const ROLE_PTO = 'pto';

    /**
     * O registro de papeis.
     *
     * A ORDEM IMPORTA e nao e' alfabetica: e' a ordem da hierarquia fisica
     * (DIO alimenta DGO, que alimenta CTO). E' a mesma ordem em que as colunas
     * aparecem no painel e em que os papeis aparecem no filtro.
     *
     * @var array<string, string> papel => chave no glpi_configs
     */
    private const ROLES = [
        self::ROLE_DIO => self::DIO_TYPES,
        self::ROLE_DGO => self::DGO_TYPES,
        self::ROLE_CTO => self::CTO_TYPES,
        self::ROLE_PTO => self::PTO_TYPES,
    ];

    // -----------------------------------------------------------------
    // Registro de papeis
    // -----------------------------------------------------------------

    /**
     * Papeis existentes, na ordem da hierarquia.
     *
     * @return string[]
     */
    public static function getRoles(): array
    {
        return array_keys(self::ROLES);
    }

    /**
     * O papel existe no registro?
     *
     * @param string $role
     * @return bool
     */
    public static function isRole(string $role): bool
    {
        return isset(self::ROLES[$role]);
    }

    /**
     * Rotulo do papel para exibicao.
     *
     * Nao passa por __(): DIO, DGO e CTO sao siglas tecnicas, iguais em
     * qualquer idioma. Traduzir viraria ruido.
     *
     * @param string $role
     * @return string
     */
    public static function getRoleLabel(string $role): string
    {
        return strtoupper($role);
    }

    /**
     * Chave do glpi_configs que guarda os Tipos deste papel.
     *
     * @param string $role
     * @return string cadeia vazia quando o papel nao existe
     */
    public static function getRoleKey(string $role): string
    {
        return self::ROLES[$role] ?? '';
    }

    /**
     * A hierarquia inteira como texto: "DIO → DGO → CTO → PTO".
     *
     * Bloco 4h: as mensagens de tela que descreviam a hierarquia tinham a
     * triade escrita a mao e mentiriam a cada papel novo. Derivada do
     * registro, a frase se corrige sozinha - mesmo principio do registro.
     * PONTO UNICO: toda mensagem que cite a cadeia usa este metodo.
     *
     * @return string
     */
    public static function getRoleChainLabel(): string
    {
        return implode(' → ', array_map([self::class, 'getRoleLabel'], self::getRoles()));
    }

    /**
     * Os papeis como lista de escolha: "DIO, DGO, CTO ou PTO".
     *
     * Mesma razao do getRoleChainLabel(), para as mensagens que oferecem os
     * papeis em vez de ordena-los.
     *
     * @return string
     */
    public static function getRoleListLabel(): string
    {
        $labels = array_map([self::class, 'getRoleLabel'], self::getRoles());
        $last   = (string) array_pop($labels);

        if ($labels === []) {
            return $last;
        }

        return implode(', ', $labels) . ' ' . __('ou', 'dgoplus') . ' ' . $last;
    }

    /**
     * O papel RECEBE alimentacao (tem faixa de entradas E1-E4)?
     *
     * Regra do 4b-2, generalizada no 4h: todo papel ABAIXO do primeiro do
     * registro recebe - ninguem alimenta o topo da hierarquia (DIO), e
     * elemento sem papel mapeado fica de fora porque sem papel nao ha como
     * afirmar que ele recebe. Antes do 4h isso estava escrito a mao
     * (DGO/CTO) em dois pontos do MapController; agora o 5o papel ja nasce
     * com a faixa. PONTO UNICO da regra.
     *
     * @param string|null $role
     * @return bool
     */
    public static function roleReceivesFeed(?string $role): bool
    {
        if ($role === null || !self::isRole($role)) {
            return false;
        }

        return $role !== (self::getRoles()[0] ?? null);
    }

    // -----------------------------------------------------------------
    // Leitura e gravacao por papel
    // -----------------------------------------------------------------

    /**
     * IDs dos Tipos configurados para um papel.
     *
     * @param string $role
     * @return int[]
     */
    public static function getTypesForRole(string $role): array
    {
        $key = self::getRoleKey($role);

        if ($key === '') {
            return [];
        }

        $values = Config::getConfigurationValues(self::CONTEXT, [$key]);

        $raw = $values[$key] ?? '';
        if ($raw === '' || $raw === null) {
            return [];
        }

        $ids = importArrayFromDB($raw);
        if (!is_array($ids)) {
            return [];
        }

        return self::cleanIds($ids);
    }

    /**
     * @param string $role
     * @param int[]  $ids
     * @return void
     */
    public static function setTypesForRole(string $role, array $ids): void
    {
        $key = self::getRoleKey($role);

        if ($key === '') {
            return;
        }

        Config::setConfigurationValues(self::CONTEXT, [
            $key => exportArrayToDB(self::cleanIds($ids)),
        ]);
    }

    /**
     * Tudo que esta mapeado, por papel.
     *
     * @return array<string, int[]>
     */
    public static function getTypesByRole(): array
    {
        $result = [];

        foreach (self::getRoles() as $role) {
            $result[$role] = self::getTypesForRole($role);
        }

        return $result;
    }

    /**
     * Todos os Tipos reconhecidos pelo plugin, de qualquer papel.
     *
     * @return int[]
     */
    public static function getAllTypes(): array
    {
        $all = [];

        foreach (self::getRoles() as $role) {
            foreach (self::getTypesForRole($role) as $id) {
                $all[] = $id;
            }
        }

        return array_values(array_unique($all));
    }

    /**
     * Qual papel este Tipo representa?
     *
     * @param int $types_id
     * @return string|null null quando o Tipo nao esta mapeado
     */
    public static function getRoleForType(int $types_id): ?string
    {
        if ($types_id <= 0) {
            return null;
        }

        foreach (self::getRoles() as $role) {
            if (in_array($types_id, self::getTypesForRole($role), true)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Qual papel este ativo tem?
     *
     * @param CommonDBTM $item
     * @return string|null
     */
    public static function getRoleOfItem(CommonDBTM $item): ?string
    {
        $field = self::getTypeField();

        return self::getRoleForType((int) ($item->fields[$field] ?? 0));
    }

    /**
     * Conflito de mapeamento: o mesmo Tipo em mais de um papel.
     *
     * Erro que so' existe a partir do 4a, e que erra em silencio: com o Tipo
     * em dois papeis, cada consulta responde uma coisa e contagem, filtro e
     * cadastro divergem sem ninguem perceber. Por isso a tela RECUSA salvar,
     * em vez de avisar.
     *
     * @param array<string, int[]> $by_role
     * @return array<int, string[]> types_id => papeis em que aparece
     */
    public static function findConflicts(array $by_role): array
    {
        $seen = [];

        foreach (self::getRoles() as $role) {
            foreach (self::cleanIds($by_role[$role] ?? []) as $id) {
                $seen[$id][] = $role;
            }
        }

        return array_filter($seen, static fn(array $roles): bool => count($roles) > 1);
    }

    // -----------------------------------------------------------------
    // Criterios de consulta
    // -----------------------------------------------------------------

    /**
     * Nome da coluna do tipo, derivado do core em vez de escrito a mao
     * (licao 32): glpi_passivedcequipmenttypes -> passivedcequipmenttypes_id.
     *
     * @return string
     */
    public static function getTypeField(): string
    {
        return PassiveDCEquipmentType::getForeignKeyField();
    }

    /**
     * Nenhum Tipo mapeado em papel NENHUM = plugin se comporta como antes do
     * 3l.
     *
     * A semantica de "lista vazia = filtro desligado" continua valendo, agora
     * sobre o conjunto: basta UM papel preenchido para o filtro ligar. Sem
     * isso, quem nunca configurou perderia o mapa inteiro.
     *
     * @return bool
     */
    public static function isTypeFilterEnabled(): bool
    {
        return self::getAllTypes() !== [];
    }

    /**
     * Criterio para somar (+) a um find() de PassiveDCEquipment, cobrindo
     * todos os papeis.
     *
     * Devolve array vazio quando o filtro esta desligado, para o `+` nao
     * alterar nada - mesmo padrao de getEntitiesRestrictCriteria (licao 5).
     *
     * @return array
     */
    public static function typesCriteria(): array
    {
        $types = self::getAllTypes();

        if ($types === []) {
            return [];
        }

        return [self::getTypeField() => $types];
    }

    /**
     * Criterio restrito a UM papel.
     *
     * Diferente do typesCriteria(): papel sem Tipo nenhum devolve um criterio
     * IMPOSSIVEL, nao um criterio vazio. Filtrar por CTO sem CTO mapeada tem
     * de devolver lista vazia, e nao a base inteira.
     *
     * @param string $role
     * @return array
     */
    public static function roleCriteria(string $role): array
    {
        $types = self::getTypesForRole($role);

        if ($types === []) {
            return [self::getTypeField() => -1];
        }

        return [self::getTypeField() => $types];
    }

    /**
     * O ativo e' reconhecido pelo plugin (tem algum papel)?
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public static function isMapped(CommonDBTM $item): bool
    {
        if (!self::isTypeFilterEnabled()) {
            return true;
        }

        return self::getRoleOfItem($item) !== null;
    }

    /**
     * Primeiro Tipo de um papel - o que gravar num elemento criado pela
     * propria tela do DGO+.
     *
     * Sem tipo, o elemento nasceria fora do filtro e desapareceria no instante
     * em que fosse criado, sem erro em log nenhum (licao 14).
     *
     * @param string $role
     * @return int 0 quando o papel nao tem Tipo mapeado
     */
    public static function getTypeForNewItem(string $role): int
    {
        $types = self::getTypesForRole($role);

        return $types === [] ? 0 : (int) $types[0];
    }

    /**
     * Tipos disponiveis para escolher na tela de configuracao.
     *
     * @return array<int, string> id => nome
     */
    public static function getAvailableTypes(): array
    {
        $type   = new PassiveDCEquipmentType();
        $result = [];

        foreach ($type->find([], ['name ASC']) as $row) {
            $result[(int) $row['id']] = (string) ($row['name'] ?? '');
        }

        return $result;
    }

    /**
     * @param array $ids
     * @return int[]
     */
    private static function cleanIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

        return array_values(array_unique($ids));
    }

    // -----------------------------------------------------------------
    // Compatibilidade com o 3l - ENCERRADA
    //
    // Bloco 4f: removidos os quatro metodos sem chamador. Os tres primeiros
    // eram uma linha delegando para getAllTypes(), setTypesForRole() e
    // typesCriteria(). O quarto tinha logica propria: se o papel DGO nao
    // tivesse Tipo, caia para o primeiro Tipo de QUALQUER papel - fallback
    // que getTypeForNewItem() nao faz. Registrado aqui caso volte a fazer
    // falta.
    //
    // Bloco 4g: removido o ultimo deles, que era um apelido de isMapped() e
    // so' continuava vivo porque o setup.php estava proibido na copia
    // disco->repositorio (licao 105). Com o bump da 1.3.0 a proibicao caiu, a
    // chamada do setup.php foi trocada e o apelido saiu junto.
    //
    // Equivalencias, se aparecer chamador em codigo externo:
    //   getDgoTypes()       -> getAllTypes()
    //   setDgoTypes($ids)   -> setTypesForRole(ROLE_DGO, $ids)
    //   dgoCriteria()       -> typesCriteria()
    //   getTypeForNewDgo()  -> getTypeForNewItem(ROLE_DGO), SEM o fallback
    //   isDgo($item)        -> isMapped($item), identico
    //
    // Nao existe mais superficie de compatibilidade do 3l nesta classe.
    // -----------------------------------------------------------------
}
