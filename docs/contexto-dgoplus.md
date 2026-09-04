# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v20 — 04/09/2026 (segunda sessão do dia). Substitui
> o v19 integralmente. Versão **1.3.22**, `master` em **`1829021`** — **código
> inalterado; sessão de validação, sem código.**
>
> **O que o v20 traz de novo em relação ao v19:**
>
> 1. **O commit dos docs v19 FOI FEITO** — o `master` avançou de `12a7202` para
>    **`1829021`**, e o tarball desse commit prova: `docs/` já contém o v19 e os
>    13 arquivos de código conferem md5 a md5 com as impressões digitais do
>    1.3.22. O passo 1 do roadmap v19 está quitado.
> 2. **Pendência 11 FECHADA em tela:** o Histórico do `DGO 01 #34` (33 linhas)
>    explica todo o delta de portas documentadas, com autor — o elemento nasceu
>    em 27/08 pela mão do `cristian.b` e foi documentado por `teste.001` no
>    mesmo dia. Mudança legítima de ambiente vivo.
> 3. **Pendência 7 FECHADA de graça, pelos mesmos prints:** as linhas do
>    Histórico carregam `teste.001 (19)` e `cristian.b (29)` como autores.
>    O mecanismo foi provado também no código nesta sessão (ver seção 3,
>    "Histórico").
> 4. **Pendência 18 FECHADA em tela (produção):** as localizações TÊM pai — e
>    a árvore é maior do que parecia: **427 linhas com VÁRIAS raízes**
>    (`Shopping` com **~42**, informado pelo usuário; além de `Fleury`,
>    `Confiance`, `Padrão`…), com **até três níveis**
>    (`Shopping > Palladium Ctba > Diretoria`). A base de localizações é
>    compartilhada com contextos que não são DGO+.
> 5. **Fatos novos de homologação:** `Plaza Campos Gerais` TEM elementos e
>    portas documentadas (`Pedro s`, 20/08); existem localizações
>    `shopping estação` e `Shopping Ventura` (esta com filha
>    `Shopping Ventura > DGO Cristian` — pai existe também na homologação).
> 6. **Fato novo para a decisão do `completename`:** na produção o prefixo
>    `Shopping > ` aparece em praticamente todo rótulo. A decisão de 28/08
>    ("completename FICA") NÃO foi reaberta — o fato está registrado para
>    quando o usuário quiser (seção 8).
> 7. **Nenhuma lição nova numerada.** As mais recentes seguem 165 e 166.
>
> Companheiro: `roadmap-dgoplus.md`. Os dois vivem em `docs/` no repositório.

---

## 0. A regra que governa tudo

**O GitHub é o repositório canônico do DGO+. A homologação é descartável.**

Todo estado do código tem que ser reconstruível a partir do `master` sozinho. A
regra nasceu de um fato: **houve um incidente doméstico em que a base de
homologação foi perdida com um repositório dentro dela, levando junto correções
que nunca chegaram ao Git.**

| Pergunta | Fonte da resposta |
|---|---|
| **O que está rodando agora?** (tela, erro, permissão) | O servidor — sempre |
| **O que o código É?** (registro durável, base de bloco novo) | **O GitHub — sempre** |
| **Como estão os DADOS da homologação?** | **Só a tela, lida na sessão** (lição 160). **A homologação é ambiente vivo dos técnicos de campo** — em 04/09 (2ª sessão) o relatório revelou localizações e documentação que nenhum doc conhecia |

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
faz parte do bloco** (lição 165).

O zip sobrevive só como artefato de Release.

### ⚠️ A skill cadastrada está desatualizada DE PROPÓSITO

**Decisão do usuário (04/09): a skill `glpi-plugin-teckcomp` cadastrada NÃO
será trocada.** Ela ainda descreve `pscp`/PuTTY, `192.168.1.50` e zip — tudo
abolido. Isso é **ruído conhecido**, como o aviso de `version changed` no log:

- A fonte da verdade do ambiente é o **`SKILL-glpi-plugin-teckcomp.md` na base
  do projeto** (md5 `edc469d2a1f5a9400b330143c0bf3891`, conferido em 04/09),
  somado a este contexto.
