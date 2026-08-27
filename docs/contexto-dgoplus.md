# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v7 — 27/08/2026 (sessão da noite). Substitui o v6
> integralmente.
> Emitido ao fim da sessão que entregou o **Bloco 5f-1b** — propor vínculo passa a
> exigir ATUALIZAR — e que, de quebra, **liquidou as duas dívidas pendentes do
> 5f-1a** com prova no log do Apache, corrigiu o comando de envio do projeto
> (`scp`, não `pscp`) e configurou o `core.pager`.
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

**O passo 2 não é opcional** — foi ele que pegou, nesta sessão, três arquivos
antigos sendo enviados no lugar dos novos (lição 140).

O zip deixou de ser o veículo do bloco. Ele sobrevive só como **artefato de
Release**.

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 27/08 (fim da sessão) | commit **`a690010`**, versão **1.3.4** |
| Versão em homologação | **1.3.4**, confirmada na tela de plug-ins |
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
git config --global core.pager cat          # aplicado em 27/08 (sessão da noite)
```

O `safe.directory` é obrigatório: a pasta é do `www-data` e o git roda como root
("dubious ownership"). O `credential.helper store` gravou
`/root/.git-credentials` (0600, root); autenticação por **token fine-grained**
(Contents: Read and write) — senha de conta não funciona em HTTPS.

O `core.pager cat` **já está aplicado**: o `git diff` não prende mais no
paginador.

**Depois de todo `git pull`/`checkout`**, rodar
`chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus` — o git escreve
como root.

### Comandos do dia a dia

**Enviar arquivo do PC para o servidor (cmd do Windows) — `scp`, não `pscp`:**

```cmd
scp -P 2078 "%USERPROFILE%\Downloads\<arquivo>" resolutto@177.87.230.179:/tmp/
```

`-P` maiúsculo é a porta. Aceita vários arquivos numa linha só. Usa a mesma chave
e a mesma configuração do `ssh` que o usuário já usa. Verificado nesta sessão:
**não converte quebra de linha** — md5 idêntico dos dois lados.

**⚠️ O `pscp` NÃO funciona neste ambiente.** Ele é da suíte PuTTY, lê chaves só em
`.ppk` de sessões salvas, e o usuário não usa PuTTY. Falha com
`FATAL ERROR: No supported authentication methods available (server sent: publickey)`.
Isso é **diferente** da lição 128 (`Remote side unexpectedly closed`), que era
conexão morrendo.

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
**qual célula** gerou cada requisição sem perguntar ao usuário. Foi assim que os
passos 5 e 6 do 5f-1a foram fechados.

### Topologia web

Quem serve é o **Apache**, nas portas **80 e 443** internamente; o acesso externo
entra por **`177.87.230.179:2077`**, redirecionado para o vhost em `:80`. A raiz
web efetiva vem de **`conf-enabled/glpi.conf`** com
`DocumentRoot /var/www/html/glpi/public`, e ela **vence** o `000-default.conf`.

Consequência que encerra um risco: **nada dentro de `plugins/` é alcançável como
arquivo pelo navegador** — nem o `.git`, nem `files/`, nem `config/`. Testado:
`curl` em `/glpi/plugins/dgoplus/public/dgoplus.js` devolve **404 com
`Set-Cookie: glpi_...`**, ou seja, quem respondeu foi o front controller do GLPI.
Sem necessidade de regra de bloqueio.

### Release — o artefato de instalação

**`v1.3.2` publicada em 27/08** com `dgoplus-1.3.2.zip` anexado (168 KB, sha256
`fd42f3a5eb0adf33a8707a59bd2b32c2495070db8734ce477e1d7eb381518752`).

O zip **nasce do commit**, nunca de pasta montada à mão:

```bash
cd /var/www/html/glpi/plugins/dgoplus
git tag -a v1.3.4 -m "..." && git push origin v1.3.4
git archive --format=zip --prefix=dgoplus/ -o /tmp/dgoplus-1.3.4.zip v1.3.4
```

⚠️ **A `v1.3.3` e a `v1.3.4` não têm tag nem Release.** Bloco REL-2.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6, `Tasks list`
2.1.8.

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
que aconteceu com o 5f-1, partido em `5f-1a` e `5f-1b`.

Toda entrega tem quatro seções fixas: **(1)** o que muda, com a decisão de
reinstalar em negrito na primeira linha; **(2)** o comando `scp` literal **com os
md5 esperados**; **(3)** os comandos de aplicar, com **`git diff` como
conferência**; **(4)** roteiro de teste numerado com resultado esperado, onde ler
o log e como reverter.

### Nome de arquivo entregue leva o bloco

**Todo arquivo de bloco sai com o bloco no nome** — `Port-5f1b.php`,
`setup-5f1b.php` — e o `cp` no servidor é que renomeia. Sem isso o download
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
devolver o estado anterior logo após um commit (lição 132).

**Padrão de trabalho do assistente ao preparar um bloco:** baixar o tarball do
commit atual, editar a cópia, `php -l`, e **provar por md5 que só os arquivos do
escopo mudaram** antes de entregar. Depois do push, **baixar o commit publicado e
conferir os md5 de novo** — foi feito no 5f-1b e fecha a proveniência.

**Número previsto (`git diff --stat`, `grep -c`) sai de comando, não de olho.**
Ver lição 141: no 5f-1b eu previ `+41 −8` e `MapController:3`, e o certo era
`+44 −11` e `:2`.

### O core do GLPI também é legível

`github.com/glpi-project/glpi` na **tag `11.0.6`**, pelos mesmos raw URLs. Dois
detalhes de caminho:

- Classes com namespace `Glpi\` ficam em **`src/Glpi/...`**, não em `src/...`.
- O schema completo está em `install/mysql/glpi-empty.sql` — forma mais rápida de
  confirmar se uma coluna existe, sem acesso ao banco.

### O sandbox do assistente TEM PHP

`php -l` é possível. **Rodar `apt-get update` e `apt-get install -y php-cli` como
dois comandos separados** — o `update` sai com código ≠ 0 e encadear com `&&`
faz o `install` nunca rodar (lição 126). Validado com PHP 8.3.6. **Todo arquivo
PHP entregue passa por `php -l` antes de sair.** Continua valendo: `php -l`
**não** pega incompatibilidade de assinatura com a classe-pai.

O sandbox também consegue **simular o `git diff --stat` do servidor**: `git init`
sobre o tarball do commit, copiar os arquivos do bloco por cima, rodar o `stat`.
É a forma correta de prever o número que o usuário vai ver.

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
  não. Desmontar (vínculo já confirmado) pede DELETE. Conferido em código:
  `Link::refuse` e a tela (`MapController:2542`) perguntam a mesma coisa.

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

**Como o direito se comporta em 1.3.4** (números conferidos em 27/08 no commit
`a690010`):

| Ação | Exige hoje | Onde |
|---|---|---|
| Ver mapa, painel, relatório | `plugin_dgoplus_port` READ | `front/map.php` |
| **Documentar porta (existente ou não)** | **`plugin_dgoplus_port` UPDATE** ✅ 5f-1a | `Port.php:506` e `:514`; tela em `MapController:2950` |
| Esvaziar porta (volta a livre) | `plugin_dgoplus_port` DELETE | `Port.php:444` |
| **Propor vínculo** | **`plugin_dgoplus_port` UPDATE** ✅ 5f-1b | `Link.php:434`; tela em `MapController:3189` |
| Confirmar / recusar vínculo | `plugin_dgoplus_port` UPDATE | `Link.php:483`, `:525` |
| Desmontar vínculo | `plugin_dgoplus_port` DELETE | `Link.php:~570` |
| Fileira / coluna / piso | `plugin_dgoplus_port` CREATE | `MapController:529`, `:735`, `Floor::$rightname` |
| Qualquer gravação de porta | **também** `datacenter` READ ⚠️ *muda no 5f-3* | 7 pontos, abaixo |
| Comentário do elemento | `datacenter` UPDATE ⚠️ *muda no 5f-2* | `DgoIdentity:216` |
| **Anexos** | `document` READ+UPDATE+CREATE **e `datacenter` UPDATE** | ✅ confirmado em tela |
| Criar elemento | `datacenter` CREATE ⚠️ *muda no 5f-2* | `MapController:412` e `:1522` |
| Configurar papéis | `config` UPDATE | `MapController:1544` |

**Os sete pontos acoplados a `datacenter` READ** (todos `can($items_id, READ)`),
**verificados em 27/08 no commit `6efab96`**. ⚠️ **O 5f-1b acrescentou +19 linhas
ao `Port.php` e +6 ao `MapController.php` — reconferir antes de escrever o 5f-3:**

| Arquivo | Linha (em `6efab96`) | Contexto |
|---|---|---|
| `src/Port.php` | 383 | `applyInput` |
| `src/Port.php` | 646 | `ensureEntry` |
| `src/Port.php` | 751 | `ensureGrid` |
| `ajax/port.php` | 48 | auto-save |
| `src/MapController.php` | 949 | `actionSaveEntryObs` |
| `src/Link.php` | 689 | `loadVisibleItem` (3 chamadas) |
| `src/DgoIdentity.php` | 323 | identidade |

`front/map.php` exige **apenas** `Port::$rightname READ`.

**A semântica que a Fase 5 instaura:**

| Direito | Significa |
|---|---|
| LER | Ver mapa, painel, relatórios, comentários, descrição das portas |
| ATUALIZAR | **Documentar portas** ✅ (5f-1a), **propor e confirmar vínculos** ✅ (5f-1b), comentar o elemento (5f-2) |
| CRIAR | Criar elementos pelo mapa, fileiras, colunas, pisos — **estrutura** |
| DELETE | Esvaziar portas, recusar por desmontagem, excluir vínculos |

**Fora do DGO+, por decisão:** criar Localização (dropdown do GLPI inteiro),
anexos (direito `document` **+ `datacenter` UPDATE**) e excluir o elemento.

**O que a Fase 5 assume conscientemente:** depois do 5f, quem tiver
`plugin_dgoplus_port` UPDATE grava porta, vínculo e comentário em elementos **da
sua entidade** sem ter direito nesses ativos. Escalada deliberada e delimitada,
decidida pelo administrador ao conceder o direito.

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
    ├── MapController.php  a tela do mapa — 3398 linhas
    ├── Dashboard.php      o painel
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade e QR do elemento (3t)
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.4** (commit `a690010`, baixado do GitHub e conferido
pelo assistente; idênticas no servidor e no sandbox):

```
0aa69700238257136ea598b714eb08e9  setup.php             (269 linhas)
d84d8788ebce8939737ca1cee52c798b  src/Port.php          (1048 linhas)
4f81bc233ffd5df1ce5a6e49e0fa0487  src/Link.php          (1204 linhas)
846bef4e6d5936ddb4d56d6f4cc1c899  src/MapController.php (3398 linhas)
```

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
| 118 | **`$can_write = haveRight($rightname, $found ? UPDATE : CREATE)`**: porta ainda não documentada exigia CREATE. ✅ *Corrigida pelo 5f-1a* |
| 119 | **Mensagem de permissão que não nomeia o direito faltante custa horas** |
| 120 | ✅ Anexar documento a um ativo exige **`datacenter` UPDATE**. Ver lição 134 |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054** |
| 122 | **Não existe `sql-errors.log` no GLPI 11.0.6.** Erro de SQL vai para `php-errors.log` como `glpi.CRITICAL` |
| 123 | **O direito Documentos fica na aba Gerência do perfil, não em Ativos** |
| 124 | A homologação também pode estar À FRENTE do `master`. *Encerrada: com o clone no servidor, a classe do problema saiu de cena* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`.** E, no `itemtype_item_revert`, esquecer `specific_itemtype` devolve a coluna vazia |
| 126 | **O sandbox do assistente tem PHP.** `apt-get update` sai com código ≠ 0 — **dois comandos separados** |
| 127 | **O CSV exportado do GLPI abre torto no Excel por duplo clique.** Solução: *Dados → Obter dados → De texto/CSV*, UTF-8, coluna como Texto |
| 128 | **`pscp` pode falhar com `Remote side unexpectedly closed network connection` e voltar sozinho.** *Distinto da lição 139: ali a conexão morre, aqui a autenticação nem começa* |
| 129 | **Arquivo remontado a partir do `master` + a descrição do bloco NÃO é verificação** |
| 130 | **O GitHub é canônico.** Qualquer ordem que deixe código existindo só no servidor já custou perda de trabalho uma vez |
| 131 | Upload pela web do GitHub cria arquivo novo em silêncio quando o nome não bate. *Aposentada pelo `git push`* |
| 132 | **`raw.githubusercontent.com` tem cache de CDN.** Para commit recém-feito: `ls-remote` para o HEAD, tarball do `codeload` para o conteúdo |
| 133 | ✅ **CONFIRMADA (27/08, log do Apache): falha de permissão no auto-save chega ao usuário como erro de rede.** O `ajax/port.php` responde **403** e o `.catch()` do `dgoplus.js` mostra **"Falha ao salvar. Use o botão Salvar."** Pior: o auto-save **reenvia** — foram **sete 403 seguidos** para uma única ação do usuário, contra uma parede que nunca vai ceder. Escopo do 5g |
| 134 | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core (`Document_Item`) e pergunta se o usuário pode atualizar o ATIVO |
| 135 | **O direito "Data centers" também fica na aba Gerência do perfil**, junto com Documentos, Contratos, Clusters, Domínios e Cabos |
| 136 | **A raiz web efetiva vem de `conf-enabled/glpi.conf` e vence o `000-default.conf`.** **404 com `Set-Cookie: glpi_` é o GLPI respondendo**, não o Apache servindo arquivo |
| 137 | **Com o clone Git na pasta do plugin, `git diff` É a conferência do bloco** e `git checkout --` é o rollback instantâneo. ✅ *`core.pager cat` aplicado em 27/08* |
| 138 | **O escopo real de um bloco de permissão está no PONTO ÚNICO, não na linha da tela.** Antes de escrever, procurar o `checkRight` no ponto único |
| **139** | **O envio é `scp`, não `pscp` — e o servidor recusa senha.** O usuário usa o **OpenSSH do Windows com chave**; o `pscp` é da suíte PuTTY, só lê `.ppk` de sessão salva, e morre com `No supported authentication methods available (server sent: publickey)`. O contexto registrou "autenticação por senha" desde o v1 e **estava errado**: o que o usuário digita é a frase-secreta da chave. Verificado nesta sessão: o `scp` **não** converte quebra de linha (md5 idêntico dos dois lados) |
| **140** | **Arquivo de bloco com o nome final colide na pasta Downloads e o `scp` manda o ANTIGO em silêncio.** Aconteceu: `setup.php`, `Port.php` e `MapController.php` do 5f-1a ainda estavam lá, o navegador salvou os novos como `setup_1.php`, `Port_1.php`, `MapController_1.php`, e o envio "teve sucesso" com três arquivos de duas horas antes. Só o `Link.php` chegou certo — era o único nome sem colisão. **Duas regras nasceram daqui: o nome entregue leva o bloco (`Port-5f1b.php`), e o `md5sum` de `/tmp` ANTES do `cp` deixa de ser opcional.** Foi ele que pegou |
| **141** | **Número previsto de `git diff --stat` e `grep -c` sai de comando, não de contagem a olho.** Previ `+41 −8` e `MapController:3`; o certo era `+44 −11` e `:2` — comentário substituindo linha de código conta `+1 −1`, e um dos três pontos tocados não continha a string procurada. Divergência de número previsto gera desconfiança falsa num bloco correto. **No sandbox: `git init` sobre o tarball do commit, copiar os arquivos por cima, rodar o `stat` de verdade** |
| **142** | **As requisições do GLPI caem no `other_vhosts_access.log`, não no `access.log`.** E o `Referer` traz `edit=<tubo>-<fibra>`, então o log diz **qual célula** gerou cada requisição. Isso substitui pedir F12 ao usuário: `grep -h "port.php" /var/log/apache2/access.log /var/log/apache2/other_vhosts_access.log \| tail`. Foi assim que os passos 5 e 6 do 5f-1a foram fechados sem depender da aba Rede |

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
| 3t | Identidade, QR e comentário | Fechado |
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
| 5f-1a | Documentar porta exige UPDATE, não CREATE | Fechado (27/08), 1.3.3, `6efab96` — **resíduo liquidado pelo 5f-1b** |
| **5f-1b** | **Propor vínculo exige UPDATE, não CREATE** | **Fechado e validado em tela + log (27/08), 1.3.4, `a690010`** |

