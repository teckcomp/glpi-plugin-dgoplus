# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v14 — 28/08/2026. Substitui o v13 integralmente.
> Emitido ao fim de uma sessão longa que fechou **cinco commits**. Versão
> **1.3.16**, commit **`02b64d5`**.
>
> **O que o v14 traz de novo em relação ao v13:**
>
> 1. **Cinco blocos fecharam numa sessão:** 5g-1b, 5g-3, PAINEL-1a, README,
>    5b e 5c, mais um 2b de correção de tela. De 1.3.11 a 1.3.16.
> 2. **O 5g-1b não era o que o v13 descrevia.** O endpoint do comentário nunca
>    devolveu 403 por falta de UPDATE, e a tela já nomeava o direito certo.
>    Virou a **lição 154**.
> 3. **Quatro decisões de produto novas**, tomadas nesta sessão — ver seção 8.
> 4. **Seis erros do assistente registrados**, todos em texto, nenhum em
>    código — ver a seção 10, que é nova.
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
   valida (`php -l`, `node --check` para JS, leitura do core quando preciso).
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
Release**. *(Pergunta refeita pelo usuário em 28/08 e a resposta continua a
mesma: o zip empacotava mas não conferia; o `git diff` confere.)*

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 28/08 | commit **`02b64d5`**, versão **1.3.16** |
| Versão em homologação | **1.3.16** |
| **Paridade** | ✅ **Estrutural**: a pasta do plugin é a árvore de trabalho do clone. `git status` limpo **é** a prova |
| Arquivos no repositório | **30** (27 do plugin + 3 em `docs/`) — contado em `02b64d5` |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha**. Lição 139 |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local** e **sem PuTTY em uso** |
| Assistente | Não tem SSH nem token. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

O shell do servidor está logado como **root** (`root@debian`). O console do GLPI
recusa root puro, então todo comando de console vai com `sudo -u www-data`.

### Git no servidor

```bash
git config --global user.name "Claudio Morett"
git config --global user.email "claudio.morett@gmail.com"
git config --global --add safe.directory /var/www/html/glpi/plugins/dgoplus
git config --global credential.helper store
git config --global core.pager cat
```

O `safe.directory` é obrigatório: a pasta é do `www-data` e o git roda como root.
Autenticação por **token fine-grained** (Contents: Read and write).

**Depois de todo `git pull`/`checkout`**, rodar
`chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus`.

⚠️ **O `master` avança sem o código mudar** (commit só de `docs/`). Conferir o
delta antes de assumir que a base do bloco anterior ainda é o topo (lição 143).

### Comandos do dia a dia

**Enviar do PC para o servidor (cmd do Windows):**

```cmd
scp -P 2078 "%USERPROFILE%\Downloads\<arquivo>" resolutto@177.87.230.179:/tmp/
```

`-P` maiúsculo é a porta. Aceita vários arquivos numa linha.

⚠️ **Arquivo `.js` não baixa** pelo navegador do usuário (lição 149). Todo JS de
bloco sai com `.txt` no fim e o `cp` no servidor renomeia. **Antes do `scp`,
conferir o nome real:**

```cmd
dir "%USERPROFILE%\Downloads\*<bloco>*"
```

**Aplicar um bloco (`ssh -p 2078 resolutto@177.87.230.179`):**

```bash
md5sum /tmp/<arquivos>              # <<< OBRIGATÓRIO, antes de qualquer cp
cd /var/www/html/glpi/plugins/dgoplus
git pull
cp /tmp/<arquivo> <caminho/no/plugin>
chown -R www-data:www-data /var/www/html/glpi/plugins/dgoplus
git diff --stat && git diff         # <<< a conferência do bloco
git add -A && git commit -m "..." && git push
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

**Reverter:**

```bash
git checkout -- <arquivos>      # descarta a cópia, ainda não commitada
git revert HEAD && git push     # desfaz o commit já empurrado
```

### Os dois logs que interessam

**Erro de PHP e de SQL.** Não há `sql-errors.log` (lição 122):

```bash
tail -n 30 /var/www/html/glpi/files/_log/php-errors.log
```

⚠️ **Caminho sempre completo, nunca abreviado com `...`** — lição 157.

**Status HTTP — e este substitui o F12** (lição 142):

```bash
grep -h "port.php" /var/log/apache2/other_vhosts_access.log | tail -n 20
```

⚠️ **Nem toda recusa vira 403.** Ver a lição 154: o `ajax/port.php` responde 403
porque `applyInput` lança; o `ajax/dgocomment.php` responde **200 com
`denied:true`** porque `applyComment` devolve erro. **Antes de escrever roteiro
que espera um status, ler o endpoint.**

### Topologia web

Apache nas portas 80/443 internamente; acesso externo por
**`177.87.230.179:2077`**. `DocumentRoot /var/www/html/glpi/public` vem de
`conf-enabled/glpi.conf`. **Nada dentro de `plugins/` é alcançável como arquivo
pelo navegador** — 404 com `Set-Cookie: glpi_` é o front controller respondendo.

### Release

**`v1.3.8` publicada em 27/08** com `dgoplus-1.3.8.zip` (177 KB, sha256
`34e1fdd1129792cf4dd500db41ecd674d10580831d07f3f0334527de6ea0ef16`). A `v1.3.2`
continua publicada.

O zip **nasce do commit**:

```bash
cd /var/www/html/glpi/plugins/dgoplus
git tag -a v1.3.X -m "..." && git push origin v1.3.X
git archive --format=zip --prefix=dgoplus/ -o /tmp/dgoplus-1.3.X.zip v1.3.X
sha256sum /tmp/dgoplus-1.3.X.zip
```

**Tags existentes:** `v1.0.0`, `v1.1.0`, `v1.2.0`, `v1.2.1`, `v1.3.0`, `v1.3.1`,
`v1.3.2`, `v1.3.8`. As versões **1.3.3 a 1.3.16 não têm tag**: são degraus
internos da Fase 5. A próxima tag sai quando a Fase 5 fechar.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6, `Tasks list`
2.1.12.

⚠️ O `fields` emite um backtrace de boot no `php-errors.log` (visto em 28/08);
não tem relação com o DGO+.

⚠️ **O `shopmap` tem a mesma trava de nome ambíguo que o 5e trata no DGO+.**

### Quando reinstalar

| Mudou | O que fazer |
|---|---|
| `src/`, `front/`, `ajax/` (PHP) | `cache:clear` + `systemctl restart apache2` |
| `public/` (JS/SVG) | **Ctrl+F5** no navegador |
| `src/Install.php` (schema, direitos) | `plugin:install --force` **e depois** `plugin:activate` |
| **Número de versão no `setup.php`** | Idem (lição 116) |
| Só `docs/` | **Nada.** Commit e pronto |

⚠️ O warning `Plugin "dgoplus" version changed. It has been deactivated` no
`php-errors.log` **é esperado** a cada bump — é a lição 116 acontecendo. Some
depois do `--force` + `activate`.

---

## 2. Fluxo de trabalho vigente

Método **entrega-em-blocos**: um bloco = uma mudança testável de uma sentada.

**Nesta sessão o método foi esticado, de propósito e com acordo:** o usuário
pediu para fechar tudo de uma vez; o assistente recusou o commit único e propôs
**um envio, vários commits, uma validação, um deploy**. Funcionou — cinco blocos
numa sentada, cada um com `git diff` e greps próprios, e nenhum revert.

⚠️ **Mas a sessão longa cobrou preço em qualidade** (seção 10). Se voltar a
acontecer, emitir contexto novo no meio, não no fim.

Toda entrega tem quatro seções fixas: **(1)** o que muda, com a decisão de
reinstalar em negrito na primeira linha; **(2)** o comando `scp` literal **com os
md5 esperados**; **(3)** os comandos de aplicar, com **`git diff` como
conferência**; **(4)** roteiro de teste numerado com resultado esperado, onde ler
o log e como reverter.

### Nome de arquivo entregue leva o bloco

`MapController-c3.php`, `Link-c4a.php`, `dgoplus-5g1b.js.txt`. O `cp` no servidor
renomeia. Sem isso o download colide na pasta Downloads e o `scp` manda o
**antigo** com sucesso aparente (lição 140).

### O repositório é público — usar isso por padrão

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
https://raw.githubusercontent.com/teckcomp/glpi-plugin-dgoplus/master/<arquivo>
```

