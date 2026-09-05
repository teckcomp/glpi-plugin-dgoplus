# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v25 — 05/09/2026 (4ª sessão do dia — release +
> deploy). Substitui o v24 integralmente. Versão **1.3.28**, `master` em
> **`f30e931`** (docs v24; último commit de CÓDIGO: `4923cab`). Tag
> **`v1.3.28`** publicada. **PRODUÇÃO RODANDO 1.3.28 — deploy validado.**
>
> **O que o v25 traz de novo em relação ao v24:**
>
> 1. **Docs v24 commitados** (`f30e931`), paridade provada por tarball md5.
> 2. **Release `v1.3.28` publicada no GitHub** — tag anotada `e59a338`
>    apontando para `f30e931`; anexo `dgoplus-v1.3.28.zip` (187 KB, sha256
>    `673bf286…48fe`) com o MESMO hash em 4 lugares independentes (servidor,
>    PC, cálculo do GitHub, download do publicado). Conteúdo do zip provado
>    idêntico ao tarball da tag por `diff -rq`.
> 3. **DEPLOY EM PRODUÇÃO FEITO E VALIDADO** — 1.3.1 → 1.3.28, aplicação
>    passou de primeira, roteiro de 6 passos aprovado integralmente. Linha
>    de base do painel EXATA antes/depois (o deploy não mexeu em nenhum
>    número). Detalhes do ambiente de produção na §1-B (novidade do v25).
> 4. **Fatos novos da produção**: rodava **1.3.1** (não 1.3.8 como os docs
>    supunham); plugin lá é **pasta solta, SEM git**; mesmo host da
>    homologação com **porta SSH 2022**; banco **`glpidb`**; GLPI 11.0.6 ✅.
>    Painel relido em tela: **185 elementos, 2556/5712 portas (44,7%),
>    10 localizações, 45/740 entradas ocupadas** (§7).
> 5. **Salto 1.3.1 → 1.3.28 medido por tarball**: 13 arquivos alterados +
>    2 novos + `docs/`; **`src/Install.php` IDÊNTICO desde a 1.3.1** e zero
>    DDL fora dele (grep provado) — schema e direitos da produção já eram
>    os atuais; deploy foi troca de arquivos + reinstalação por bump.
> 6. **Lição 169** (§4): no cmd do Windows, destino de `scp` entre aspas
>    não pode terminar em `\`.
> 7. **Ruído promovido a conhecido**: `glpi.CRITICAL` do `CacheClearCommand`
>    após `cache:clear` — visto nos DOIS ambientes, sempre com saída de
>    sucesso e sistema íntegro.
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
| **Como estão os DADOS?** (homologação E produção) | **Só a tela, lida na sessão** (lição 160). Em 05/09 a produção tinha 185 elementos onde os retratos diziam 159 |
| **De onde sai um DEPLOY?** | **Da tag/Release — nunca do master solto** (exercido no 1.3.28) |

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
faz parte do bloco** (lição 165). O zip sobrevive só como artefato de Release
— e foi exatamente esse artefato que fez o deploy em produção.

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

### 1-A. Homologação (desenvolvimento)

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 05/09 (4ª) | **`f30e931`** (docs v24); último commit de CÓDIGO: **`4923cab`** (1.3.28) |
| **Tag/Release** | **`v1.3.28` PUBLICADA** (tag anotada `e59a338` → `f30e931`); anexo `dgoplus-v1.3.28.zip` 187 KB, sha256 `673bf2863776caeb02ce59e3469ebfeb87f2a6608c4a1917a453283bc40048fe` |
| Versão em homologação | **1.3.28** — aplicada, reinstalada e ativada em 05/09 (3ª) |
| **Paridade** | ✅ Provada na 4ª sessão: tarball da tag = tarball `f30e931` (`diff -rq`); zip do Release = tarball da tag (conteúdo) e sha256 idêntico em 4 fontes |
| Arquivos no repositório | **32** (29 do plugin + 3 em `docs/`) |
| **`docs/` no repositório** | `contexto-dgoplus.md`, `roadmap-dgoplus.md`, `README.md` — nomes SEM versão. Conteúdo atual: **v24** (commit `f30e931`); o v25 entra por cima |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave** (`%USERPROFILE%\.ssh\id_ed25519`). O servidor **recusa senha** (lição 139) |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local**, **sem PuTTY** |
| Assistente | Não tem SSH nem token. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

O shell do servidor está logado como **root**. Console do GLPI sempre com
`sudo -u www-data`.

### 1-B. PRODUÇÃO (novidade do v25 — lido em tela/terminal em 05/09)

| | |
|---|---|
| Host | **Mesmo IP da homologação: `177.87.230.179`** — o que muda é a porta |
| **SSH** | **porta `2022`**, usuário `resolutto`, MESMA chave: `ssh -i %USERPROFILE%\.ssh\id_ed25519 -p 2022 resolutto@177.87.230.179` (prompt: `root@glpi`) |
| GLPI | **11.0.6** (console confirmou), `/var/www/html/glpi`, banco **`glpidb`** |
| **DGO+** | **1.3.28, ativo** — deploy de 05/09 (antes: 1.3.1, de 22/08) |
| **Forma de implantação** | **Pasta solta, SEM git.** Deploy = zip da Release → `/tmp` → backup → unzip → chown → reinstalação. NÃO existe `git pull` na produção |
| Outros plugins lá | `mod`, `projectplus`, `qrservice`, `taskplus` (+ tarball `glpi-mod-11.0.5.tar.gz` solto na pasta — nome NÃO indica a versão do GLPI) |
| `mysqldump` | Presente (`/usr/bin/mysqldump`) |
| **Backups do deploy 1.3.28** (INTACTOS, remoção pendente de decisão) | `/root/dgoplus-tabelas-pre-1328.sql` (397 960 bytes, 4 tabelas) · `/root/dgoplus-1.3.1-bak.tar.gz` · pasta `/var/www/html/glpi/plugins/dgoplus-1.3.1-old` · `/tmp/dgoplus-v1.3.28.zip` |
| Usuários em produção | Técnicos reais documentando (ex.: anexos e vínculos vivos) — **toda leitura de dado é retrato datado** |

### O deploy 1.3.28 — registro do que foi feito (05/09)

1. **Levantamento primeiro** (regra cumprida): produção relida em tela;
   descoberto 1.3.1 (não 1.3.8) e pasta sem git.
2. **Salto medido por tarball** (v1.3.1 × v1.3.28): 13 alterados + 2 novos
   (`front/document.send.php`, `src/ItemLabel.php`) + `docs/`;
   `src/Install.php` idêntico; `grep -riE 'ALTER TABLE|CREATE TABLE|DROP
   TABLE'` fora do Install.php = vazio. **Zero mudança de schema/direitos.**
