# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v19 — 04/09/2026. Substitui o v18 integralmente.
> Versão **1.3.22**, `master` em **`12a7202`** — **inalterados; sessão sem
> código.**
>
> **O que o v19 traz de novo em relação ao v18:**
>
> 1. **Pendência 19 fechada em tela:** a F1.06 da `DGO 01 #34` está limpa —
>    código `2153` presente, SEM vínculo, E3 `livre`. A faxina do
>    `PurgeCleaner` na purga da `#36` está provada.
> 2. **Pendência 13 fechada em tela:** o `PISO VAZIO TESTE` existia (em
>    `Outlet Porto Belo`), foi provado vazio e **removido**. A lista
>    administrativa de Pisos terminou com 3 linhas.
> 3. **Regra do filtro de piso relida no código:** o dropdown do mapa usa
>    `floorsWithItems()` — só pisos COM elemento no escopo corrente (decisão
>    do bloco 5b). **Existência de piso se confere na tela administrativa,
>    nunca no filtro do mapa.**
> 4. **Localização nova descoberta:** `Plaza Campos Gerais`, com pisos `L1` e
>    `L2` — nenhum doc anterior a conhecia. Elementos dela NÃO lidos em tela.
> 5. **Dívida 5 quitada POR DECISÃO:** a skill cadastrada não será trocada. O
>    retrato correto do ambiente vive na base do projeto
>    (`SKILL-glpi-plugin-teckcomp.md`); o conteúdo antigo da skill vira ruído
>    conhecido.
> 6. **`docs/` do repositório verificado no tarball:** os nomes são SEM versão
>    (`contexto-dgoplus.md`, `roadmap-dgoplus.md`, `README.md`) e o conteúdo
>    ainda é o **v17** — o v18 nunca foi commitado; o v19 entra direto.
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
| **Como estão os DADOS da homologação?** | **Só a tela, lida na sessão** (lição 160). **A homologação é ambiente vivo dos técnicos de campo** — elementos nascem e somem entre sessões. Em 04/09 apareceu até uma LOCALIZAÇÃO nova (`Plaza Campos Gerais`) que nenhum doc conhecia |

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
| `master` em 04/09 | commit **`12a7202`**, versão **1.3.22** (confirmado por `ls-remote` na sessão) |
| Versão em homologação | **1.3.22** — aplicada e reinstalada em 03/09; nada mudou em 04/09 |
| **Paridade** | ✅ Provada por md5 em 03/09 (arquivos do `12a7202` = entregues) |
| Arquivos no repositório | **31** (28 do plugin + 3 em `docs/`) |
| **`docs/` no repositório** | `contexto-dgoplus.md`, `roadmap-dgoplus.md`, `README.md` — **nomes SEM versão; a versão vive no cabeçalho** (verificado no tarball em 04/09). Conteúdo atual: **v17** — o v18 nunca foi commitado |
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

⚠️ Quando o HEAD é commit de `docs/`, o `git log -1` mostra o commit dos docs —
o último commit de CÓDIGO pode ser anterior. Normal (lição 143).

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

⚠️ **Sessão de VALIDAÇÃO não é entrega de bloco** — só roteiro. A sessão de
04/09 foi inteira assim: duas conferências em tela, uma remoção, zero código.
⚠️ **Bloco sem cenário de teste na homologação não é entregue.**
⚠️ **Bloco preparado pode MORRER antes de aplicar** (03/09, 5e-2d-2) — não é
erro, é o processo funcionando. **Ver a alternativa rodando vale mais que
decisão fechada em abstrato.**

### Roteiro de teste — exigências acumuladas

- Se confere contra o código antes de sair (lição 158). ⚠️ Reforçada em 04/09:
  **explicação de comportamento de tela também se confere no código** — a
  diferença entre a lista administrativa de pisos e o dropdown do mapa só se
  explicou lendo o `floorsWithItems()`, não deduzindo.
