# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v6 — 27/08/2026 (sessão da tarde). Substitui o v5
> integralmente.
> Emitido ao fim da sessão que **liquidou a prioridade 1 inteira**: os documentos
> foram para o repositório, o clone Git nasceu no servidor, a Release do 1.3.2 foi
> publicada e conferida, a pendência do anexo virou decisão de produto e o
> **Bloco 5f-1a** foi entregue pelo fluxo novo.
>
> Companheiro: `roadmap-dgoplus.md`. **Os dois vivem em `docs/` no repositório
> desde o commit `1ded500`** — o nome perdeu o sufixo de versão de propósito: o
> histórico é o Git, o número fica no cabeçalho.

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

### A ordem de entrega — agora com Git no servidor

⚠️ **Mudou em 27/08 e simplificou.** O v5 previa "commit primeiro, zip nasce do
commit" porque não havia Git na máquina. **Agora a pasta do plugin É a árvore de
trabalho do repositório**, então a ordem vigente é:

1. O assistente prepara os arquivos a partir do **tarball do commit atual** e
   valida (`php -l`, leitura do core quando preciso).
2. O usuário envia por `pscp` e copia por cima dos arquivos na pasta do plugin.
3. **`git diff` — a conferência do bloco.** Mostra exatamente o que mudou, arquivo
   a arquivo. Substituiu o `grep -c`. Divergiu do esperado: não commita, avisa.
4. `git add -A` → `git commit` → `git push`. **O código vai ao GitHub antes do
   teste.** Reprovou? `git revert` ou `git checkout --`.
5. Console do GLPI + restart, e então o roteiro de teste.

**Rodou inteiro pela primeira vez no 5f-1a e funcionou**: os md5 dos três arquivos
bateram entre o sandbox do assistente, o commit `6efab96` e o servidor.

O zip deixou de ser o veículo do bloco. Ele sobrevive só como **artefato de
Release** (ver seção "Release").

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 27/08 (fim da sessão) | commit **`6efab96`**, versão **1.3.3** |
| Versão em homologação | **1.3.3**, confirmada na tela de plug-ins |
| **Paridade** | ✅ **Estrutural agora**: a pasta do plugin é a árvore de trabalho do clone. `git status` limpo **é** a prova, e substituiu o ritual de `md5sum` dos 27 arquivos |
| Arquivos no repositório | **30** (27 do plugin + 3 em `docs/`) |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** — senha |
| PC do usuário | **Windows, sem Git local.** Envio por `pscp` |
| Assistente | Não tem SSH nem senha. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

> O endereço `192.168.1.50` do contexto v1 (e da skill `glpi-plugin-teckcomp`)
> está **morto**. A skill não foi atualizada — ao lê-la, substituir host e usuário
> e acrescentar a porta.

O shell do servidor está logado como **root** (`root@debian`). O console do GLPI
recusa root puro, então todo comando de console vai com `sudo -u www-data`.

### Git no servidor — instalado em 27/08

**Não existia clone** (`fatal: not a git repository`, verificado). O binário `git`
já estava presente. O clone foi criado assim: `git clone` para `/tmp`, e só a
pasta `.git` movida para dentro da pasta do plugin — os arquivos que ficaram são
**os do servidor**. Resultado imediato: `nothing to commit, working tree clean`.

Configuração aplicada:

```bash
git config --global user.name "Claudio Morett"
git config --global user.email "claudio.morett@gmail.com"
git config --global --add safe.directory /var/www/html/glpi/plugins/dgoplus
git config --global credential.helper store
```

O `safe.directory` é obrigatório: a pasta é do `www-data` e o git roda como root
("dubious ownership"). O `credential.helper store` gravou
`/root/.git-credentials` (0600, root) — o push não pergunta mais nada.
Autenticação por **token fine-grained** do GitHub (Contents: Read and write);
senha de conta não funciona em HTTPS.