3. **Backup duplo** antes de tocar (dump das 4 tabelas + tar + `mv` da pasta).
4. sha256 do zip conferido no servidor ANTES do unzip; versão e arquivos
   novos conferidos DEPOIS; `plugin:install --force` + `plugin:activate` +
   `cache:clear` + restart.
5. **Roteiro de 6 passos aprovado integral**: plugin 1.3.28 ativo; linha de
   base do painel EXATA (185/61/2556-5712/3156/10 loc.); pastilhas nasceram
   com dado real (45/740 + 695/740 = 740, autoconferência fechou); badges e
   abas no `#187`; relatório filtrando por Localização; log só com ruído
   conhecido. **Rollback armado e não usado.**

**Rollback do deploy (ainda válido enquanto os backups existirem):**

```bash
cd /var/www/html/glpi/plugins
rm -rf dgoplus && mv dgoplus-1.3.1-old dgoplus
chown -R www-data:www-data dgoplus
sudo -u www-data php /var/www/html/glpi/bin/console plugin:install --force -u glpi dgoplus
sudo -u www-data php /var/www/html/glpi/bin/console plugin:activate dgoplus
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

### Git no servidor (só homologação)

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

`-P` maiúsculo é a porta (produção: `-P 2022`). ⚠️ **JS de bloco sai com
`.txt` no fim** (lição 149). ⚠️ **Destino entre aspas NÃO termina em `\`**
(lição 169): `"%USERPROFILE%\Downloads"` e não `"%USERPROFILE%\Downloads\"`.

**Aplicar um bloco na HOMOLOGAÇÃO (`ssh -p 2078 resolutto@177.87.230.179`):**

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

**Conferência de estado, no começo de toda sessão (homologação):**

