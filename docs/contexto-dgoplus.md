# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v24 — 05/09/2026 (3ª sessão do dia). Substitui o
> v23 integralmente. Versão **1.3.28**, `master` em **`4923cab`**
> (bloco 5i-2 endpoint, código + bump).
>
> **O que o v24 traz de novo em relação ao v23:**
>
> 1. **Docs v23 commitados** (`4358c98`), paridade por tarball md5 a md5.
> 2. **Bloco 5h-2 + 5i entregue e validado em aplicação ÚNICA** (decisão do
>    dono) — 1.3.27, `c74e32a`. 5h-2: o `nosearch` da Localização (search
>    option 8) caiu; o relatório filtra por localização. 5i: gerenciador de
>    anexos com formulário do PRÓPRIO plugin — anexar exige só o Atualizar
>    do DGO+ (Document::add com `_filename` não checa direito nativo,
>    provado no core; validação de tipo continua a nativa); ação
>    `attach_document` no map.php com a trava do 5f-3b. Os gates
>    `Document::canView()` da lista caíram.
> 3. **5g-3 QUITADO sem código** — a nota de anexos já existia no
>    `ProfileTab.php`; o roadmap estava desatualizado. **PAINEL-1a já estava
>    entregue** (rodapé "Ver todas as portas por atualização" na Atividade
>    recente); **PAINEL-1b virou decisão negativa** (§8).
> 4. **Bloco 5i-2 versão "cadeado" preparado e DESCARTADO antes de aplicar**
>    — decorava a exigência nativa em vez de eliminá-la; contrariava o
>    objetivo declarado. Origem da lição 168 (§4).
> 5. **Bloco 5i-2 endpoint entregue e validado** — 1.3.28, `4923cab`.
>    `front/document.send.php` do PLUGIN (arquivo NOVO): ver/baixar anexo
>    pelo mapa exige só o Ler do DGO+ — nenhum direito em Gerência →
>    Documentos. Porteiro: Ler do plugin + parentIsReachable + vínculo
>    doc↔elemento obrigatório (docid solto recusa falado). Serve por
>    `Document::getAsResponse()`; o retorno de script legado vira a resposta
>    HTTP (LegacyFileLoadController, lido na sessão). `documentUrl()` foi o
>    único retarget — ponto único trocou o porteiro de miniatura, lista e
>    clique de uma vez. Texto de anexos da aba de perfil reescrito.
> 6. **Anexos 100% concentrados no plugin** (decisão de produto, §8):
>    Atualizar anexa, Ler vê. Dedução FALSEADA em tela: a rota do
>    `canViewFileFromItem` via READ do ativo NÃO abriu neste ambiente — o
>    que abria no send.php do core era Documentos · Ler (investigação
>    opcional, sem bloco).
>
> Companheiro: `roadmap-dgoplus.md`. Os dois vivem em `docs/` no repositório.

---

## 0. A regra que governa tudo

**O GitHub é o repositório canônico do DGO+. A homologação é descartável.**

Todo estado do código tem que ser reconstruível a partir do `master` sozinho.
A regra nasceu de um fato: **houve um incidente doméstico em que a base de
homologação foi perdida com um repositório dentro dela, levando junto correções
que nunca chegaram ao Git.**

| Pergunta | Fonte da resposta |
|---|---|
| **O que está rodando agora?** (tela, erro, permissão) | O servidor — sempre |
| **O que o código É?** (registro durável, base de bloco novo) | **O GitHub — sempre** |
| **Como estão os DADOS da homologação?** | **Só a tela, lida na sessão** (lição 160). Ambiente vivo dos técnicos — em 05/09 apareceram 25 entradas ocupadas que nenhum doc conhecia |

### A ordem de entrega

1. O assistente prepara os arquivos a partir do **tarball do commit atual** e
   valida (`php -l`, `node --check` para JS, leitura do core quando preciso).
2. O usuário envia por **`scp`** e — **antes de tocar no plugin** — confere o
   `md5sum` dos arquivos em `/tmp`.
3. Copia por cima dos arquivos na pasta do plugin.
4. **`git diff` — a conferência do bloco.** Divergiu do esperado: não commita, avisa.
5. `git add -A` → `git commit` → `git push`. **O código vai ao GitHub antes do
   teste.** Reprovou? `git revert` ou `git checkout --`.
6. Console do GLPI + restart, e então o roteiro de teste.

**O passo 2 não é opcional** (lição 140). ⚠️ **O bump de versão no `setup.php`
faz parte do bloco** (lição 165). Nos dois blocos de 05/09 (2ª) o
`git diff --stat` bateu EXATO com o previsto por comando (5 files +104/−13;
2 files +54/−24) e a paridade dos commits publicados foi provada por md5.

O zip sobrevive só como artefato de Release.

### ⚠️ A skill cadastrada está desatualizada DE PROPÓSITO