⚠️ **Recomendado e ainda não aplicado:** `git config --global core.pager cat`. Sem
isso o `git diff` abre paginador e o usuário fica preso no `(END)` — aconteceu no
5f-1a. Saída: `q` com o terminal em foco.

**Depois de todo `git pull`/`checkout`**, rodar
`chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus` — o git escreve
como root.

### Comandos do dia a dia

**Enviar arquivo do PC para o servidor (cmd do Windows):**

```cmd
pscp -P 2078 "%USERPROFILE%\Downloads\<arquivo>" resolutto@177.87.230.179:/tmp/
```

**`-P` maiúsculo** é a porta (lição 115). Aceita vários arquivos numa linha só.
Verificado: **não** converte quebra de linha — md5 idêntico dos dois lados.

**Trazer do servidor para o PC:**

```cmd
pscp -P 2078 resolutto@177.87.230.179:/caminho/arquivo "%USERPROFILE%\Downloads\arquivo"
```

⚠️ **O `pscp` já falhou uma vez** com `FATAL ERROR: Remote side unexpectedly closed
network connection` e voltou sozinho. Causa não diagnosticada. Triagem: (1) o
PuTTY comum conecta? (2) `pscp -V` e `-v` para ver onde morre o handshake;
(3) `pscp -sftp`; (4) `auth.log` e `fail2ban-client status sshd`.

**Aplicar um bloco (PuTTY, `ssh -p 2078 resolutto@177.87.230.179`):**