- Quando a skill carregada e o contexto divergirem, **o contexto manda**.
- Não voltar a propor a troca sem fato novo (decisão negativa, seção 8).

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 04/09 (2ª sessão) | commit **`1829021`** (docs v19), versão **1.3.22** — confirmado por `ls-remote` na sessão |
| Último commit de CÓDIGO | `12a7202` (1.3.22) — o `1829021` só mudou `docs/` |
| Versão em homologação | **1.3.22** — aplicada e reinstalada em 03/09 |
| **Paridade** | ✅ Provada por md5 NESTA sessão: os 13 arquivos de código do `1829021` = impressões digitais do 1.3.22 |
| Arquivos no repositório | **31** (28 do plugin + 3 em `docs/`) |
| **`docs/` no repositório** | `contexto-dgoplus.md`, `roadmap-dgoplus.md`, `README.md` — **nomes SEM versão; a versão vive no cabeçalho**. Conteúdo atual: **v19** (commit `1829021`); o v20 entra por cima |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha**. Lição 139 |
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

⚠️ Quando o HEAD é commit de `docs/` (é o caso do `1829021`), o `git log -1`
mostra o commit dos docs — o último commit de CÓDIGO pode ser anterior. Normal
(lição 143).

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
(lição 154). Ruído conhecido no log: aviso de `version changed` da
reinstalação (lições 114/116), backtrace do plugin `fields`, `Test logger`.

### Topologia web

Apache 80/443 interno; externo por `177.87.230.179:2077`.
`DocumentRoot /var/www/html/glpi/public` via `conf-enabled/glpi.conf`. Nada de
`plugins/` é alcançável como arquivo pelo navegador.

### Release

`v1.3.8` publicada em 27/08 (zip 177 KB, sha256 `34e1fd…ef16`); `v1.3.2`
continua publicada. Tags existentes: `v1.0.0` … `v1.3.2`, `v1.3.8`. **As
versões 1.3.3 a 1.3.22 não têm tag** — degraus internos da Fase 5. Próxima tag
quando a Fase 5 fechar.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, `codexplus` 0.5.2-alpha, `datainjection`,
`archimap`, `gantt` 1.3.4, `moreticket`, `projectplus` 1.1.0-beta, `shopmap`
0.1.0, `stab`, `tag`, `taskplus` 0.2.1-beta, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6,
`Tasks list` 2.1.12.

### O shopmap — frente irmã, fora deste repositório

⚠️ **`github.com/teckcomp/glpi-plugin-shopmap` está PRIVADO** (404 anônimo,
28/08). O assistente não lê esse código. Problema: "Vincular ativo (nome)"
lista nome + itemtype apenas.

> **Referência a ativo é `itemtype` + `id`. Nome é rótulo, nunca chave.**

Falta responder — e só a base do shopmap responde: *o vínculo é guardado pelo
NOME ou por `itemtype`+`id`?* Por chave → só tela. Por nome → tela + migração
em três baldes. O `MapController::normalizeName()` (5e-2d-1) é a peça
reaproveitável.

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

⚠️ **Sessão de VALIDAÇÃO não é entrega de bloco** — só roteiro. As duas
sessões de 04/09 foram assim: higiene (1ª) e fechamento de pendências 7/11/18
(2ª), zero código.
⚠️ **Bloco sem cenário de teste na homologação não é entregue.**
⚠️ **Bloco preparado pode MORRER antes de aplicar** (03/09, 5e-2d-2) — não é
erro, é o processo funcionando. **Ver a alternativa rodando vale mais que
decisão fechada em abstrato.**
⚠️ **Roteiro preparado pode ficar DESNECESSÁRIO** (04/09, pendência 7: as
telas da pendência 11 já provaram o autor — nada foi gravado à toa).

### Roteiro de teste — exigências acumuladas

- Se confere contra o código antes de sair (lição 158). Na 2ª sessão de 04/09
  isso significou ler `Port.php`, `Link.php`, `Log.php` e `CommonDBChild.php`
  (core) ANTES de escrever o roteiro do histórico.
