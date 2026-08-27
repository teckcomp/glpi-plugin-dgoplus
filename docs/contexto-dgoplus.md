# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v5 — 27/08/2026. Substitui o v4 integralmente.
> Emitido ao fechar a **dívida de repositório do Bloco 5h**: o `master` foi
> alinhado em 1.3.2 e a paridade com a homologação foi **provada por md5 nos 27
> arquivos**, não deduzida.
>
> Companheiro: `roadmap-dgoplus-v5.md`.

---

## 0. A regra que governa tudo (nova em 27/08)

**O GitHub é o repositório canônico do DGO+. A homologação é descartável.**

Todo estado do código tem que ser reconstruível a partir do `master` sozinho. Se
o servidor sumir amanhã, nenhuma linha de código se perde.

A regra nasceu de um fato, não de preferência: **houve um incidente doméstico em
que a base de homologação foi perdida com um repositório dentro dela, levando
junto correções que nunca chegaram ao Git.**

Isso convive com a regra de precisão do projeto (fonte da verdade = saída real
verificada na sessão) porque são duas perguntas diferentes:

| Pergunta | Fonte da resposta |
|---|---|
| **O que está rodando agora?** (diagnóstico de tela, erro, permissão) | O servidor — sempre |
| **O que o código É?** (registro durável, base de qualquer bloco novo) | **O GitHub — sempre** |

O incidente é justamente o caso em que as duas se separaram e a segunda morreu
com a máquina.

### A ordem de entrega decorre daí

A partir do próximo bloco, nesta ordem, sem exceção:

1. O assistente prepara a mudança e valida (`php -l`, leitura do core quando preciso).
2. **Commit primeiro** — o arquivo vai ao GitHub antes de qualquer coisa tocar o servidor.
3. **O zip nasce do commit**, não do rascunho: baixa-se o tarball do commit novo e
   monta-se o pacote a partir dele.
4. O usuário aplica e testa. Se reprovar, o `revert` no GitHub e o zip de rollback
   saem do mesmo lugar.

O ganho: zip, repositório e servidor passam a ser **o mesmo artefato provado por
md5**, em vez de três cópias que se parecem. Custa uma ida e volta a mais por
bloco.

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 27/08 (fim da sessão) | commit **`bd28ffdb40ee48224d3abe188ab7fb82dc19808d`**, versão **1.3.2** |
| Versão em homologação | **1.3.2** |
| **Paridade** | ✅ **Provada em 27/08: os 27 arquivos batem por md5 entre servidor e `master`.** Nenhum arquivo sobrando de nenhum lado. A divergência que o v4 registrava está encerrada |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** — senha |
| PC do usuário | **Windows, sem Git local.** Envio por `pscp`; commits pela **web do GitHub** |
| Assistente | Não tem SSH nem senha. Prepara e valida → commit → zip a partir do commit → o usuário aplica |

> O endereço `192.168.1.50` do contexto v1 (e da skill `glpi-plugin-teckcomp`)
> está **morto**. A skill não foi atualizada — ao lê-la, substituir host e usuário
> e acrescentar a porta.

Nas sessões observadas o shell do servidor estava logado como **root**
(`root@debian`). O console do GLPI recusa root puro, então todo comando de
console vai com `sudo -u www-data`. ⚠️ Em 27/08 o `sudo` **não** estava presente
no sandbox do assistente — isso é do sandbox, não do servidor.

**Enviar o pacote (cmd do Windows — nunca omitir esta linha da entrega):**

```cmd
pscp -P 2078 "%USERPROFILE%\Downloads\<nome-do-zip>.zip" resolutto@177.87.230.179:/tmp/
```

**`-P` maiúsculo** é a porta (lição 115).

**Trazer arquivo do servidor para o PC** (usado em 27/08 para provar o `Port.php`):

```cmd
pscp -P 2078 resolutto@177.87.230.179:/var/www/html/glpi/plugins/dgoplus/src/Port.php "%USERPROFILE%\Downloads\Port.php"
```

Verificado: o `pscp` **não** converte quebras de linha — o arquivo chegou em LF,
UTF-8, com `diff` limpo contra o original.

⚠️ **Em 27/08 o `pscp` falhou uma vez** com `FATAL ERROR: Remote side unexpectedly
closed network connection`, e voltou a funcionar na sequência. Causa **não
diagnosticada**. Roteiro se repetir: (1) o PuTTY comum conecta? (2) `pscp -V` e
`pscp -P 2078 -v ...` para ver onde morre o handshake; (3) `pscp -sftp` como
transporte alternativo; (4) `sudo tail -n 40 /var/log/auth.log` e
`fail2ban-client status sshd` no servidor.