- Todo passo que troca de tela diz COMO chegar lá (lição 159).
- Toda pré-condição de dados é lida em tela antes de virar passo (lição 160).
- Passo que prevê "não muda" também é passo.
- Passo que prova a decisão de projeto vem nomeado como tal.

### Nome de arquivo entregue leva o bloco

`MapController-5e3b.php`, `dgoplus-5e3b.js.txt`. O `cp` renomeia (lição 140).
Docs versionam no nome do arquivo ENTREGUE (`contexto-dgoplus-v19.md`); no
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
tabelas do plugin — daí o `PurgeCleaner` (✅ **faxina provada em tela em
04/09**: a purga da `#36` levou o vínculo da F1.06 da `#34` junto e liberou a
E3). Escopo: **Localização (nativa) → Piso (intitulado do plugin)**.

### Pisos — cadastro × filtro (relido no código em 04/09)

- **Cadastro** em `Configurar → Listas suspensas → Pisos` — é lá que se
  confere a EXISTÊNCIA de um piso.
- **O filtro do mapa usa `floorsWithItems()`** — só pisos COM elemento no
  escopo corrente. Decisão do bloco 5b, comentada no código: *"piso vazio na
  lista é uma promessa falsa — escolher um deles esvaziava a tela sem dizer
  por quê"*. Consequências práticas:
  - Piso vazio NUNCA aparece no dropdown do mapa — ausência ali não prova
    inexistência.
  - Localização sem piso COM elemento não renderiza o filtro (visto em tela:
    `shopping palladium`).
  - Escopo cheio ("Todos os papéis" + "Todos os pisos") sem o piso na lista
    **prova que ele está vazio** — foi a prova usada antes da remoção do
    `PISO VAZIO TESTE`.
- `Floor::getForLocation()` (a lista bruta, com entidade somada via
  `getEntitiesRestrictCriteria`) é o que valida `?floor=` na entrada do
  controlador.

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

✅ Porta de grade **com vínculo e sem nome CONTA como documentada** —
`applyInput` linha 512 não apaga linha com vínculo; `statsForDgo` (947–967)
conta linhas e desconta sem-acoplador. Destrava o BADGE-C.

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

✅ **Decisão fechada (28/08): `completename` FICA.** Não reabrir sem fato novo.
Nome vazio imprime `sem nome`; ausente devolve `elemento #%d`.

**Consumidores (medidos no `fbf1952`, inalterados):** `MapController.php` 8,
`Link.php` 6, `Dashboard.php` 1.

⚠️ **O seletor de DESTINO continua fora do `ItemLabel`** (`CTO 01 (CTO) #35`
— revisto em tela em 04/09, na F1.06). É a dívida 7.

### O selo de nome duplicado — 5e-2d-1

Três peças no `MapController`: `duplicateNamesAt(int $locations_id)` (uma
consulta própria por carga, memorizada, só nomes com 2+); `normalizeName()`
(sem caixa e sem espaço — `DGO 001` casa com `DGO001`); `renderDuplicateMark()`
(ponto único da marca: ícone na aba, pílula `bg-orange-lt` no cabeçalho;
`DUP_COLOR = '#D68A3A'`).

**A consulta é PRÓPRIA de propósito** — calcular do `getDgosAtLocation()`
faria o selo sumir conforme o filtro de piso/papel. ✅ 100% exercitado
(tooltip dinâmico conferido em 03/09); em 04/09 os pares pós-purga (`#34/#37`
e `#35/#38`) continuavam acesos e os únicos, apagados.

Fora do selo, de propósito: trilha, Alimenta, anexos, painel da porta. Nome
vazio nunca acende.

### Abas — o único modo de exibição (5e-3a e 5e-3b)

**O seletor único morreu. `MAX_TABS` não existe mais.** Todos os elementos são
abas de verdade, agrupadas por papel, em **linha única que não quebra**:

- `<ul class='nav nav-tabs …' data-dgoplus-tabs='1'
  style='flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden'>` — estilo inline
  de propósito (lição 156); cada `<li>` com `flex:0 0 auto;white-space:nowrap`.
- A **barra de rolagem horizontal** aparece nativa, **só quando há overflow**
  — visto de novo em 04/09: 8 elementos na `Outlet Porto Belo`, sem barra.
- **5e-3b:** módulo IIFE no fim do `dgoplus.js` que, na carga, havendo
  overflow E aba ativa, **centraliza a ativa mexendo só no `scrollLeft` da
  própria UL** — nada de `scrollIntoView`. Posição por
  `getBoundingClientRect`. Sem overflow, sem ativa ou sem a UL: sai calado.

O formato de aba segue `shortForRow` + selo + contagem. A armadilha "o
`<option>` não aceita HTML" continua verdadeira no GLPI, mas sem consumidor no
plugin.

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
mapa é GLOBAL — é o desenho, **e busca PORTAS** (código/nome/observação): em
04/09, "piso" devolveu 0 porque nenhuma porta carrega esse texto — a busca não
enxerga pisos nem elementos.

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito
`plugin_dgoplus_port`, matriz de 4 níveis = 15; aba **DGO+**, linha **"Portas
de DGO"**. Tabela de exigências inalterada (READ vê; UPDATE documenta, comenta
e mexe em vínculo; DELETE esvazia/desmonta; CREATE cria; entidade trava toda
gravação; anexos = `document` R+U+C **e `datacenter` UPDATE**; papéis =
`config` UPDATE). Os dois greps de guarda continuam devolvendo 0/nada.
`parentIsReachable()` falha fechado.

⚠️ **A Fase 5 ainda não chegou à produção.** Deploy com plano de rollback —
bloco próprio, sem data. Começa por RELER a produção em tela (retrato é de
28/08).

### Anexos

Cartão usa formulário do core → exige `datacenter` UPDATE (lição 134). Mas
`CommonDBTM::add()` não checa direito (lição 148) — o que falta é tela:
candidato **5i**.

### Arquivos

**31 no repositório** (28 + 3 em `docs/`). `docs/` = `contexto-dgoplus.md`,
`roadmap-dgoplus.md`, `README.md` (nomes sem versão — verificado no tarball em
04/09).

**Impressões digitais do 1.3.22**, medidas no commit `12a7202` (03/09,
inalteradas):

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
em 04/09**. As mais recentes:

| # | Lição |
|---|---|
| 165 | **Bump de versão no `setup.php` faz PARTE do bloco de código** |
| 166 | **Antes de afirmar a consequência de uma alternativa, escrever o resultado por extenso** |

Reforço de 04/09 (sem número novo — coberto pelas regras existentes):
**explicação de comportamento de tela se confere no código antes de virar
afirmação.** A diferença lista administrativa × dropdown do mapa foi explicada
lendo o `floorsWithItems()`, depois de uma primeira inferência incompleta
("filtro aparece se há piso cadastrado" — o critério real é piso COM
elemento).

**Armadilhas permanentes do GLPI 11**: lista integral mantida.

---

## 5. Estado por bloco

Blocos 1 a 5e-3b: fechados e validados (até 1.3.22). A frente 5e está
ENCERRADA. O que muda no v19:

| Bloco | Entrega | Estado |
|---|---|---|
| 5e-2d-1 | Selo de nome duplicado | ✅ 100% exercitado; pares pós-purga revistos em 04/09 |
| 5e-2d-2 | Marca textual no seletor único | ☠️ Cancelado sem código (03/09) |
| 5e-3a | Abas sempre + rolagem horizontal | ✅ Fechado e validado (1.3.21, `28d5079`) |
| 5e-3b | Aba ativa centralizada na carga | ✅ Fechado e validado (1.3.22, `12a7202`) |
| **SKILL** | `SKILL.md` reescrito, entregue em 03/09 | ✅ **ENCERRADO POR DECISÃO (04/09):** a skill cadastrada NÃO será trocada; o arquivo na base do projeto é a fonte e este contexto prevalece sobre a skill antiga |

