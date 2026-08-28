# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v13 — 28/08/2026. Substitui o v12 integralmente.
> Emitido ao fim dos blocos **5g-2** e **5g-2b**. Versão **1.3.11**, commit
> **`560fb64`**.
>
> **O que o v13 traz de novo em relação ao v12:**
>
> 1. **O 5g-2 fechou e o 5g-2b desfez metade dele — de propósito.** A última
>    tarja muda do plugin nomeia o direito, e a dívida 6 está quitada. Mas as
>    duas dicas que o 5g-2 pôs na **moldura do mapa** foram recusadas ao serem
>    vistas em tela e removidas no mesmo dia. Isso virou regra de produto.
> 2. **A pendência 10 está respondida, e a resposta é SIM.** O
>    `public/dgoplus-identity.js` tem exatamente o mesmo defeito que o 5g-1
>    corrigiu no `dgoplus.js`. **O bloco 5g-1b existe e é o próximo.**
> 3. **Duas lições novas: 152 e 153.** A 153 é a regra que evita repetir o
>    erro do 5g-2.
> 4. **O estado da DGO 01 na homologação mudou sem explicação registrada** —
>    ver a seção 7.
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
Release**.

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 28/08 | commit **`560fb64`**, versão **1.3.11** |
| Versão em homologação | **1.3.11** |
| **Paridade** | ✅ **Estrutural**: a pasta do plugin é a árvore de trabalho do clone. `git status` limpo **é** a prova |
| Arquivos no repositório | **30** (27 do plugin + 3 em `docs/`) — conferido em `560fb64` |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha** (`server sent: publickey`). Lição 139 |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local** e **sem PuTTY em uso** |
| Assistente | Não tem SSH nem token. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

> O endereço `192.168.1.50` do contexto v1 (e da skill `glpi-plugin-teckcomp`)
> está **morto**. A skill não foi atualizada — ao lê-la, substituir host e usuário,
> acrescentar a porta e **trocar `pscp` por `scp`**.

O shell do servidor está logado como **root** (`root@debian`). O console do GLPI
recusa root puro, então todo comando de console vai com `sudo -u www-data`.

### Git no servidor

Clone criado em 27/08. Configuração aplicada:

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
bloco anterior ainda é o topo (lição 143). *Aconteceu de novo em 28/08: o v12
apontava `f94dbe5`, o HEAD real era `f6a1c78`, e o delta eram só os dois
documentos. O assistente conferiu por `diff -rq` antes de usar a base — a
terceira vez que essa conferência se paga.*

### Comandos do dia a dia

**Enviar arquivo do PC para o servidor (cmd do Windows) — `scp`, não `pscp`:**

```cmd
scp -P 2078 "%USERPROFILE%\Downloads\<arquivo>" resolutto@177.87.230.179:/tmp/
```

`-P` maiúsculo é a porta. Aceita vários arquivos numa linha só. **Não converte
quebra de linha** — md5 idêntico dos dois lados, verificado.

⚠️ **Arquivo `.js` não baixa** pelo navegador do usuário (lição 149). Todo JS de
bloco sai com `.txt` no fim (`dgoplus-5g1b.js.txt`) e o `cp` no servidor
renomeia. O `.php` baixa normalmente. **Antes do `scp`, conferir o nome real:**

```cmd
dir "%USERPROFILE%\Downloads\*<bloco>*"
```

**Trazer do servidor para o PC:**

```cmd
scp -P 2078 resolutto@177.87.230.179:/caminho/arquivo "%USERPROFILE%\Downloads\arquivo"
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
grep -h "port.php" /var/log/apache2/other_vhosts_access.log | tail -n 20
```

O `Referer` traz a URL do mapa com `edit=<tubo>-<fibra>`, então dá para saber
**qual célula** gerou cada requisição sem perguntar ao usuário. **Foi este log
que validou o 5g-1**: uma única linha `POST … 403 … edit=1-7` para três
disparos de auto-save. **Para o 5g-1b, o alvo é `dgocomment.php`.**

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

**`v1.3.8` publicada em 27/08** — o marco da Fase 5 — com `dgoplus-1.3.8.zip`
anexado (177 KB, 30 arquivos, sha256
`34e1fdd1129792cf4dd500db41ecd674d10580831d07f3f0334527de6ea0ef16`).

✅ **Proveniência fechada pelo assistente**: o zip foi baixado da Release, o
sha256 bateu com o que o servidor gerou, e o `diff -rq` contra o commit
`38018e3` não acusou nenhuma diferença.

A `v1.3.2` continua publicada (168 KB, sha256
`fd42f3a5eb0adf33a8707a59bd2b32c2495070db8734ce477e1d7eb381518752`).

O zip **nasce do commit**, nunca de pasta montada à mão:

```bash
cd /var/www/html/glpi/plugins/dgoplus
git tag -a v1.3.X -m "..." && git push origin v1.3.X
git archive --format=zip --prefix=dgoplus/ -o /tmp/dgoplus-1.3.X.zip v1.3.X
sha256sum /tmp/dgoplus-1.3.X.zip
```

Depois: `scp` do zip para o PC e anexar em
`github.com/teckcomp/glpi-plugin-dgoplus/releases/new`, escolhendo a tag.

**Tags existentes** (conferidas por `ls-remote --tags` em 27/08): `v1.0.0`,
`v1.1.0`, `v1.2.0`, `v1.2.1`, `v1.3.0`, `v1.3.1`, `v1.3.2`, `v1.3.8`.