**Aplicar (PuTTY, `ssh -p 2078 resolutto@177.87.230.179`).** Os zips têm prefixo
`dgoplus/`, então extrai-se a partir de `plugins/`:

```bash
cd /var/www/html/glpi/plugins
sudo unzip -o /tmp/<nome-do-zip>.zip
sudo chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
sudo systemctl restart apache2
```

**Conferir que o arquivo novo chegou** (sempre, antes de diagnosticar qualquer
"não fez efeito"):

```bash
grep -c "<token_que_só_existe_na_versão_nova>" /var/www/html/glpi/plugins/dgoplus/<arquivo>
```

**Provar paridade com o repositório** (ferramenta nova, criada em 27/08 — vale a
cada marco):

```bash
cd /var/www/html/glpi/plugins/dgoplus
find . -type f -not -path './.git/*' | sort | xargs md5sum
```

A saída colada no chat permite ao assistente comparar arquivo a arquivo contra o
tarball do commit. Foi assim que a paridade dos 27 arquivos foi provada.

**Log que interessa — e só existe um.** Não há `sql-errors.log` nesta instalação
(lição 122). Erro de SQL aparece no `php-errors.log` como `glpi.CRITICAL`, com a
query inteira e o backtrace:

```bash
sudo tail -n 100 /var/www/html/glpi/files/_log/php-errors.log
```

⚠️ **A confirmar:** se existe pasta de repositório Git no servidor novo, e onde.
Comando de triagem:

```bash
ls -la /var/www/html/glpi/plugins/dgoplus/.git 2>/dev/null | head -3
which git
find /root /home /opt -maxdepth 3 -name ".git" -type d 2>/dev/null
```

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`. Os quatro em
negrito são instalados manualmente. Na tela de 27/08 também aparecem `Diagrams`
3.3.14, `Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6 e
`Tasks list` 2.1.8.

### Quando reinstalar — decidir, não pedir por precaução

| Mudou | O que fazer |
|---|---|
| `src/`, `front/`, `ajax/` (PHP) | `cache:clear` + `systemctl restart apache2` (OPcache) |
| `public/` (JS/SVG) | **Ctrl+F5** no navegador |
| `src/Install.php` (schema, direitos) | `plugin:install --force dgoplus` **e depois** `plugin:activate dgoplus` |
| **Número de versão no `setup.php`** | Idem: `--force` + `activate`, mesmo com `Install.php` idêntico (lição 116) |

---

## 2. Fluxo de trabalho vigente

Método **entrega-em-blocos**: um bloco = uma mudança testável de uma sentada. Se
o teste passa de ~8 passos ou toca duas áreas independentes, divide-se em
`5f-1`, `5f-2` — assim uma falha isola sozinha.

Toda entrega tem quatro seções fixas: **(1)** o que muda, com a decisão de
reinstalar em negrito na primeira linha; **(2)** o comando `pscp` literal;
**(3)** os comandos de aplicar, com o `grep -c` de conferência; **(4)** roteiro
de teste numerado com resultado esperado, onde ler o log e como reverter.

Zip **sempre com nome versionado**, e **sempre gerado a partir do commit** (seção 0).

### Commit pela web do GitHub — o procedimento que funciona

Sem Git no Windows e sem repositório localizado no servidor, os commits saem pela
interface web. Duas formas, e a escolha importa:

- **Editar trechos** (lápis): bom para 1-3 linhas. Ruim para blocos longos —
  erro de digitação em comentário não é pego por nada.
- **Substituir arquivo inteiro** (`Add file` → `Upload files`, dentro da pasta
  certa): garante byte a byte. **É o caminho preferido** quando o arquivo já
  existe pronto (baixado do servidor, por exemplo).

⚠️ **A armadilha, vivida em 27/08:** o upload **não substitui** se o nome não
bater exatamente — cria arquivo novo ao lado, em silêncio. Foi o que gerou
`src/Port.php.php`. E o Windows, ocultando extensões, faz `Port-servidor.php`
virar `Port.php.php` quando se "renomeia para Port.php". Ver lição 131.

**Conserto quando acontecer** (a web não deixa renomear por cima de arquivo
existente): (1) `Delete file` no antigo; (2) lápis no arquivo errado → editar o
**campo de caminho** no topo → nome certo → commit. Dois commits.

**Conferência obrigatória depois de todo upload:** contagem de arquivos da pasta.
`src/` tem **13 arquivos**; a raiz tem `README.md`, `hook.php`, `logo.png`,
`setup.php` mais as pastas `ajax`, `front`, `public`, `src`.