**Nenhum bloco está no estado "entregue e não exercitado".**

---

## 6. Dívidas conhecidas

1. ~~README~~ ✅ quitada.
2. **Sem catálogo de tradução** — decisão de produto pendente.
3. **Lista integral de lições (1–113)** — só pelo documento original.
4. ~~Tag/Release~~ ✅ quitada.
5. ~~Skill desatualizada~~ ✅ **QUITADA POR DECISÃO (04/09)** — a skill fica
   como está; fonte da verdade = arquivo na base do projeto + este contexto.
6. ~~"Desmontar" sem botão~~ ✅ quitada.
7. **Seletor de DESTINO fora do `ItemLabel`** (`CTO 01 (CTO) #35`). Última
   inconsistência de formato — revista em tela em 04/09.
8. ~~Seletor único sem marca de colisão~~ ✅ quitada por remoção (03/09).

---

## 7. Medições de campo

⚠️ **Duas bases; tudo aqui é retrato datado** (lição 160). **A homologação é
ambiente vivo dos técnicos** — reler SEMPRE.

### Produção (retrato de 28/08, NÃO relido desde então)

159 elementos (DIO 3, DGO 67, CTO 88, PTO 1; 2 na lixeira; 1 fora dos papéis);
4944 portas (2220 doc., 44,9%); 9 localizações — `Palladium Umuarama` 91,0% …
`Palladium Ctba` 2,5%. Documentadores: Claudio Morett, Kayan Lucas, Pedro s,
cristian.b. Pergunta das localizações-pai segue aberta e de baixa prioridade.

### Homologação — localizações conhecidas (04/09)

| Localização | O que se sabe | Fonte |
|---|---|---|
| `Outlet Porto Belo` | 8 elementos (tabela abaixo); piso `MALL - PORTO BELO` | Tela 03–04/09 |
| `shopping palladium` | 4 elementos; **sem piso com elemento** (filtro Piso nem renderiza) | Tela 03–04/09 |
| **`Plaza Campos Gerais`** | **NOVA aos docs (04/09).** Pisos `L1` e `L2` cadastrados. **Elementos NÃO lidos em tela — não afirmar** | Tela administrativa de Pisos |

**Pisos cadastrados (tela administrativa, 04/09, pós-remoção):** `L1` e `L2`
(`Plaza Campos Gerais`), `MALL - PORTO BELO` (`Outlet Porto Belo`) — 3 linhas.
**`PISO VAZIO TESTE` removido em 04/09** ("Operação realizada com sucesso").

### `Outlet Porto Belo` — 8 elementos (lido 03/09, revisto parcialmente 04/09)

| Elemento | id | Papel | Selo | Obs |
|---|---|---|---|---|
| `DIO 001` | 39 | DIO | — | 2 doc. Criado pelos técnicos |
| `DGO 01 - PORTO BELO` | 33 | DGO | — | 1 doc |
| `DGO 01` | 34 | DGO | ⚠ par com #37 | **5 doc** — ✅ **F1.06 conferida em 04/09: código `2153`, SEM vínculo, E3 `livre`** (faxina do `PurgeCleaner` provada) |
| `DGO 01` | 37 | DGO | ⚠ par com #34 | 1 doc. **FICA — treinamento** |
| `CTO 01` | 35 | CTO | ⚠ par com #38 | 1 doc |
| `CTO 01` | 38 | CTO | ⚠ par com #35 | 0 doc. Criado pelos técnicos |
| `TESTE 5e2d2 A` | 41 | CTO | — | 0 doc. **FICA — treinamento** |
| `TESTE 5e2d2 B` | 42 | CTO | — | 0 doc. **FICA — treinamento** |