```bash
cd /var/www/html/glpi/plugins/dgoplus
git status --short && git log -1 --oneline && grep PLUGIN_DGOPLUS_VERSION setup.php
```

⚠️ O HEAD é commit de `docs/` (`f30e931`) — o último commit de CÓDIGO é o
`4923cab` (lição 143). Após o commit dos docs v25, idem.

**Reverter (homologação):**

```bash
git checkout -- <arquivos>      # descarta a cópia, ainda não commitada
git revert HEAD && git push     # desfaz o commit já empurrado
rm -f src/<ArquivoNovo>.php     # arquivo NOVO não some com revert de merge sujo
```

### Os dois logs que interessam (mesmos caminhos nos dois ambientes)

```bash
tail -n 30 /var/www/html/glpi/files/_log/php-errors.log
grep -h "<endpoint>" /var/log/apache2/other_vhosts_access.log | tail -n 10
```

Não existe `sql-errors.log` (lição 122). **Nem toda recusa vira 403** —
`dgocomment.php` responde 200 com `denied:true`; `port.php` responde 403
(lição 154). Ruído conhecido: aviso de `version changed` da reinstalação
(114/116), backtrace do plugin `fields`, `Test logger`, e — **promovido a
conhecido no v25** — `glpi.CRITICAL` do `CacheClearCommand` após
`cache:clear`: visto nos DOIS ambientes, sempre com "Cache esvaziado com
sucesso" na saída e sistema íntegro. Ignorar quando a saída do comando for
limpa.

### Topologia web

Apache 80/443 interno; homologação externa por `177.87.230.179:2077`.
`DocumentRoot /var/www/html/glpi/public` via `conf-enabled/glpi.conf`. Nada de
`plugins/` é alcançável como arquivo pelo navegador.

### Release

**`v1.3.28` publicada em 05/09** — a release da Fase 5. Anexo
`dgoplus-v1.3.28.zip` (187 KB, prefixo `dgoplus/`, gerado por `git archive`
na tag), sha256 `673bf2863776caeb02ce59e3469ebfeb87f2a6608c4a1917a453283bc40048fe`.
Corpo com changelog da fase + instalação + hash. `v1.3.8` (27/08) e `v1.3.2`
continuam publicadas. Tags: `v1.0.0` … `v1.3.2`, `v1.3.8`, **`v1.3.28`**.
As versões 1.3.3–1.3.27 não têm tag (degraus internos). **Deploy sai da tag
— exercido: a produção roda exatamente este zip.**

### Outros plugins na homologação

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
⚠️ **Bloco sem cenário de teste não é entregue.**
⚠️ **Decisão vigente pode ser REABERTA pelo usuário.**
⚠️ **Tela NOVA pede mockup aprovado antes do código** (lição 167).
⚠️ **Deploy em produção**: começa por RELER a produção (tela E terminal),
mede o salto por tarball das duas versões, faz backup duplo, aplica do zip
da Release e valida contra linha de base capturada ANTES. Roteiro de deploy
é só-leitura — nada de gravar dado de produção no teste.

### Roteiro de teste — exigências acumuladas

- Se confere contra o código antes de sair (lição 158).
- Todo passo que troca de tela diz COMO chegar lá (lição 159).
- Toda pré-condição de dados é lida em tela antes de virar passo (lição 160).
- Roteiro autoconferente quando possível (ex.: 45/740 + 695/740 = 740 no
  deploy).
- Passo que prevê "não muda" também é passo — a linha de base do painel no
  deploy foi exatamente isso, e passou exata.
- **Frases novas de tela são simuladas por extenso ANTES de codar**
  (lição 166) — incluindo casos zerado e de escopo vazio (`0/0`).

### Nome de arquivo entregue leva o bloco

`Port-badgec.php`, `Dashboard-painel2b.php` etc. ⚠️ **`ajax/port.php` e
`front/port.php` têm o MESMO nome-base** — a entrega usa nome desambiguado
(`port-ajax-<bloco>.php`) e o `cp` leva o caminho completo. Docs versionam no
nome ENTREGUE (`contexto-dgoplus-v25.md`); no repositório o `cp` grava sem
versão (`docs/contexto-dgoplus.md`).