- Todo passo que troca de tela diz COMO chegar lá (lição 159).
- Toda pré-condição de dados é lida em tela antes de virar passo (lição 160).
- Passo que prevê "não muda" também é passo.
- Passo que prova a decisão de projeto vem nomeado como tal.

### Nome de arquivo entregue leva o bloco

`MapController-5e3b.php`, `dgoplus-5e3b.js.txt`. O `cp` renomeia (lição 140).
Docs versionam no nome do arquivo ENTREGUE (`contexto-dgoplus-v20.md`); no
repositório o `cp` grava sem versão (`docs/contexto-dgoplus.md`).

### O repositório é público — usar isso por padrão

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
```

Preferir `codeload` com SHA ao `raw` (lição 132); `api.github.com` bate no
limite anônimo. **Padrão:** baixar tarball do commit atual, editar cópia,
validar, provar por `diff -rq` que só o escopo mudou; depois do push, baixar o
commit publicado e provar paridade por md5.

**Número previsto sai de comando** (lições 141, 150, 155, 163). Espelho da
163: `grep -c '^+[^-]'`/`'^-[^-]'` não conta linhas em branco — a contagem
completa é `^+`/`^-` menos os cabeçalhos `+++`/`---`.

### O core do GLPI também é legível

`github.com/glpi-project/glpi`, tag `11.0.6`; classes `Glpi\` em
`src/Glpi/...`; schema em `install/mysql/glpi-empty.sql`. ⚠️ **O CSS do tema
NÃO é legível por esse caminho** (lição 156). Caminhos baratos: classe que o
plugin já imprime em tela, ou estilo inline (foi o inline no 5e-3a).
⚠️ Para arquivo de TAG (estável), `raw.githubusercontent.com` serve — o
problema de cache do raw é com commit recente, não com tag (usado em 04/09
para `Log.php` e `CommonDBChild.php` da 11.0.6).

### O sandbox do assistente TEM PHP e Node

`php -l` (8.3.6) e `node --check` (v22.22.2). `apt-get update` e
`apt-get install -y php-cli` em dois comandos (lição 126).

### Práticas abolidas

Lista integral mantida (lições 114–166), sem adição em 04/09.
Destaques permanentes: reinstalar por precaução; `pscp`; zip como veículo;
nome final em vez de `<Arquivo>-<bloco>`; JS sem `.txt`; F12 para status;
prever números de cabeça; julgar tela sem confirmar versão; remontar arquivo
de memória; caminho abreviado; roteiro sem conferir contra o código; dado de
homologação sem reler em tela; purgar sem ler as quatro entradas; bloco sem
bump; afirmar consequência sem simular o formato inteiro.

---

## 3. Arquitetura

### O que é do GLPI e o que é do plugin

A DGO **não é um itemtype do plugin**. Cada elemento é um `PassiveDCEquipment`
nativo; o plugin acrescenta grade, escopo e vínculos. O core não conhece as
tabelas do plugin — daí o `PurgeCleaner` (faxina provada em tela em 04/09).
Escopo: **Localização (nativa) → Piso (intitulado do plugin)**.

### Pisos — cadastro × filtro

- **Cadastro** em `Configurar → Listas suspensas → Pisos` — é lá que se
  confere a EXISTÊNCIA de um piso.
- **O filtro do mapa usa `floorsWithItems()`** — só pisos COM elemento no
  escopo corrente (decisão do bloco 5b). Piso vazio NUNCA aparece no dropdown;
  localização sem piso com elemento não renderiza o filtro; escopo cheio sem o
  piso na lista prova que ele está vazio.
- `Floor::getForLocation()` (lista bruta, com entidade somada) valida
  `?floor=` na entrada do controlador.

### Papéis

`Setting::ROLES` **é** a hierarquia: `dio` → `dgo` → `cto` → `pto`. Splitter
fora; proporção no OBS. Produção: um Tipo por papel (`DIO+`, `DGO+`, `CTO+`,
`PTO+`), em `glpi_configs`, contexto `plugin:dgoplus`. ⚠️ Produção mostra 1
elemento fora dos papéis.

### Portas

Uma tabela, dois `kind`: `KIND_GRID` (tubo × fibra, ABNT/EIA) e `KIND_ENTRY`
(E1–E4, `tube_num = 0`). Chave única `(itemtype, items_id, tube_num,
fiber_num)`, `kind` fora (lição 112). **`Port::applyInput()` é o ponto único
de gravação** — faz o `checkRight(UPDATE)` que lança o 403. Grade padrão 4×16
= 64 posições (lição 146). Porta sem acoplador não conta como documentada.

Porta de grade **com vínculo e sem nome CONTA como documentada** —
`applyInput` não apaga linha com vínculo; `statsForDgo` (947–968 no 1.3.22)
conta linhas da grade (`is_deleted=0`) e desconta as sem-acoplador.

**Carimbo de documentação (bloco 3s):** `documentStamp()` é o ponto único de
`users_id_documenter`/`date_documented`; carimba só quando o VALOR do código
muda — corrigir OBS não rouba autoria; apagar código não apaga carimbo.

### Histórico — mecanismo PROVADO em código e em tela (04/09, 2ª sessão)

Fatos verificados no commit `1829021` + core 11.0.6:

- `Port` estende `CommonDBChild` com `dohistory = true`; **não declara**
  `$logs_for_parent` — vale o default do core, **`true`**
  (`CommonDBChild.php:62`). Toda gravação de porta gera linha no **Histórico
  do elemento pai** (`HISTORY_ADD/UPDATE/DELETE_SUBITEM`).
- `applyInput` grava por `$port->add()`/`$port->update()` (objeto, nunca SQL
  direto) — os ganchos de histórico disparam sempre.
- Cada linha do `glpi_logs` grava **`user_name`** = nome do usuário logado
  (`Log::history` → `User::getNameForLog`). **O técnico aparece como autor** —
  provado em tela: `cristian.b (29)` e `teste.001 (19)` nomeados linha a linha
  no Histórico do `DGO 01 #34`.