```bash
cd /var/www/html/glpi/plugins/dgoplus
cp /tmp/<arquivo> <caminho/no/plugin>
chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus
git diff --stat && git diff        # <<< a conferência do bloco
git add -A && git commit -m "..." && git push
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

**Reverter, antes ou depois do commit:**

```bash
git checkout -- <arquivos>      # descarta a cópia, ainda não commitada
git revert HEAD && git push     # desfaz o commit já empurrado
```

**Log que interessa — e só existe um.** Não há `sql-errors.log` (lição 122). Erro
de SQL vai para o `php-errors.log` como `glpi.CRITICAL`, com query e backtrace:

```bash
sudo tail -n 100 /var/www/html/glpi/files/_log/php-errors.log
```

### Topologia web — mapeada em 27/08

Quem serve é o **Apache**, nas portas **80 e 443** (`ss -ltnp` confirmou; existe
nginx instalado, mas não escuta nada). A raiz web efetiva vem de
**`conf-enabled/glpi.conf`** com `DocumentRoot /var/www/html/glpi/public`, e ela
**vence** o `000-default.conf`, que aponta para `/var/www/html`.

Consequência que encerra um risco: **nada dentro de `plugins/` é alcançável como
arquivo pelo navegador** — nem o `.git`, nem `files/`, nem `config/`. Testado:
`curl` em `/glpi/plugins/dgoplus/public/dgoplus.js` devolve **404 com
`Set-Cookie: glpi_...`**, ou seja, quem respondeu foi o front controller do GLPI,
não o Apache servindo diretório. Sem necessidade de regra de bloqueio.

### Release — o artefato de instalação

**`v1.3.2` publicada em 27/08** com `dgoplus-1.3.2.zip` anexado (168 KB, sha256
`fd42f3a5eb0adf33a8707a59bd2b32c2495070db8734ce477e1d7eb381518752`).

O zip **nasce do commit**, nunca de pasta montada à mão:

```bash
cd /var/www/html/glpi/plugins/dgoplus
git tag -a v1.3.2 -m "..." && git push origin v1.3.2
git archive --format=zip --prefix=dgoplus/ -o /tmp/dgoplus-1.3.2.zip v1.3.2
```

Conferido pelo assistente: **30 arquivos, os 30 idênticos por md5** ao tarball do
commit; 36 entradas no zip (30 arquivos + 6 diretórios — é essa diferença que fez
o v4 contar 32 arquivos errado). Prefixo `dgoplus/` correto para `unzip -o` a
partir de `plugins/`.

⚠️ **A `v1.3.3` ainda não tem tag nem Release.**

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
reinstalar em negrito na primeira linha; **(2)** o comando `pscp` literal;
**(3)** os comandos de aplicar, com **`git diff` como conferência**; **(4)**
roteiro de teste numerado com resultado esperado, onde ler o log e como reverter.

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
devolver o estado anterior logo após um commit (lição 132). O `ls-remote` é
autoritativo para o HEAD; o tarball do SHA, para o conteúdo. O domínio
`release-assets.githubusercontent.com` também está liberado no sandbox.

**Padrão de trabalho do assistente ao preparar um bloco:** baixar o tarball do
commit atual, editar a cópia, `php -l`, e **provar por md5 que só os arquivos do
escopo mudaram** antes de entregar.

### O core do GLPI também é legível

`github.com/glpi-project/glpi` na **tag `11.0.6`**, pelos mesmos raw URLs. Dois
detalhes de caminho:

- Classes com namespace `Glpi\` ficam em **`src/Glpi/...`**, não em `src/...`.
- O schema completo está em `install/mysql/glpi-empty.sql` — forma mais rápida de
  confirmar se uma coluna existe, sem acesso ao banco.

### O sandbox do assistente TEM PHP

`php -l` é possível. **Rodar `apt-get update` e `apt-get install -y php-cli` como
dois comandos separados** — o `update` sai com código ≠ 0 e encadear com `&&`
faz o `install` nunca rodar (lição 126). Validado nesta sessão com PHP 8.3.6.
**Todo arquivo PHP entregue passa por `php -l` antes de sair.** Continua valendo:
`php -l` **não** pega incompatibilidade de assinatura com a classe-pai.

Não há `sudo` no sandbox (já é root) e a homologação é inalcançável de lá.

### Práticas abolidas

- Reinstalar o plugin "por precaução" a cada bloco.
- Mandar colar `sed`, heredoc ou edição manual de arquivo grande no terminal.
- Zip como veículo de bloco (só Release).
- **Upload pela web do GitHub** — substituído pelo `git push` (lição 131 aposentada
  na prática, mantida como registro).
- Julgar tela sem antes confirmar a versão instalada (lição 114).
- Remontar arquivo a partir do `master` + a descrição do bloco (lição 129).
- **Ritual de `md5sum` dos 27 arquivos** — `git status` faz isso melhor.

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

**Como o direito se comporta em 1.3.3** (números reconferidos em 27/08 no commit
`6efab96`):

| Ação | Exige hoje | Onde |
|---|---|---|
| Ver mapa, painel, relatório | `plugin_dgoplus_port` READ | `front/map.php` |
| **Documentar porta (existente ou não)** | **`plugin_dgoplus_port` UPDATE** | `Port.php:506` e `:514`; tela em `MapController:2950` |
| Esvaziar porta (volta a livre) | `plugin_dgoplus_port` DELETE | `Port.php:444` |
| Propor vínculo | `plugin_dgoplus_port` CREATE ⚠️ *muda no 5f-1b* | `MapController:3183`, `Link.php:431` |
| Fileira / coluna / piso | `plugin_dgoplus_port` CREATE | `MapController:529`, `:735`, `Floor::$rightname` |
| Qualquer gravação de porta | **também** `datacenter` READ | 7 pontos, abaixo |
| Comentário do elemento | `datacenter` UPDATE | `DgoIdentity:216` |
| **Anexos** | `document` READ+UPDATE+CREATE **e `datacenter` UPDATE** | ✅ confirmado em tela |
| Criar elemento | `datacenter` CREATE | `MapController:412` e `:1522` |
| Configurar papéis | `config` UPDATE | `MapController:1544` |

**Os sete pontos acoplados a `datacenter` READ** (todos `can($items_id, READ)`),
**verificados em 27/08 no commit `6efab96`** — o 5f-1a deslocou o `Port.php` em
+12 linhas:

| Arquivo | Linha | Contexto |
|---|---|---|
| `src/Port.php` | **383** | `applyInput` |
| `src/Port.php` | **646** | `ensureEntry` |
| `src/Port.php` | **751** | `ensureGrid` |
| `ajax/port.php` | 48 | auto-save |
| `src/MapController.php` | 949 | `actionSaveEntryObs` |
| `src/Link.php` | 689 | `loadVisibleItem` (3 chamadas) |
| `src/DgoIdentity.php` | 323 | identidade |

`front/map.php` exige **apenas** `Port::$rightname READ`.

**A semântica que a Fase 5 instaura:**

| Direito | Significa |
|---|---|
| LER | Ver mapa, painel, relatórios, comentários, descrição das portas |
| ATUALIZAR | **Documentar portas** ✅ (5f-1a), propor/confirmar vínculos (5f-1b), comentar o elemento (5f-2) |
| CRIAR | Criar elementos pelo mapa, fileiras, colunas, pisos — **estrutura** |
| DELETE | Excluir portas e vínculos |

**Fora do DGO+, por decisão:** criar Localização (dropdown do GLPI inteiro),
anexos (direito `document` **+ `datacenter` UPDATE**) e excluir o elemento.

**O que a Fase 5 assume conscientemente:** depois do 5f, quem tiver
`plugin_dgoplus_port` UPDATE grava porta e comentário em elementos **da sua
entidade** sem ter direito nesses ativos. Escalada deliberada e delimitada,
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
    ├── Port.php           porta; applyInput é o ponto único — 1029 linhas
    ├── Link.php           vínculo; propose é o ponto único — 1196 linhas
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (rightname = plugin_dgoplus_port)
    ├── MapController.php  a tela do mapa — 3392 linhas
    ├── Dashboard.php      o painel
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade e QR do elemento (3t)
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.3** (commit `6efab96`, idênticas no servidor):

```
6edd8f4a349e62d573a61242068b2a4f  setup.php           (269 linhas)
8abbba52ce256a9cdd3dd813a7d34c83  src/Port.php        (1029 linhas)
7b021701ef0dd88a03a186330ef24dfd  src/MapController.php (3392 linhas)
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
| 115 | **`pscp` usa `-P` maiúsculo para a porta** |
| 116 | **Bump de versão no `setup.php` conta como mudança de instalação.** `--force` + `activate` mesmo com `Install.php` idêntico |
| 117 | **`$parent->can($items_id, READ)` acopla o plugin ao direito do itemtype pai — e ao menu do core.** `Session::haveAccessToEntity()` preserva a proteção sem o acoplamento |
| 118 | **`$can_write = haveRight($rightname, $found ? UPDATE : CREATE)`**: porta ainda não documentada exigia CREATE. Causa real de "o técnico não consegue documentar". ✅ *Corrigida pelo 5f-1a* |
| 119 | **Mensagem de permissão que não nomeia o direito faltante custa horas** |
| 120 | ✅ **CONFIRMADA em 27/08:** anexar documento a um ativo exige **`datacenter` UPDATE**, não basta o direito Documentos. Ver lição 134 |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054** |
| 122 | **Não existe `sql-errors.log` no GLPI 11.0.6.** Erro de SQL vai para `php-errors.log` como `glpi.CRITICAL` |
| 123 | **O direito Documentos fica na aba Gerência do perfil, não em Ativos** |
| 124 | **A homologação também pode estar À FRENTE do `master`.** *Encerrada como divergência ativa; com o clone no servidor, a classe inteira do problema saiu de cena* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`.** E, no `itemtype_item_revert`, esquecer `specific_itemtype` devolve a coluna vazia |
| 126 | **O sandbox do assistente tem PHP.** `apt-get update` sai com código ≠ 0 — **dois comandos separados** |
| 127 | **O CSV exportado do GLPI abre torto no Excel por duplo clique**: acentos viram `Ã§Ã£o` e **`(10)` vira `-10`**. Solução: *Dados → Obter dados → De texto/CSV*, UTF-8, coluna como Texto |
| 128 | **`pscp` pode falhar com `Remote side unexpectedly closed network connection` e voltar sozinho** |
| 129 | **Arquivo remontado a partir do `master` + a descrição do bloco NÃO é verificação.** A descrição registra o *efeito*, o commit registra o *diff* |
| 130 | **O GitHub é canônico.** Qualquer ordem que deixe código existindo só no servidor já custou perda de trabalho uma vez |
| 131 | **Upload pela web do GitHub não avisa quando o nome não bate — cria arquivo novo em silêncio.** *Aposentada na prática pelo `git push`, mantida para quem voltar à web* |
| 132 | **`raw.githubusercontent.com` tem cache de CDN.** Para commit recém-feito: `ls-remote` para o HEAD, tarball do `codeload` para o conteúdo |
| **133** | **Falha de permissão no auto-save chega ao usuário como erro de rede.** O `.catch()` do `dgoplus.js` só distingue "HTTP ≠ 200", então um 403 do `checkRight` vira **"Falha ao salvar. Use o botão Salvar."** — o usuário não descobre que faltou direito, nem qual. Recusa de *regra* (com `ok:false`) mostra o motivo; recusa de *permissão* não. Lição 119 num lugar novo → escopo do 5g |
| **134** | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core (`Document_Item`) e pergunta se o usuário pode atualizar o ATIVO, não se pode mexer em documentos. Provado em tela: sem `datacenter` UPDATE aparece só "Nenhum resultado encontrado"; com ele, aparecem "Adicionar um documento" e "Associar um documento existente" |
| **135** | **O direito "Data centers" também fica na aba Gerência do perfil**, junto com Documentos, Contratos, Clusters, Domínios e Cabos — não em Ativos. Generalização da lição 123 |
| **136** | **A raiz web efetiva vem de `conf-enabled/glpi.conf` e vence o `000-default.conf`.** Corolário de diagnóstico: **404 com `Set-Cookie: glpi_` é o GLPI respondendo**, não o Apache servindo arquivo — é assim que se distingue "arquivo não existe" de "rota não existe" |
| **137** | **Com o clone Git na pasta do plugin, `git diff` É a conferência do bloco** (substitui o `grep -c`) e `git checkout --` é o rollback instantâneo. Configurar `core.pager cat`, senão o `git diff` prende o usuário no paginador |
| **138** | **O escopo real de um bloco de permissão está no PONTO ÚNICO, não na linha da tela.** O roadmap descrevia o 5f-1 como "`MapController:2946` e `:3179`", mas o `checkRight` que grava mora em `Port::applyInput` e `Link::propose`. Mudar só a tela deixaria o campo editável e o Salvar em 403 — a divergência que as lições 47 e 48 proíbem. **Antes de escrever, procurar o `checkRight` no ponto único** |

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
| 5h | JOIN da coluna Localização no relatório | Fechado, validado e commitado (27/08), 1.3.2, `bd28ffd` |
| **DOC** | `docs/` no repositório | **Fechado (27/08), `1ded500`** |
| **GIT-1** | Clone Git na pasta do plugin | **Fechado (27/08)** |
| **GIT-2** | Primeiro `push` do servidor, com token | **Fechado (27/08)** |
| **REL** | Tag `v1.3.2` + Release com zip | **Fechado e conferido por md5 (27/08)** |
| **5f-1a** | **Documentar porta exige UPDATE, não CREATE** | **Fechado e validado em tela (27/08), 1.3.3, `6efab96`** |

### O que o 5f-1a fez, em detalhe

Três arquivos, três trechos, `+22 −6`:

| Onde | O quê |
|---|---|
| `src/Port.php`, `applyInput` | `checkRight(CREATE)` do INSERT → **UPDATE**; restauração da lixeira também. O `checkRight(UPDATE)` subiu para antes do `if`, cobrindo os dois caminhos. Mais 14 linhas de comentário explicando o porquê |
| `src/MapController.php:2950` | `$found ? UPDATE : CREATE` → **sempre UPDATE** |
| `setup.php:25` | 1.3.2 → **1.3.3** |

**Validado em tela (27/08)** com o perfil **Tecnicos N1 (ID 12)** em
LER + ATUALIZAR, **CRIAR e DELETE desmarcados**, e `datacenter` só READ:

- Plug-in em **1.3.3** ativo.
- Célula em branco (F1.03 da DGO 01) abre **editável**, sem a tarja de leitura.
- Gravou `2153` + observação e **persistiu após F5** — aparece na grade, badge foi
  para "3 de 16 documentadas". **Este é o passo que reprovava no 1.3.2.**
- Propor vínculo continua recusando com "exige permissão de criação" — escopo não
  vazou; é o 5f-1b.

⚠️ **Dois passos do roteiro ficaram sem confirmação explícita:** editar célula já
documentada (passo 5) e esvaziar célula sem DELETE (passo 6). Na F1.02 apareceu
"Falha ao salvar. Use o botão Salvar." — **hipótese não confirmada:** foi a
tentativa de esvaziar/desmarcar, que cai no ramo de exclusão e exige DELETE. O
valor `1202` continua na célula, o que é consistente com um 403. **Confirmar no
próximo bloco.**

---

## 6. Dívidas conhecidas

1. **README desatualizado** — manda baixar `dgoplus-v1.0.0.zip` (linhas 38, 45,
   56), fala em três tabelas quando são quatro (111, 142), e a linha 119 avisa
   sobre portas órfãs, defeito que o 3q resolveu. **Agora há Release de verdade
   para apontar.**
2. **Sem catálogo de tradução**: interface pt-BR fixa.
3. ⚠️ **Lista integral de lições (1–113)** não incorporada.
4. **Sem tag nem Release para o 1.3.3** — a `v1.3.2` está publicada; a nova custa
   três comandos.
5. **Passos 5 e 6 do roteiro do 5f-1a** sem confirmação (ver acima).
6. ⚠️ **`core.pager cat` não configurado** — `git diff` prende no paginador.

*(As dívidas do v5 sobre documentos fora do repositório, clone Git não localizado
e Release inexistente foram **todas liquidadas** em 27/08.)*

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

**27/08 (tarde):**
- **DGO 01**, em `Outlet Porto Belo`, piso `MALL - PORTO BELO`: grade de **16
  posições** (F1 e F2 × 8), **3 documentadas** ao fim do teste.
- Aba de escopo mostrando `DGO 01 - PORTO BELO (0)`, `DGO 01 (3)`, `CTO 01 (0)`.
- Perfil de teste: **Tecnicos N1, ID 12**.

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
| **Anexo pelo técnico** | **Descartada (27/08)** | Exige `datacenter` UPDATE (lição 134), que devolveria o menu de Dispositivos passivos e daria edição plena nos ativos. **Decisão do usuário: o técnico não precisa anexar — supervisor cobre.** O formulário próprio de upload fica no estacionamento, sem dono |
| **Documentos versionados dentro do repositório** | **Descartada (27/08)** | `docs/contexto-dgoplus.md` sem sufixo: o histórico é o Git, o número fica no cabeçalho |

---

## 9. Próximo passo imediato

1. **Bloco 5f-1b** — propor vínculo passa a exigir UPDATE. Sete pontos já
   mapeados: `Port.php` 664, 685, 771, 803 (`ensureEntry`/`ensureGrid`),
   `Link.php:431`, `MapController:3183` + o texto da mensagem. Confirmar de
   passagem os passos 5 e 6 pendentes do 5f-1a.
2. **5f-2** → **5f-3** → **5g**, nesta ordem. O **5h-2** cabe em qualquer
   intervalo: é um atributo.
3. **Tag + Release do 1.3.3** quando a Fase 5 tiver um marco.