### O repositório é público — usar isso por padrão

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
```

Preferir `codeload` com SHA (lição 132); tags por
`tar.gz/refs/tags/<tag>`; `api.github.com` bate no limite anônimo.
**Padrão:** tarball do commit atual → editar cópia → validar → `diff -rq`
provando escopo → depois do push, baixar o publicado e provar paridade por
md5. **Número previsto sai de comando** (lições 141, 150, 155, 163).
**Anexo de Release também se baixa e se prova** (feito no 1.3.28: sha256 do
download = sha256 do servidor).

### O core do GLPI também é legível

`github.com/glpi-project/glpi`, tag `11.0.6`; classes `Glpi\` em
`src/Glpi/...`; schema em `install/mysql/glpi-empty.sql`. ⚠️ O CSS do tema NÃO
é legível por esse caminho (lição 156) — o atalho é classe que o plugin já
imprime em tela.

### O sandbox do assistente TEM PHP e Node

`php -l` (8.3.6) e `node --check`. `apt-get update` e `apt-get install -y
php-cli` em dois comandos (lição 126). ⚠️ O sandbox pode nascer SEM php-cli E
com repositório apt quebrado (nodesource 403) — remover
`/etc/apt/sources.list.d/nodesource.list` antes do update resolve.

### Práticas abolidas

Lista integral mantida (lições 114–169). Destaques: reinstalar por precaução;
`pscp`; zip como veículo de BLOCO (como artefato de Release e veículo de
DEPLOY ele é o padrão); nome final em vez de `<Arquivo>-<bloco>`; JS sem
`.txt`; F12 para status; prever números de cabeça; julgar tela sem confirmar
versão; remontar arquivo de memória; caminho abreviado; roteiro sem conferir
contra o código; dado de homologação OU produção sem reler em tela; bloco sem
bump; tela nova sem mockup aprovado; **deploy sem levantamento prévio e sem
backup duplo**; **`scp` com destino terminando em `\"`** (169).

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
⚠️ Produção mostra 1 elemento fora dos papéis (relido em 05/09 — segue 1).

### Portas

Uma tabela, dois `kind`: `KIND_GRID` (tubo × fibra) e `KIND_ENTRY` (E1–E4,
`tube_num = 0`, `MAX_ENTRIES = 4`). Chave única `(itemtype, items_id,
tube_num, fiber_num)`, `kind` fora (lição 112). **`Port::applyInput()` é o
ponto único de gravação** — `checkRight(UPDATE)` que lança o 403. Grade padrão
4×16 = 64. Porta sem acoplador não conta como documentada.

**`Port::statsForDgo()` é o ponto único das DUAS contagens do elemento:**
grade (`documented`, `no_coupler`, `total`, via `gridCriteria()`) E entradas
(`entries_occupied`, `entries_total` = `MAX_ENTRIES` fixo). **Entrada ocupada
= linha de entrada viva com vínculo apontando para ela
(`Link::findByDestinations`), pendente incluso** — a MESMA definição da faixa
E1–E4 (`renderEntryBox`). Badge do cabeçalho e pastilhas do painel consomem
daqui/da mesma definição, nunca de conta própria divergente.

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
`MapController` 8, `Link` 6, `Dashboard` 1. ⚠️ Seletor de DESTINO continua
fora do `ItemLabel` (dívida 7, mantida).

### O cabeçalho da grade — badges (BADGE-C, 1.3.25)

`MapController::renderBadges(documented, capacity, no_coupler,
entries_occupied, entries_total)` — 5 parâmetros. Renderiza: `bg-blue-lt`
"`N/cap grade`", `bg-green-lt` "`M/4 entradas`", e `bg-red-lt` "N sem
acoplador" só quando > 0. **Dois chamadores, sempre juntos:** `displayGrid`
(carga) e `ajax/port.php` (reescreve o span `#dgoplus-badges` inteiro a cada
porta salva). O selo de duplicado fica FORA do span de propósito. **Visto
vivo em produção no `#187`: `18/72 grade · 0/4 entradas · 2 sem acoplador`.**

### O seletor de destino — 5e-4 + 5d

Select nativo em `MapController`, formato próprio `nome (PAPEL) #id`
(dívida 7). Segundo tempo do pulo pré-seleciona destino/entrada/localização.

### O selo de nome duplicado — 5e-2d-1

`duplicateNamesAt()` (consulta própria, memorizada), `normalizeName()`,
`renderDuplicateMark()`. Nome vazio nunca acende.