**Decisão do usuário (04/09): a skill `glpi-plugin-teckcomp` cadastrada NÃO
será trocada.** Ela ainda descreve `pscp`/PuTTY, `192.168.1.50` e zip — tudo
abolido. Ruído conhecido:

- A fonte da verdade do ambiente é o **`SKILL-glpi-plugin-teckcomp.md` na base
  do projeto** (md5 `edc469d2a1f5a9400b330143c0bf3891`), somado a este contexto.
- Quando a skill carregada e o contexto divergirem, **o contexto manda**.
- Não voltar a propor a troca sem fato novo (decisão negativa, §8).

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 05/09 (3ª) | commit **`4923cab`** (5i-2 endpoint), versão **1.3.28** — push visto na sessão |
| Último commit de CÓDIGO | o próprio `4923cab` (anterior: `c74e32a`, 5h-2+5i) |
| Versão em homologação | **1.3.28** — aplicada, reinstalada e ativada em 05/09 |
| **Paridade** | ✅ Provada por md5 na sessão para docs v23 (`4358c98`), `c74e32a` (3 arquivos) e `4923cab` (4 arquivos) |
| Arquivos no repositório | **32** (29 do plugin + 3 em `docs/`) — `front/document.send.php` é o novo |
| **`docs/` no repositório** | `contexto-dgoplus.md`, `roadmap-dgoplus.md`, `README.md` — nomes SEM versão. Conteúdo atual: **v23** (commit `4358c98`); o v24 entra por cima |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha** (lição 139) |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local**, **sem PuTTY** |
| Assistente | Não tem SSH nem token. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

O shell do servidor está logado como **root**. Console do GLPI sempre com
`sudo -u www-data`.

### Git no servidor

```bash
git config --global user.name "Claudio Morett"
git config --global user.email "claudio.morett@gmail.com"
git config --global --add safe.directory /var/www/html/glpi/plugins/dgoplus
git config --global credential.helper store
git config --global core.pager cat
```

Token fine-grained (Contents: Read and write). **Depois de todo
`git pull`/`checkout`:** `chown -R www-data:www-data` na pasta do plugin.

### Comandos do dia a dia

**Enviar do PC (cmd do Windows):**

```cmd
dir "%USERPROFILE%\Downloads\*<bloco>*"
scp -P 2078 "%USERPROFILE%\Downloads\<arquivo>" resolutto@177.87.230.179:/tmp/
```

`-P` maiúsculo é a porta. ⚠️ **JS de bloco sai com `.txt` no fim** (lição 149).

**Aplicar um bloco (`ssh -p 2078 resolutto@177.87.230.179`):**

