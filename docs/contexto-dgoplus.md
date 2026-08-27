# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v9 — 27/08/2026 (sessão da noite, terceira parte).
> Substitui o v8 integralmente.
> Emitido ao fim do **Bloco 5f-2b** — criar elemento pelo mapa passa a exigir só
> `plugin_dgoplus_port` CREATE. Com ele o **5f-2 inteiro fecha**, e o plugin
> deixa de citar o direito do ativo em qualquer lugar: versão **1.3.6**, commit
> **`04ac8fd`**.
>
> Companheiro: `roadmap-dgoplus.md`. Os dois vivem em `docs/` no repositório.

---

## 0. A regra que governa tudo

**O GitHub é o repositório canônico do DGO+. A homologação é descartável.**

Todo estado do código tem que ser reconstruível a partir do `master` sozinho. A
regra nasceu de um fato: **houve um incidente doméstico em que a base de
homologação foi perdida com um repositório dentro dela, levando junto correções
que nunca chegaram ao Git.**

Convive com a regra de precisão do projeto porque são duas perguntas diferentes:

| Pergunta | Fonte da resposta |
|---|---|
| **O que está rodando agora?** (tela, erro, permissão) | O servidor — sempre |
| **O que o código É?** (registro durável, base de bloco novo) | **O GitHub — sempre** |

### A ordem de entrega

1. O assistente prepara os arquivos a partir do **tarball do commit atual** e
   valida (`php -l`, leitura do core quando preciso).
2. O usuário envia por **`scp`** e — **antes de tocar no plugin** — confere o
   `md5sum` dos arquivos em `/tmp`.
3. Copia por cima dos arquivos na pasta do plugin.
4. **`git diff` — a conferência do bloco.** Divergiu do esperado: não commita, avisa.
5. `git add -A` → `git commit` → `git push`. **O código vai ao GitHub antes do
   teste.** Reprovou? `git revert` ou `git checkout --`.
6. Console do GLPI + restart, e então o roteiro de teste.

**O passo 2 não é opcional** — foi ele que pegou três arquivos antigos sendo
enviados no lugar dos novos (lição 140).

O zip deixou de ser o veículo do bloco. Ele sobrevive só como **artefato de
Release**.

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 27/08 (fim da sessão) | commit **`04ac8fd`**, versão **1.3.6** |
| Versão em homologação | **1.3.6**, confirmada na tela de plug-ins |
| **Paridade** | ✅ **Estrutural**: a pasta do plugin é a árvore de trabalho do clone. `git status` limpo **é** a prova |
| Arquivos no repositório | **30** (27 do plugin + 3 em `docs/`) |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha** (`server sent: publickey`). Lição 139 |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local** e **sem PuTTY em uso** |
| Assistente | Não tem SSH nem token. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

> O endereço `192.168.1.50` do contexto v1 (e da skill `glpi-plugin-teckcomp`)
> está **morto**. A skill não foi atualizada — ao lê-la, substituir host e usuário,
> acrescentar a porta e **trocar `pscp` por `scp`**.

O shell do servidor está logado como **root** (`root@debian`). O console do GLPI
recusa root puro, então todo comando de console vai com `sudo -u www-data`.

### Git no servidor

O clone foi criado em 27/08. Configuração aplicada:

```bash
git config --global user.name "Claudio Morett"
git config --global user.email "claudio.morett@gmail.com"
git config --global --add safe.directory /var/www/html/glpi/plugins/dgoplus
git config --global credential.helper store
git config --global core.pager cat
```

O `safe.directory` é obrigatório: a pasta é do `www-data` e o git roda como root
("dubious ownership"). O `credential.helper store` gravou
`/root/.git-credentials` (0600, root); autenticação por **token fine-grained**
(Contents: Read and write) — senha de conta não funciona em HTTPS.

**Depois de todo `git pull`/`checkout`**, rodar
`chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus` — o git escreve
como root.

⚠️ **O `master` avança sem o código mudar.** Com `docs/` versionado, um commit só
de documentação move o HEAD. **O HEAD do `ls-remote` não é necessariamente o
último commit de código** — conferir o delta antes de assumir que a base do
bloco anterior ainda é o topo (lição 143).

### Comandos do dia a dia

**Enviar arquivo do PC para o servidor (cmd do Windows) — `scp`, não `pscp`:**

```cmd
scp -P 2078 "%USERPROFILE%\Downloads\<arquivo>" resolutto@177.87.230.179:/tmp/
```

`-P` maiúsculo é a porta. Aceita vários arquivos numa linha só. **Não converte
quebra de linha** — md5 idêntico dos dois lados, verificado.

**Trazer do servidor para o PC:**

```cmd
scp -P 2078 resolutto@177.87.230.179:/caminho/arquivo "%USERPROFILE%\Downloads\arquivo"
```

**Aplicar um bloco (`ssh -p 2078 resolutto@177.87.230.179`):**