### Abas — o único modo de exibição (5e-3a/b)

Todos os elementos são abas por papel, linha única com rolagem horizontal;
IIFE no `dgoplus.js` centraliza a ativa via `scrollLeft`. ⚠️ Não medido com
dezenas de abas — mas a produção (73 DGOs numa localização não; 19+ abas na
Jockey Plaza) rendeu tela usável no deploy; medição formal segue pendente.

### O painel — `src/Dashboard.php`

Faixa 1 com **4 cartões**: Elementos cadastrados (xl-4), Sem documentação
(xl-4), Ocupação geral (xl-2, compacto), Portas livres (xl-2, compacto).
**Frações de grade dos compactos usam `gridCriteria()` — decisão do 4d,
intocável.** **PAINEL-2b (1.3.26):** pastilha `entriesPill()` (`bg-green-lt`)
no rodapé dos dois compactos: ocupadas na Ocupação geral, livres nas Portas
livres (`livres = max(0, total − ocupadas)`; `total = MAX_ENTRIES ×
elementos no escopo`). **Em produção: `45/740` + `695/740` = 740 ✅.**
Rodapé "Ver todas as portas por atualização" na Atividade recente
(PAINEL-1a). O cartão próprio "Entradas ocupadas" foi removido (decisão
negativa, §8).

### Comentário do elemento

`DgoIdentity::applyComment()` é o ponto único. `denied => true` só na recusa.

### Auto-save — os dois JS

`public/dgoplus.js` (475) e `public/dgoplus-identity.js` (362).

### Busca e relatório — tabela polimórfica

Para a porta, `itemtype_item_revert` + `specific_itemtype`. Search options do
Port: 1 code, 2 name, 3 itemtype, 5 tube, 6 fiber, 7 comment, 8 Localização
(**pesquisável desde o 5h-2** — validado em produção com 400+ localizações),
9 no_coupler, 10 kind, 11 documentado por, 12 date_documented, 19 date_mod,
121 date_creation. `Port::getReportUrl()` é o ponto único da URL. A busca do
mapa é GLOBAL e busca PORTAS.

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito
`plugin_dgoplus_port`, matriz de 4 níveis = 15. `parentIsReachable()` falha
fechado. **Fato medido no deploy: `src/Install.php` é idêntico desde a
v1.3.1 — nenhum bloco da Fase 4 tardia/Fase 5 tocou schema ou direitos.**

### Anexos — 100% do plugin (5i + 5i-2, 1.3.27/1.3.28)

**Nenhum direito nativo entra na conta dentro do DGO+.** Anexar = Atualizar
do DGO+; ver/baixar = Ler do DGO+ (o mesmo do mapa).

- **Anexar:** formulário do plugin no gerenciador → ação `attach_document`
  do `map.php` → `actionAttachDocument()`: `checkRight(UPDATE)` +
  `parentIsReachable` (trava 5f-3b) → arquivo vai a `GLPI_TMP_DIR` com
  prefixo único → `Document::add()` com `_filename`/`_prefix_filename` +
  `itemtype`/`items_id` (o `post_addItem` cria o `Document_Item` sozinho).
  O caminho NÃO checa direito nativo (lição 148, provado no core); a
  validação de TIPO continua a nativa. Toda saída tem frase.
- **Ver/baixar:** `front/document.send.php` do PLUGIN. Porteiro: Ler do
  DGO+ + `parentIsReachable` + vínculo doc↔elemento obrigatório (docid
  solto recusa falado). Serve por `Document::getAsResponse()`.
  `MapController::documentUrl()` é o ponto único do link.
- **Fora do DGO+** valem os direitos nativos — o core não foi tocado.
- **Em produção os técnicos JÁ usam**: `#187` exibia 3 anexos reais no
  deploy — a seção nasceu com conteúdo.

### Arquivos

**32 no repositório** (29 + 3 em `docs/`).

**Impressões digitais do 1.3.28** (commit `4923cab`, inalteradas — nenhum
código mudou nesta sessão):

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

⚠️ Lacuna 1–113 mantida (dívida 3). Tabela 3–168 integralmente válida.
**Lições recentes e a nova de 05/09 (4ª sessão):**