(`api.github.com` bate no limite anônimo — 403.)

⚠️ **Preferir o `codeload` com o SHA ao `raw`** (lição 132).

**Padrão de trabalho ao preparar um bloco:** baixar o tarball do commit atual,
editar a cópia, validar sintaxe, e **provar por md5 que só os arquivos do escopo
mudaram**. Depois do push, **baixar o commit publicado e conferir os md5 de
novo** — feito nos cinco commits desta sessão.

**Número previsto sai de comando, não de olho** (lições 141, 150 e **155**).
Nesta sessão os `git diff --stat` acertaram nos cinco; **um `grep -c` previsto de
cabeça errou** (lição 155).

**Documento é entrega, e entrega tem quatro seções** (lição 145).

**Número de linha citado em documento é ponteiro, não fato** (lição 144).

### O core do GLPI também é legível

`github.com/glpi-project/glpi` na tag `11.0.6`. Classes com namespace `Glpi\`
ficam em **`src/Glpi/...`**. O schema está em `install/mysql/glpi-empty.sql`.

⚠️ **O CSS do tema NÃO é legível por esse caminho** — três tentativas em 28/08
deram 404 (`templates/components/alerts.html.twig`, `css/includes/_alerts.scss`,
`public/lib/tabler.css`). **Classe CSS não confirmada não entra** (lição 156).

### O sandbox do assistente TEM PHP e Node

`php -l` e `node --check`. **Rodar `apt-get update` e `apt-get install -y
php-cli` como dois comandos separados** (lição 126). Validado com PHP 8.3.6 e
Node v22.22.2. `php -l` **não** pega incompatibilidade de assinatura com a
classe-pai.

### Práticas abolidas

- Reinstalar o plugin "por precaução" a cada bloco.
- Mandar colar `sed`, heredoc ou edição manual de arquivo grande no terminal.
- Zip como veículo de bloco (só Release).
- **`pscp`** — não autentica neste servidor. É `scp`.
- **Entregar arquivo com o nome final** em vez de `<Arquivo>-<bloco>.php`.
- **Entregar JS sem `.txt`** (lição 149).
- Julgar tela sem antes confirmar a versão instalada (lição 114).
- Remontar arquivo a partir do `master` + a descrição do bloco (lição 129).
- **Pedir F12 ao usuário para saber status HTTP.**
- **Prever `grep -c` de cabeça** (lições 150 e 155).
- **Tratar item de escopo escrito no roadmap como decisão tomada** (lição 152).
- **Dica de permissão permanente na moldura da tela** (lição 153).
- **Descrever defeito por analogia com outro arquivo** (lição 154).
- **Usar classe CSS sem confirmar o comportamento dela** (lição 156).
- **Abreviar caminho de arquivo em comando para copiar e colar** (lição 157).
- **Escrever roteiro de teste sem conferir contra o código** (lição 158).

---

## 3. Arquitetura

### O que é do GLPI e o que é do plugin

A DGO **não é um itemtype do plugin**. Cada elemento é um `PassiveDCEquipment`
nativo; o plugin acrescenta a grade de portas, o escopo e os vínculos. O core não
conhece as tabelas do plugin — daí o `PurgeCleaner`.

O escopo é **Localização (nativa) → Piso (intitulado do plugin)**.

### Papéis — a hierarquia física

`Setting::ROLES`, nesta ordem, **é** a hierarquia: `dio` → `dgo` → `cto` → `pto`.
**Quatro degraus bastam.** O splitter fica deliberadamente fora. As entradas
E1–E4 registram mais de uma fibra alimentando o mesmo elemento, e a proporção
(1/8, 1/12, 1/16) vai no campo OBS.

Mapeamento em produção: **um Tipo por papel** — `DIO+`, `DGO+`, `CTO+`, `PTO+`.
Gravado em `glpi_configs`, contexto `plugin:dgoplus`.

`Setting::getRoleOfItem()` devolve o papel de um ativo;
`Setting::roleReceivesFeed()` diz se o papel pode receber entrada (um DIO não
recebe).

⚠️ O painel de produção mostra **1 elemento fora dos papéis configurados**.

### Portas

Uma tabela, dois tipos de linha, separados por `kind`:

- `KIND_GRID` (`grade`) — a matriz tubo × fibra, cores ABNT/EIA.
- `KIND_ENTRY` (`entrada`) — E1 a E4 (`MAX_ENTRIES = 4`), `tube_num = 0`.

`kind` fica **fora** da chave única (lição 112) — a chave é
`(itemtype, items_id, tube_num, fiber_num)`. `Port::applyInput()` é o **ponto
único de gravação**, e é ele que faz `Session::checkRight(UPDATE)`, que **lança**
e produz o 403.

`Port::gridCriteria()` e `Port::entryCriteria()` são os dois recortes.
`Port::statsForDgo()` conta **só a grade** — relevante para o BADGE-C.

**Grade padrão:** `Panel::DEFAULT_TUBES` = 4 e `DEFAULT_FIBERS` = 16 — todo
elemento novo nasce com **64 posições** e encolher exige DELETE (lição 146).
**Fica como está**, por decisão de 28/08.

**Porta sem acoplador** não pode ser usada e **não conta como documentada**.

### Vínculos

`glpi_plugin_dgoplus_links`: **uma linha, dois lados**. Regras fechadas:

- **Sem `is_deleted`.** Recusa apaga a linha.
- **Pendente já ocupa a porta**, nas duas pontas.
- **Hierarquia permissiva**: pode pular nível, nunca subir nem empatar.
  `Link::hierarchyAllows()` compara posição (`$order[$src] < $order[$dst]`), então
  sabe que desceu, **não sabe quanto** — lacuna do 5d.
- **Só vínculo confirmado sobe na trilha** (4e).
- `Link::propose()` é o **ponto único de criação**.
- **Recusar e confirmar pedem o mesmo direito (UPDATE)**, de propósito.
  Desmontar pede DELETE.

**`Link::upstreamLevels($itemtype, $items_id, ?$from_entry_id)`** — desde o 5c o
terceiro parâmetro restringe o **nível 0** a uma entrada. Do nível 1 para cima o
comportamento do 4e continua. Entrada inválida devolve trilha **vazia**, nunca a
do elemento inteiro. Chamador único: `MapController::displayEntryCard()`.

⚠️ **Pendente que envelhece não avisa ninguém.**

### Comentário do elemento

`DgoIdentity::applyComment()` é o **ponto único**, usado pelo POST clássico e
pelo `ajax/dgocomment.php` (lição 47). Grava o campo `comment` **nativo** do
`PassiveDCEquipment`, então aparece no Histórico do ativo.

**Desde o 5g-1b** ele devolve **`denied => true`** quando a recusa é de
permissão de sessão — só nesse ramo, porque só ele autoriza travar a página
inteira. O endpoint repassa a chave; o JS a usa. **A regra continua num lugar
só** — o endpoint não checa direito, de propósito.

### Auto-save — os dois JS, agora ambos corrigidos

**`public/dgoplus.js`** (440 linhas) — o painel da porta. Desde o 5g-1 o
`.catch()` distingue 403 de queda de rede; `permissionDenied` é estado do módulo.

**`public/dgoplus-identity.js`** (362 linhas) — o comentário do ativo. **Desde o
5g-1b:** o `Error` carrega `status`; `data.denied === true` liga
`permissionDenied` e guarda a frase do PHP em `deniedText`; o 403 é tratado
**antes** do `fallbackOnFailure`, com mensagem própria (aqui o 403 vem do
`checkRight(READ)` do endpoint, não do UPDATE).

**Princípio, do bloco 4a:** o formulário continua sendo um POST completo e
válido; se o JS não carregar, o botão Salvar recarrega a página.

`mount()` e `mountComment()` **saem na entrada** se não acharem o `[data-...-flag]`,
e esse elemento só é impresso para quem tem escrita — **sem o direito, o JS nem
se instala** (lição 151).

### Permissão na tela — a regra do 5g-2b

**O painel da porta nomeia o direito; a moldura do mapa fica calada.**

Mensagem de permissão só aparece para quem **esbarrou na recusa**. O lugar de
explicar direito ao administrador é a **aba DGO+ do perfil** — feito no 5g-3.

### Busca e relatório — tabela polimórfica

- Os `jointype` que existem no 11.0.6: `child`, `item_item`, `item_item_revert`,
  `mainitemtype_mainitem`, `itemtype_item`, `itemtype_item_revert`,
  `itemtypeonly`, `custom_condition_only` e o `default`. **Qualquer outro valor
  cai no `default` em silêncio.**
- Para a porta, o jointype certo é **`itemtype_item_revert`**.
- **`specific_itemtype` é obrigatório**: sem ele a coluna volta vazia.

**Search options do Port** (conferidas em `02b64d5`): 1 `code`, 2 `name`,
3 `itemtype`, 5 `tube_num`, 6 `fiber_num`, 7 `comment`, 8 Localização
(`nosearch`), 9 `is_no_coupler`, 10 `kind`, 11 documentado por,
12 `date_documented`, **19 `date_mod`**, 121 `date_creation`.

**`Port::getReportUrl(array $params)`** é o **ponto único da URL do relatório**
desde o PAINEL-1a. Aceita os parâmetros do motor de busca em forma de **array**
(`sort[0]`, `order[0]`, `criteria[0][...]`). O escalar funciona, mas o core o
marca como compatibilidade com links anteriores ao 10.0
(`SearchEngine::prepareDataForSearch`, 11.0.6:346-367).

⚠️ **Observado em tela (28/08):** passando `searchtype=equals` num campo
`datatype string`, a tela renderiza **"contém"**. O recorte fica correto no caso
do `kind` (`grade` não é substring de `entrada`), mas o `equals` não é respeitado
como escrito.

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito próprio
`plugin_dgoplus_port`, matriz de 4 níveis = **15**. Na tela do perfil a aba
chama-se **DGO+** e a linha, **"Portas de DGO"**, com **LER, ATUALIZAR, CRIAR,
DELETE**.

| Ação | Exige hoje |
|---|---|
| Ver mapa, painel, relatório | `plugin_dgoplus_port` READ |
| Documentar porta | `plugin_dgoplus_port` UPDATE |
| Esvaziar porta | `plugin_dgoplus_port` DELETE |
| Propor / confirmar / recusar vínculo | `plugin_dgoplus_port` UPDATE |
| Desmontar vínculo | `plugin_dgoplus_port` DELETE |
| Comentário e OBS do elemento | `plugin_dgoplus_port` UPDATE |
| Criar elemento, fileira, coluna, piso | `plugin_dgoplus_port` CREATE |
| **Qualquer gravação — trava de entidade** | **`Session::haveAccessToEntity()`** |
| **Anexos** | `document` READ+UPDATE+CREATE **e `datacenter` UPDATE** |
| Configurar papéis | `config` UPDATE |

**O acoplamento a `datacenter` acabou.** Os dois greps que provam isso —
**rodados em `02b64d5`**:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

**`Port::parentIsReachable()` é o ponto único da visibilidade do pai**, chamado
em seis lugares:

```php
Session::haveAccessToEntity($parent->getEntityID(), $parent->isRecursive())
```

`getEntityID()` devolve **-1** quando o itemtype não é entity-assign, então a
regra **falha fechado**.

`front/map.php` exige **apenas** `Port::$rightname READ`.

⚠️ **A Fase 5 ainda não chegou à produção.** Quando chegar, os perfis de quem
documenta hoje (Claudio Morett, Kayan Lucas, Pedro s, cristian.b) mudam de
permissão de verdade. **Bloco próprio de deploy, com plano de rollback.**

### Anexos

O cartão usa o formulário do **core** (`Document_Item::showForItem`, chamado em
`MapController::displayDocumentsManager`), e ele pergunta se o usuário pode
**atualizar o ativo** — daí o `datacenter` UPDATE (lição 134).

⚠️ **Mas essa não é a única porta (lição 148):** `CommonDBTM::add()` não faz
checagem de direito. Um endpoint próprio poderia criar `Document` +
`Document_Item` com a checagem que o DGO+ decidir. **O que falta não é
permissão, é tela** — candidato **5i**, e reescrever o cartão inteiro não é
bloco pequeno (medido em 28/08: `Document::canView()` guarda dois pontos).

### Arquivos

**30 no repositório** — 27 do plugin + 3 em `docs/`.

```
dgoplus/
├── setup.php              hooks, menu, botão da ficha, JS — 269 linhas
├── hook.php               instalação / desinstalação
├── README.md              reescrito no commit 2 — 165 linhas
├── logo.png
├── docs/                  README.md, contexto-dgoplus.md, roadmap-dgoplus.md
├── ajax/                  port.php (123), dgocomment.php (52)
├── public/                dgoplus.js (440), dgoplus-identity.js (362), qrcode.js, dgoplus-mark.svg
├── front/                 map.php, port.php (26), pending.php, config.form.php
└── src/                   13 arquivos
    ├── Install.php        schema, direitos, migrações
    ├── Setting.php        papéis e Tipos de cada papel
    ├── Port.php           porta; applyInput, parentIsReachable, getReportUrl — 1120
    ├── Link.php           vínculo; propose, upstreamLevels, hierarchyAllows — 1245
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (rightname = plugin_dgoplus_port)
    ├── MapController.php  a tela do mapa — 3523 linhas
    ├── Dashboard.php      o painel — 1280 linhas
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade, QR e comentário — 381 linhas
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil — 184 linhas
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.16** (commit `02b64d5`, baixado do GitHub e medido
pelo assistente em 28/08):