### O repositório é público — usar isso por padrão

O assistente **lê o código do `master` durante a sessão**, sem SSH e sem token:

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://raw.githubusercontent.com/teckcomp/glpi-plugin-dgoplus/master/<arquivo>
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
```

(`api.github.com` bate no limite anônimo — 403.)

⚠️ **Preferir o `codeload` com o SHA ao `raw`** para conferir commit recém-feito:
o `raw` tem cache de CDN e pode devolver o estado anterior. O `git ls-remote` é
autoritativo para o HEAD; o tarball do SHA é autoritativo para o conteúdo.

### O core do GLPI também é legível — e foi decisivo no 5h

`github.com/glpi-project/glpi` na **tag `11.0.6`**, pelos mesmos raw URLs. Dois
detalhes de caminho que custam tempo se esquecidos:

- As classes com namespace `Glpi\` ficam em **`src/Glpi/...`**, não em `src/...`.
  Ex.: `src/Glpi/Search/Provider/SQLProvider.php` (`src/Search/Provider/...` dá 404).
- O schema completo está em `install/mysql/glpi-empty.sql` — é a forma mais rápida
  de confirmar se uma coluna existe numa tabela do core, sem acesso ao banco.

### O sandbox do assistente TEM PHP

`php -l` é possível. **Rodar `apt-get update` e `apt-get install -y php-cli` como
dois comandos separados** — o `update` sai com código ≠ 0 (repositório
`deb.nodesource.com` devolve 403), então encadear com `&&` faz o `install` nunca
rodar. Validado em 27/08 com PHP 8.3.6. **Todo arquivo PHP entregue passa por
`php -l` antes do zip.** Continua valendo: `php -l` **não** pega
incompatibilidade de assinatura com a classe-pai.

O que ainda **não** dá no sandbox: alcançar a homologação (só domínios de pacote
e GitHub liberados), e não há `sudo` (o usuário já é root).

### Práticas abolidas

- Reinstalar o plugin "por precaução" a cada bloco.
- Mandar colar `sed`, heredoc ou edição manual de arquivo grande no terminal.
- Zip do projeto inteiro a cada rodada: só em marco.
- Julgar tela sem antes confirmar a versão instalada (lição 114).
- **Aplicar no servidor antes de commitar** (seção 0).
- **Remontar arquivo a partir do `master` + a descrição do bloco** (lição 129).

---

## 3. Arquitetura

### O que é do GLPI e o que é do plugin

A DGO **não é um itemtype do plugin**. Cada elemento é um `PassiveDCEquipment`
nativo; o plugin acrescenta a grade de portas, o escopo e os vínculos. O core não
conhece as tabelas do plugin, então purgar o ativo deixaria linhas órfãs — daí o
`PurgeCleaner`.

O escopo é **Localização (nativa) → Piso (intitulado do plugin)**. O Setor foi
abandonado em 29/07/2026.

### Papéis — a hierarquia física

`Setting::ROLES`, nesta ordem, **é** a hierarquia: `dio` → `dgo` → `cto` → `pto`.
**Quatro degraus bastam — decidido em 23/08.** O splitter fica deliberadamente
fora: é componente da caixa, não elo da cadeia. As entradas E1–E4 existem
justamente para registrar mais de uma fibra alimentando o mesmo elemento, e a
proporção (1/8, 1/12, 1/16), quando importa, vai no campo OBS ao lado das
entradas.

Mapeamento em produção: **um Tipo por papel** — `DIO+`, `DGO+`, `CTO+`, `PTO+`.
Nenhum Tipo em dois papéis, nenhum elemento fora de papel (31 elementos, todos
reconhecidos). Gravado em `glpi_configs`, contexto `plugin:dgoplus`.

### Portas

Uma tabela, dois tipos de linha, separados por `kind`:

- `KIND_GRID` (`grade`) — a matriz tubo × fibra, cores ABNT/EIA.
- `KIND_ENTRY` (`entrada`) — E1 a E4 (`MAX_ENTRIES = 4`), `tube_num = 0`.

`kind` fica **fora** da chave única (lição 112) — a chave é
`(itemtype, items_id, tube_num, fiber_num)`. `Port::applyInput()` é o **ponto
único de gravação**.

### Vínculos

`glpi_plugin_dgoplus_links`: **uma linha, dois lados**. Regras fechadas:

- **Sem `is_deleted`.** Recusa apaga a linha. Verificado em 23/08: quatro recusas
  seguidas e a quinta proposta na mesma entrada passou normal.
- **Pendente já ocupa a porta**, nas duas pontas.
- **Hierarquia permissiva**: pode pular nível, nunca subir nem empatar.
  `Link::hierarchyAllows()` compara `$order[$src] < $order[$dst]` — sabe que
  desceu, **não sabe quanto desceu**. É a lacuna que o Bloco 5d fecha.
- **Só vínculo confirmado sobe na trilha** (4e). A trilha vem de
  `Link::upstreamLevels()` (definida em `Link.php:735`) chamado com o
  **elemento**, não com a entrada, em `MapController.php:2497` — daí o Bloco 5c.
  **Consumidor único**, o que torna a troca segura.
- `Link::propose()` é o **ponto único de criação**.

### Busca e relatório — o que o core exige de uma tabela polimórfica

Fechado no Bloco 5h, com o core lido (`SQLProvider::getLeftJoinCriteria`):

- Os `jointype` que **existem** no 11.0.6 são: `child`, `item_item`,
  `item_item_revert`, `mainitemtype_mainitem`, `itemtype_item`,
  `itemtype_item_revert`, `itemtypeonly`, `custom_condition_only` e o `default`
  (join padrão). **Qualquer outro valor cai no `default` em silêncio** — foi o
  que o `'empty'` fazia.
- Para a porta (que carrega `itemtype`/`items_id` e aponta para o ativo), o
  jointype certo é **`itemtype_item_revert`**: o ON é
  `ativo.id = ports.items_id AND ports.itemtype = <itemtype>`.
- **`specific_itemtype` é obrigatório** nesse caso: sem ele o core usa o itemtype
  da busca (`PluginDgoplusPort`) na condição do ON, e a coluna voltaria vazia em
  todas as linhas — falha silenciosa, não erro.
- `computeComplexJoinID()` só considera `condition`, `beforejoin` e
  `jointype=child` com `linkfield`. Por isso o `beforejoin` do 5h não gera alias
  extra: a tabela do ativo entra com o próprio nome.

Confirmado no `glpi-empty.sql` do 11.0.6: `glpi_passivedcequipments` tem
`locations_id` **e `is_recursive`** (⚠️ conferido no schema oficial do core, não
por `DESCRIBE` na instalação — mas é pré-requisito do 5f-3 e está de pé).

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito próprio
`plugin_dgoplus_port`, matriz de 4 níveis = **15**.

**Como o direito se comporta hoje (medido em 23/08, linhas reconferidas em 27/08
contra o commit `bd28ffd`):**

| Ação | Exige hoje | Onde |
|---|---|---|
| Ver mapa, painel, relatório | `plugin_dgoplus_port` READ | `front/map.php` |
| Documentar porta **já existente** | `plugin_dgoplus_port` UPDATE | `MapController:2946` |
| Documentar porta **ainda não criada** | `plugin_dgoplus_port` **CREATE** | `MapController:2946` (`$found ? UPDATE : CREATE`) |
| Propor vínculo | `plugin_dgoplus_port` CREATE | `MapController:3179` |
| Fileira / coluna / piso | `plugin_dgoplus_port` CREATE | `MapController:529`, `:735`, `Floor::$rightname` |
| Qualquer gravação de porta | **também** `datacenter` READ | 7 pontos, abaixo |
| Comentário do elemento | `datacenter` UPDATE | `DgoIdentity:216` |
| Anexos | `document` READ+UPDATE+CREATE **e** ⚠️ provavelmente `datacenter` UPDATE | — |
| Criar elemento | `datacenter` CREATE | `MapController:412` e `:1522` |
| Configurar papéis | `config` UPDATE | `MapController:1544` |

**Os sete pontos acoplados a `datacenter` READ** (todos `can($items_id, READ)`),
**números verificados em 27/08 no commit `bd28ffd`** — o 5h deslocou o `Port.php`
em +20 linhas:

| Arquivo | Linha | Contexto |
|---|---|---|
| `src/Port.php` | **383** | `applyInput` |
| `src/Port.php` | **634** | `ensureEntry` |
| `src/Port.php` | **739** | `ensureGrid` |
| `ajax/port.php` | 48 | auto-save |
| `src/MapController.php` | 949 | `actionSaveEntryObs` |
| `src/Link.php` | 689 | `loadVisibleItem` (3 chamadas) |
| `src/DgoIdentity.php` | 323 | identidade |

`front/map.php` exige **apenas** `Port::$rightname READ` — a leitura do mapa não
depende de datacenter, e todas as listagens usam `getEntitiesRestrictCriteria`.

**A semântica que a Fase 5 vai instaurar** (decidida em 23/08):

| Direito | Passa a significar |
|---|---|
| LER | Ver mapa, painel, relatórios, comentários, descrição das portas |
| ATUALIZAR | Documentar portas, propor/confirmar vínculos, comentar o elemento |
| CRIAR | Criar elementos pelo mapa, fileiras, colunas, pisos |
| DELETE | Excluir portas e vínculos |

**Fora do DGO+, por decisão:** criar Localização (é dropdown do GLPI inteiro),
anexos (direito `document`, do core) e **excluir o elemento** (continua exigindo
`datacenter` — quem apaga ativo é admin).

**O que a Fase 5 assume conscientemente:** depois do 5f, quem tiver
`plugin_dgoplus_port` UPDATE grava porta e comentário em elementos **da sua
entidade** sem ter direito nesses ativos; quem tiver CREATE cria
`PassiveDCEquipment` sem direito em Data centers. É escalada deliberada e
delimitada, decidida pelo administrador ao conceder o direito.

### Arquivos

**27 arquivos** — contagem real, verificada em 27/08 nos dois lados por
`find . -type f | xargs md5sum`. **O v4 dizia 32 e estava errado**: contava
entradas de diretório junto.

```
dgoplus/
├── setup.php              hooks, menu, botão da ficha, JS — 269 linhas
├── hook.php               instalação / desinstalação
├── README.md              desatualizado — dívida 2
├── logo.png
├── ajax/                  port.php (auto-save, 4a), dgocomment.php (comentário, 3t)
├── public/                dgoplus.js, dgoplus-identity.js, qrcode.js, dgoplus-mark.svg
├── front/                 map.php, port.php, pending.php, config.form.php
└── src/                   13 arquivos
    ├── Install.php        schema, direitos, migrações
    ├── Setting.php        papéis e Tipos de cada papel
    ├── Port.php           porta; applyInput é o ponto único — 1017 linhas
    ├── Link.php           vínculo; propose é o ponto único
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (rightname = plugin_dgoplus_port)
    ├── MapController.php  a tela do mapa (141 KB)
    ├── Dashboard.php      o painel
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade e QR do elemento (3t)
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.2** (commit `bd28ffd`, idênticas no servidor):