| # | Lição |
|---|---|
| 165 | Bump de versão no `setup.php` faz PARTE do bloco de código |
| 166 | Antes de afirmar a consequência de uma alternativa, escrever o resultado por extenso |
| 167 | Elemento visual NOVO no produto pede mockup aprovado ANTES do bloco |
| 168 | Objetivo de produto declarado não se rebaixa diante de obstáculo técnico sem perguntar |
| **169** | **No cmd do Windows, destino de `scp` entre aspas não pode terminar em `\`** — a barra invertida escapa a aspa de fechamento e o scp recebe um caminho com aspa no fim (`open local "...Downloads"": No such file or directory`). Usar `"%USERPROFILE%\Downloads"` sem barra final. Causa: comando escrito pelo assistente sem simular o parsing do cmd |

Reforços sem número novo (05/09, 4ª): lição 160 rendeu de novo em dose dupla
— a produção rodava **1.3.1** onde os docs supunham 1.3.8, e tinha **185
elementos** onde os retratos diziam 159; a suposição "produção = última
release" caiu na primeira leitura de terminal. Dedução declarada como dedução
de novo acertou o processo: "tarball `glpi-mod-11.0.5` sugere GLPI 11.0.5"
foi marcada como pista, e o console falseou (11.0.6). O princípio "medir o
salto por tarball antes do deploy" (derivado das lições 141/147) transformou
o deploy de aposta em procedimento: Install.php idêntico + zero DDL fora dele
foram PROVADOS antes de qualquer comando na produção.

**Armadilhas permanentes do GLPI 11**: lista integral mantida.

---

## 5. Estado por bloco

Fase 5: **todos os blocos fechados, validados e EM PRODUÇÃO** (1.3.28,
deploy validado em 05/09). Marcos da 4ª sessão: docs v24 commitados
(`f30e931`); tag `v1.3.28` + Release publicadas; deploy 1.3.1 → 1.3.28
aprovado nos 6 passos, linha de base exata, rollback não usado.

**Não há bloco de código pendente. Não há bloco "entregue e não exercitado".**

---

## 6. Dívidas conhecidas

1. ~~README~~ ✅. 2. **Sem catálogo de tradução.** 3. **Lições 1–113 só no
documento original.** 4. ~~Tag/Release~~ ✅ (v1.3.28 publicada). 5. ~~Skill~~
✅ por decisão. 6. ~~"Desmontar" sem botão~~ ✅. 7. **Seletor de DESTINO fora
do `ItemLabel`** — mantida por decisão (5e-4). 8. ~~Marca de colisão~~ ✅.

**Nova (operacional, não de código): limpeza pós-deploy da produção** —
`/root/dgoplus-tabelas-pre-1328.sql`, `/root/dgoplus-1.3.1-bak.tar.gz`,
pasta `dgoplus-1.3.1-old` e `/tmp/dgoplus-v1.3.28.zip` ficam até o dono
mandar limpar (janela de estabilização a critério dele).

---

## 7. Medições de campo

⚠️ **Duas bases; tudo aqui é retrato datado** (lição 160). Reler SEMPRE.

### PRODUÇÃO (05/09/2026, deploy — RELIDO em tela e terminal)

- **185 elementos** (DIO 10, DGO 73, CTO 101, PTO 1); 2 na lixeira; 1 fora
  dos papéis. **61 sem documentação** (DGO 29, CTO 32).
- **2556 de 5712 portas documentadas (44,7%)**; 3156 livres, 92 na lixeira.
- **45/740 entradas ocupadas** (pastilhas nasceram com dado real — técnicos
  já criavam vínculos na 1.3.1 via fluxo de pendentes).
- **10 localizações** com elementos: Estacao, Gravatai, Itajaí, Jockey
  Plaza, Palladium Ctba, Palladium Umuarama, Pato Branco, Petropolis, Plaza
  Campos Gerais, Pulse Open Mall (todas sob raiz `Shopping >`). Localizações
  cadastradas: 427 linhas (retrato de 04/09, não relido).
- Amostra viva: `DIO L1 E G1 · #187` — `18/72 grade`, `0/4 entradas`,
  `2 sem acoplador`, **3 anexos reais**. Jockey Plaza com 19+ abas navegáveis.
- Linha de base pré-deploy = pós-deploy, EXATA (roteiro passo 2).

### Homologação — painel geral (05/09, 2ª sessão — não relido na 4ª)