As versões **1.3.3 a 1.3.11 não têm tag**: são degraus internos da Fase 5, e o
histórico delas é o Git. Release é artefato de instalação, não registro de
história. A próxima tag sai quando a Fase 5 fechar.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6, `Tasks list`
2.1.12.

⚠️ **O `shopmap` tem a mesma trava de nome ambíguo que o 5e trata no DGO+**
(relatado em 28/08). Isso muda a natureza do 5e: a solução deve ser um **padrão
de desambiguação**, não um remendo local.

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
que aconteceu com o 5f-1, o 5f-2 e o **5g** (5g-1, **5g-1b**, 5g-2, **5g-2b**,
5g-3).

Toda entrega tem quatro seções fixas: **(1)** o que muda, com a decisão de
reinstalar em negrito na primeira linha; **(2)** o comando `scp` literal **com os
md5 esperados**; **(3)** os comandos de aplicar, com **`git diff` como
conferência**; **(4)** roteiro de teste numerado com resultado esperado, onde ler
o log e como reverter.

### Nome de arquivo entregue leva o bloco

**Todo arquivo de bloco sai com o bloco no nome** — `MapController-5g2b.php`,
`dgoplus-5g1.js.txt` — e o `cp` no servidor é que renomeia. Sem isso o download
colide com o do bloco anterior na pasta Downloads, o navegador salva como
`Port_1.php`, e o `scp` manda o **antigo** com sucesso aparente (lição 140).

**JS sai sempre com `.txt` no fim** (lição 149).

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
commit atual, editar a cópia, validar sintaxe, e **provar por md5 que só os
arquivos do escopo mudaram** antes de entregar. Depois do push, **baixar o commit
publicado e conferir os md5 de novo** — feito no 5f-1b, 5f-2a, 5g-1, **5g-2 e
5g-2b**, e é o que fecha a proveniência.

**Número previsto sai de comando, não de olho** (lições 141 e 150): `git init`
sobre o tarball do commit, copiar os arquivos do bloco por cima, rodar o `stat`
**e os `grep -c`** de verdade. No 5g-2 (`+56 −10`) e no 5g-2b (`+3 −35`) os dois
foram medidos e os dois acertaram.

**Documento é entrega, e entrega tem quatro seções** (lição 145): o contexto e o
roadmap também saem com o comando `scp` literal, nunca só com os comandos do
servidor.

**Número de linha citado em documento é ponteiro, não fato** (lição 144).
Reconferir por `grep -n` no commit do dia, sempre.

### O core do GLPI também é legível

`github.com/glpi-project/glpi` na **tag `11.0.6`**, pelos mesmos raw URLs:

- Classes com namespace `Glpi\` ficam em **`src/Glpi/...`**, não em `src/...`.
- O schema completo está em `install/mysql/glpi-empty.sql` — forma mais rápida de
  confirmar se uma coluna existe, sem acesso ao banco.

### O sandbox do assistente TEM PHP e Node

`php -l` e `node --check` são possíveis. **Rodar `apt-get update` e
`apt-get install -y php-cli` como dois comandos separados** — o `update` sai com
código ≠ 0 e encadear com `&&` faz o `install` nunca rodar (lição 126). Validado
com PHP 8.3.6 e Node v22.22.2. **Todo arquivo entregue passa pelo validador da
sua linguagem antes de sair.** Continua valendo: `php -l` **não** pega
incompatibilidade de assinatura com a classe-pai.

Não há `sudo` no sandbox (já é root) e a homologação é inalcançável de lá.

### Práticas abolidas

- Reinstalar o plugin "por precaução" a cada bloco.
- Mandar colar `sed`, heredoc ou edição manual de arquivo grande no terminal.
- Zip como veículo de bloco (só Release).
- **`pscp`** — não autentica neste servidor. É `scp`.
- **Entregar arquivo com o nome final** (`Port.php`) em vez de `Port-<bloco>.php`.
- **Entregar JS sem `.txt`** — não baixa (lição 149).
- **Upload pela web do GitHub** — substituído pelo `git push`.
- Julgar tela sem antes confirmar a versão instalada (lição 114).
- Remontar arquivo a partir do `master` + a descrição do bloco (lição 129).
- **Ritual de `md5sum` dos 27 arquivos** — `git status` faz isso melhor.
- **Pedir F12 ao usuário para saber status HTTP** — o `other_vhosts_access.log` diz.
- **Reaproveitar número de linha de documento antigo** (lição 144).
- **Prever `grep -c` de cabeça** (lição 150).
- **Tratar item de escopo escrito no roadmap como decisão de produto tomada**
  (lição 152).
- **Dica de permissão permanente na moldura da tela** (lição 153).

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

⚠️ **O painel de produção mostra "1 elemento fora dos papéis configurados"**
(visto em 28/08). Papel nulo não participa da hierarquia — com o parque
crescendo, isso tende a crescer junto.

### Portas

Uma tabela, dois tipos de linha, separados por `kind`:

- `KIND_GRID` (`grade`) — a matriz tubo × fibra, cores ABNT/EIA.
- `KIND_ENTRY` (`entrada`) — E1 a E4 (`MAX_ENTRIES = 4`), `tube_num = 0`.

`kind` fica **fora** da chave única (lição 112) — a chave é
`(itemtype, items_id, tube_num, fiber_num)`. `Port::applyInput()` é o **ponto
único de gravação**.

**Grade padrão:** `Panel::DEFAULT_TUBES` = 4 e `DEFAULT_FIBERS` = 16, então todo
elemento novo nasce com **64 posições** e encolher exige DELETE (lição 146).
**Decisão de produto de 28/08: fica como está** — o administrador do ambiente
resolve concedendo DELETE a quem cria. Ver seção 8.

**Porta sem acoplador** não pode ser usada e **não conta como documentada** — a
tela diz isso explicitamente no painel da porta.

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

⚠️ **Pendente que envelhece não avisa ninguém.** Em 28/08 a homologação tinha um
vínculo pendente **há 7 dias** (`CTO01 → PTO 4 · E3`, por cristian.b), segurando
duas portas nas duas pontas. Não é defeito — é o modelo funcionando — mas virou
candidato no roadmap.

### Comentário do elemento

`DgoIdentity::applyComment()` é o **ponto único**, usado pelo POST clássico
(`MapController::actionSaveDgoComment`) e pelo `ajax/dgocomment.php` — os dois não
podem divergir (lição 47). Grava o campo `comment` **nativo** do
`PassiveDCEquipment` por `CommonDBTM::update()`, então aparece na ficha do ativo
e no Histórico dele.

Quem decide o direito é `DgoIdentity::canWriteComment()`, **um método com dois
chamadores** (tela e ponto único). Desde o 5f-2a ele pergunta pelo direito do
plugin.

⚠️ **O auto-save deste cartão ainda mente** — ver a próxima subseção.

### Auto-save — dois JS, e só um foi corrigido

**`public/dgoplus.js`** (440 linhas em `560fb64`) — o painel da porta.
**Princípio, do bloco 4a:** o formulário continua sendo um POST completo e
válido; se o JS não carregar, o botão Salvar recarrega a página como antes.

`mount(form)` **sai na entrada se não achar `[data-dgoplus-flag]`** — e esse
elemento só é impresso para quem tem direito de escrita. Consequência, que é a
lição 151: **sem o direito, o JS nem se instala.**

**Desde o 5g-1** o `.catch()` distingue o 403 da queda de rede:

- o `Error` lançado no `!response.ok` **carrega `status`**;
- 403 é tratado **antes** do `fallbackOnFailure` — o `form.submit()` não roda,
  porque tomaria o mesmo 403 e ainda perderia o que foi digitado;
- `permissionDenied` é **estado do módulo, não da célula**: negado numa porta,
  negado em todas as da página, porque o direito é da sessão;
- com ele ligado, `save()` **não reenvia — mas repete a mensagem** (lição 16).

⚠️ **`public/dgoplus-identity.js`** (306 linhas em `560fb64`) — o comentário do
ativo. **Tem exatamente o defeito que o 5g-1 corrigiu no outro arquivo**, lido e
confirmado em 28/08:

| Onde | O que faz hoje |
|---|---|
| `mountComment()` | mesma convenção: sai na entrada se não achar `[data-dgoplus-dgo-flag]` (impresso em `DgoIdentity.php:288`) |
| `.then(response)` | `throw new Error('HTTP ' + response.status)` — **o status é perdido na string** |
| `.catch()` | não distingue nada; com `fallbackOnFailure` chama `form.submit()` |
| mensagem | *"Falha ao salvar. Use o botão Salvar."* — a mesma mentira do 1.3.8 |

**Isso é o bloco 5g-1b**, e é o próximo. O endpoint é `ajax/dgocomment.php`
(45 linhas).

### Permissão na tela — a regra que o 5g-2b instaurou

**O painel da porta nomeia o direito; a moldura do mapa fica calada.**

Mensagem de permissão só aparece para quem **esbarrou na recusa** — clicou numa
porta e encontrou campo bloqueado, ou abriu um vínculo confirmado sem ter o botão
Desmontar. Banner permanente na moldura da tela (abaixo da grade, na faixa de
busca) é **ruído carregado em toda abertura**, para um estado que não é erro.

O 5g-2 pôs quatro mensagens; o 5g-2b removeu as duas da moldura no mesmo dia,
por decisão de produto tomada com a tela na frente. **As duas que ficaram estão
ambas dentro do painel da porta.** O lugar de explicar direito ao administrador é
a **aba DGO+ do perfil** — bloco 5g-3.

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
no `glpi-empty.sql` do core, não por `DESCRIBE`).

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito próprio
`plugin_dgoplus_port`, matriz de 4 níveis = **15**. Na tela do perfil a aba
chama-se **DGO+** e a linha, **"Portas de DGO"**, com as colunas **LER,
ATUALIZAR, CRIAR, DELETE** (conferido em tela, 28/08).

**Como o direito se comporta em 1.3.11** — os números de linha abaixo vêm do
commit `f94dbe5` e **envelheceram no `MapController.php`**, que perdeu 32 linhas
entre o 5g-2 e o 5g-2b. **Reconferir por `grep -n` antes de usar** (lição 144).

| Ação | Exige hoje | Onde |
|---|---|---|
| Ver mapa, painel, relatório | `plugin_dgoplus_port` READ | `front/map.php` |
| Documentar porta (existente ou não) | `plugin_dgoplus_port` UPDATE ✅ 5f-1a | `Port.php:542` e `:550` |
| Esvaziar porta (volta a livre) | `plugin_dgoplus_port` DELETE | `Port.php:480` |
| Propor vínculo | `plugin_dgoplus_port` UPDATE ✅ 5f-1b | `Link.php:439` |
| Confirmar / recusar vínculo | `plugin_dgoplus_port` UPDATE | `Link.php:484`, `:526` |
| Desmontar vínculo | `plugin_dgoplus_port` DELETE | `Link.php:565`; tela em `$can_dismantle` |
| Comentário do elemento | `plugin_dgoplus_port` UPDATE ✅ 5f-2a | `DgoIdentity.php:227` |
| OBS do elemento (faixa das entradas) | `plugin_dgoplus_port` UPDATE | `MapController::actionSaveEntryObs` |
| Criar elemento pelo mapa | `plugin_dgoplus_port` CREATE ✅ 5f-2b | POST e `displayDgoTabs` |
| Fileira / coluna / piso | `plugin_dgoplus_port` CREATE | `Floor::$rightname` |
| Esvaziar fileira / coluna | `plugin_dgoplus_port` DELETE | `MapController` |
| **Qualquer gravação — a trava de entidade** | **`Session::haveAccessToEntity()`** ✅ 5f-3a/b | `Port::parentIsReachable`, `Port.php:359` |
| **Anexos** | `document` READ+UPDATE+CREATE **e `datacenter` UPDATE** | Formulário do core — ver "Anexos" |
| Configurar papéis | `config` UPDATE | `MapController` |

**O acoplamento a `datacenter` acabou.** Os dois greps que provam isso, e que
valem como conferência de qualquer bloco futuro — **rodados em `560fb64`**:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

**`Port::parentIsReachable()` é o ponto único da visibilidade do pai** — nasceu no
5f-3a e é chamado em **seis** lugares: `Port.php` (três), `ajax/port.php`,
`Link.php` (`loadVisibleItem`), `DgoIdentity.php` (`applyComment`) e
`MapController.php` (`actionSaveEntryObs`).

```php
Session::haveAccessToEntity($parent->getEntityID(), $parent->isRecursive())
```

Semântica conferida no core 11.0.6: `Session.php:1394` recusa ID < 0, aceita
entidade ativa e, só com `is_recursive`, entidade ancestral;
`CommonDBTM::getEntityID()` (`:3197`) devolve **-1** quando o itemtype não é
entity-assign, então a regra **falha fechado**.

⚠️ **Diferença assumida:** `can()` também passava por `canViewItem()` do itemtype.
Para o `PassiveDCEquipment` isso é a checagem de entidade, coberta. Se algum dia
outro itemtype com `canViewItem()` customizado virar pai de porta, a regra será
mais permissiva que antes.

`front/map.php` exige **apenas** `Port::$rightname READ`.

**A semântica que a Fase 5 instaura:**

| Direito | Significa |
|---|---|
| LER | Ver mapa, painel, relatórios, comentários, descrição das portas |
| ATUALIZAR | **Documentar portas** ✅, **propor e confirmar vínculos** ✅, **comentar o elemento** ✅, OBS do elemento, atribuir piso |
| CRIAR | **Criar elementos pelo mapa** ✅, fileiras, colunas, pisos — **estrutura** |
| DELETE | Esvaziar portas, recusar por desmontagem, excluir vínculos |

**Fora do DGO+, por decisão:** criar Localização (dropdown do GLPI inteiro),
anexos (direito `document` **+ `datacenter` UPDATE**) e excluir o elemento.

**O que a Fase 5 assume conscientemente:** quem tiver `plugin_dgoplus_port`
UPDATE grava porta, vínculo, OBS e comentário em elementos **da sua entidade**
sem ter direito nenhum nesses ativos. Escalada deliberada e delimitada, decidida
pelo administrador ao conceder o direito. **Efeito visível no core:** o
comentário passa pelo `update()` do ativo, então **o Histórico do
`PassiveDCEquipment` registra a alteração em nome do técnico**.

⚠️ **A Fase 5 ainda não chegou à produção.** Quando chegar, os perfis de quem
documenta hoje (Claudio Morett, Kayan Lucas, Pedro s, cristian.b) mudam de
permissão de verdade. Isso precisa de **bloco próprio de deploy, com plano de
rollback** — não é coisa de aplicar no meio de uma sessão.

### Anexos — o que está fechado e o que não está

O cartão de anexos do mapa usa o formulário do **core** (`Document_Item`), e ele
pergunta se o usuário pode **atualizar o ativo**:
`Document_Item::canCreateItem()` cai em `CommonDBRelation::canCreateItem()`
(`CommonDBRelation.php:659`), que chama `canRelationItem('canUpdateItem', ...)`.
Daí a lição 134: anexar exige `datacenter` UPDATE, e conceder isso devolveria o
menu inteiro.

⚠️ **Mas essa não é a única porta (lição 148):** `CommonDBTM::add()` (core
`CommonDBTM.php:1286`) **não faz nenhuma checagem de direito**. Um endpoint
próprio do plugin pode criar `Document` + `Document_Item` fazendo a checagem que
o DGO+ decidir. O perfil de teste **já tem** o direito `document`.

O que falta não é permissão, é **tela**. Candidato **5i**, ainda **sem leitura do
mecanismo de upload** — o tamanho estimado é expectativa, não medição.

**Este é o primeiro item que o 5g-3 deve explicar na aba do perfil** — foi o
exemplo que o usuário deu ao pedir a nota: *"Para usar anexos é necessário…"*.

### Arquivos

**30 no repositório** — 27 do plugin + 3 em `docs/` (contado em `560fb64`).

```
dgoplus/
├── setup.php              hooks, menu, botão da ficha, JS — 269 linhas
├── hook.php               instalação / desinstalação
├── README.md              desatualizado — dívida 1
├── logo.png
├── docs/                  README.md, contexto-dgoplus.md, roadmap-dgoplus.md
├── ajax/                  port.php (auto-save, 4a — 123), dgocomment.php (3t — 45)
├── public/                dgoplus.js (440), dgoplus-identity.js (306), qrcode.js, dgoplus-mark.svg
├── front/                 map.php, port.php, pending.php, config.form.php
└── src/                   13 arquivos
    ├── Install.php        schema, direitos, migrações
    ├── Setting.php        papéis e Tipos de cada papel
    ├── Port.php           porta; applyInput e parentIsReachable — 1086 linhas
    ├── Link.php           vínculo; propose é o ponto único — 1206 linhas
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (rightname = plugin_dgoplus_port)
    ├── MapController.php  a tela do mapa — 3421 linhas
    ├── Dashboard.php      o painel
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade, QR e comentário (3t) — 372 linhas
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil — alvo do 5g-3
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.11** (commit `560fb64`, baixado do GitHub e
conferido pelo assistente em 28/08):