```
3631ec3b4c3e9e26c95ce0559bb665b2  setup.php        (269 linhas)
d904a84c203cbbdf4936791b968153e8  src/Port.php     (1017 linhas)
```

---

## 4. Lições aprendidas

⚠️ **Lacuna, ainda aberta.** O código cita lições numeradas até a **113**; a lista
integral vive no documento original, não recuperado. Abaixo: as recuperáveis
pelas citações (número confiável, enunciado deduzido) mais as **novas, que são
fato**. Se a lista integral aparecer, ela substitui a parte reconstruída — **não**
as lições 114+.

| # | Lição |
|---|---|
| 3 | Em `front/` e `ajax/` do GLPI 11 a sessão, o autoload e o `$CFG_GLPI` já estão de pé |
| 5 | `getEntitiesRestrictCriteria()` devolve array para ser **somado**, não para substituir |
| 12 | `$_SERVER['PHP_SELF']` está morto no GLPI 11 para montar URL |
| 13 | Montagem de URL num lugar só (`getPageUrl()`), nunca espalhada |
| 14 | **Falha silenciosa custa mais que falha barulhenta.** O mapa mentindo é o defeito mais caro deste projeto |
| 16 | **Estado vazio nunca fica mudo** |
| 20 | Componente que não cabe na coluna some sem avisar — vai em largura total |
| 21 | Só classes CSS que existem nos templates do 11.0.6 |
| 23 | Vermelho do projeto em alfa para o fundo da célula sem acoplador |
| 27 | `outline` seria cortado pelo `overflow` — usar outro recurso visual |
| 31 | `ALTER` repetido sem guarda devolve erro 1060 |
| 32 | União de Tipos sempre **por id**, nunca por nome |
| 34 | Piso como intitulado próprio do plugin |
| 35 | O gancho tem que disparar **sempre**, inclusive no purge forçado |
| 39 | O que a `executeMigration` não cobre sai por `doQuery` |
| 44 | Campo fora do array de gravação de propósito |
| 45 | O `0` garantido no HTML — não se mexe no JS para compensar |
| 47 | POST e AJAX **não podem divergir** |
| 48 | O AJAX tem que bater com o recarregamento de página |
| 49 | Regra de carregamento de estático do plugin |
| 63 | Carimbo de quem alterou a porta e quando é o que a torna auditável |
| 104 | O estado vem da renderização, não é deduzido |
| 105 | Divergência de versão entre disco e repositório |
| 112 | `kind` **fora** da chave única |
| 113 | Cuidado com `DEFAULT_TUBES`/`DEFAULT_FIBERS` como constantes |
| 114 | **Homologação pode estar atrás do `master` sem ninguém ter errado.** Confirmar a versão antes de julgar qualquer tela |
| 115 | **`pscp` usa `-P` maiúsculo para a porta.** O `-p` minúsculo preserva timestamps |
| 116 | **Bump de versão no `setup.php` conta como mudança de instalação.** Rodar `--force` + `activate` mesmo com `Install.php` idêntico |
| 117 | **`$parent->can($items_id, READ)` acopla o plugin ao direito do itemtype pai — e ao menu do core.** `Session::haveAccessToEntity()` preserva a proteção sem o acoplamento |
| 118 | **`$can_write = haveRight($rightname, $found ? UPDATE : CREATE)`**: porta ainda não documentada exige **CREATE**. Foi a causa real de "o técnico não consegue documentar" |
| 119 | **Mensagem de permissão que não nomeia o direito faltante custa horas** |
| 120 | ⚠️ **Anexar documento a um ativo parece exigir direito no ativo, não só em Documentos.** A confirmar com `datacenter` UPDATE marcado |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054.** Em tabela polimórfica o join tem que passar pela tabela do ativo |
| 122 | **Não existe `sql-errors.log` no GLPI 11.0.6.** Erro de SQL vai para `php-errors.log` como `glpi.CRITICAL` |
| 123 | **O direito Documentos fica na aba Gerência do perfil, não em Ativos** |
| 124 | **A homologação também pode estar À FRENTE do `master`.** Enquanto o commit não sai, ler o repositório **não** descreve o que roda no servidor. **Antes de diagnosticar qualquer tela, confirmar os dois lados.** Corolário da 114 e da 105. *Encerrada como divergência ativa em 27/08, mantida como regra permanente* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`.** Os jointypes reais do 11.0.6 estão na seção 3 — **conferir contra o `switch` do `SQLProvider` antes de escrever qualquer `joinparams`**. E, no `itemtype_item_revert`, esquecer `specific_itemtype` não quebra a tela: devolve a coluna vazia (lição 14 outra vez) |
| 126 | **O sandbox do assistente tem PHP.** ⚠️ *Refinada em 27/08:* o `apt-get update` **sai com código ≠ 0** (repositório `deb.nodesource.com` devolve 403), então `update && install` faz o `install` nunca rodar. **Dois comandos separados** |
| 127 | ⚠️ **O CSV exportado do GLPI abre torto no Excel por duplo clique**: acentos viram `Ã§Ã£o` e **`(10)` vira `-10`**. O segundo é o perigoso — não parece quebrado, só está errado. Solução: *Dados → Obter dados → De texto/CSV*, origem UTF-8, coluna como Texto. **Não é defeito do plugin** |
| 128 | **`pscp` pode falhar com `Remote side unexpectedly closed network connection` e voltar sozinho.** Roteiro de triagem na seção 1 — a primeira pergunta é sempre "o PuTTY comum conecta?" |
| **129** | **Arquivo remontado a partir do `master` + a descrição do bloco NÃO é verificação.** A descrição registra o *efeito*, o commit registra o *diff*. Em 27/08 o `Port.php` do servidor tinha 16 linhas de comentário que nenhum documento mencionava; commitar a reconstrução as teria apagado em silêncio. **Conteúdo idêntico com número de linha divergente É divergência** — investigar antes de commitar. Quando o servidor está à frente, o que se commita é **o arquivo baixado do servidor** |
| **130** | **O GitHub é canônico; o zip nasce do commit.** Qualquer ordem que deixe código existindo só no servidor já custou perda de trabalho uma vez (incidente doméstico). Ver seção 0 |
| **131** | **Upload pela web do GitHub não avisa quando o nome não bate — cria arquivo novo em silêncio em vez de substituir.** Agravado pelo Windows ocultando extensões, que transforma "renomear para `Port.php`" em `Port.php.php`. **Conferir a contagem de arquivos da pasta depois de todo upload** e ligar *Exibir → Extensões de nomes de arquivos* no Explorer. Lição 14 fora do plugin |
| **132** | **`raw.githubusercontent.com` tem cache de CDN e pode devolver o estado anterior** logo após um commit. Para conferir commit recém-feito: `git ls-remote` para o HEAD (autoritativo) e **tarball do `codeload` com o SHA** para o conteúdo |

**Armadilhas do GLPI 11 que valem como regra permanente:**

- **CSRF**: o core valida POST sozinho — nunca `Session::checkCSRF` manual.
- **Iterator: `COUNT` + `GROUPBY` juntos descartam os campos do `SELECT`.**
- Todo `WHERE`/`ORDER` com JOIN precisa de **coluna qualificada**.
- **Filtro nunca pode sumir**: lista de ids vazia vira `[0]`, jamais filtro ignorado.
- JSON em `<script type="application/json">` exige as flags HEX.
- Endpoint `ajax/` **não se testa pela URL direta**.
- `php -l` não pega incompatibilidade de assinatura com a classe-pai.
- `Dropdown::showFromArray` renderiza **select2**, que esconde o `<select>` real.
- Classes com namespace `Glpi\` moram em `src/Glpi/...` no repositório do core.

---

## 5. Estado por bloco

| Bloco | Entrega | Estado |
|---|---|---|
| 1 | Schema, classes, direito de perfil, menu, relatório nativo | Fechado |
| ⚠️ 2, 3a–3f | Não citados no código do 1.3.x | A confirmar |
| 3g | Piso em Configurar → Intitulados | Fechado |
| 3h | Setor abandonado; escopo Localização → Piso | Fechado |
| 3j | DGO na Análise de impacto | Fechado |
| 3k | Atalho "Abrir no mapa DGO+" na ficha | Fechado |
| 3l | Configuração de Tipos por papel | Fechado |
| 3m | Trava de entidade na gravação de porta (`can($items_id, READ)`) | Fechado — revisto pelo 5f-3 |
| 3q | `PurgeCleaner` | Fechado |
| 3r | Posição exibida contínua | Fechado |
| 3s | Carimbo de documentação | Fechado |
| 3t | Identidade, QR e comentário | Fechado |
| 4a-1/2/3 | Auto-save; papel no painel; abas e filtro por papel | Fechado |
| 4b-1/2 | `kind` na porta; tabela de vínculos + entradas E1–E4 | Fechado |
| 4c/4c-2 | Propor, confirmar, recusar, desmontar | Fechado |
| 4d | Página e cartão de pendentes | Fechado |
| 4e | Trilha de alimentação | Fechado — ajuste no 5c |
| 4g | Bump 1.3.0; `isDgo()` → `isMapped()` | Fechado |
| 4h | — | Fechado (1.3.0, `27d54d2`) |
| 5-sync | Subir a homologação de 1.3.0 para 1.3.1 | Fechado (22/08) |
| 5a | Escopo Localização → Piso no seletor de destino | Fechado e validado em tela (23/08) |
| 5h | JOIN da coluna Localização no relatório | **Fechado, validado em tela e COMMITADO (27/08) — 1.3.2** |

### O que o 5h fez, em detalhe

Dois arquivos. O `diff` completo contra o 1.3.1, verificado em 27/08, são
**exatamente três trechos**:

| Onde | O quê |
|---|---|
| `setup.php:25` | Bump `1.3.1` → `1.3.2` |
| `src/Port.php:7` | `use PassiveDCEquipment;` |
| `src/Port.php`, antes da opção 8 | **bloco de comentário de 16 linhas** explicando o porquê do `itemtype_item_revert` |
| `src/Port.php`, opção 8 | `beforejoin` → `glpi_passivedcequipments`, `jointype => 'itemtype_item_revert'`, `specific_itemtype => PassiveDCEquipment::class` |

`+1 +16 = +17` linhas, o que deslocou o `'id' => 8,` de 203 para 220 e todo o
resto do arquivo em +20 no bloco dos `can()`.

`forcegroupby` e `nosearch` **ficaram como estavam**, de propósito: remover o
`nosearch` (que impede filtrar por Localização) é o Bloco 5h-2.

**Validado em tela (27/08):** plug-in em 1.3.2 ativo; relatório exibe a coluna
Localização com o `completename` (`Shopping Ventura > DGO Cristian`); exportação
sai com a coluna preenchida; 49 linhas listadas. Único desvio: o CSV aberto no
Excel por duplo clique mostra acentos quebrados e `(10)` como `-10` — lição 127,
não é defeito do plugin.

### O que a sessão de 27/08 fez depois disso

| Commit | O quê |
|---|---|
| `266fb09` | `setup.php` → 1.3.2 |
| `63fa8f1` | Upload do `Port.php` do servidor — ⚠️ caiu como `Port.php.php` (lição 131) |
| **`bd28ffd`** | `Delete` do `Port.php` antigo + rename do `Port.php.php` → **estado correto** |

Encerrado com **paridade md5 dos 27 arquivos** entre servidor e `master`.

---

## 6. Dívidas conhecidas

1. **README desatualizado** — manda baixar `dgoplus-v1.0.0.zip` (linhas 38, 45,
   56), fala em três tabelas quando são quatro (111, 142), e a linha 119 avisa
   que purgar o ativo deixa portas órfãs, defeito que o 3q resolveu.
2. **Sem catálogo de tradução**: interface pt-BR fixa.
3. ⚠️ **Lista integral de lições (1–113)** não incorporada.
4. **Sem tag nem Release para o 1.3.2.** O zip instalável existe **só na pasta
   `Downloads` do PC** — é a última cópia de artefato fora do lugar protegido
   (seção 0).
5. ⚠️ **Pasta do repositório Git no servidor novo** não localizada. Comando de
   triagem na seção 1. Decide se o fluxo definitivo é Git no servidor ou Git for
   Windows.
6. **Documentos de contexto e roadmap fora do repositório** — ⚠️ *em resolução
   nesta sessão:* subir `docs/contexto-dgoplus-v5.md` e `docs/roadmap-dgoplus-v5.md`.
   É a memória do projeto (decisões, lições, o porquê de cada bloco), e hoje ela
   vive só na base de conhecimento do chat.

*(A dívida 1 do v4 — "5h aplicado mas não commitado" — foi **fechada** em 27/08.)*

---

## 7. Medições de campo

**23/08:**
- **31 elementos**, todos com `locations_id`; **7 localizações**.
- **1665 portas**, 28 documentadas — **1,7%** de ocupação.
- **14 elementos** sem nenhuma porta registrada.
- **Plaza Campos Gerais**: 7 candidatos a destino. Com piso **L1**, sobra 1; com
  **L2**, nenhum → **6 de 7 sem piso atribuído**.

**27/08:**
- Relatório listando **49 linhas** (⚠️ sem filtro registrado — não interpretar
  como contagem de portas documentadas sem conferir os critérios da busca).
- Localizações vistas na coluna nova: `Shopping Ventura > DGO Cristian`,
  `shopping estação`, `shopping palladium`, `Shopping itajai/Bigode`,
  `Plaza Campos Gerais`.
- Documentadores ativos: Claudio Morett, Kayan Lucas, Pedro s, cristian.b.
- **27 arquivos** no plugin, `master` e servidor idênticos por md5.

---

## 8. Decisões negativas registradas

Avaliadas e **recusadas**, com motivo. Existem para a próxima sessão não as
ressuscitar como novidade.

| Ideia | Decisão | Motivo |
|---|---|---|
| Atribuição de piso em lote | **Descartada** | O piso não vai ser preenchido em massa; "Todos os pisos" cobre o uso |
| Esconder o seletor de piso | **Descartada** | O seletor fica; a meia-medida (5b) resolve |
| Alerta de salto de degrau só no JS | **Descartada** | Salto é exceção, e exceção que passa batido vira topologia errada — vai para o servidor |
| Proporção do splitter como campo estruturado | **Descartada** | Já cabe no OBS das entradas |
| Splitter como papel na hierarquia | **Descartada** | É componente da caixa, não elo da cadeia |
| Importação CSV de portas | **Adiada** | Não há fonte de dados; revisitar se aparecer planilha pronta |
| Criar Localização pelo direito do DGO+ | **Descartada** | `Location` é dropdown do GLPI inteiro |
| Anexos pelo direito do DGO+ | **Descartada** | `Document` é do core |
| Excluir elemento pelo direito do DGO+ | **Descartada** | Purgar ativo é do admin |
| Corrigir a acentuação do CSV no plugin | **Descartada (27/08)** | O relatório é tela do core; o problema é o Excel lendo UTF-8 como ANSI (lição 127) |

---

## 9. Próximo passo imediato

1. **Subir `docs/` ao repositório** com este documento e o roadmap v5 — fecha a
   dívida 6 e é o último item de memória desprotegida.
2. **Confirmar a lição 120**: marcar `datacenter` UPDATE no perfil Tecnicos N1 e
   ver se o formulário de anexo aparece. Decide o desenho final das permissões e
   destrava o 5f-1.
3. Seguir com **5f-1 → 5f-2 → 5f-3 → 5g**, nesta ordem. O **5h-2** cabe em
   qualquer intervalo: é um atributo.