```
84e5262938e37012506b974897e13725  setup.php                    (269 linhas)
63421003891b3524bc9a96b1ab7dcb99  public/dgoplus.js            (440 linhas)
d58fdb6b783801190a79eb1ace005fca  public/dgoplus-identity.js   (362 linhas)
52ab95366b20809e952972c1c1a9b823  src/Port.php                 (1120 linhas)
966e05e77a62108e6839a93865002985  src/Link.php                 (1245 linhas)
ec73f9f6cd4a35d83fbcf1039cb67fa4  src/MapController.php        (3523 linhas)
36ecd197f374c180a42ef7bbccc47b8c  src/DgoIdentity.php          (381 linhas)
e32167af58e70818a70429e0ac52122e  src/Dashboard.php            (1280 linhas)
f4d2f1d2773e81bfb6486e15371ef816  src/ProfileTab.php           (184 linhas)
b0e4f1837feab5a54d42868e8d88a4b7  ajax/port.php                (123 linhas)
dae5e817600bfdb6db3345cfa0383ea0  ajax/dgocomment.php           (52 linhas)
4b1c3380384313d07614738dbc52bbd5  front/port.php                (26 linhas)
9e68cde24dfd0694f1bf4bc4fdbffd9f  README.md                    (165 linhas)
```