```
e7499016c3cf10f11c5c69cc62742645  setup.php                    (269 linhas)
63421003891b3524bc9a96b1ab7dcb99  public/dgoplus.js            (440 linhas)
19ab841cbbf95b888577e3eb6115ea9b  public/dgoplus-identity.js   (306 linhas)
cbc5e6c2dbce9f694a0e3351880d791c  src/Port.php                 (1086 linhas)
743b74c1f490ef4fb99bfd9ccdf43916  src/Link.php                 (1206 linhas)
622f3f31704566d3c6f637df30adf038  src/MapController.php        (3421 linhas)
42e24072fd23666602f8b3f2fe63cd3d  src/DgoIdentity.php          (372 linhas)
b0e4f1837feab5a54d42868e8d88a4b7  ajax/port.php                (123 linhas)
1406df3db888f5b10846b6ab37fad0da  ajax/dgocomment.php           (45 linhas)
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
| 117 | ~~`$parent->can($items_id, READ)` acopla o plugin ao direito do itemtype pai~~ ✅ **CUMPRIDA pelos blocos 5f-3a e 5f-3b** |
| 118 | ~~`$can_write = haveRight($rightname, $found ? UPDATE : CREATE)`~~ ✅ *Corrigida pelo 5f-1a* |
| 119 | **Mensagem de permissão que não nomeia o direito faltante custa horas.** ⚠️ **Delimitada pela lição 153**: nomear onde a pessoa esbarra, não em banner permanente |
| 120 | ✅ Anexar documento a um ativo exige **`datacenter` UPDATE**. Ver lição 134 |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054** |
| 122 | **Não existe `sql-errors.log` no GLPI 11.0.6.** Erro de SQL vai para `php-errors.log` como `glpi.CRITICAL` |
| 123 | **O direito Documentos fica na aba Gerência do perfil, não em Ativos** |
| 124 | A homologação também pode estar À FRENTE do `master`. *Encerrada pelo clone no servidor* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`.** E, no `itemtype_item_revert`, esquecer `specific_itemtype` devolve a coluna vazia |
| 126 | **O sandbox do assistente tem PHP.** `apt-get update` sai com código ≠ 0 — **dois comandos separados** |
| 127 | **O CSV exportado do GLPI abre torto no Excel por duplo clique.** Solução: *Dados → Obter dados → De texto/CSV*, UTF-8, coluna como Texto |
| 128 | **`pscp` pode falhar com `Remote side unexpectedly closed network connection`** |
| 129 | **Arquivo remontado a partir do `master` + a descrição do bloco NÃO é verificação** |
| 130 | **O GitHub é canônico.** Qualquer ordem que deixe código existindo só no servidor já custou perda de trabalho uma vez |
| 131 | Upload pela web do GitHub cria arquivo novo em silêncio quando o nome não bate. *Aposentada pelo `git push`* |
| 132 | **`raw.githubusercontent.com` tem cache de CDN.** Para commit recém-feito: `ls-remote` para o HEAD, tarball do `codeload` para o conteúdo |
| 133 | ✅ **CONFIRMADA e CORRIGIDA pelo 5g-1 no `dgoplus.js`.** ⚠️ **Mas vale IGUAL para o `dgoplus-identity.js`** — lido e confirmado em 28/08. É o bloco 5g-1b |
| 134 | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core (`Document_Item`) |
| 135 | **O direito "Data centers" também fica na aba Gerência do perfil** |
| 136 | **A raiz web efetiva vem de `conf-enabled/glpi.conf`.** **404 com `Set-Cookie: glpi_` é o GLPI respondendo** |
| 137 | **Com o clone Git na pasta do plugin, `git diff` É a conferência do bloco** e `git checkout --` é o rollback instantâneo |
| 138 | **O escopo real de um bloco de permissão está no PONTO ÚNICO, não na linha da tela** |
| 139 | **O envio é `scp`, não `pscp` — e o servidor recusa senha.** O que o usuário digita é a frase-secreta da chave |
| 140 | **Arquivo de bloco com o nome final colide na pasta Downloads e o `scp` manda o ANTIGO em silêncio.** O nome leva o bloco, e o `md5sum` de `/tmp` ANTES do `cp` não é opcional |
| 141 | **Número previsto de `git diff --stat` sai de comando, não de contagem a olho** |
| 142 | **As requisições do GLPI caem no `other_vhosts_access.log`, não no `access.log`.** E o `Referer` traz `edit=<tubo>-<fibra>` |
| 143 | **Com `docs/` versionado, o HEAD do `master` avança sem o código mudar.** `ls-remote` para o HEAD, `diff -rq` contra o último commit de código conhecido, e **só então** decidir a base do bloco. *Aconteceu três vezes: `4144a5c`, `1361dd3` e `f6a1c78`* |
| 144 | **Número de linha em documento é ponteiro, não fato — e envelhece a cada bloco.** Todo bloco reconfere os seus alvos por `grep -n` no commit do dia. *O 5g-2b encurtou o `MapController.php` em 32 linhas: toda referência de linha a ele no v12 está morta* |
| 145 | **Documento também é entrega — e entrega sem a seção de envio não chega.** O formato de quatro seções vale para QUALQUER arquivo entregue, inclusive `.md` |
| 146 | **Elemento novo nasce com 4 × 16 = 64 posições e encolher a grade exige DELETE.** Um perfil com CRIAR e sem DELETE cria uma CTO de 64 e não consegue ajustá-la. **Decisão de 28/08: fica como está** |
| 147 | **`actionSaveEntryObs` NÃO grava OBS de entrada: grava a OBS do ELEMENTO**, por `Panel::setCommentForItem`. **Nome de método não é especificação: ler o corpo antes de descrever um passo de teste** |
| 148 | **O formulário de anexo do core exige UPDATE no ativo, mas a API não.** `CommonDBTM::add()` não checa direito nenhum. Reabre a decisão negativa como candidato **5i** |
| 149 | **O navegador do usuário barra download de `.js`; o `.php` passa.** Todo JS de bloco sai com `.txt` no fim e o `cp` no servidor renomeia — o md5 é idêntico, então a conferência da lição 140 continua valendo. **Conferir o nome real com `dir` antes do `scp`** |
| 150 | **`grep -c` conta LINHAS que contêm, não ocorrências — e previsão de `grep` também sai de comando.** Rodar os dois, sempre |
| 151 | **Depois do 5f-1a, tirar o direito e RECARREGAR não produz 403: produz tela somente-leitura.** `mount()` sai na entrada quando não acha `[data-dgoplus-flag]`, e esse elemento só é impresso para quem tem escrita — sem o direito, **o JS nem se instala e nenhum `fetch` acontece**. O 403 do auto-save só é alcançável com **aba já aberta cujo direito foi retirado depois da renderização**. **Roteiro de teste de permissão tem que dizer QUANDO o direito é retirado, não só qual.** ⚠️ **A mesma convenção vale para o `mountComment()`** — vale para o roteiro do 5g-1b |
| **152** | **Item de escopo escrito no roadmap NÃO é decisão de produto tomada.** O 5g-2 entregou quatro mensagens porque as quatro estavam listadas no "próximo passo" do contexto v12; duas foram recusadas pelo usuário **assim que apareceram na tela**, e removidas no mesmo dia pelo 5g-2b. Ninguém errou: planejar em texto e ver renderizado são evidências de qualidade diferente. **Bloco que muda texto visível deve tratar a lista do roadmap como candidatos, não como aprovação** — e vale descrever a aparência esperada na seção 1 da entrega, para a recusa vir antes do commit |
| **153** | **Mensagem de permissão permanente na moldura da tela é ruído; contextual é ajuda.** A dica abaixo da grade e a da faixa de busca eram carregadas em toda abertura do mapa, por todo perfil somente-leitura, para um estado que **não é erro**. Já a tarja do painel da porta e a dica do vínculo só aparecem para quem **clicou e esbarrou na recusa**. **Regra: o painel da porta nomeia o direito; a moldura do mapa fica calada.** O lugar de explicar direito ao administrador é a aba DGO+ do perfil (5g-3). Delimita a lição 119 sem revogá-la |

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
| 3t | Identidade, QR e comentário | Fechado — direito revisto pelo 5f-2a; ⚠️ auto-save pendente no 5g-1b |
| 4a-1/2/3 | Auto-save; papel no painel; abas e filtro por papel | Fechado — mensagem de erro revista pelo 5g-1 |
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
| GIT-1 / GIT-2 | Clone Git e primeiro `push` do servidor | Fechados (27/08) |
| REL / REL-2 | Tags `v1.3.2` e `v1.3.8` + Releases | Fechados e conferidos por sha256 (27/08) |
| 5f-1a | Documentar porta exige UPDATE, não CREATE | Fechado (27/08), 1.3.3, `6efab96` |
| 5f-1b | Propor vínculo exige UPDATE, não CREATE | Fechado e validado (27/08), 1.3.4, `a690010` |
| 5f-2a | Comentário do elemento exige o direito do plugin | Fechado e validado (27/08), 1.3.5, `1114077` |
| 5f-2b | Criar elemento pelo mapa exige só o direito do plugin | Fechado e validado (27/08), 1.3.6, `04ac8fd` |
| 5f-3a | Caminho da porta larga o `datacenter` READ | Fechado e validado (27/08), 1.3.7, `72d4e55` |
| 5f-3b | OBS, vínculo e comentário largam o `datacenter` READ | Fechado e validado (27/08), 1.3.8, `0005c90` |
| 5g-1 | Auto-save da porta distingue 403 de falha de rede | Fechado e validado em tela + log (28/08), 1.3.9, `f94dbe5` |
| **5g-2** | **Telas nomeiam o direito que falta** | **Fechado e validado em tela (28/08), 1.3.10, `8da0634`** |
| **5g-2b** | **Dicas de permissão saem da moldura do mapa** | **Fechado e validado em tela (28/08), 1.3.11, `560fb64`** |