```bash
md5sum /tmp/<arquivos>              # <<< OBRIGATÓRIO, antes de qualquer cp
cd /var/www/html/glpi/plugins/dgoplus
cp /tmp/<arquivo> <caminho/no/plugin>
chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus
git diff --stat && git diff         # <<< a conferência do bloco
git add -A && git commit -m "..." && git push
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

**Reverter, antes ou depois do commit:**

```bash
git checkout -- <arquivos>      # descarta a cópia, ainda não commitada
git revert HEAD && git push     # desfaz o commit já empurrado
```

**⚠️ O `pscp` NÃO funciona neste ambiente** (suíte PuTTY, só lê `.ppk`). É `scp`.

### Os dois logs que interessam

**Erro de PHP e de SQL.** Não há `sql-errors.log` (lição 122). Erro de SQL vai
para o `php-errors.log` como `glpi.CRITICAL`, com query e backtrace:

```bash
sudo tail -n 100 /var/www/html/glpi/files/_log/php-errors.log
```

**Status HTTP — e este substitui o F12.** As requisições do GLPI caem no
**`other_vhosts_access.log`**, não no `access.log` (lição 142):

```bash
grep -h "port.php" /var/log/apache2/access.log /var/log/apache2/other_vhosts_access.log | tail -n 10
```

O `Referer` traz a URL do mapa com `edit=<tubo>-<fibra>`, então dá para saber
**qual célula** gerou cada requisição sem perguntar ao usuário.

### Topologia web

Quem serve é o **Apache**, nas portas **80 e 443** internamente; o acesso externo
entra por **`177.87.230.179:2077`**, redirecionado para o vhost em `:80`. A raiz
web efetiva vem de **`conf-enabled/glpi.conf`** com
`DocumentRoot /var/www/html/glpi/public`, e ela **vence** o `000-default.conf`.

Consequência que encerra um risco: **nada dentro de `plugins/` é alcançável como
arquivo pelo navegador** — nem o `.git`, nem `files/`, nem `config/`. Testado:
`curl` em `/glpi/plugins/dgoplus/public/dgoplus.js` devolve **404 com
`Set-Cookie: glpi_...`**, ou seja, quem respondeu foi o front controller do GLPI.

### Release — o artefato de instalação

**`v1.3.2` publicada em 27/08** com `dgoplus-1.3.2.zip` anexado (168 KB, sha256
`fd42f3a5eb0adf33a8707a59bd2b32c2495070db8734ce477e1d7eb381518752`).

O zip **nasce do commit**, nunca de pasta montada à mão:

```bash
cd /var/www/html/glpi/plugins/dgoplus
git tag -a v1.3.6 -m "..." && git push origin v1.3.6
git archive --format=zip --prefix=dgoplus/ -o /tmp/dgoplus-1.3.6.zip v1.3.6
```

⚠️ **Da `v1.3.3` à `v1.3.6` não há tag nem Release.** Bloco REL-2.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6, `Tasks list`
2.1.12.

### Quando reinstalar — decidir, não pedir por precaução

| Mudou | O que fazer |
|---|---|
| `src/`, `front/`, `ajax/` (PHP) | `cache:clear` + `systemctl restart apache2` (OPcache) |
| `public/` (JS/SVG) | **Ctrl+F5** no navegador |
| `src/Install.php` (schema, direitos) | `plugin:install --force dgoplus` **e depois** `plugin:activate dgoplus` |
| **Número de versão no `setup.php`** | Idem: `--force` + `activate`, mesmo com `Install.php` idêntico (lição 116) |
| Só `docs/` | **Nada.** Commit e pronto |

---

## 2. Fluxo de trabalho vigente

Método **entrega-em-blocos**: um bloco = uma mudança testável de uma sentada. Se
o teste passa de ~8 passos ou toca duas áreas independentes, divide-se — foi o
que aconteceu com o 5f-1 (`5f-1a`/`5f-1b`) e com o 5f-2 (`5f-2a`/`5f-2b`).

Toda entrega tem quatro seções fixas: **(1)** o que muda, com a decisão de
reinstalar em negrito na primeira linha; **(2)** o comando `scp` literal **com os
md5 esperados**; **(3)** os comandos de aplicar, com **`git diff` como
conferência**; **(4)** roteiro de teste numerado com resultado esperado, onde ler
o log e como reverter.

### Nome de arquivo entregue leva o bloco

**Todo arquivo de bloco sai com o bloco no nome** — `DgoIdentity-5f2a.php`,
`setup-5f2a.php` — e o `cp` no servidor é que renomeia. Sem isso o download
colide com o do bloco anterior na pasta Downloads, o navegador salva como
`Port_1.php`, e o `scp` manda o **antigo** com sucesso aparente (lição 140).

### O repositório é público — usar isso por padrão

O assistente **lê o código do `master` durante a sessão**, sem SSH e sem token:

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
https://raw.githubusercontent.com/teckcomp/glpi-plugin-dgoplus/master/<arquivo>
https://github.com/teckcomp/glpi-plugin-dgoplus/releases/download/<tag>/<arquivo>
```

(`api.github.com` bate no limite anônimo — 403.)

⚠️ **Preferir o `codeload` com o SHA ao `raw`**: o `raw` tem cache de CDN e pode
devolver o estado anterior logo após um commit (lição 132). O `codeload` aceita
SHA abreviado, **mas a pasta extraída sai com o nome abreviado** — não presumir o
SHA completo no `cd`.

**Padrão de trabalho do assistente ao preparar um bloco:** baixar o tarball do
commit atual, editar a cópia, `php -l`, e **provar por md5 que só os arquivos do
escopo mudaram** antes de entregar. Depois do push, **baixar o commit publicado e
conferir os md5 de novo** — feito no 5f-1b e no 5f-2a, e é o que fecha a
proveniência.