- `history_blacklist = ['users_id_documenter', 'date_documented']` — o
  carimbo NÃO vira linha de histórico (seria ruído); o que aparece é a mudança
  de código/OBS/acoplador.
- `Link` tem `dohistory = true` (histórico próprio, é `CommonDBTM`).

### Vínculos

`glpi_plugin_dgoplus_links`: uma linha, dois lados. Sem `is_deleted` — recusa
apaga. Pendente já ocupa a porta. Uma porta alimenta um destino só. Hierarquia
permissiva: pode pular nível, nunca subir nem empatar; `hierarchyAllows()` não
sabe QUANTO desceu — lacuna do 5d. Só confirmado sobe na trilha (4e).
**`Link::propose()` é o ponto único de criação.** Recusar e confirmar pedem
UPDATE; desmontar pede DELETE. `upstreamLevels(…, ?$from_entry_id)` restringe
o nível 0 a uma entrada (5c); chamador único `displayEntryCard()`.
⚠️ Pendente que envelhece não avisa ninguém.

### O rótulo de elemento — `src/ItemLabel.php`

Ponto único do nome em tela. `forRow`/`forItem` = `nome · localização · #id`
(telas que atravessam localizações); `shortForRow` = `nome · #id` (telas
recortadas). Localização via `Dropdown::getDropdownName` — devolve o
**`completename`** (árvore com `>`), memorizado em cache estático.

✅ **Decisão fechada (28/08): `completename` FICA.** ⚠️ **FATO NOVO registrado
em 04/09 (2ª sessão), sem reabrir a decisão:** as localizações da produção
usadas pelo DGO+ são filhas da raiz `Shopping` — rótulo tipo
`nome · Shopping > Palladium Umuarama · #id` — e a árvore chega a **três
níveis** (`Shopping > Palladium Ctba > Diretoria`); se algum elemento for
pendurado num terceiro nível, o rótulo carrega os três degraus. Reabrir é
decisão do usuário (seção 8). Nome vazio imprime `sem nome`; ausente devolve
`elemento #%d`.