```bash
md5sum /tmp/<arquivos>              # <<< OBRIGATÓRIO, antes de qualquer cp
cd /var/www/html/glpi/plugins/dgoplus
git pull
cp /tmp/<arquivo> <caminho/no/plugin>
chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus
git status --short
git diff --stat && git diff         # <<< a conferência do bloco
git add -A && git commit -m "..." && git push
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

**Reinstalar, quando o `setup.php` mudou de versão:**

```bash
sudo -u www-data php /var/www/html/glpi/bin/console plugin:install --force -u glpi dgoplus
sudo -u www-data php /var/www/html/glpi/bin/console plugin:activate dgoplus
```

**Conferência de estado, no começo de toda sessão:**

```bash
cd /var/www/html/glpi/plugins/dgoplus
git status --short && git log -1 --oneline && grep PLUGIN_DGOPLUS_VERSION setup.php
```

⚠️ Quando o HEAD é commit de `docs/`, o `git log -1` mostra o commit dos docs —
o último commit de CÓDIGO pode ser anterior (lição 143). Após o commit dos
docs v24, o HEAD será de docs e o código seguirá no `4923cab`.

**Reverter:**

```bash
git checkout -- <arquivos>      # descarta a cópia, ainda não commitada
git revert HEAD && git push     # desfaz o commit já empurrado
rm -f src/<ArquivoNovo>.php     # arquivo NOVO não some com revert de merge sujo
```

### Os dois logs que interessam

```bash
tail -n 30 /var/www/html/glpi/files/_log/php-errors.log
grep -h "<endpoint>" /var/log/apache2/other_vhosts_access.log | tail -n 10
```

Não existe `sql-errors.log` (lição 122). **Nem toda recusa vira 403** —
`dgocomment.php` responde 200 com `denied:true`; `port.php` responde 403
(lição 154). Ruído conhecido: aviso de `version changed` da reinstalação
(114/116), backtrace do plugin `fields`, `Test logger`. ⚠️ **Candidato em
observação:** `glpi.CRITICAL` do `CacheClearCommand` no `cache:clear` —
em 05/09 (2ª) o comando rodou 2× com saída limpa; o log não foi inspecionado
especificamente. Seguir observando antes de promover a conhecido.

### Topologia web

Apache 80/443 interno; externo por `177.87.230.179:2077`.
`DocumentRoot /var/www/html/glpi/public` via `conf-enabled/glpi.conf`. Nada de
`plugins/` é alcançável como arquivo pelo navegador.

### Release

`v1.3.8` publicada em 27/08 (zip 177 KB, sha256 `34e1fd…ef16`); `v1.3.2`
continua publicada. Tags: `v1.0.0` … `v1.3.2`, `v1.3.8`. **As versões 1.3.3 a
1.3.28 não têm tag** — degraus internos da Fase 5. Com os blocos de código da
Fase 5 fechados, a tag é candidata a sair ANTES do deploy em produção (deploy
a partir da tag).

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, `codexplus` 0.5.2-alpha, `datainjection`,
`archimap`, `gantt` 1.3.4, `moreticket`, `projectplus` 1.1.0-beta, `shopmap`
0.1.0, `stab`, `tag`, `taskplus` 0.2.1-beta, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6,
`Tasks list` 2.1.12.

### O shopmap — frente irmã, fora deste repositório

⚠️ **`github.com/teckcomp/glpi-plugin-shopmap` está PRIVADO** (404 anônimo,
28/08). O assistente não lê esse código.

> **Referência a ativo é `itemtype` + `id`. Nome é rótulo, nunca chave.**

Falta responder — e só a base do shopmap responde: *o vínculo é guardado pelo
NOME ou por `itemtype`+`id`?* Por chave → só tela. Por nome → tela + migração
em três baldes. O `MapController::normalizeName()` é a peça reaproveitável.

### Quando reinstalar

| Mudou | O que fazer |
|---|---|
| `src/`, `front/`, `ajax/` (PHP) | `cache:clear` + `systemctl restart apache2` |
| `public/` (JS/SVG) | **Ctrl+F5** no navegador |
| `src/Install.php` (schema, direitos) | `plugin:install --force` **e depois** `plugin:activate` |
| **Número de versão no `setup.php`** | Idem (lições 116 e 165) |
| Só `docs/` | **Nada.** Commit e pronto |

---

## 2. Fluxo de trabalho vigente

Método **entrega-em-blocos**. Entrega em quatro seções fixas: **(1)** o que
muda, decisão de reinstalar em negrito na primeira linha; **(2)** `scp` literal
com md5 esperados; **(3)** comandos de aplicar com `git diff` como conferência;
**(4)** roteiro numerado com resultado esperado, log e reversão.

⚠️ **Sessão de VALIDAÇÃO não é entrega de bloco** — só roteiro.
⚠️ **Bloco sem cenário de teste na homologação não é entregue.**
⚠️ **Decisão vigente pode ser REABERTA pelo usuário.** Em 05/09 (2ª) o dono
exerceu isso duas vezes: (a) aprovou bloco ÚNICO para BADGE-C+PAINEL-2 depois
de ouvir a recomendação de dividir — a mitigação foi o ponto único de contagem
e roteiro autoconferente; (b) rejeitou o cartão próprio do painel após vê-lo
em tela e escolheu a variante B sobre mockup. Custo total: um bloco a mais,
zero retrabalho de dados.
⚠️ **Tela NOVA pede mockup aprovado antes do código** (lição 167).

### Roteiro de teste — exigências acumuladas

- Se confere contra o código antes de sair (lição 158). Em 05/09 (2ª):
  `statsForDgo`, `renderBadges`, `displayGrid`, `displayEntryStrip`,
  `renderEntryBox`, `findByDestinations`, `card()` e o endpoint AJAX foram
  lidos ANTES de qualquer afirmação.
- Todo passo que troca de tela diz COMO chegar lá (lição 159).
- Toda pré-condição de dados é lida em tela antes de virar passo (lição 160).
- Roteiro autoconferente quando possível: o badge de entradas se prova contra
  a faixa E1–E4 da MESMA tela; a pastilha do painel se prova contra o badge.
- Passo que prevê "não muda" também é passo (frações do 4d intactas).
- **Frases novas de tela são simuladas por extenso ANTES de codar**
  (lição 166) — incluindo casos zerado e de escopo vazio (`0/0`).

### Nome de arquivo entregue leva o bloco

`Port-badgec.php`, `Dashboard-painel2b.php` etc. ⚠️ **`ajax/port.php` e
`front/port.php` têm o MESMO nome-base** — a entrega usa nome desambiguado
(`port-ajax-<bloco>.php`) e o `cp` leva o caminho completo. Docs versionam no
nome ENTREGUE (`contexto-dgoplus-v24.md`); no repositório o `cp` grava sem
versão (`docs/contexto-dgoplus.md`).

### O repositório é público — usar isso por padrão

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
```