**41 elementos** (DIO 6, DGO 16, CTO 13, PTO 6), nenhum na lixeira; 18 sem
porta; **2165 portas de grade, 43 documentadas (2,0%)**, 2122 livres, 3 na
lixeira; **25/164 entradas ocupadas**; 9 localizações com elementos.

**Pendência 20 (opcional):** quebra pendente×confirmado dos 25 da
homologação. SQL pronta (homologação usa banco `glpi`; produção usa
`glpidb`):

```bash
mysql glpi -e "
SELECT COUNT(*) AS total, SUM(status='pendente') AS pendentes,
       SUM(status='confirmado') AS confirmados
FROM glpi_plugin_dgoplus_links;"
```

### `Outlet Porto Belo` — homologação (retrato de 05/09 1ª sessão)

Tabela do v24 mantida: `#39 DIO 001` (F1.02 confirmado → `#41 E1`), `#33`,
`#34`/`#37` (par, #37 FICA — treinamento), `#35`/`#38` (par), `#41`/`#42`
(treinamento, FICAM; `#42` tem anexo de teste `001.png`).

Perfil de teste: `Tecnicos N1, ID 12`, usuário `teste.001`.
⚠️ **Estado do perfil N1: dado a RELER em tela** (mexido nos testes 5i/5i-2).

---

## 8. Decisões negativas registradas

Tabela integral do v24 mantida (inclui: 5d é ciência, não bloqueio; skill
não será trocada; cartão próprio "Entradas ocupadas" rejeitado; PAINEL-1b
não será feito; 5i-2 "cadeado" descartado; exigência de direitos nativos
para anexar morta).

### Decisões de produto vigentes

- **`completename` FICA (28/08).**
- **`#id` sempre no seletor de destino (5e-4).**
- **5d · confirmar em dois tempos** — ciência, não bloqueio.
- **BADGE-C** — dois contadores no cabeçalho, texto seco + tooltip.
- **PAINEL-2b variante B** — entradas como pastilha nos dois compactos.
- **Anexos 100% no plugin** — anexar = Atualizar; ver/baixar = Ler.
- **Deploy sai da tag/Release, nunca do master solto** (exercida no 1.3.28).
- **Backups do deploy só saem por ordem do dono** (janela de estabilização).
- **Abas sempre, rolagem horizontal** · **Filtro de piso só com pisos
  ocupados** · **Elementos de treinamento `#37`, `#41`, `#42` permanentes.**
- Bloco único (duas mudanças numa aplicação) é decisão pontual do dono
  quando exercida — não vira padrão.

---

## 9. Próximo passo imediato

**A Fase 5 está encerrada E em produção.** O que resta, em ordem:

1. **Commit dos docs v25** (`docs/` na homologação → sem reinstalação).
2. **Janela de estabilização da produção** — alguns dias de uso real pelos
   técnicos; ao fim, decisão do dono sobre a **limpeza dos backups**
   (`dgoplus-1.3.1-old`, tar, dump, zip em `/tmp`).
3. **REV — revisão competitiva** (liberada pelo dono para depois das
   validações em produção): avaliar softwares similares de documentação de
   rede óptica e adaptar o que interessar. É a próxima frente de trabalho.
4. **Frente shopmap** — bloqueada (pendência 16, repositório privado).
   **Pendências 20 e 21** — opcionais, sem bloco.

---

## 10. O que correu mal do lado do assistente

**Zero erro na aplicação do deploy — passou de primeira**, sha256 exato,
linha de base exata, rollback não usado. Dois tropeços menores, ambos
corrigidos na hora: **(a)** o comando de `scp` para o PC saiu com `\"` no
fim do destino — o cmd escapou a aspa e a cópia falhou; virou a lição 169;
**(b)** o diff de salto foi feito primeiro contra a v1.3.8 por suposição
("produção = última release") — a leitura do terminal derrubou a suposição
(1.3.1) e o diff foi refeito da versão certa antes de qualquer comando de
deploy; reforço da lição 160, sem lição nova (a regra já existia e foi ela
que mandou ler antes de agir). Acertos de processo: levantamento completo
antes do bloco; salto medido por tarball com prova de Install.php idêntico e
zero DDL fora dele; backup duplo; roteiro só-leitura com linha de base
autoconferente; ruído do `CacheClearCommand` promovido a conhecido com
evidência de dois ambientes.