**Consumidores (medidos no `fbf1952`, inalterados):** `MapController.php` 8,
`Link.php` 6, `Dashboard.php` 1.

⚠️ **O seletor de DESTINO continua fora do `ItemLabel`** (`CTO 01 (CTO) #35`).
É a dívida 7.

### O selo de nome duplicado — 5e-2d-1

Três peças no `MapController`: `duplicateNamesAt(int $locations_id)` (uma
consulta própria por carga, memorizada, só nomes com 2+); `normalizeName()`
(sem caixa e sem espaço — `DGO 001` casa com `DGO001`); `renderDuplicateMark()`
(ponto único da marca: ícone na aba, pílula `bg-orange-lt` no cabeçalho;
`DUP_COLOR = '#D68A3A'`).

**A consulta é PRÓPRIA de propósito** — calcular do `getDgosAtLocation()`
faria o selo sumir conforme o filtro de piso/papel. ✅ 100% exercitado; em
04/09 (2ª sessão) o tooltip do `#34` foi revisto de novo, aceso e correto
("Nome repetido nesta localização: #34, #37").

Fora do selo, de propósito: trilha, Alimenta, anexos, painel da porta. Nome
vazio nunca acende.

### Abas — o único modo de exibição (5e-3a e 5e-3b)

**O seletor único morreu. `MAX_TABS` não existe mais.** Todos os elementos são
abas de verdade, agrupadas por papel, em **linha única que não quebra**:

- `<ul class='nav nav-tabs …' data-dgoplus-tabs='1'
  style='flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden'>` — estilo inline
  de propósito (lição 156); cada `<li>` com `flex:0 0 auto;white-space:nowrap`.
- A **barra de rolagem horizontal** aparece nativa, **só quando há overflow**
  — 8 elementos na `Outlet Porto Belo`, sem barra (revisto de novo em 04/09).
- **5e-3b:** módulo IIFE no fim do `dgoplus.js` que, na carga, havendo
  overflow E aba ativa, **centraliza a ativa mexendo só no `scrollLeft` da
  própria UL** — nada de `scrollIntoView`. Sem overflow, sem ativa ou sem a
  UL: sai calado.

O formato de aba segue `shortForRow` + selo + contagem.

### Comentário do elemento

`DgoIdentity::applyComment()` é o ponto único (POST clássico e
`ajax/dgocomment.php`). Devolve `denied => true` só na recusa de permissão; o
endpoint repassa; a frase da tela vem do PHP. Regra num lugar só.

### Auto-save — os dois JS

`public/dgoplus.js` (**475 linhas** — painel da porta + módulo das abas) e
`public/dgoplus-identity.js` (362 — comentário). 403 ≠ rede nos dois; recusa
não reenvia (5g-1b, provado em campo). `save()` sai cedo em
`current === lastSaved` — roteiro de reenvio precisa de textos DIFERENTES.
Sem o direito, o JS nem se instala (lição 151).

### Busca e relatório — tabela polimórfica

Jointypes válidos no 11.0.6; para a porta, `itemtype_item_revert` +
`specific_itemtype` obrigatório. Search options do Port: 1 code, 2 name,
3 itemtype, 5 tube, 6 fiber, 7 comment, 8 Localização (`nosearch`), 9
no_coupler, 10 kind, 11 documentado por, 12 date_documented, 19 date_mod,
121 date_creation. `Port::getReportUrl()` é o ponto único da URL. A busca do
mapa é GLOBAL e busca PORTAS (código/nome/observação) — não enxerga pisos nem
elementos.

⚠️ **Portas anteriores ao bloco 3s aparecem com "Documentado por" vazio no
relatório** (visto em 04/09: linhas de 04–17/08 sem carimbo). Comportamento
esperado, não defeito — o carimbo não é retroativo.

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito
`plugin_dgoplus_port`, matriz de 4 níveis = 15; aba **DGO+**, linha **"Portas
de DGO"**. Tabela de exigências inalterada (READ vê; UPDATE documenta, comenta
e mexe em vínculo; DELETE esvazia/desmonta; CREATE cria; entidade trava toda
gravação; anexos = `document` R+U+C **e `datacenter` UPDATE**; papéis =
`config` UPDATE). Os dois greps de guarda continuam devolvendo 0/nada.
`parentIsReachable()` falha fechado.