### O que o 5g-2 e o 5g-2b fizeram, em detalhe

O 5g-2 mexeu em quatro pontos, todos do `MapController.php` (`+56 −10`, medido no
sandbox e confirmado no servidor). O 5g-2b removeu dois deles (`+3 −35`), por
decisão de produto tomada com a tela na frente.

| Ponto | 5g-2 (1.3.10) | 5g-2b (1.3.11) |
|---|---|---|
| Tarja do painel da porta | *"Você tem permissão apenas de leitura nesta porta."* → nomeia **Atualizar** em **Portas de DGO** | **Mantido** |
| Dica do vínculo confirmado | Sem DELETE, deixa de mandar "Desmontar" e diz que exige **Excluir** | **Mantido** |
| Dica abaixo da grade | Criada, nomeando **Criar** e **Excluir** | **Removida** |
| Dica na faixa de busca | Criada, nomeando **Criar** | **Removida** |
| `$has_delete` | Criado para separar direito de limite de tamanho | **Removido** — só existia para a dica |

**A dívida 6 está quitada:** o texto não fala mais de um botão que não é
renderizado.

**Não mudou nenhuma regra de permissão.** As chamadas `Session::haveRight`
continuam pedindo exatamente os mesmos direitos; o `diff` contra o 1.3.9 mostra
só texto e a variável `$can_dismantle`.