- Grade da `#34` em 04/09: E1 ocupada (F1.01), E2–E4 livres; F1.02 `1202`,
  F1.03 `1214`, F1.05 `2153-01…`, F1.06 `2153`; 5 de 16 documentadas.
- `CTO 01 #36` purgada em 03/09; pendências 8 (v17) e 19 encerradas.
- 8 abas em linha única, sem barra (revisto em 04/09).
- Elemento `#40`: não visto em tela; não afirmar.

**Painel/pendentes:** retrato do v16, não relido. Perfil de teste: `Tecnicos
N1, ID 12`, usuário `teste.001` (não usados desde 28/08).

---

## 8. Decisões negativas registradas

Tabela integral do v18 mantida (MAX_TABS revogada e removida; sufixo na
`<option>` morto; `scrollIntoView`, múltiplas linhas, lista lateral e dropdown
com limite maior descartados; mais as 36 do v17). Nova, de 04/09:

| Ideia | Decisão | Motivo |
|---|---|---|
| **Atualizar a skill `glpi-plugin-teckcomp` cadastrada** | **DESCARTADA por decisão do usuário (04/09)** | A skill fica com o conteúdo antigo (`pscp`, `192.168.1.50`, zip); a fonte da verdade do ambiente é o arquivo na base do projeto + este contexto, que PREVALECEM sobre a skill. **Não voltar a propor a troca sem fato novo** |

### Decisões de produto vigentes

- **BADGE-C · variante C** — dois contadores (`0/16 grade` · `2/4 entradas`);
  toca `statsForDgo`, `renderBadges`, `ajax/port.php`.
- **5d · confirmar em dois tempos** — vínculo que pula degrau não grava na
  primeira. O trabalho real: destino e entrada sobreviverem ao redirect.
  Limitações aceitas: marcador forjável no POST; nada registrado depois.
- **Abas sempre, rolagem horizontal** — vigente desde 03/09.
- **Filtro de piso só com pisos ocupados** (bloco 5b) — confirmada de fato em
  04/09; a tela administrativa é quem responde pela existência.
- **Elementos de treinamento na homologação** — `#37`, `#41`, `#42`
  permanentes; purgá-los exige nova decisão do usuário.

---

## 9. Próximo passo imediato

1. **Commit dos docs v19** no repositório (só `docs/` → sem reinstalação).
   O v18 nunca foi commitado — o v19 entra direto sobre o v17.
2. **Commit — 5d**, confirmação em dois tempos. ⚠️ Mexe no `Link::propose()`.
3. **Commit — BADGE-C + contador de entradas separado.**
4. **5h-2** (remover `nosearch` da Localização no relatório), **5i** (anexo
   por formulário próprio), e o **bloco de deploy em produção** (com rollback;
   começa por reler a produção em tela).
5. **Frente shopmap** — bloqueada pela pendência 16 (repositório privado).
6. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.

---

## 10. O que correu mal do lado do assistente

**O padrão se mantém: zero erro em código gravado** (sessão de 04/09 nem teve
código). Ocorrências:

| # | Ocorrência | Custo | Vira lição? |
|---|---|---|---|
| 1 | Inferência incompleta sobre o filtro de Piso ("aparece se a localização tem piso cadastrado" — o critério real é piso COM elemento) | Zero — corrigida lendo o `floorsWithItems()` no código ANTES de qualquer ação | Não — coberta pelas regras existentes; registrada como reforço na seção 2 |
| 2 | Memória de que o `docs/` do repositório usava nomes versionados | Zero — desmentida pelo tarball antes de montar o commit | Não — regra 1 do projeto aplicada (verificar antes de afirmar) |

**O que o processo provou em 04/09:** a existência do piso foi decidida pela
tela administrativa (não pelo filtro do mapa) e o vazio foi provado pelo
escopo cheio do filtro — duas fontes, cada uma respondendo à sua pergunta; a
F1.06 fechou com um único print; e as duas inferências erradas do assistente
morreram na verificação, antes de custar qualquer coisa.