### O que o 5f-1b fez, em detalhe

Quatro arquivos, `+44 −11`:

| Onde | O quê |
|---|---|
| `src/Port.php`, `ensureEntry` (2 pontos) | restauração da lixeira e INSERT da linha de entrada: `CREATE` → **UPDATE** |
| `src/Port.php`, `ensureGrid` (2 pontos) | restauração da lixeira e INSERT da linha de grade: `CREATE` → **UPDATE** |
| `src/Link.php`, `propose()` | **o ponto único de criação de vínculo**: `CREATE` → **UPDATE** |
| `src/MapController.php` (trava da tela) | `haveRight(CREATE)` → **UPDATE**, e a mensagem passou a nomear o direito: *"exige a permissão «Atualizar» em «Portas de DGO» (Administração → Perfis → aba DGO+)"* |
| `src/MapController.php` (docblock) | "usuario com CREATE" → "usuario com UPDATE" |
| `setup.php` | 1.3.3 → **1.3.4** |

**Proveniência fechada:** o assistente baixou o commit `a690010` do GitHub e
provou por md5 que os quatro arquivos publicados são idênticos aos preparados no
sandbox, e que **nenhum outro dos 30 arquivos mudou**.

**Validado em tela (27/08)** com o perfil **Tecnicos N1 (ID 12)** em
LER + ATUALIZAR, **CRIAR e DELETE desmarcados**, e `datacenter` só READ:

1. Plug-in em **1.3.4** ativo.
2. Célula **livre** (F1.04 da DGO 01): a seção "Alimenta" mostra os seletores e o
   botão **Propor vínculo**. **Era aqui que o 1.3.3 exibia a mensagem cinza.**
3. Proposta gravada: **"E2 de CTO 01 · pendente"**. Duas linhas nasceram no banco
   — grade e entrada — **sem o perfil ter CRIAR**.
4. O técnico **confirmou** o vínculo do lado do CTO 01 (`Confirmado por
   teste.001`) e o F1.04 voltou mostrando **confirmado**. Ciclo completo em
   ATUALIZAR.
5. A aba de escopo passou de **DGO 01 (3)** para **DGO 01 (4)**.

**Escopo não vazou:** o cartão de Comentários continua com *"Você tem permissão
apenas de leitura neste ativo"* — é o `datacenter` UPDATE do `DgoIdentity:216`,
que é o **5f-2**, ainda não tocado.

**Os dois passos pendentes do 5f-1a foram fechados pelo log**, sem F12:

- `edit=1-3` (F1.03, célula **já documentada**) → **HTTP 200**, duas vezes.
  Passo 5 aprovado.
- `edit=1-2` (F1.02, **esvaziar** sem DELETE) → **HTTP 403**, sete vezes.
  Passo 6 aprovado: a recusa é real e o `delete()` nunca roda.