**Proveniência fechada nos dois:** o assistente baixou `8da0634` e `560fb64` do
GitHub e provou por md5 que os arquivos publicados são idênticos aos preparados
no sandbox, e que **nenhum outro dos 30 mudou**.

**Validado em tela (28/08)**, perfil `Tecnicos N1 (ID 12)` com **só LER**,
usuário `teste.001`: as duas dicas da moldura sumiram, a tarja do painel da porta
continua, e devolver os três direitos traz de volta os botões de fileira/coluna e
o formulário de novo elemento sem nada torto na coluna da direita.

---

## 6. Dívidas conhecidas

1. **README desatualizado** — manda baixar `dgoplus-v1.0.0.zip`, fala em três
   tabelas quando são quatro, e avisa sobre portas órfãs, defeito que o 3q
   resolveu. **Escopo decidido em 28/08:** vira porta de entrada curta — o que o
   plugin faz, requisitos, instalação apontando para a **página de Releases**
   (nunca uma versão fixa, foi assim que ele envelheceu), e links para `docs/`.
2. **Sem catálogo de tradução**: interface pt-BR fixa. ⚠️ **Decisão de produto
   pendente antes de qualquer código:** existe demanda real de outro idioma, ou é
   higiene? O trabalho tocaria praticamente todos os 27 arquivos, o que é o
   oposto de um bloco — só funciona partido por arquivo ou por tela, e o JS
   precisa de mecanismo próprio.