**Número previsto (`git diff --stat`, `grep -c`) sai de comando, não de olho**
(lição 141): `git init` sobre o tarball do commit, copiar os arquivos do bloco
por cima, rodar o `stat` de verdade. No 5f-2a o previsto `+28 −10` foi o que
apareceu no servidor.

**Documento é entrega, e entrega tem quatro seções** (lição 145): o contexto e o
roadmap também saem com o comando `scp` literal, nunca só com os comandos do
servidor.

**Número de linha citado em documento é ponteiro, não fato** (lição 144).
Reconferir por `grep -n` no commit do dia, sempre — nunca copiar da versão
anterior deste documento.

### O core do GLPI também é legível

`github.com/glpi-project/glpi` na **tag `11.0.6`**, pelos mesmos raw URLs:

- Classes com namespace `Glpi\` ficam em **`src/Glpi/...`**, não em `src/...`.
- O schema completo está em `install/mysql/glpi-empty.sql` — forma mais rápida de
  confirmar se uma coluna existe, sem acesso ao banco.

### O sandbox do assistente TEM PHP

`php -l` é possível. **Rodar `apt-get update` e `apt-get install -y php-cli` como
dois comandos separados** — o `update` sai com código ≠ 0 e encadear com `&&`
faz o `install` nunca rodar (lição 126). Validado com PHP 8.3.6. **Todo arquivo
PHP entregue passa por `php -l` antes de sair.** Continua valendo: `php -l`
**não** pega incompatibilidade de assinatura com a classe-pai.

Não há `sudo` no sandbox (já é root) e a homologação é inalcançável de lá.

### Práticas abolidas

- Reinstalar o plugin "por precaução" a cada bloco.
- Mandar colar `sed`, heredoc ou edição manual de arquivo grande no terminal.
- Zip como veículo de bloco (só Release).
- **`pscp`** — não autentica neste servidor. É `scp`.
- **Entregar arquivo com o nome final** (`Port.php`) em vez de `Port-<bloco>.php`.
- **Upload pela web do GitHub** — substituído pelo `git push`.
- Julgar tela sem antes confirmar a versão instalada (lição 114).
- Remontar arquivo a partir do `master` + a descrição do bloco (lição 129).
- **Ritual de `md5sum` dos 27 arquivos** — `git status` faz isso melhor.
- **Pedir F12 ao usuário para saber status HTTP** — o `other_vhosts_access.log` diz.
- **Reaproveitar número de linha de documento antigo** (lição 144).

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
**Quatro degraus bastam.** O splitter fica deliberadamente fora: é componente da
caixa, não elo da cadeia. As entradas E1–E4 registram mais de uma fibra
alimentando o mesmo elemento, e a proporção (1/8, 1/12, 1/16) vai no campo OBS.

Mapeamento em produção: **um Tipo por papel** — `DIO+`, `DGO+`, `CTO+`, `PTO+`.
Gravado em `glpi_configs`, contexto `plugin:dgoplus`.

### Portas

Uma tabela, dois tipos de linha, separados por `kind`:

- `KIND_GRID` (`grade`) — a matriz tubo × fibra, cores ABNT/EIA.
- `KIND_ENTRY` (`entrada`) — E1 a E4 (`MAX_ENTRIES = 4`), `tube_num = 0`.

`kind` fica **fora** da chave única (lição 112) — a chave é
`(itemtype, items_id, tube_num, fiber_num)`. `Port::applyInput()` é o **ponto
único de gravação**.

### Vínculos

`glpi_plugin_dgoplus_links`: **uma linha, dois lados**. Regras fechadas:

- **Sem `is_deleted`.** Recusa apaga a linha.
- **Pendente já ocupa a porta**, nas duas pontas.
- **Hierarquia permissiva**: pode pular nível, nunca subir nem empatar.
  `Link::hierarchyAllows()` sabe que desceu, **não sabe quanto** — lacuna do 5d.
- **Só vínculo confirmado sobe na trilha** (4e). `Link::upstreamLevels()` é
  chamado com o **elemento**, não com a entrada — daí o 5c. **Consumidor único.**
- `Link::propose()` é o **ponto único de criação**.
- **Recusar e confirmar pedem o mesmo direito (UPDATE)**, de propósito: exigir
  DELETE para recusar deixaria um perfil capaz de aceitar mas incapaz de dizer
  não. Desmontar (vínculo já confirmado) pede DELETE.

### Comentário do elemento

`DgoIdentity::applyComment()` é o **ponto único**, usado pelo POST clássico
(`MapController::actionSaveDgoComment`) e pelo `ajax/dgocomment.php` — os dois não
podem divergir (lição 47). Grava o campo `comment` **nativo** do
`PassiveDCEquipment` por `CommonDBTM::update()`, então aparece na ficha do ativo
e no Histórico dele.

Quem decide o direito é `DgoIdentity::canWriteComment()`, **um método com dois
chamadores** (tela e ponto único). Desde o 5f-2a ele pergunta pelo direito do
plugin.

### Busca e relatório — tabela polimórfica

Fechado no Bloco 5h, com o core lido (`SQLProvider::getLeftJoinCriteria`):

- Os `jointype` que **existem** no 11.0.6: `child`, `item_item`,
  `item_item_revert`, `mainitemtype_mainitem`, `itemtype_item`,
  `itemtype_item_revert`, `itemtypeonly`, `custom_condition_only` e o `default`.
  **Qualquer outro valor cai no `default` em silêncio.**
- Para a porta, o jointype certo é **`itemtype_item_revert`**.
- **`specific_itemtype` é obrigatório**: sem ele a coluna volta vazia em todas as
  linhas — falha silenciosa, não erro.

`glpi_passivedcequipments` tem `locations_id` **e `is_recursive`** (⚠️ conferido
no `glpi-empty.sql` do core, não por `DESCRIBE` — pré-requisito do 5f-3).

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito próprio
`plugin_dgoplus_port`, matriz de 4 níveis = **15**. Na tela do perfil a aba
chama-se **DGO+** e a linha, **"Portas de DGO"**.

**Como o direito se comporta em 1.3.6** — todos os números **verificados por
`grep -n` no commit `04ac8fd`**, nesta sessão:

| Ação | Exige hoje | Onde |
|---|---|---|
| Ver mapa, painel, relatório | `plugin_dgoplus_port` READ | `front/map.php` |
| **Documentar porta (existente ou não)** | **`plugin_dgoplus_port` UPDATE** ✅ 5f-1a | `Port.php:506` e `:514` |
| Esvaziar porta (volta a livre) | `plugin_dgoplus_port` DELETE | `Port.php:444` |
| **Propor vínculo** | **`plugin_dgoplus_port` UPDATE** ✅ 5f-1b | `Link.php:439`; tela em `MapController:3189` |
| Confirmar / recusar vínculo | `plugin_dgoplus_port` UPDATE | `Link.php:484`, `:526` |
| Esvaziar fileira / coluna | `plugin_dgoplus_port` DELETE | `MapController:571`, `:780` |
| Desmontar vínculo | `plugin_dgoplus_port` DELETE | `Link.php:565` |
| **Comentário do elemento** | **`plugin_dgoplus_port` UPDATE** ✅ **5f-2a** | `DgoIdentity.php:227` (`canWriteComment`) |
| Fileira / coluna / piso | `plugin_dgoplus_port` CREATE | `MapController:534`, `:740`, `Floor::$rightname` |
| **Criar elemento pelo mapa** | **`plugin_dgoplus_port` CREATE** ✅ **5f-2b** | `MapController:417` (POST) e `:1529` (tela) |
| Qualquer gravação de porta | **também** `datacenter` READ ⚠️ *muda no 5f-3* | 7 pontos, abaixo |
| **Anexos** | `document` READ+UPDATE+CREATE **e `datacenter` UPDATE** | ✅ confirmado em tela |
| Configurar papéis | `config` UPDATE | `MapController:1551` |

**Os sete pontos acoplados a `datacenter` READ** (todos `can($items_id, READ)`),
**verificados por `grep -n` em `04ac8fd`** — são o **último** acoplamento ao
direito do ativo que resta no plugin:

| Arquivo | Linha (em `04ac8fd`) | Contexto |
|---|---|---|
| `src/Port.php` | 383 | `applyInput` |
| `src/Port.php` | 646 | `ensureEntry` |
| `src/Port.php` | 765 | `ensureGrid` |
| `ajax/port.php` | 48 | auto-save |
| `src/MapController.php` | **954** *(era 949 antes do 5f-2b)* | `actionSaveEntryObs` |
| `src/Link.php` | 697 | `loadVisibleItem` |
| `src/DgoIdentity.php` | 338 | `applyComment` — a trava de entidade (3m) |

✅ **`grep -c 'PassiveDCEquipment::$rightname' src/MapController.php` devolve 0**
desde o 5f-2b: nenhuma linha do plugin cita mais o direito do ativo pelo nome.

`front/map.php` exige **apenas** `Port::$rightname READ`.

**A semântica que a Fase 5 instaura:**

| Direito | Significa |
|---|---|
| LER | Ver mapa, painel, relatórios, comentários, descrição das portas |
| ATUALIZAR | **Documentar portas** ✅ (5f-1a), **propor e confirmar vínculos** ✅ (5f-1b), **comentar o elemento** ✅ (5f-2a) |
| CRIAR | **Criar elementos pelo mapa** ✅ (5f-2b), fileiras, colunas, pisos — **estrutura** |
| DELETE | Esvaziar portas, recusar por desmontagem, excluir vínculos |

**Fora do DGO+, por decisão:** criar Localização (dropdown do GLPI inteiro),
anexos (direito `document` **+ `datacenter` UPDATE**) e excluir o elemento.

**O que a Fase 5 assume conscientemente:** depois do 5f, quem tiver
`plugin_dgoplus_port` UPDATE grava porta, vínculo e comentário em elementos **da
sua entidade** sem ter direito nesses ativos. Escalada deliberada e delimitada,
decidida pelo administrador ao conceder o direito. **Efeito visível no core:** o
comentário passa pelo `update()` do ativo, então **o Histórico do
`PassiveDCEquipment` registra a alteração em nome do técnico** — desejável para
auditoria, e é a primeira vez que o direito do plugin aparece numa tela do core.

### Arquivos

**30 no repositório** — 27 do plugin + 3 em `docs/`.

```
dgoplus/
├── setup.php              hooks, menu, botão da ficha, JS — 269 linhas
├── hook.php               instalação / desinstalação
├── README.md              desatualizado — dívida 1
├── logo.png
├── docs/                  README.md, contexto-dgoplus.md, roadmap-dgoplus.md
├── ajax/                  port.php (auto-save, 4a), dgocomment.php (comentário, 3t)
├── public/                dgoplus.js, dgoplus-identity.js, qrcode.js, dgoplus-mark.svg
├── front/                 map.php, port.php, pending.php, config.form.php
└── src/                   13 arquivos
    ├── Install.php        schema, direitos, migrações
    ├── Setting.php        papéis e Tipos de cada papel
    ├── Port.php           porta; applyInput é o ponto único — 1048 linhas
    ├── Link.php           vínculo; propose é o ponto único — 1204 linhas
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (rightname = plugin_dgoplus_port)
    ├── MapController.php  a tela do mapa — 3405 linhas
    ├── Dashboard.php      o painel
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade, QR e comentário (3t) — 370 linhas
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.6** (commit `04ac8fd`, baixado do GitHub e conferido
pelo assistente nesta sessão):