---

## 4. Lições aprendidas

⚠️ **Lacuna, agora com resposta parcial.** O código cita lições numeradas até a
**113**; a lista integral vive no documento original, não recuperado.
**Medido em 28/08:** o `grep` no código devolve **30 lições distintas** — 3, 5,
12, 13, 14, 16, 20, 21, 23, 27, 31, 32, 34, 35, 39, 44, 45, 47, 48, 49, 63, 104,
105, 112, 113, 117, 118, 119, 121, 133 — e **todas já estão listadas abaixo**.
**O caminho (a) da dívida 3 está esgotado**: não há mais nada a recuperar do
código. Só o documento original traria as demais.

| # | Lição |
|---|---|
| 3 | Em `front/` e `ajax/` do GLPI 11 a sessão, o autoload e o `$CFG_GLPI` já estão de pé |
| 5 | `getEntitiesRestrictCriteria()` devolve array para ser **somado** |
| 12 | `$_SERVER['PHP_SELF']` está morto no GLPI 11 para montar URL |
| 13 | Montagem de URL num lugar só, nunca espalhada |
| 14 | **Falha silenciosa custa mais que falha barulhenta.** O mapa mentindo é o defeito mais caro |
| 16 | **Estado vazio nunca fica mudo** |
| 20 | Componente que não cabe na coluna some sem avisar — vai em largura total |
| 21 | Só classes CSS que existem nos templates do 11.0.6. ⚠️ **Reforçada pela 156** |
| 23 | Vermelho do projeto em alfa para o fundo da célula sem acoplador |
| 27 | `outline` seria cortado pelo `overflow` |
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
| 114 | **Homologação pode estar atrás do `master` sem ninguém ter errado** |
| 115 | `-P` maiúsculo para a porta, no `pscp` e no `scp` |
| 116 | **Bump de versão no `setup.php` conta como mudança de instalação.** `--force` + `activate`. *O warning no log é o sintoma* |
| 117 | ~~`can($items_id, READ)` acopla o plugin ao direito do pai~~ ✅ cumprida pelos 5f-3a/b |
| 118 | ~~`$can_write` com CREATE~~ ✅ corrigida pelo 5f-1a |
| 119 | **Mensagem de permissão que não nomeia o direito custa horas.** ⚠️ Delimitada pela 153 |
| 120 | Anexar documento a um ativo exige `datacenter` UPDATE. Ver 134 |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054** |
| 122 | **Não existe `sql-errors.log` no 11.0.6.** Erro de SQL vai para `php-errors.log` como `glpi.CRITICAL` |
| 123 | **O direito Documentos fica na aba Gerência do perfil** |
| 124 | A homologação também pode estar À FRENTE do `master`. *Encerrada pelo clone* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`** |
| 126 | **O sandbox do assistente tem PHP.** `apt-get update` sai com código ≠ 0 — dois comandos separados |
| 127 | **O CSV exportado do GLPI abre torto no Excel por duplo clique** |
| 128 | `pscp` pode falhar com `Remote side unexpectedly closed network connection` |
| 129 | **Arquivo remontado a partir do `master` + descrição NÃO é verificação** |
| 130 | **O GitHub é canônico** |
| 131 | Upload pela web do GitHub cria arquivo novo em silêncio. *Aposentada pelo `git push`* |
| 132 | **`raw.githubusercontent.com` tem cache de CDN** |
| 133 | ✅ Corrigida pelo 5g-1 no `dgoplus.js` e pelo **5g-1b** no `dgoplus-identity.js` |
| 134 | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core |
| 135 | **O direito "Data centers" também fica na aba Gerência** |
| 136 | **A raiz web efetiva vem de `conf-enabled/glpi.conf`** |
| 137 | **Com o clone Git, `git diff` É a conferência do bloco** |
| 138 | **O escopo real de um bloco de permissão está no PONTO ÚNICO** |
| 139 | **O envio é `scp`, não `pscp` — e o servidor recusa senha** |
| 140 | **Nome final colide na pasta Downloads e o `scp` manda o ANTIGO em silêncio** |
| 141 | **Número previsto de `git diff --stat` sai de comando** |
| 142 | **As requisições caem no `other_vhosts_access.log`.** O `Referer` traz `edit=<tubo>-<fibra>` |
| 143 | **Com `docs/` versionado, o HEAD avança sem o código mudar** |
| 144 | **Número de linha em documento é ponteiro, não fato** |
| 145 | **Documento também é entrega** |
| 146 | **Elemento novo nasce com 64 posições e encolher exige DELETE.** Fica como está |
| 147 | **`actionSaveEntryObs` grava a OBS do ELEMENTO.** Nome de método não é especificação |
| 148 | **O formulário de anexo do core exige UPDATE, mas a API não** |
| 149 | **O navegador barra download de `.js`; o `.php` passa** |
| 150 | **`grep -c` conta LINHAS que contêm, não ocorrências** |
| 151 | **Tirar o direito e RECARREGAR não produz 403: produz tela somente-leitura.** O 403 só é alcançável com **aba já aberta cujo direito foi retirado depois** |
| 152 | **Item de escopo escrito no roadmap NÃO é decisão de produto tomada** |
| 153 | **Mensagem de permissão permanente na moldura é ruído; contextual é ajuda** |
| **154** | **Defeito descrito por analogia com outro arquivo é dedução, não leitura.** O v13 dizia que o `dgoplus-identity.js` tinha "o mesmo defeito, mesma correção" do `dgoplus.js`. **Era falso:** o `ajax/port.php` responde 403 porque `Port::applyInput()` usa `checkRight` (que lança); o `ajax/dgocomment.php` responde **200** porque `DgoIdentity::applyComment()` devolve `ok:false`. A tela do comentário **nunca mentiu** — já nomeava o direito. O defeito real era outro (reenvio a cada blur, e `form.submit()` perdendo texto no 403 de READ). **Ler o endpoint antes de descrever o defeito** |
| **155** | **Previsão de `grep -c` errada mesmo com o método certo à mão.** No commit 3 o assistente rodou três greps no sandbox e **deduziu o quarto de cabeça** — previu 5, eram 6, porque o método novo também chamava `Floor::getForLocation()`. É a lição 150 repetida. **Todo número que vai para a seção 3 da entrega sai de comando, sem exceção** |
| **156** | **Classe CSS não confirmada não entra.** O 5g-3 usou `alert alert-info` e o conteúdo renderizou em COLUNAS lado a lado. A hipótese (o `.alert` do tema é container flex) **não foi verificável**: os três caminhos tentados no repositório do core deram 404. A correção não dependeu da hipótese — trocou por `card`, que o plugin já usa em dezenas de lugares. **Na dúvida, usar o recipiente que o próprio projeto já provou** |
| **157** | **Caminho de arquivo em comando nunca vai abreviado.** O roteiro do 4a trazia `tail -n 30 .../php-errors.log`; o usuário colou e recebeu "arquivo inexistente". O `...` era abreviação de leitura, não de terminal. **Todo comando entregue tem que ser colável como está** |
| **158** | **Roteiro de teste também se confere contra o código.** O passo 2 do 4a mandava abrir o card de uma entrada "sem pai confirmado" — mas `displayEntryCard()` sai antes se não houver vínculo, então esse card não existe. O passo era impossível. **O roteiro descreve telas: se a tela não foi lida, o passo é chute** |

**Armadilhas do GLPI 11 que valem como regra permanente:**

- **CSRF**: o core valida POST sozinho — nunca `Session::checkCSRF` manual.
- **Iterator: `COUNT` + `GROUPBY` juntos descartam os campos do `SELECT`.**
- Todo `WHERE`/`ORDER` com JOIN precisa de **coluna qualificada**.
- **Filtro nunca pode sumir**: lista de ids vazia vira `[0]`.
- JSON em `<script type="application/json">` exige as flags HEX.
- Endpoint `ajax/` **não se testa pela URL direta**.
- `php -l` não pega incompatibilidade de assinatura com a classe-pai.
- `Dropdown::showFromArray` renderiza **select2**, que esconde o `<select>` real.
- Classes com namespace `Glpi\` moram em `src/Glpi/...` no repositório do core.
- **`Session::checkRight` lança; devolver array não lança.** O status HTTP que o
  navegador vê depende de qual dos dois o caminho usa (lição 154).

---

## 5. Estado por bloco

| Bloco | Entrega | Estado |
|---|---|---|
| 1 | Schema, classes, direito, menu, relatório | Fechado |
| ⚠️ 2, 3a–3f | Não citados no código do 1.3.x | A confirmar |
| 3g–3t | Piso, escopo, impacto, atalho, Tipos, entidade, purge, carimbo, identidade | Fechados |
| 4a-1/2/3 | Auto-save; papel no painel; abas e filtro por papel | Fechado |
| 4b-1/2 | `kind` na porta; tabela de vínculos + entradas E1–E4 | Fechado |
| 4c/4c-2 | Propor, confirmar, recusar, desmontar | Fechado |
| 4d | Página e cartão de pendentes | Fechado |
| 4e | Trilha de alimentação | Fechado — ajustado pelo 5c |
| 4g / 4h | Bump 1.3.0; `isDgo()` → `isMapped()` | Fechados |
| 5-sync | Homologação de 1.3.0 para 1.3.1 | Fechado (22/08) |
| 5a | Escopo Localização → Piso no seletor de destino | Fechado e validado (23/08), 1.3.1 |
| 5h | JOIN da coluna Localização no relatório | Fechado (27/08), 1.3.2 |
| DOC / GIT-1 / GIT-2 / REL / REL-2 | `docs/`, clone, push, tags e Releases | Fechados (27/08) |
| 5f-1a … 5f-3b | A frente de permissões inteira | Fechados e validados (27/08), até 1.3.8 |
| 5g-1 | Auto-save da porta distingue 403 de falha de rede | Fechado e validado (28/08), 1.3.9 |
| 5g-2 / 5g-2b | Telas nomeiam o direito; dicas saem da moldura | Fechados e validados (28/08), 1.3.11 |
| **5g-1b** | **Auto-save do comentário não reenvia recusa** | **Fechado (28/08), 1.3.12, `15d0c30`.** ⚠️ Fumaça validada; a recusa em si **não foi exercitada** |
| **5g-3** | **Nota de permissões na aba DGO+ do perfil** | **Fechado e validado em tela (28/08), 1.3.13 + 2b** |
| **PAINEL-1a** | **"Ver todos" em Atividade recente** | **Fechado e validado em tela (28/08), 1.3.13** |
| **README** | **Reescrito** | **Fechado (28/08), 1.3.13** |
| **2b** | **Nota vira card abaixo da matriz; relatório ganha volta ao mapa** | **Fechado e validado (28/08), 1.3.14, `327c62c`** |
| **5b** | **Seletor de piso lista só pisos com elemento** | **Fechado e validado em tela (28/08), 1.3.15, `e3faec0`** |
| **5e** | **Desambiguação por colisão no seletor de destino** | **Entregue (28/08), 1.3.15.** ⚠️ **Não exercitado** |
| **5c** | **Trilha parte da entrada, não do elemento** | **Entregue (28/08), 1.3.16, `02b64d5`.** ⚠️ Não regressão validada; **a correção não foi exercitada** |

---

## 6. Dívidas conhecidas

1. ~~README desatualizado~~ ✅ **QUITADA (28/08).**
2. **Sem catálogo de tradução**: interface pt-BR fixa. ⚠️ **Decisão de produto
   pendente:** demanda real ou higiene? Tocaria os 27 arquivos.
3. **Lista integral de lições (1–113)** não incorporada. ⚠️ **O caminho barato
   está esgotado** (medido em 28/08: o código só cita as 30 já listadas). Resta
   buscar o documento original nas conversas antigas.
4. ~~Sem tag nem Release~~ ✅ **QUITADA (27/08).**
5. **A skill `glpi-plugin-teckcomp` está desatualizada**: host, usuário, porta,
   `pscp` → `scp`, e a ordem de entrega com `git diff`. **Aprovada** — bloco SKILL.
6. ~~Texto fala de "Desmontar" sem o botão existir~~ ✅ **QUITADA (28/08).**

---

## 7. Medições de campo

⚠️ **Existem DUAS bases, e confundi-las é o erro mais caro desta seção.**

### Produção — o parque real (28/08)

| | |
|---|---|
| Elementos | **159** — DIO 3, DGO 67, CTO 88, PTO 1. **2 na lixeira** |
| ⚠️ Papel | **1 elemento fora dos papéis configurados** |
| Portas | **4944** — 2220 documentadas, 2724 livres. **42 na lixeira** |
| Ocupação geral | **44,9%** |
| Localizações | **9** |
| Sem documentação | 57 elementos (DGO 28, CTO 29) |

Por localização: `Palladium Umuarama` 91,0% · `Jockey Plaza` 81,1% · `Gravataí`
75,6% · `Plaza Campos Gerais` 68,4% · `Pato Branco` 61,9% · `Itajaí` 57,6% ·
`Petropolis` 38,3% · `Estacao` 5,0% · **`Palladium Ctba` 2,5% (49/1887)**.

Documentadores ativos: Claudio Morett, Kayan Lucas, Pedro s, cristian.b.

### Homologação — a base de teste (28/08, painel lido em tela)

| | |
|---|---|
| Elementos | **36** — DIO 5, DGO 14, CTO 11, PTO 6. **nenhum na lixeira** |
| Sem documentação | **18** — DIO 1, DGO 5, CTO 7, PTO 5 |
| Portas | **1889** — 36 documentadas, 1853 livres |
| Ocupação geral | **1,9%** |
| Localizações | **9** |

Por localização: `A+` 0,0% (0/96) · `Bio qualquer > bio001` 1,5% (3/191) ·
`Outlet Porto Belo` 3,8% (6/154) · `Plaza Campos Gerais` 2,2% (9/394) ·
`shopping estação` 1,5% (7/473) · `Shopping itajai/Bigode - 000` 1,1% (2/178) ·
`shopping palladium` 5,9% (8/128) · `Shopping Pato Branco` 0,0% (0/64) ·
`Shopping Ventura > DGO Cristian` 0,6% (1/175).

**`Outlet Porto Belo`, piso `MALL - PORTO BELO`** — o cenário de teste da sessão:

- **DGO 01**, badge **6 de 16**: F1.01 → E1, F1.02 `1202`, F1.03 `1214`,
  F1.04 → E2, F1.05 `2153-01…`, F1.06 `2153` (⚠️ com acoplador desmarcado E
  vínculo confirmado → E3), F1.07 livre, F1.08 e F2.09–F2.16 livres.
- **DGO 01 - PORTO BELO**, 0 documentadas.
- **CTO 01** — ⚠️ **mudou de papel durante a sessão** (CTO → PTO), por teste.
- **CTO TESTE 5f2b** — ⚠️ **foi renomeado para `CTO 01`** durante o teste do 5e.
  **Agora existem dois `CTO 01` na mesma localização e no mesmo piso**, que é
  exatamente o caso que o 5e trata e que a trava do 5e-2 vai impedir dali em
  diante. **64 portas mortas, 3,4% da base** — continua na fila de purga.
- **PISO VAZIO TESTE** — piso criado em 28/08 para validar o 5b. Sem elementos,
  de propósito. **Pode ser removido.**

**Vínculos pendentes:** `CTO01 → PTO 4 · E3` (cristian.b) e
`DIO 001 → CTO 001 · E2` (Claudio Morett).

Perfil de teste: **Tecnicos N1, ID 12**; usuário `teste.001`.

---

## 8. Decisões negativas registradas

| Ideia | Decisão | Motivo |
|---|---|---|
| Atribuição de piso em lote | **Descartada** | O piso não vai ser preenchido em massa |
| Esconder o seletor de piso | **Descartada** | O seletor fica; a meia-medida (5b) resolveu |
| Alerta de salto de degrau só no JS | **Descartada** | Exceção que passa batido vira topologia errada |
| Proporção do splitter como campo estruturado | **Descartada** | Já cabe no OBS |
| Splitter como papel na hierarquia | **Descartada** | É componente da caixa, não elo da cadeia |
| Importação CSV de portas | **Adiada** | Não há fonte de dados |
| Criar Localização pelo direito do DGO+ | **Descartada** | `Location` é dropdown do GLPI inteiro |
| Excluir elemento pelo direito do DGO+ | **Descartada** | Purgar ativo é do admin |
| Corrigir a acentuação do CSV no plugin | **Descartada** | O relatório é tela do core |
| Anexo pelo técnico **pelo formulário do core** | **Descartada** | O formulário é do core |
| Anexo pelo técnico **por formulário próprio** | ⚠️ **REABERTA** | Lição 148. Candidato **5i** |
| Documentos versionados dentro do repositório | **Descartada** | O histórico é o Git |
| Exigir DELETE para recusar vínculo | **Descartada (4c)** | Recusar e confirmar são a mesma resposta |
| `pscp` como veículo de envio | **Descartada** | Não autentica (lição 139) |
| Tirar `$dgo` da assinatura de `canWriteComment` | **Descartada** | Fora do escopo de bloco de permissão |
| Grade padrão por papel | **Descartada (28/08)** | A solução é operacional |
| Entradas na conta da ocupação geral ("variante B") | **Descartada (28/08)** | Mudaria o significado dos 44,9% |
| Item de roadmap para o estado do perfil de teste | **Descartada (28/08)** | É do administrador |
| "Ver todos" no cartão de pendentes | **Descartada (28/08)** | Já existe |
| Dica de permissão abaixo da grade / na faixa de busca | **Descartadas (28/08, 5g-2b)** | Lição 153 |
| **Commit único para "fechar tudo de uma vez"** | **Descartada (28/08)** | Dez mudanças num diff só = `git revert` joga fora as dez sem dizer qual quebrou. **Contraproposta aceita:** um envio, vários commits, uma validação, um deploy |
| **`checkRight(UPDATE)` dentro do `ajax/dgocomment.php`** | **Descartada (28/08, 5g-1b)** | Seria uma linha a menos, mas criaria uma **segunda sede da regra**. O ponto único classifica (`denied`), o endpoint repassa |
| **Trava de duplicados que barre também na ficha nativa** | **Impossível, não descartada** | A ficha do ativo, o `datainjection` e o SQL não passam pelo plugin. **A trava cobre a criação pelo mapa; o texto dela não pode prometer que duplicados não existem** |

### Decisões de produto tomadas em 28/08

**BADGE-C · variante C** — a badge do elemento mostra **dois contadores lado a
lado**: `0/16 grade` e `2/4 entradas`. Não mistura os números e **não mexe na
ocupação geral**. A linha de entradas só aparece para papéis que podem receber
(`Setting::roleReceivesFeed()`), e **some por regra, não por acaso**.
⚠️ Escopo medido: toca `Port::statsForDgo()` (só conta grade hoje),
`MapController::renderBadges()` (público, o AJAX reescreve) e `ajax/port.php`.

**Contador de entradas nos cards do painel · SEPARADO** — os cards "Ocupação
geral" e "Portas livres" ganham contagem de entradas **à parte**. A ocupação
continua sendo `36 de 1889`. É a mesma contagem do BADGE-C, em outro lugar —
por isso os dois andam no mesmo bloco.

**5d · confirmar em dois tempos** — vínculo que pula degrau não grava na
primeira tentativa: devolve "isso pula os níveis X e Y, confirma?" e um segundo
envio grava. ⚠️ **O trabalho real é fazer destino e entrada sobreviverem ao
redirect** — hoje ele leva só `edit=<tubo>-<fibra>`. ⚠️ **Duas limitações
aceitas:** o marcador viaja no POST e pode ser forjado (quem forjaria já tem
UPDATE); e nada fica registrado depois — o vínculo com salto fica idêntico a um
normal no banco.

**5e-2 · rótulo único + trava de duplicados** — ⚠️ **APROVADO MAS NÃO
DETALHADO. O usuário pediu explicitamente para discutir antes de qualquer
código.** O que já está fechado: a trava existe, e o critério é **nome +
localização**, independente de papel e de piso (`CTO 01 (CTO)` e `CTO 01 (PTO)`
na mesma localização = erro). O que falta discutir: **como** o rótulo
desambigua em cada tela (o 5e usa `#id`, mas piso ou papel podem informar mais),
**onde** aplicar (são 8 pontos de impressão, e sufixo em aba estreita pode
quebrar layout — lição 20), **o que a trava faz com os duplicados que já
existem**, e **o texto da recusa**.
⚠️ **Medido:** o nome do elemento é impresso em pelo menos 8 lugares
(`MapController` 1340, 1413, 1683, 1708, 2041, 2764, 2831, 2973, 3048;
`Dashboard` 271; `Pending`). **Oito cópias da regra é o que os pontos únicos
existem para evitar** — o caminho é um método único de rótulo.