3. ⚠️ **Lista integral de lições (1–113)** não incorporada. **Dois caminhos:**
   (a) barato e parcial — `grep -rn "lição\|Lição" src/ ajax/ front/ public/
   setup.php`; (b) buscar o documento original nas conversas anteriores.
4. ~~Sem tag nem Release~~ ✅ **QUITADA (27/08).**
5. **A skill `glpi-plugin-teckcomp` está desatualizada**: host, usuário, porta e
   o comando de envio (`pscp` → `scp`), mais a ordem de entrega com `git diff`,
   que ela não conhece. **Aprovada em 28/08 para execução** — bloco SKILL.
6. ~~Texto que fala de ação indisponível ("Desmontar" sem botão)~~ ✅ **QUITADA
   pelo 5g-2 (28/08).**

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
| Sem documentação | 57 elementos sem nenhuma porta registrada (DGO 28, CTO 29) |

Por localização, do mais cheio ao mais vazio: `Palladium Umuarama` 91,0% (262
documentadas, 26 livres) · `Jockey Plaza` 81,1% (986/230) · `Gravataí` 75,6%
(236/76) · `Plaza Campos Gerais` 68,4% (301/139) · `Pato Branco` 61,9% (109/67)
· `Itajaí` 57,6% (228/168) · `Petropolis` 38,3% (46/74) · `Estacao` 5,0% (3/57)
· **`Palladium Ctba` 2,5% (49/1887)** — 58 elementos e o maior estoque de portas
livres do parque.

Documentadores ativos (27/08): Claudio Morett, Kayan Lucas, Pedro s, cristian.b.

### Homologação — a base de teste (28/08)

| | |
|---|---|
| Elementos | **36** — DIO 5, DGO 14, CTO 11, PTO 6 |
| Portas | **1889** — 36 documentadas, 1853 livres |
| Ocupação geral | **1,9%** |
| Localizações | **9** |

*(Em 23/08 eram 31 elementos e 1665 portas. A base de homologação também cresce.)*

**DGO 01**, em `Outlet Porto Belo`, piso `MALL - PORTO BELO`: grade de 16
posições (F1 e F2), badge em **6 de 16**. Estado lido na tela de 28/08:

| Posição | Estado |
|---|---|
| F1.01 | → E1 (vínculo confirmado, `CTO 01`) |
| F1.02 | `1202` |
| F1.03 | `1214` |
| F1.04 | → E2 (`cto 01`) |
| F1.05 | `2153-01…` |
| F1.06 | → E3 (`CTO TESTE 5f2b`) |
| F1.07 | **livre** |
| F1.08 e F2.09–F2.16 | livres |

⚠️ **Isto diverge do contexto v12**, que registrava F1.07 com `2153` e badge em
7/16 depois do teste do 5g-1, e não citava F1.02. O saldo é o mesmo (6), mas as
portas trocaram. **Causa não registrada** — a tela é a fonte da verdade, e o v13
grava o que a tela mostrou.