```
4c1e837e446efe08a3a57ebd9ee41416  setup.php             (269 linhas)
b7487ba16bd9e4ecf6b7adfb6fe0c7b9  src/DgoIdentity.php   (370 linhas)
570d703783670f76e858fddb5c96ac8d  src/MapController.php (3405 linhas)
d84d8788ebce8939737ca1cee52c798b  src/Port.php          (1048 linhas)
4f81bc233ffd5df1ce5a6e49e0fa0487  src/Link.php          (1204 linhas)
```

*(Port e Link estão idênticos desde o `a690010`.)*

---

## 4. Lições aprendidas

⚠️ **Lacuna, ainda aberta.** O código cita lições numeradas até a **113**; a lista
integral vive no documento original, não recuperado. Abaixo: as recuperáveis
pelas citações (número confiável, enunciado deduzido) mais as **novas, que são
fato**.

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
| 115 | `pscp` usa `-P` maiúsculo para a porta. *Vale igual para o `scp` — ver lição 139* |
| 116 | **Bump de versão no `setup.php` conta como mudança de instalação.** `--force` + `activate` mesmo com `Install.php` idêntico |
| 117 | **`$parent->can($items_id, READ)` acopla o plugin ao direito do itemtype pai — e ao menu do core.** `Session::haveAccessToEntity()` preserva a proteção sem o acoplamento |
| 118 | ~~`$can_write = haveRight($rightname, $found ? UPDATE : CREATE)`~~ ✅ *Corrigida pelo 5f-1a* |
| 119 | **Mensagem de permissão que não nomeia o direito faltante custa horas** |
| 120 | ✅ Anexar documento a um ativo exige **`datacenter` UPDATE**. Ver lição 134 |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054** |
| 122 | **Não existe `sql-errors.log` no GLPI 11.0.6.** Erro de SQL vai para `php-errors.log` como `glpi.CRITICAL` |
| 123 | **O direito Documentos fica na aba Gerência do perfil, não em Ativos** |
| 124 | A homologação também pode estar À FRENTE do `master`. *Encerrada pelo clone no servidor* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`.** E, no `itemtype_item_revert`, esquecer `specific_itemtype` devolve a coluna vazia |
| 126 | **O sandbox do assistente tem PHP.** `apt-get update` sai com código ≠ 0 — **dois comandos separados** |
| 127 | **O CSV exportado do GLPI abre torto no Excel por duplo clique.** Solução: *Dados → Obter dados → De texto/CSV*, UTF-8, coluna como Texto |
| 128 | **`pscp` pode falhar com `Remote side unexpectedly closed network connection`.** *Distinta da 139: ali a conexão morre, aqui a autenticação nem começa* |
| 129 | **Arquivo remontado a partir do `master` + a descrição do bloco NÃO é verificação** |
| 130 | **O GitHub é canônico.** Qualquer ordem que deixe código existindo só no servidor já custou perda de trabalho uma vez |
| 131 | Upload pela web do GitHub cria arquivo novo em silêncio quando o nome não bate. *Aposentada pelo `git push`* |
| 132 | **`raw.githubusercontent.com` tem cache de CDN.** Para commit recém-feito: `ls-remote` para o HEAD, tarball do `codeload` para o conteúdo |
| 133 | ✅ **CONFIRMADA: falha de permissão no auto-save chega ao usuário como erro de rede.** O `ajax/port.php` responde **403** e o `.catch()` do `dgoplus.js` mostra **"Falha ao salvar. Use o botão Salvar."** Pior: o auto-save **reenvia** — sete 403 seguidos para uma ação só. Escopo do 5g |
| 134 | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core (`Document_Item`) e pergunta se o usuário pode atualizar o ATIVO |
| 135 | **O direito "Data centers" também fica na aba Gerência do perfil**, junto com Documentos, Contratos, Clusters, Domínios e Cabos |
| 136 | **A raiz web efetiva vem de `conf-enabled/glpi.conf` e vence o `000-default.conf`.** **404 com `Set-Cookie: glpi_` é o GLPI respondendo** |
| 137 | **Com o clone Git na pasta do plugin, `git diff` É a conferência do bloco** e `git checkout --` é o rollback instantâneo |
| 138 | **O escopo real de um bloco de permissão está no PONTO ÚNICO, não na linha da tela.** Antes de escrever, procurar o `checkRight` no ponto único |
| 139 | **O envio é `scp`, não `pscp` — e o servidor recusa senha.** O `pscp` só lê `.ppk` de sessão salva e morre com `No supported authentication methods available (server sent: publickey)`. O que o usuário digita é a frase-secreta da chave. O `scp` **não** converte quebra de linha |
| 140 | **Arquivo de bloco com o nome final colide na pasta Downloads e o `scp` manda o ANTIGO em silêncio.** Duas regras nasceram daqui: o nome entregue leva o bloco (`Port-5f1b.php`), e o `md5sum` de `/tmp` ANTES do `cp` deixa de ser opcional |
| 141 | **Número previsto de `git diff --stat` e `grep -c` sai de comando, não de contagem a olho.** No sandbox: `git init` sobre o tarball do commit, copiar os arquivos por cima, rodar o `stat` de verdade |
| 142 | **As requisições do GLPI caem no `other_vhosts_access.log`, não no `access.log`.** E o `Referer` traz `edit=<tubo>-<fibra>`, então o log diz **qual célula** gerou cada requisição. Substitui pedir F12 |
| **143** | **Com `docs/` versionado, o HEAD do `master` avança sem o código mudar.** O roadmap v7 apontava `a690010` como topo; o HEAD real era `4144a5c`, e o delta eram só os dois documentos. Nenhum erro aconteceu porque o assistente **conferiu o delta** antes de usar a base. Regra: `ls-remote` para o HEAD, `diff -rq` entre o HEAD e o último commit de código conhecido, e **só então** decidir a base do bloco |
| **144** | **Número de linha em documento é ponteiro, não fato — e envelhece a cada bloco.** O v7 citava, para o 5f-3, `Port.php:751`, `Link.php:689` e `DgoIdentity.php:323`; em `1114077` são **765**, **697** e **338**. O `Link::propose` estava como 434 e é **439**. O deslocamento vem de blocos anteriores que acrescentaram linhas ACIMA do ponto. **Todo bloco reconfere os seus alvos por `grep -n` no commit do dia, antes de escrever a primeira linha** |

| **145** | **Documento também é entrega — e entrega sem a seção de envio não chega.** Ao fechar o 5f-2a o assistente deu os comandos de `cp`/`git` do contexto e do roadmap v8 **sem o `scp`**, e o servidor respondeu `cp: não foi possível obter estado de '/tmp/contexto-dgoplus-v8.md'`. Custo baixo (nada quebrou, `working tree clean`), mas a causa é a que interessa: **o formato de quatro seções vale para QUALQUER arquivo entregue**, inclusive `.md`. Se sai do sandbox para o servidor, sai com o `scp` na frente |

| **146** | **Elemento novo nasce com 4 × 16 = 64 posições** (`Panel::DEFAULT_TUBES` = 4, `DEFAULT_FIBERS` = 16, conferido em `src/Panel.php:24` e `:27`), **e encolher a grade exige DELETE** (`MapController:571` e `:780`). Consequência prática vista no teste do 5f-2b: um perfil com **CRIAR e sem DELETE** cria uma CTO de 64 posições e **não consegue ajustá-la** — o painel passa a dizer "0 de 64" para uma caixa que na prática tem 8 ou 16, e a métrica de ocupação do parque piora sozinha. Não é defeito do 5f-2b; é uma decisão de produto que o bloco tornou alcançável pelo técnico |

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
| 3m | Trava de entidade na gravação de porta | Fechado — revisto pelo 5f-3 |
| 3q | `PurgeCleaner` | Fechado |
| 3r | Posição exibida contínua | Fechado |
| 3s | Carimbo de documentação | Fechado |
| 3t | Identidade, QR e comentário | Fechado — direito revisto pelo 5f-2a |
| 4a-1/2/3 | Auto-save; papel no painel; abas e filtro por papel | Fechado |
| 4b-1/2 | `kind` na porta; tabela de vínculos + entradas E1–E4 | Fechado |
| 4c/4c-2 | Propor, confirmar, recusar, desmontar | Fechado |
| 4d | Página e cartão de pendentes | Fechado |
| 4e | Trilha de alimentação | Fechado — ajuste no 5c |
| 4g | Bump 1.3.0; `isDgo()` → `isMapped()` | Fechado |
| 4h | — | Fechado (1.3.0, `27d54d2`) |
| 5-sync | Homologação de 1.3.0 para 1.3.1 | Fechado (22/08) |
| 5a | Escopo Localização → Piso no seletor de destino | Fechado e validado (23/08), 1.3.1 |
| 5h | JOIN da coluna Localização no relatório | Fechado (27/08), 1.3.2, `bd28ffd` |
| DOC | `docs/` no repositório | Fechado (27/08), `1ded500` |
| GIT-1 | Clone Git na pasta do plugin | Fechado (27/08) |
| GIT-2 | Primeiro `push` do servidor, com token | Fechado (27/08) |
| REL | Tag `v1.3.2` + Release com zip | Fechado e conferido por md5 (27/08) |
| 5f-1a | Documentar porta exige UPDATE, não CREATE | Fechado (27/08), 1.3.3, `6efab96` |
| 5f-1b | Propor vínculo exige UPDATE, não CREATE | Fechado e validado em tela + log (27/08), 1.3.4, `a690010` |
| 5f-2a | Comentário do elemento exige o direito do plugin | Fechado e validado em tela nas duas pontas (27/08), 1.3.5, `1114077` |
| **5f-2b** | **Criar elemento pelo mapa exige só o direito do plugin** | **Fechado e validado em tela (27/08), 1.3.6, `04ac8fd`** |

### O que o 5f-2a fez, em detalhe

Dois arquivos, **`+28 −10`** (número previsto no sandbox e confirmado no
servidor):

| Onde | O quê |
|---|---|
| `src/DgoIdentity.php`, `canWriteComment` | `$dgo->can($items_id, UPDATE)` → **`Session::haveRight(Port::$rightname, UPDATE)`**. Um método, **dois chamadores** — tela e ponto único mudaram juntos |
| `src/DgoIdentity.php` (imports) | `use Session;` |
| `src/DgoIdentity.php` (tarja da tela) | *"Você tem permissão apenas de leitura neste ativo"* → **"Somente leitura. Comentar exige a permissão «Atualizar» em «Portas de DGO» (Administração → Perfis → aba DGO+)."** (lição 119) |
| `src/DgoIdentity.php` (`applyComment`) | a mesma frase na recusa do POST/AJAX |
| `src/DgoIdentity.php` (2 docblocks) | a regra antiga estava documentada; ficaria mentindo |
| `setup.php` | 1.3.4 → **1.3.5** |

**Não mudou:** a trava de entidade (`can($items_id, READ)`, hoje na linha 338) —
é do 3m e é o **5f-3** que mexe nela.

**Proveniência fechada:** o assistente baixou o commit `1114077` e provou por md5
que os dois arquivos publicados são idênticos aos preparados no sandbox, e que
**nenhum outro dos 30 arquivos mudou**.

**Validado em tela (27/08)**, perfil **Tecnicos N1 (ID 12)**, usuário
`teste.001`, LER + ATUALIZAR, **CRIAR e DELETE desmarcados**, `datacenter` só
READ:

1. Plug-in em **1.3.5** ativo.
2. Cartão Comentários com o textarea **editável** e o botão Salvar — **a tarja
   cinza sumiu**. Era esse o sinal.
3. Auto-save no blur: **"Salvo ✓"**, sem "Falha ao salvar".
4. **Super-Admin, em outra sessão, lê o mesmo texto** ("teste 5f-2a"): foi ao
   banco de verdade.
5. Tirando ATUALIZAR do perfil: textarea `readonly` e a tarja nova, com o
   caminho do direito. As duas pontas provadas.

⚠️ **Não conferido:** o Histórico da ficha do ativo, com `teste.001` como autor.
Pendência 7 da Parte C — não bloqueia o bloco.

### O que o 5f-2b fez, em detalhe

Dois arquivos, **`+11 −4`** (previsto no sandbox, confirmado no servidor):

| Onde | O quê |
|---|---|
| `MapController::actionCreateDgo` | **removida** a linha `Session::checkRight(PassiveDCEquipment::$rightname, CREATE)`. Ficou só a do `Port::$rightname` (hoje linha 417) |
| `MapController::displayDgoTabs` | `$can_create` deixou de somar o direito do ativo: passou a espelhar exatamente a trava do POST (hoje linha 1529) |
| `setup.php` | 1.3.5 → **1.3.6** |

**Não mudou:** a entidade. O elemento continua nascendo em
`Session::getActiveEntity()`, e a validação de nome, papel e Tipo (3l / 4a-3)
ficou intacta.

**Proveniência fechada** por md5 contra o commit `04ac8fd`; nenhum outro dos 30
arquivos mudou.

**Validado em tela (27/08)**, `Tecnicos N1 (ID 12)` com **LER + ATUALIZAR +
CRIAR**, DELETE desmarcado, `datacenter` só READ:

1. Plug-in em **1.3.6** ativo.
2. Perfil com CRIAR marcado (tela do perfil conferida).
3. No mapa do técnico apareceu o formulário **Papel + Nome do novo elemento +
   "Novo elemento"** — e junto os botões **"Nova fileira"** e **"Nova coluna"**,
   que sempre foram CREATE do plugin.
4. Criado **`CTO TESTE 5f2b`**, papel CTO: nasceu, abriu direto, a aba **CTO
   passou de 1 para 2** e o contador do escopo, de `DGO 2`.

*(Os passos 5 e 6 do roteiro — conferir na lista de Dispositivos passivos e
desmarcar CRIAR de novo — foram dados como ok pelo usuário, **sem tela**.)*

⚠️ **Resíduos deste teste, na base de homologação:** o ativo `CTO TESTE 5f2b`
existe (grade 4 × 16, piso não atribuído) e o perfil de teste ficou com **CRIAR
ligado**. Ver Parte C, pendências 8 e 9 do roadmap.

---

## 6. Dívidas conhecidas

1. **README desatualizado** — manda baixar `dgoplus-v1.0.0.zip` (linhas 38, 45,
   56), fala em três tabelas quando são quatro (111, 142), e a linha 119 avisa
   sobre portas órfãs, defeito que o 3q resolveu.
2. **Sem catálogo de tradução**: interface pt-BR fixa.
3. ⚠️ **Lista integral de lições (1–113)** não incorporada.
4. **Sem tag nem Release para o 1.3.3, o 1.3.4 e o 1.3.5** — a `v1.3.2` está
   publicada.
5. **A skill `glpi-plugin-teckcomp` está desatualizada**: host, usuário, porta e
   o comando de envio (`pscp` → `scp`).
6. **Texto que fala de ação indisponível.** No painel da porta com vínculo
   confirmado, a dica diz *"Desmontar remove o vínculo dos dois lados"* mesmo
   quando o perfil não tem DELETE e o botão não é renderizado. Candidato ao 5g.

---

## 7. Medições de campo

**23/08:**
- **31 elementos**, todos com `locations_id`; **7 localizações**.
- **1665 portas**, 28 documentadas — **1,7%** de ocupação.
- **14 elementos** sem nenhuma porta registrada.

**27/08 (manhã):**
- Relatório listando **49 linhas** (⚠️ sem filtro registrado).
- Localizações vistas: `Shopping Ventura > DGO Cristian`, `shopping estação`,
  `shopping palladium`, `Shopping itajai/Bigode`, `Plaza Campos Gerais`.
- Documentadores ativos: Claudio Morett, Kayan Lucas, Pedro s, cristian.b.

**27/08 (tarde e noite):**
- **DGO 01**, em `Outlet Porto Belo`, piso `MALL - PORTO BELO`: grade de **16
  posições**; **comentário do ativo = "teste 5f-2a"**; F1.03 com nome `1214`.
- **CTO 01**, mesmo piso, grade de 16 posições; entradas **E1 (← DGO 01 · F1.01)**
  e **E2 (← DGO 01 · F1.04)**, ambas confirmadas.
- Cartão "Anexos do elemento" da DGO 01: **0**.
- **`CTO TESTE 5f2b`** criado no teste do 5f-2b, mesma localização, **piso não
  atribuído**, grade **4 × 16 = 64 posições**, 0 documentadas. É lixo de teste —
  ver lição 146.
- Perfil de teste: **Tecnicos N1, ID 12**; usuário `teste.001`.
- URL externa do GLPI: `http://177.87.230.179:2077/`.