---

## 6. Dívidas conhecidas

1. **README desatualizado** — manda baixar `dgoplus-v1.0.0.zip` (linhas 38, 45,
   56), fala em três tabelas quando são quatro (111, 142), e a linha 119 avisa
   sobre portas órfãs, defeito que o 3q resolveu.
2. **Sem catálogo de tradução**: interface pt-BR fixa.
3. ⚠️ **Lista integral de lições (1–113)** não incorporada.
4. **Sem tag nem Release para o 1.3.3 e o 1.3.4** — a `v1.3.2` está publicada.
5. **A skill `glpi-plugin-teckcomp` está desatualizada**: host, usuário, porta e
   agora também o comando de envio (`pscp` → `scp`).
6. **Texto que fala de ação indisponível.** No painel da porta com vínculo
   confirmado, a dica diz *"Desmontar remove o vínculo dos dois lados"* mesmo
   quando o perfil não tem DELETE e o botão não é renderizado. Candidato ao 5g.

*(As dívidas do v6 — passos 5 e 6 do 5f-1a sem confirmação, `core.pager` não
configurado — foram **liquidadas** em 27/08.)*

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
  posições** (F1 e F2 × 8), **4 documentadas** ao fim dos testes.
- **CTO 01**, mesmo piso, grade de 16 posições, **0 documentadas**; entradas
  **E1 (← DGO 01 · F1.01)** e **E2 (← DGO 01 · F1.04)**, ambas confirmadas.
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
| **Exigir DELETE para recusar vínculo** | **Descartada (já no 4c)** | Recusar e confirmar são as duas metades da mesma resposta: um perfil capaz de aceitar e incapaz de dizer não é pior que a escalada |
| **`pscp` como veículo de envio** | **Descartada (27/08)** | Não autentica: o servidor só aceita `publickey` e o `pscp` não lê chave OpenSSH. É `scp` (lição 139) |

---

## 9. Próximo passo imediato

1. **Bloco 5f-2** — comentário e criação de elemento migram para o direito do
   plugin. `DgoIdentity:216` (comentário: `datacenter` UPDATE →
   `plugin_dgoplus_port` UPDATE) e `MapController:412` e `:1522` (criar elemento:
   `datacenter` CREATE → `plugin_dgoplus_port` CREATE). ⚠️ **Números de linha do
   commit `6efab96` — reconferir em `a690010` antes de escrever.** Efeito visível:
   some a tarja "Você tem permissão apenas de leitura neste ativo".
2. **5f-3** → **5g**, nesta ordem. O **5h-2** cabe em qualquer intervalo: é um
   atributo.
3. **Tag + Release do 1.3.4** quando a Fase 5 tiver um marco.