⚠️ **A Fase 5 ainda não chegou à produção.** Deploy com plano de rollback —
bloco próprio, sem data. Começa por RELER a produção em tela.

### Anexos

Cartão usa formulário do core → exige `datacenter` UPDATE (lição 134). Mas
`CommonDBTM::add()` não checa direito (lição 148) — o que falta é tela:
candidato **5i**.

### Arquivos

**31 no repositório** (28 + 3 em `docs/`). `docs/` = `contexto-dgoplus.md`,
`roadmap-dgoplus.md`, `README.md` (nomes sem versão).

**Impressões digitais do 1.3.22** — re-conferidas em 04/09 (2ª sessão) no
tarball do `1829021`, todas batendo:

```
34f90a86923074e0d79a3a95e721a0f2  setup.php                    (269 linhas)
3d9daa717ad679a9091fbd548ad92191  public/dgoplus.js            (475 linhas)
d58fdb6b783801190a79eb1ace005fca  public/dgoplus-identity.js   (362 linhas)
f8d60d99db81dc8958e67424a844351f  src/ItemLabel.php            (166 linhas)
52ab95366b20809e952972c1c1a9b823  src/Port.php                 (1120 linhas)
9a7634edb132423b73bd9357e36b9230  src/Link.php                 (1235 linhas)
1fc2dc632ebbdb24c540e857712f2871  src/MapController.php        (3637 linhas)
36ecd197f374c180a42ef7bbccc47b8c  src/DgoIdentity.php          (381 linhas)
d2baa8fdfdfe4d54cfdd784f59b0443a  src/Dashboard.php            (1282 linhas)
f4d2f1d2773e81bfb6486e15371ef816  src/ProfileTab.php           (184 linhas)
b0e4f1837feab5a54d42868e8d88a4b7  ajax/port.php                (123 linhas)
dae5e817600bfdb6db3345cfa0383ea0  ajax/dgocomment.php           (52 linhas)
4b1c3380384313d07614738dbc52bbd5  front/port.php                (26 linhas)
9e68cde24dfd0694f1bf4bc4fdbffd9f  README.md                    (165 linhas)
```

---

## 4. Lições aprendidas

⚠️ Lacuna 1–113 mantida (dívida 3, caminho barato esgotado). A tabela de
lições 3–166 permanece integralmente válida — **nenhuma lição nova numerada
em 04/09** (nas duas sessões). As mais recentes:

| # | Lição |
|---|---|
| 165 | **Bump de versão no `setup.php` faz PARTE do bloco de código** |
| 166 | **Antes de afirmar a consequência de uma alternativa, escrever o resultado por extenso** |

Reforços de 04/09 (sem número novo): explicação de comportamento de tela se
confere no código antes de virar afirmação (1ª sessão, `floorsWithItems()`;
2ª sessão, `logs_for_parent` — o comentário do `Port.php` afirmava `true`, mas
a propriedade não está declarada; a prova veio do default do core, lido).

**Armadilhas permanentes do GLPI 11**: lista integral mantida.

---

## 5. Estado por bloco

Blocos 1 a 5e-3b: fechados e validados (até 1.3.22). A frente 5e está
ENCERRADA. Sem mudança de estado de bloco no v20 — a sessão foi de pendências
(seção 7 do roadmap).

**Nenhum bloco está no estado "entregue e não exercitado".**

---

## 6. Dívidas conhecidas

1. ~~README~~ ✅ quitada.
2. **Sem catálogo de tradução** — decisão de produto pendente.
3. **Lista integral de lições (1–113)** — só pelo documento original.
4. ~~Tag/Release~~ ✅ quitada.
5. ~~Skill desatualizada~~ ✅ quitada por decisão (04/09).
6. ~~"Desmontar" sem botão~~ ✅ quitada.
7. **Seletor de DESTINO fora do `ItemLabel`** (`CTO 01 (CTO) #35`). Última
   inconsistência de formato.