Preferir `codeload` com SHA (lição 132); `api.github.com` bate no limite
anônimo. **Padrão:** tarball do commit atual → editar cópia → validar →
`diff -rq` provando escopo → depois do push, baixar o publicado e provar
paridade por md5 (feito 2× nesta sessão). **Número previsto sai de comando**
(lições 141, 150, 155, 163) — o `git diff --stat` é previsto criando um git
local sobre o tarball no sandbox.

### O core do GLPI também é legível

`github.com/glpi-project/glpi`, tag `11.0.6`; classes `Glpi\` em
`src/Glpi/...`; schema em `install/mysql/glpi-empty.sql`. ⚠️ O CSS do tema NÃO
é legível por esse caminho (lição 156) — o atalho é classe que o plugin já
imprime em tela (`bg-green-lt` foi validado assim: o cartão "Portas livres"
já o imprimia, provado por screenshot ANTES do BADGE-C usá-lo).

### O sandbox do assistente TEM PHP e Node

`php -l` (8.3.6) e `node --check`. `apt-get update` e `apt-get install -y
php-cli` em dois comandos (lição 126). ⚠️ O sandbox pode nascer SEM php-cli E
com repositório apt quebrado (nodesource 403) — remover
`/etc/apt/sources.list.d/nodesource.list` antes do update resolve. Instalar
faz parte do preparo, não é erro.

### Práticas abolidas

Lista integral mantida (lições 114–167). Destaques: reinstalar por precaução;
`pscp`; zip como veículo; nome final em vez de `<Arquivo>-<bloco>`; JS sem
`.txt`; F12 para status; prever números de cabeça; julgar tela sem confirmar
versão; remontar arquivo de memória; caminho abreviado; roteiro sem conferir
contra o código; dado de homologação sem reler em tela; bloco sem bump;
afirmar consequência sem simular o formato inteiro; **tela nova sem mockup
aprovado** (167).

---

## 3. Arquitetura

### O que é do GLPI e o que é do plugin

A DGO **não é um itemtype do plugin**. Cada elemento é um `PassiveDCEquipment`
nativo; o plugin acrescenta grade, escopo e vínculos. O core não conhece as
tabelas do plugin — daí o `PurgeCleaner`. Escopo: **Localização (nativa) →
Piso (intitulado do plugin)**.

### Pisos — cadastro × filtro

- **Cadastro** em `Configurar → Listas suspensas → Pisos`.
- **O filtro do mapa usa `floorsWithItems()`** — só pisos COM elemento no
  escopo corrente (5b). Piso vazio NUNCA aparece no dropdown.
- `Floor::getForLocation()` valida `?floor=` na entrada do controlador.

### Papéis

`Setting::ROLES` **é** a hierarquia: `dio` → `dgo` → `cto` → `pto`. Splitter
fora; proporção no OBS. Produção: um Tipo por papel (`DIO+`, `DGO+`, `CTO+`,
`PTO+`), em `glpi_configs`, contexto `plugin:dgoplus`.
⚠️ Produção mostra 1 elemento fora dos papéis.

### Portas

Uma tabela, dois `kind`: `KIND_GRID` (tubo × fibra) e `KIND_ENTRY` (E1–E4,
`tube_num = 0`, `MAX_ENTRIES = 4`). Chave única `(itemtype, items_id,
tube_num, fiber_num)`, `kind` fora (lição 112). **`Port::applyInput()` é o
ponto único de gravação** — `checkRight(UPDATE)` que lança o 403. Grade padrão
4×16 = 64. Porta sem acoplador não conta como documentada.

**`Port::statsForDgo()` — desde o BADGE-C é o ponto único das DUAS contagens
do elemento:** grade (`documented`, `no_coupler`, `total`, via
`gridCriteria()`) E entradas (`entries_occupied`, `entries_total` =
`MAX_ENTRIES` fixo). **Entrada ocupada = linha de entrada viva com vínculo
apontando para ela (`Link::findByDestinations`), pendente incluso** — a MESMA
definição da faixa E1–E4 (`renderEntryBox`). Badge do cabeçalho e pastilhas
do painel consomem daqui/da mesma definição, nunca de conta própria divergente.

**Carimbo de documentação (3s):** `documentStamp()` é o ponto único; carimba
só quando o VALOR do código muda; não retroativo.

### Histórico — mecanismo provado (v20, íntegro)

`Port` estende `CommonDBChild` com `dohistory = true`; toda gravação de porta
gera linha no Histórico do elemento pai com `user_name` do usuário logado.
`history_blacklist = ['users_id_documenter', 'date_documented']`. `Link` tem
`dohistory = true` próprio.

### Vínculos

`glpi_plugin_dgoplus_links`: uma linha, dois lados (`plugin_dgoplus_ports_id_src`
grade de origem, `plugin_dgoplus_ports_id_dst` entrada de destino; UNIQUE nos
dois lados). `status`: `pendente` | `confirmado`. Sem `is_deleted` — recusa e
desmonte apagam. Pendente já ocupa. Hierarquia permissiva; `hierarchyAllows()`
compara ordem. **5d (1.3.24):** pulo de degrau exige ciência em dois tempos —
`skipWarning()` ponto único da frase; `needs_ack` no `propose()`; é ciência,
não bloqueio. `Link::propose()` é o ponto único de criação.
`findByDestinations()`/`findByOrigins()` devolvem `[]` para lista vazia
(filtro nunca some). ⚠️ Pendente que envelhece não avisa ninguém.

⚠️ **Dois "confirmar" distintos:** Confirmar/Recusar no destino = fluxo do
pendente (Fase 4). "Confirmar mesmo assim" do 5d = na proposta, antes de
gravar. Coexistem.

### O rótulo de elemento — `src/ItemLabel.php`

`forRow`/`forItem` = `nome · localização · #id`; `shortForRow` = `nome · #id`.
`completename` FICA (decisão de 28/08). Consumidores (medidos no `fbf1952`):
`MapController` 8, `Link` 6, `Dashboard` 1 — blocos posteriores não mexeram.
⚠️ Seletor de DESTINO continua fora do `ItemLabel` (dívida 7, mantida).