---

## 9. Próximo passo imediato

1. **Validar o que ficou entregue mas não exercitado.** São três, e todos
   precisam de cenário montado:
   - **5g-1b**: tirar ATUALIZAR **com a aba já aberta** (lição 151), digitar no
     comentário e sair do campo 3×. Esperado: a frase se repete e **sai uma
     única linha** `POST … 200 … dgocomment.php` no
     `/var/log/apache2/other_vhosts_access.log`.
   - **5e**: abrir porta livre da DGO 01 → seção **Alimenta** → o dropdown de
     destino deve mostrar `CTO 01 (PTO) #<id>` **duas vezes, com ids
     diferentes** (os dois `CTO 01` existem agora).
   - **5c**: desmontar a E2 da `CTO 01`, propor da `DGO 01 - PORTO BELO` F1.01
     para `CTO 01` E2, confirmar. Depois: card **E1** mostra só `DGO 01`; card
     **E2** mostra só `DGO 01 - PORTO BELO`. Antes do 5c os dois cards
     mostrariam os dois pais separados por `+`.
2. **Commit 4b — bloco 5d**, confirmação em dois tempos.
3. **Commit 5 — BADGE-C + contador de entradas separado**, que são a mesma
   contagem em dois lugares.
4. **5e-2 — DISCUTIR ANTES DE CODAR.** Ver a seção 8.
5. **Higiene**: purgar o `CTO TESTE 5f2b` (agora chamado `CTO 01`), remover o
   `PISO VAZIO TESTE`, e devolver o papel original da `CTO 01`.