8. ~~Seletor único sem marca de colisão~~ ✅ quitada por remoção (03/09).

---

## 7. Medições de campo

⚠️ **Duas bases; tudo aqui é retrato datado** (lição 160). **A homologação é
ambiente vivo dos técnicos** — reler SEMPRE.

### Produção (retratos de 28/08 e 04/09)

- **Elementos/portas (28/08, NÃO relido):** 159 elementos (DIO 3, DGO 67,
  CTO 88, PTO 1; 2 na lixeira; 1 fora dos papéis); 4944 portas (2220 doc.,
  44,9%); 9 localizações COM elementos — `Palladium Umuarama` 91,0% …
  `Palladium Ctba` 2,5%. Documentadores: Claudio Morett, Kayan Lucas, Pedro s,
  cristian.b.
- **Localizações (04/09, 2ª sessão — pendência 18 FECHADA):** a lista
  administrativa tem **427 linhas**, com **VÁRIAS raízes**: `Shopping` (as
  unidades DGO+: `Shopping > Palladium Ctba`, `Shopping > Jockey Plaza`,
  `Shopping > Palladium Umuarama`, `Shopping > Estacao`,
  `Shopping > Plaza Campos Gerais`, etc.), e também `Fleury`, `Confiance` e
  `Padrão`, entre outras — **apenas ~42 das 427 são de shoppings (informado
  pelo usuário)**. A árvore chega a **três níveis**
  (`Shopping > Palladium Ctba > Diretoria`). **A base de localizações da
  produção é compartilhada com contextos alheios ao DGO+** — o grosso das 427
  não tem elemento óptico. Consequências anotadas: rótulo (seção 3, ItemLabel)
  e valor do 5h-2 (seção 9).

### Homologação — localizações conhecidas (04/09, 2ª sessão)

| Localização | O que se sabe | Fonte |
|---|---|---|
| `Outlet Porto Belo` | 8 elementos (tabela abaixo); piso `MALL - PORTO BELO` | Tela 03–04/09 |
| `shopping palladium` | Elementos com portas (Claudio Morett, 20/08, no relatório); sem piso com elemento | Tela 03–04/09 |
| `Plaza Campos Gerais` | Pisos `L1`/`L2`; **TEM elementos com portas documentadas** (`Pedro s`, 20/08 — grade e entrada, visto no relatório). Elementos não abertos no mapa — contagens não afirmadas | Relatório de portas 04/09 |
| `shopping estação` | **NOVA aos docs (04/09, 2ª sessão).** Portas documentadas por `Pedro s` (20/08) e outras de 08–13/08 sem carimbo | Relatório de portas 04/09 |
| `Shopping Ventura` | **NOVA aos docs.** Tem filha `Shopping Ventura > DGO Cristian` — **pai existe também na homologação**; porta de 04/08 sem carimbo | Relatório de portas 04/09 |

**Relatório de portas da homologação em 04/09: 66 linhas no total.**

**Pisos cadastrados (04/09, 1ª sessão):** `L1` e `L2` (`Plaza Campos
Gerais`), `MALL - PORTO BELO` (`Outlet Porto Belo`) — 3 linhas.

### `Outlet Porto Belo` — 8 elementos

| Elemento | id | Papel | Selo | Obs |
|---|---|---|---|---|
| `DIO 001` | 39 | DIO | — | 2 doc. Criado pelos técnicos |
| `DGO 01 - PORTO BELO` | 33 | DGO | — | 1 doc |
| `DGO 01` | 34 | DGO | ⚠ par com #37 | **5 doc** (contador revisto em 04/09, 2ª sessão). **Histórico lido: 33 linhas** — criado por `cristian.b` em 27/08 09:18; F1.01–F1.06 adicionadas em 27/08 (cristian.b a F1.01; teste.001 as demais); E1 adicionada por Claudio em 29/08 |
| `DGO 01` | 37 | DGO | ⚠ par com #34 | 1 doc. **FICA — treinamento** |
| `CTO 01` | 35 | CTO | ⚠ par com #38 | 1 doc |
| `CTO 01` | 38 | CTO | ⚠ par com #35 | 0 doc. Criado pelos técnicos |
| `TESTE 5e2d2 A` | 41 | CTO | — | 0 doc. **FICA — treinamento** |
| `TESTE 5e2d2 B` | 42 | CTO | — | 0 doc. **FICA — treinamento** |