### O cabeçalho da grade — badges (BADGE-C, 1.3.25)

`MapController::renderBadges(documented, capacity, no_coupler,
entries_occupied, entries_total)` — assinatura de 5 parâmetros desde o
BADGE-C. Renderiza: `bg-blue-lt` "`N/cap grade`" (title "N de cap portas de
grade documentadas"), `bg-green-lt` "`M/4 entradas`" (title "M de 4 entradas
ocupadas"), e `bg-red-lt` "N sem acoplador" só quando > 0. **Dois chamadores,
sempre juntos:** `displayGrid` (carga) e `ajax/port.php` (reescreve o span
`#dgoplus-badges` inteiro a cada porta salva — o badge de entradas sobrevive
ao AJAX por construção). O selo de duplicado fica FORA do span de propósito.

### O seletor de destino — 5e-4 + 5d

Select nativo em `MapController`, formato próprio `nome (PAPEL) #id`
(dívida 7). Segundo tempo do pulo pré-seleciona destino/entrada/localização.

### O selo de nome duplicado — 5e-2d-1

`duplicateNamesAt()` (consulta própria, memorizada), `normalizeName()`,
`renderDuplicateMark()`. Nome vazio nunca acende.

### Abas — o único modo de exibição (5e-3a/b)

Todos os elementos são abas por papel, linha única com rolagem horizontal;
IIFE no `dgoplus.js` centraliza a ativa via `scrollLeft`. ⚠️ Não medido com
dezenas de abas.

### O painel — `src/Dashboard.php`

Faixa 1 com **4 cartões** (layout restaurado no PAINEL-2b): Elementos
cadastrados (xl-4), Sem documentação (xl-4), Ocupação geral (xl-2, compacto),
Portas livres (xl-2, compacto). **As frações de grade dos dois compactos usam
`gridCriteria()` nas duas consultas — decisão do 4d, intocável** (entrada
nunca entra nessas contas). **PAINEL-2b (1.3.26):** os dois compactos ganham
no rodapé a pastilha `entriesPill()` (ponto único do estilo, `bg-green-lt`
com tooltip): "`25/164 entradas ocupadas`" na Ocupação geral e
"`139/164 entradas livres`" nas Portas livres (`livres = max(0, total −
ocupadas)`; `total = MAX_ENTRIES × elementos no escopo`). O `collect()` faz
UMA consulta de entradas + `findByDestinations` e devolve
`entries_occupied`/`entries_total`. O cartão próprio "Entradas ocupadas"
existiu só no 1.3.25 e foi removido (decisão negativa, §8).

### Comentário do elemento

`DgoIdentity::applyComment()` é o ponto único. `denied => true` só na recusa.

### Auto-save — os dois JS

`public/dgoplus.js` (475) e `public/dgoplus-identity.js` (362). Nenhum bloco
de 05/09 tocou JS — o span de badges é reescrito com o HTML que o PHP manda.

### Busca e relatório — tabela polimórfica

Para a porta, `itemtype_item_revert` + `specific_itemtype`. Search options do
Port: 1 code, 2 name, 3 itemtype, 5 tube, 6 fiber, 7 comment, 8 Localização
(**pesquisável desde o 5h-2** — o `nosearch` caiu; join `itemtype_item_revert`
+ `specific_itemtype` intacto), 9 no_coupler, 10 kind, 11 documentado por,
12 date_documented, 19 date_mod, 121 date_creation. `Port::getReportUrl()` é o
ponto único da URL. A busca do mapa é GLOBAL e busca PORTAS.

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito
`plugin_dgoplus_port`, matriz de 4 níveis = 15. Tabela de exigências
inalterada (anexos = `document` R+U+C e `datacenter` UPDATE; papéis =
`config` UPDATE). `parentIsReachable()` falha fechado.

⚠️ **A Fase 5 ainda não chegou à produção.** Deploy com rollback — bloco
próprio, sem data. Começa por RELER a produção em tela.

### Anexos — 100% do plugin (5i + 5i-2, 1.3.27/1.3.28)

**Nenhum direito nativo entra na conta dentro do DGO+.** Anexar = Atualizar
do DGO+; ver/baixar = Ler do DGO+ (o mesmo do mapa).

- **Anexar:** formulário do plugin no gerenciador → ação `attach_document`
  do `map.php` → `actionAttachDocument()`: `checkRight(UPDATE)` +
  `parentIsReachable` (trava 5f-3b) → arquivo vai a `GLPI_TMP_DIR` com
  prefixo único → `Document::add()` com `_filename`/`_prefix_filename` +
  `itemtype`/`items_id` (o `post_addItem` cria o `Document_Item` sozinho —
  um add() faz o par). O caminho NÃO checa direito nativo (lição 148,
  provado no core 11.0.6); a validação de TIPO continua a nativa
  (`isValidDoc` × `glpi_documenttypes`). Toda saída tem frase.
- **Ver/baixar:** `front/document.send.php` do PLUGIN (arquivo novo).
  Porteiro: Ler do DGO+ + `parentIsReachable` + vínculo doc↔elemento
  obrigatório — docid solto recusa falado ("O anexo não pertence a este
  elemento."). Serve por `Document::getAsResponse()`; o retorno do script
  legado vira a resposta HTTP (`LegacyFileLoadController`).
  `MapController::documentUrl()` é o ponto único do link — miniaturas,
  lista do gerenciador e cliques passam todos por ele.
- **Fora do DGO+** (ficha do ativo, Gerência → Documentos) valem os
  direitos nativos — o send.php do core não foi tocado.
- ⚠️ Dedução falseada em tela (05/09, 3ª): a rota `canViewFileFromItem`
  (READ no ativo) do send.php do CORE não abriu neste ambiente; quem abria
  era Documentos · Ler. Investigação opcional, sem bloco.

### Arquivos

**32 no repositório** (29 + 3 em `docs/`) — `front/document.send.php` nasceu
no 5i-2.

**Impressões digitais do 1.3.28** (commit `4923cab`; os alterados nas duas
entregas de 05/09 3ª conferidos por md5 contra o publicado; demais herdados):

```
a2bc8ec846e155c4dcff4103089e5d72  setup.php                    (269 linhas)
b7b83e65d39fb94a7bb1c62c56209a18  src/Port.php                 (1145 linhas)
f3ad281c9cbc3c220e9def5a584b4607  src/MapController.php        (3881 linhas)
c4d807b2d89e3ed82748ceabe152728d  src/ProfileTab.php           (186 linhas)
25ecbfedf29adcb4ce6f3b6f069eba8e  front/document.send.php       (58 linhas)
1a1f77115c954785cec105bf3227094a  src/Dashboard.php            (1352 linhas)
2597d942e15dae5d5ff02a9308a7c0db  ajax/port.php                (125 linhas)
3d9daa717ad679a9091fbd548ad92191  public/dgoplus.js            (475 linhas)
d58fdb6b783801190a79eb1ace005fca  public/dgoplus-identity.js   (362 linhas)
f8d60d99db81dc8958e67424a844351f  src/ItemLabel.php            (166 linhas)
b61cb5d74230088b7e7c02ffb35ddff2  src/Link.php                 (1310 linhas)
36ecd197f374c180a42ef7bbccc47b8c  src/DgoIdentity.php          (381 linhas)
dae5e817600bfdb6db3345cfa0383ea0  ajax/dgocomment.php           (52 linhas)
4b1c3380384313d07614738dbc52bbd5  front/port.php                (26 linhas)
9e68cde24dfd0694f1bf4bc4fdbffd9f  README.md                    (165 linhas)
```

---

## 4. Lições aprendidas

⚠️ Lacuna 1–113 mantida (dívida 3). Tabela 3–167 integralmente válida.
**Lições recentes e a nova de 05/09 (3ª sessão):**

| # | Lição |
|---|---|
| 165 | Bump de versão no `setup.php` faz PARTE do bloco de código |
| 166 | Antes de afirmar a consequência de uma alternativa, escrever o resultado por extenso |
| 167 | Elemento visual NOVO no produto pede mockup aprovado ANTES do bloco |
| **168** | **Objetivo de produto declarado não se rebaixa diante de obstáculo técnico sem perguntar.** A meta era "permissão 100% no plugin"; quando a miniatura quebrou, a primeira proposta decorou o recuo (cadeado + exigir direito nativo) em vez de buscar o caminho que preservava a meta (endpoint próprio — que existia e era MENOR). O bloco errado foi preparado e descartado antes de aplicar (custo zero em código); a causa foi tratar a própria recomendação anterior como teto do possível |

Reforços sem número novo (05/09, 3ª): lição 160 rendeu 2× (a nota do 5g-3 já
existia no código — roadmap desatualizado; PAINEL-1a já entregue); lição 147
aplicada a fundo (caminho inteiro do upload e do download lidos no core antes
de qualquer afirmação); o princípio "bloco preparado descarta a custo zero"
exercido no 5i-2 cadeado; dedução declarada como dedução foi FALSEADA em tela
(rota via READ do ativo) e o registro corrigiu na hora.

**Armadilhas permanentes do GLPI 11**: lista integral mantida.

---

## 5. Estado por bloco

Blocos 1 a PAINEL-2b: fechados e validados (até 1.3.26). **5h-2 + 5i
fechados e validados em aplicação única (1.3.27, `c74e32a`)** — filtro de
Localização no relatório; anexo por formulário do plugin. **5i-2 endpoint
fechado e validado (1.3.28, `4923cab`)** — ver anexo só com o Ler do DGO+.
**5g-3 quitado sem código** (nota já existia). **5i-2 "cadeado": preparado e
descartado antes de aplicar** (lição 168). **PAINEL-1a: já estava entregue;
PAINEL-1b: decisão negativa.**

**Nenhum bloco no estado "entregue e não exercitado". Não há bloco de código
pendente na Fase 5.**

---

## 6. Dívidas conhecidas

1. ~~README~~ ✅. 2. **Sem catálogo de tradução.** 3. **Lições 1–113 só no
documento original.** 4. ~~Tag/Release~~ ✅. 5. ~~Skill~~ ✅ por decisão.
6. ~~"Desmontar" sem botão~~ ✅. 7. **Seletor de DESTINO fora do `ItemLabel`**
— mantida por decisão (5e-4). 8. ~~Marca de colisão~~ ✅.

---

## 7. Medições de campo

⚠️ **Duas bases; tudo aqui é retrato datado** (lição 160). Reler SEMPRE.

### Produção (retratos de 28/08 e 04/09 — NÃO relidos em 05/09)

- 159 elementos (DIO 3, DGO 67, CTO 88, PTO 1; 2 na lixeira; 1 fora dos
  papéis); 4944 portas (2220 doc., 44,9%); 9 localizações com elementos.
- Localizações: 427 linhas, várias raízes, até três níveis.

### Homologação — painel geral (05/09, 2ª sessão — RELIDO)

**41 elementos** (DIO 6, DGO 16, CTO 13, PTO 6), nenhum na lixeira; 18 sem
porta; **2165 portas de grade, 43 documentadas (2,0%)** — era 42 em 04/09 —
**2122 livres, 3 na lixeira**; **25/164 entradas ocupadas** (fato novo — os
técnicos criaram dezenas de vínculos); 9 localizações com elementos (tabela
integral no print da sessão: A+, Bio qualquer > bio001, Outlet Porto Belo,
Plaza Campos Gerais, shopping estação, Shopping itajai/Bigode - 000,
shopping palladium, Shopping Pato Branco, Shopping Ventura > DGO Cristian).

**Pendência 20 (opcional):** quebra pendente×confirmado dos 25. SQL pronta:

```bash
mysql glpi -e "
SELECT COUNT(*) AS total, SUM(status='pendente') AS pendentes,
       SUM(status='confirmado') AS confirmados
FROM glpi_plugin_dgoplus_links;"
```

(Credenciais, se o socket recusar: `/var/www/html/glpi/config/config_db.php`.)

### `Outlet Porto Belo` — 8 elementos (retrato de 05/09 1ª sessão, parcial)

| Elemento | id | Papel | Obs |
|---|---|---|---|
| `DIO 001` | 39 | DIO | F1.02 com vínculo CONFIRMADO → `#41 E1` (visto 05/09 1ª; não desmontado) |
| `DGO 01 - PORTO BELO` | 33 | DGO | — |
| `DGO 01` | 34 | DGO | ⚠ par com #37 |
| `DGO 01` | 37 | DGO | ⚠ par com #34. FICA — treinamento |
| `CTO 01` | 35 | CTO | ⚠ par com #38 |
| `CTO 01` | 38 | CTO | ⚠ par com #35 |
| `TESTE 5e2d2 A` | 41 | CTO | E1 ocupada (confirmado do #39). FICA — treinamento |
| `TESTE 5e2d2 B` | 42 | CTO | FICA — treinamento |

Perfil de teste: `Tecnicos N1, ID 12`, usuário `teste.001`.

⚠️ **Estado do perfil N1 após 05/09 (3ª): dado a RELER em tela.** Os testes
do 5i/5i-2 mexeram nos direitos do N1 (só-leitura temporário; Documentos
zerado na Gerência). O esperado é que tenha voltado a Ler+Atualizar do DGO+
e Gerência limpa — mas isso se confere na tela, não aqui (lição 160). Há
pelo menos 1 anexo de teste (`001.png`) no `TESTE 5e2d2 B #42`.

---

## 8. Decisões negativas registradas

Tabela integral do v22 mantida (inclui: 5d é ciência, não bloqueio; skill não
será trocada). **Nova (05/09, 2ª):**

- **Cartão PRÓPRIO "Entradas ocupadas" no painel: REJEITADO após visto em
  tela.** A forma vigente é a pastilha dentro dos dois KPIs (variante B do
  PAINEL-2b). Não repropor cartão próprio sem fato novo.

**Novas (05/09, 3ª):**

- **PAINEL-1b ("Ver todos" em Equipamentos mais ocupados): NÃO FAZER.** O
  relatório lista portas, não elementos — não existe alvo honesto para o
  link; o cartão fica como top-5. Não repropor sem fato novo.
- **5i-2 versão "cadeado" (aceitar Documentos · Ler + decorar a recusa):
  DESCARTADA antes de aplicar.** Contrariava o objetivo de concentração
  total. A forma vigente é o endpoint próprio. Não repropor exigência de
  direito nativo para VER anexo no mapa.
- **Exigência "Documento R+U+C + Data centers UPDATE" para anexar: MORTA**
  — substituída pelos direitos do plugin (5i/5i-2).

### Decisões de produto vigentes

- **`completename` FICA (28/08).**
- **`#id` sempre no seletor de destino (5e-4).**
- **5d · confirmar em dois tempos (05/09)** — ciência, não bloqueio.
- **BADGE-C (05/09, 2ª)** — dois contadores no cabeçalho: `N/cap grade`
  (azul) · `M/4 entradas` (verde), texto seco + tooltip; "sem acoplador"
  intacto. Entrada ocupada = vínculo (pendente incluso), denominador fixo 4.
- **PAINEL-2b variante B (05/09, 2ª, sobre mockup)** — entradas como pastilha
  nos cartões Ocupação geral e Portas livres; frações do 4d intocáveis;
  layout de 4 cartões.
- **Bloco único BADGE-C+PAINEL-2 foi decisão pontual do dono** — não vira
  padrão; o método continua um-bloco-uma-mudança. (Exercida de novo em
  05/09 3ª: 5h-2+5i em aplicação única, também pontual.)
- **Anexos 100% no plugin (05/09, 3ª)** — anexar = Atualizar do DGO+;
  ver/baixar pelo mapa = Ler do DGO+; nada em Gerência → Documentos. Fora
  do DGO+ valem os direitos nativos (core intocado).
- **Abas sempre, rolagem horizontal** · **Filtro de piso só com pisos
  ocupados** · **Elementos de treinamento `#37`, `#41`, `#42` permanentes.**

---

## 9. Próximo passo imediato

**Os blocos de código da Fase 5 acabaram.** O que resta:

1. **Commit dos docs v24** (`docs/` → sem reinstalação). Código no `4923cab`.
2. **Tag/Release da Fase 5** (candidata: `v1.3.28`) — antes do deploy, para
   o deploy sair da tag. Decisão do dono.
3. **Deploy em produção** — bloco próprio com rollback; começa por RELER a
   produção em tela (retratos de 28/08–04/09 estão velhos).
4. **Frente shopmap** — bloqueada (pendência 16). **Pendência 20** (SQL dos
   25) — opcional. Investigação opcional nova: por que a rota via READ do
   ativo do `canViewFile` não abriu (sem bloco; só curiosidade de core).
5. **REV** — revisão competitiva, ao fim de tudo.

---

## 10. O que correu mal do lado do assistente

**Zero erro em código APLICADO — as duas aplicações da sessão passaram de
primeira**, md5 e `git diff --stat` exatos, paridade provada 3× (docs v23,
`c74e32a`, `4923cab`). O erro da sessão foi de LEITURA DE OBJETIVO: diante da
miniatura quebrada no perfil só-leitura, a primeira proposta de 5i-2 aceitou
a exigência nativa e a decorou com cadeado — o contrário da meta declarada
("permissão 100% no plugin"). O dono barrou antes da aplicação; o bloco foi
descartado a custo zero e o 5i-2 endpoint (menor que o descartado: +70/−5 em
4 arquivos) passou de primeira. Virou a lição 168. Acertos de processo: a
expectativa sobre a rota de download foi declarada como expectativa na
entrega do 5i e, quando a tela a falseou, o registro corrigiu na hora sem
defender a dedução; todo o caminho core (upload, download, roteador legado)
foi lido antes de qualquer afirmação (lição 147).