6. **SKILL**, **5h-2**, **5i**, e o **bloco de deploy em produção** (com
   rollback próprio).
7. **REV** — revisão competitiva, ao fim de tudo.

---

## 10. O que correu mal do lado do assistente — seção nova

Existe porque o usuário perguntou, em 28/08, se deveria se preocupar com a
frequência de erros. A resposta honesta exige o registro.

**Seis erros numa sessão, e o padrão importa mais que a contagem: nenhum foi em
código, todos foram em texto.**

| # | Erro | Quem pegou |
|---|---|---|
| 1 | Comentário no `ProfileTab` citando "as três marcadas com ✅" — não havia nenhuma | O próprio assistente, relendo o diff |
| 2 | `alert alert-info` sem confirmar o comportamento → layout em colunas | O usuário, na tela |
| 3 | `grep -c` previsto de cabeça: 5 quando eram 6 | O usuário, rodando |
| 4 | Passo de roteiro descrevendo um teste impossível | O usuário, tentando executar |
| 5 | Caminho de log abreviado (`.../php-errors.log`) num comando para colar | O usuário, colando |
| 6 | Registrou "trava descartada" quando o usuário disse o oposto | O usuário, corrigindo |

**Por que só em texto:** o código passa por `php -l`, `node --check`, md5 e
`git diff` — quatro validadores. O texto ao redor não passa por nenhum.

**Fator provável:** a sessão foi longa demais — seis commits preparados, dezenas
de leituras, e o contexto na base nove versões atrasado. **A regra que sai
disso: emitir contexto novo no meio da sessão longa, não no fim.**

**O que o processo provou:** os seis foram pegos, nenhum chegou ao `master`
errado, nenhum dado foi gravado errado, nenhum bloco precisou de revert. O
`md5sum` antes do `cp`, o `git diff` como conferência e o roteiro em tela
existem porque o assistente erra — **não afrouxar nenhum dos três**.