**Nota aberta (pequena, opcional):** o Histórico do `#34` mostra **6 adições
de grade** (F1.01–F1.06) e o contador da aba mostra **5**. Pela regra do
`statsForDgo`, a que não conta ou está sem acoplador ou foi esvaziada e
apagada — candidata: **F1.04**. Um olhar na grade fecha; não é pendência
numerada.

**Painel/pendentes:** retrato do v16, não relido. Perfil de teste: `Tecnicos
N1, ID 12`, usuário `teste.001` — **`teste.001 (19)` visto ativo no histórico
de 27/08**.

---

## 8. Decisões negativas registradas

Tabela integral do v19 mantida (skill não será trocada; MAX_TABS revogada;
sufixo na `<option>` morto; `scrollIntoView`, múltiplas linhas, lista lateral
e dropdown com limite maior descartados; mais as 36 do v17).

### Decisões de produto vigentes

- **`completename` FICA (28/08)** — ⚠️ com **fato novo registrado em 04/09**:
  as localizações DGO+ da produção são filhas de `Shopping` (prefixo
  `Shopping > ` nos rótulos) e a árvore chega a três níveis. **A decisão NÃO
  foi reaberta**; reabrir exige vontade do usuário, agora com o fato na mesa.
- **BADGE-C · variante C** — dois contadores (`0/16 grade` · `2/4 entradas`);
  toca `statsForDgo`, `renderBadges`, `ajax/port.php`.
- **5d · confirmar em dois tempos** — vínculo que pula degrau não grava na
  primeira. O trabalho real: destino e entrada sobreviverem ao redirect.
  Limitações aceitas: marcador forjável no POST; nada registrado depois.
- **Abas sempre, rolagem horizontal** — vigente desde 03/09.
- **Filtro de piso só com pisos ocupados** (bloco 5b).
- **Elementos de treinamento na homologação** — `#37`, `#41`, `#42`
  permanentes; purgá-los exige nova decisão do usuário.

---

## 9. Próximo passo imediato

1. **Commit dos docs v20** no repositório (só `docs/` → sem reinstalação).
   Entra por cima do v19 (commit `1829021`).
2. **Commit — 5d**, confirmação em dois tempos. ⚠️ Mexe no `Link::propose()`.
3. **Commit — BADGE-C + contador de entradas separado.**
4. **5h-2** (remover `nosearch` da Localização no relatório — **valorizado
   pelo fato das 427 localizações da produção**), **5i** (anexo por formulário
   próprio), e o **bloco de deploy em produção** (com rollback; começa por
   reler a produção em tela).
5. **Frente shopmap** — bloqueada pela pendência 16 (repositório privado).
6. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.

---

## 10. O que correu mal do lado do assistente

**O padrão se mantém: zero erro em código gravado** (sessão sem código).
Nenhuma ocorrência na 2ª sessão de 04/09 — as afirmações de mecanismo
(histórico, autor, blacklist) foram todas lidas no código/core ANTES do
roteiro, e a única suspeita (comentário do `logs_for_parent` sem propriedade
declarada) foi resolvida lendo o default do core, não assumindo.

**O que o processo provou em 04/09 (2ª sessão):** um roteiro preparado
(pendência 7) morreu por já estar provado pelas telas de outra pendência —
nada foi gravado à toa na homologação; a leitura da produção rendeu fatos que
mudam prioridade (427 localizações, maioria alheia ao DGO+ → 5h-2 mais
valioso) e alimentam uma decisão futura (rótulo com até três degraus); e a
primeira leitura do v20 ("o padrão `Shopping >` domina"), corretamente
ressalvada como "só página 1 lida", foi **corrigida pelo usuário com mais
tela ANTES do commit** — várias raízes, ~42 de shoppings. A ressalva
funcionou como desenhada: dedução declarada nunca virou fato.