Comentário do ativo: `teste 5f-2a vbv`. OBS do elemento preenchida.

**Vínculos pendentes na homologação:** `CTO01 → PTO 4 · E3` (por cristian.b, **7
dias**) e `DIO 001 → CTO 001 · E2` (por Claudio Morett, 4 dias).

⚠️ **`CTO TESTE 5f2b`** — lixo de teste do 5f-2b, mesma localização, piso não
atribuído, grade **4 × 16 = 64**, 0 documentadas. Purgar leva o vínculo junto
(3q). São 64 portas mortas em 1889 — **3,4% da base**. **Continua lá:** o vínculo
F1.06 → E3 aparecia confirmado na tela de 28/08.

Perfil de teste: **Tecnicos N1, ID 12**; usuário `teste.001`.

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
| Anexo pelo técnico **pelo formulário do core** | **Descartada (27/08)** | O formulário é do core e pergunta pelo `datacenter` UPDATE |
| Anexo pelo técnico **por formulário próprio** | ⚠️ **REABERTA (27/08)** | A lição 148 derrubou a premissa. Virou candidato **5i** |
| Documentos versionados dentro do repositório | **Descartada (27/08)** | `docs/contexto-dgoplus.md` sem sufixo: o histórico é o Git |
| Exigir DELETE para recusar vínculo | **Descartada (já no 4c)** | Recusar e confirmar são as duas metades da mesma resposta |
| `pscp` como veículo de envio | **Descartada (27/08)** | Não autentica: o servidor só aceita `publickey` (lição 139) |
| Tirar `$dgo` da assinatura de `canWriteComment` | **Descartada (27/08)** | Mudar assinatura de método público não é escopo de bloco de permissão |
| Grade padrão por papel, ou grade escolhida ao criar | **Descartada (28/08)** | A lição 146 é real, mas a solução é operacional: o administrador concede DELETE a quem concede CRIAR |
| Entradas na conta da ocupação geral (badge "variante B") | **Descartada (28/08)** | Mudaria o **significado** dos 44,9% da produção e misturaria porta de saída com alimentação |
| Item de roadmap para o estado do perfil de teste | **Descartada (28/08)** | É decisão do administrador de cada ambiente |
| "Ver todos" no cartão de vínculos pendentes | **Descartada (28/08)** | Já existe: "Abrir a fila completa", do bloco 4d |
| **Dica de permissão abaixo da grade** | **Descartada (28/08, pelo 5g-2b)** | Banner permanente para estado que não é erro. Lição 153 |
| **Dica de permissão na faixa de busca ("Criar elemento pelo mapa exige…")** | **Descartada (28/08, pelo 5g-2b)** | Idem. O lugar dessa informação é a aba DGO+ do perfil (5g-3) |

### Decisão de produto tomada em 28/08 — a badge do elemento

**Variante C, escolhida com apoio visual.** A badge passa a mostrar **dois
contadores lado a lado** — `0/16 grade` e `2/4 entradas` — em vez de um número
só. Não mistura as duas coisas e não mexe na ocupação geral.

⚠️ **Um DIO não recebe entrada** (é o topo da hierarquia). A linha de entradas só
aparece para papéis que podem receber (`dgo`, `cto`, `pto`), e **some por regra,
não por acaso** (lição 16).

⚠️ **Nada disso foi lido no código ainda.** `Dashboard.php` e `MapController.php`
não foram abertos para esta decisão — o desenho é proposta, e o bloco começa
pela leitura.

---

## 9. Próximo passo imediato

**A frente de permissões tem um defeito real ainda aberto, e ele foi descoberto
nesta sessão.**

1. **5g-1b — o auto-save do comentário do elemento.** É o 5g-1 aplicado ao
   `public/dgoplus-identity.js` (306 linhas) e ao endpoint
   `ajax/dgocomment.php` (45 linhas). Mesmo defeito, mesma correção: o `Error`
   carrega `status`, o 403 é tratado antes do `fallbackOnFailure`,
   `permissionDenied` é estado do módulo, e a mensagem nomeia **Atualizar** em
   **Portas de DGO**. ⚠️ **O roteiro tem que seguir a lição 151**: o
   `mountComment()` também sai na entrada sem `[data-dgoplus-dgo-flag]`, então o
   direito precisa ser retirado **com a aba já aberta**. Prova no
   `other_vhosts_access.log`, filtrando `dgocomment.php`.
2. **5g-3 — a nota na aba DGO+ do perfil.** É onde entra o *"para usar anexos é
   necessário…"* pedido pelo usuário, e é o destino de toda informação de
   permissão que **não** cabe na tela do técnico (lição 153). Alvo:
   `src/ProfileTab.php`. Conteúdo mínimo: o que cada nível concede (tabela da
   seção 3), o que fica **fora** do DGO+ (Localização, exclusão do elemento) e o
   que **anexos** exigem — `document` READ+UPDATE+CREATE **e `datacenter`
   UPDATE**.
3. **Higiene**: purgar `CTO TESTE 5f2b` (64 portas mortas) e confirmar a
   pendência do Histórico do ativo.
4. **SKILL** — barato, e para de custar em toda sessão nova.
5. Depois: **5h-2**, **BADGE-C**, **PAINEL-1**, **5b**, **5c**, **5d**, **5e**
   (com o ShopMap junto) e **5i**.
6. **REV** — ao fim das correções, revisão competitiva. Ver roadmap.