---

## 8. Decisões negativas registradas

Avaliadas e **recusadas**, com motivo. Existem para a próxima sessão não as
ressuscitar como novidade.

| Ideia | Decisão | Motivo |
|---|---|---|
| Atribuição de piso em lote | **Descartada** | O piso não vai ser preenchido em massa |
| Esconder o seletor de piso | **Descartada** | O seletor fica; a meia-medida (5b) resolve |
| Alerta de salto de degrau só no JS | **Descartada** | Exceção que passa batido vira topologia errada |
| Proporção do splitter como campo estruturado | **Descartada** | Já cabe no OBS das entradas |
| Splitter como papel na hierarquia | **Descartada** | É componente da caixa, não elo da cadeia |
| Importação CSV de portas | **Adiada** | Não há fonte de dados |
| Criar Localização pelo direito do DGO+ | **Descartada** | `Location` é dropdown do GLPI inteiro |
| Excluir elemento pelo direito do DGO+ | **Descartada** | Purgar ativo é do admin |
| Corrigir a acentuação do CSV no plugin | **Descartada** | O relatório é tela do core (lição 127) |
| Anexo pelo técnico | **Descartada (27/08)** | Exige `datacenter` UPDATE (lição 134), que devolveria o menu de Dispositivos passivos. **Supervisor cobre** |
| Documentos versionados dentro do repositório | **Descartada (27/08)** | `docs/contexto-dgoplus.md` sem sufixo: o histórico é o Git |
| Exigir DELETE para recusar vínculo | **Descartada (já no 4c)** | Recusar e confirmar são as duas metades da mesma resposta |
| `pscp` como veículo de envio | **Descartada (27/08)** | Não autentica: o servidor só aceita `publickey` (lição 139) |
| **Tirar `$dgo` da assinatura de `canWriteComment`** | **Descartada (27/08, no 5f-2a)** | O parâmetro ficou sem uso, mas os dois chamadores já têm o objeto, e o dia em que a regra precisar olhar o ativo (entidade, estado) ele está lá. Mudar assinatura de método público não é escopo de bloco de permissão |

---

## 9. Próximo passo imediato

1. **Bloco 5f-3** — os sete `can($items_id, READ)` passam a
   `Session::haveAccessToEntity()`. **É este bloco que faz "Dispositivos
   passivos" sumir do menu do técnico**, que é o objetivo original da frente de
   permissões. ⚠️ **Candidato a divisão**: sete pontos em cinco arquivos, e o
   teste precisa cobrir gravação de porta, auto-save, OBS de entrada, vínculo,
   comentário e identidade. Avaliar partir em `5f-3a` (caminho da porta:
   `Port.php` + `ajax/port.php`) e `5f-3b` (o resto).
2. Depois: **5g**. O **5h-2** cabe em qualquer intervalo: é um atributo.
3. **Limpar o resíduo do teste**: purgar `CTO TESTE 5f2b` (o `PurgeCleaner` cuida
   das linhas do plugin) e decidir se o perfil de teste fica com CRIAR.
4. **Tag + Release do 1.3.6** quando a Fase 5 tiver um marco.
