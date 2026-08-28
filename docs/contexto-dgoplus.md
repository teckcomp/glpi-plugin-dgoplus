# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v16 — 28/08/2026. Substitui o v15 integralmente.
> Emitido ao fim de uma sessão de **três blocos entregues e validados em tela**.
> Versão **1.3.19**, `master` em **`770ce71`**.
>
> **O que o v16 traz de novo em relação ao v15:**
>
> 1. **O 5e-2 foi inteiro ao ar**, em três blocos: **5e-2a**, **5e-2b** e
>    **5e-2c**. Nasceu o `src/ItemLabel.php`, ponto único do rótulo de elemento.
> 2. **A trava de duplicados foi CANCELADA** por decisão do usuário. O princípio
>    vira regra operacional; o software só sinalizará.
> 3. **Nasceu o 5e-2d**, o selo de fora de conformidade — com escopo medido.
> 4. **As pendências 12 e 14 estão respondidas**, e a 8 tem alvo nomeado.
> 5. **Frente nova, fora do DGO+:** o `shopmap` ganha trabalho próprio.
> 6. **Três lições novas: 162, 163, 164.**
>
> Companheiro: `roadmap-dgoplus.md`. Os dois vivem em `docs/` no repositório.

---

## 0. A regra que governa tudo

**O GitHub é o repositório canônico do DGO+. A homologação é descartável.**

Todo estado do código tem que ser reconstruível a partir do `master` sozinho. A
regra nasceu de um fato: **houve um incidente doméstico em que a base de
homologação foi perdida com um repositório dentro dela, levando junto correções
que nunca chegaram ao Git.**

Convive com a regra de precisão do projeto porque são perguntas diferentes:

| Pergunta | Fonte da resposta |
|---|---|
| **O que está rodando agora?** (tela, erro, permissão) | O servidor — sempre |
| **O que o código É?** (registro durável, base de bloco novo) | **O GitHub — sempre** |
| **Como estão os DADOS da homologação?** | **Só a tela, lida na sessão** (lição 160) |

O contexto guarda o retrato dos dados, e o retrato envelhece mais rápido que o
código.

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
| `master` em 28/08 (fim da sessão) | commit **`770ce71`**, versão **1.3.19** |
| Versão em homologação | **1.3.19** — ✅ conferida em tela (Configuração → Plugins) |
| **Paridade** | ✅ Provada: o tarball do `770ce71` é byte a byte igual ao preparado |
| Arquivos no repositório | **31** (28 do plugin + 3 em `docs/`) |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha**. Lição 139 |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local**, **sem PuTTY em uso** |
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
git status --short
git diff --stat && git diff         # <<< a conferência do bloco
git add -A && git commit -m "..." && git push
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

**Conferência de estado, no começo de toda sessão:**

```bash
cd /var/www/html/glpi/plugins/dgoplus
git status --short && git log -1 --oneline && grep PLUGIN_DGOPLUS_VERSION setup.php
```

**Reverter:**

```bash
git checkout -- <arquivos>      # descarta a cópia, ainda não commitada
git revert HEAD && git push     # desfaz o commit já empurrado
rm -f src/<ArquivoNovo>.php     # arquivo NOVO não some com revert de merge sujo
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

**Ruído conhecido no `php-errors.log`, sem relação com defeito:**

- `Plugin "dgoplus" version changed. It has been deactivated…` — é a lição 116.
  **Aviso no log não é estado atual: a fonte da verdade é a tela Configuração →
  Plugins** (lição 114).
- Backtrace de boot do plugin `fields`.
- `glpi.WARNING: *** Test logger`.

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
`v1.3.2`, `v1.3.8`. As versões **1.3.3 a 1.3.19 não têm tag**: são degraus
internos da Fase 5. A próxima tag sai quando a Fase 5 fechar.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6, `Tasks list`
2.1.12.

### O shopmap — frente irmã, fora deste repositório

⚠️ **`github.com/teckcomp/glpi-plugin-shopmap` está PRIVADO** — conferido em
28/08: `git ls-remote` e `codeload` devolvem **404 anônimo**, enquanto o dgoplus
responde 200 pelo mesmo caminho. **O assistente não lê o código do shopmap.**

O problema lá é o mesmo do 5e: o campo **"Vincular ativo (nome)"** lista os
candidatos com **nome + itemtype apenas** — sem localização, sem id. Com nomes
repetidos entre unidades, escolher vira sorteio. O print de 28/08 mostra ainda
`DGO 001` e `DGO001` na mesma lista: colisão para o olho, não para o `=`.

**A regra fechada nesta sessão, e que vale para os dois plugins:**

> **Referência a ativo é `itemtype` + `id`. Nome é rótulo, nunca chave.**
> Nome muda, colide entre unidades e não distingue tipos. O `id` sozinho também
> não serve: ele é único dentro da **sua** tabela. É a lição 32 aplicada a
> vínculo entre plugins.

Para o trabalho no shopmap começar, falta responder — e **só a base do shopmap
responde**: *o vínculo é guardado hoje pelo NOME ou por `itemtype`+`id`?*

- Por **chave** → o trabalho é só de tela (enriquecer o rótulo da lista).
- Por **nome** → é tela **mais migração** dos vínculos existentes, com três
  baldes: nome resolve para 1 ativo (migra), para 2+ (fica para decisão humana),
  para 0 (o vínculo já estava quebrado).

Sugestões registradas para essa frente: tratar ativo removido com rótulo
explícito (`ativo removido (Dispositivo passivo #35)`, nunca em branco);
respeitar entidade na busca; normalizar espaços na comparação para `DGO001`
casar com `DGO 001`; e aceitar `#id` digitado na busca.

### Quando reinstalar

| Mudou | O que fazer |
|---|---|
| `src/`, `front/`, `ajax/` (PHP) | `cache:clear` + `systemctl restart apache2` |
| `public/` (JS/SVG) | **Ctrl+F5** no navegador |
| `src/Install.php` (schema, direitos) | `plugin:install --force` **e depois** `plugin:activate` |
| **Número de versão no `setup.php`** | Idem (lição 116) |
| Só `docs/` | **Nada.** Commit e pronto |

---

## 2. Fluxo de trabalho vigente

Método **entrega-em-blocos**: um bloco = uma mudança testável de uma sentada.

Toda entrega tem quatro seções fixas: **(1)** o que muda, com a decisão de
reinstalar em negrito na primeira linha; **(2)** o comando `scp` literal **com os
md5 esperados**; **(3)** os comandos de aplicar, com **`git diff` como
conferência**; **(4)** roteiro de teste numerado com resultado esperado, onde ler
o log e como reverter.

⚠️ **Sessão de VALIDAÇÃO não é entrega de bloco.** Não tem seções 1 a 3 — não há
arquivo, não há `scp`, não há reinstalação. Tem só o roteiro.

### Roteiro de teste — as exigências acumuladas

- O roteiro **se confere contra o código** antes de sair (lição 158).
- **Todo passo que troca de tela diz COMO chegar lá** (lição 159).
- **Toda pré-condição de dados é lida em tela antes de virar passo** (lição 160).
- **Passo que prevê "não muda" também é passo.** Provado no 5e-2c: o passo 7
  mandava conferir que o seletor de destino continuava no formato antigo.

### Nome de arquivo entregue leva o bloco

`MapController-5e2c.php`, `Link-5e2a.php`, `dgoplus-5g1b.js.txt`. O `cp` no
servidor renomeia. Sem isso o download colide na pasta Downloads e o `scp` manda
o **antigo** com sucesso aparente (lição 140).

### O repositório é público — usar isso por padrão

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
https://raw.githubusercontent.com/teckcomp/glpi-plugin-dgoplus/master/<arquivo>
```

(`api.github.com` bate no limite anônimo — 403.)

⚠️ **Preferir o `codeload` com o SHA ao `raw`** (lição 132).

**Padrão de trabalho ao preparar um bloco:** baixar o tarball do commit atual,
editar a cópia, validar sintaxe, e **provar por `diff -rq` que só os arquivos do
escopo mudaram**. Depois do push, **baixar o commit publicado e provar paridade
com a cópia de trabalho**. Rodado três vezes em 28/08, sempre limpo.

**Número previsto sai de comando, não de olho** (lições 141, 150, 155, 163).

**Documento é entrega, e entrega tem quatro seções** (lição 145).

**Número de linha citado em documento é ponteiro, não fato** (lição 144).

**Inventário de duplicação usa padrão LARGO, não o padrão da primeira forma
encontrada** (lição 164).

### O core do GLPI também é legível

`github.com/glpi-project/glpi` na tag `11.0.6`. Classes com namespace `Glpi\`
ficam em **`src/Glpi/...`**. O schema está em `install/mysql/glpi-empty.sql`.

⚠️ **O CSS do tema NÃO é legível por esse caminho** — três tentativas em 28/08
deram 404. **Classe CSS não confirmada não entra** (lição 156).

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
- **Passo de roteiro que troca de elemento sem dizer como chegar lá** (lição 159).
- **Usar dado de dados da homologação, vindo do contexto, como pré-condição de
  teste sem reler em tela** (lição 160).
- **Purgar elemento "de teste" sem antes ler as quatro entradas** (lição 161).
- **Nomear função pelo comentário vizinho em vez do chamador** (lição 162).
- **Contar linhas de diff com `^-[^-]`** (lição 163).
- **Declarar inventário completo a partir de um `grep` de padrão estreito**
  (lição 164).

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

**Porta sem acoplador** não pode ser usada e **não conta como documentada**.

⚠️ **Porta de grade com vínculo conta como documentada mesmo sem nome.** Visto em
tela em 28/08. Não conferi qual ramo de `statsForDgo()` produz isso — pendência 15.

### Vínculos

`glpi_plugin_dgoplus_links`: **uma linha, dois lados**. Regras fechadas:

- **Sem `is_deleted`.** Recusa apaga a linha.
- **Pendente já ocupa a porta**, nas duas pontas.
- **Uma porta alimenta um destino só.**
- **Hierarquia permissiva**: pode pular nível, nunca subir nem empatar.
  `Link::hierarchyAllows()` compara posição, então sabe que desceu, **não sabe
  quanto** — lacuna do 5d.
- **Só vínculo confirmado sobe na trilha** (4e).
- `Link::propose()` é o **ponto único de criação**.
- **Recusar e confirmar pedem o mesmo direito (UPDATE)**, de propósito.
  Desmontar pede DELETE.

**`Link::upstreamLevels($itemtype, $items_id, ?$from_entry_id)`** — desde o 5c o
terceiro parâmetro restringe o **nível 0** a uma entrada. Entrada inválida
devolve trilha **vazia**. Chamador único: `MapController::displayEntryCard()`.

⚠️ **Pendente que envelhece não avisa ninguém.**

### O rótulo de elemento — `src/ItemLabel.php` (5e-2)

**Ponto único do nome de elemento na tela.** Nasceu no 5e-2a e concentra o que
antes estava espalhado em **oito** cópias.

Duas saídas públicas, e a escolha entre elas é por **recorte de tela**:

| Método | Formato | Onde |
|---|---|---|
| `forRow($row, $id)` / `forItem($type, $id)` | `nome · localização · #id` | Telas que **atravessam** localizações |
| `shortForRow($row, $id)` | `nome · #id` | Telas **recortadas** por localização |

As duas dividem o privado `compose($row, $id, $with_location)` — a regra tem um
dono só. O nome da localização é memorizado num cache estático por `locations_id`,
com o mesmo tratamento do `Dashboard::locationLabel()` (inclusive o `&nbsp;` que
o `getDropdownName` devolve para id inexistente).

**Regras internas:**

- nome vazio imprime `sem nome` — lição 16, some seria pior;
- elemento ausente devolve `elemento #%d`, **sem** alegar remoção;
- `forRow` não consulta nada além da localização: consome linha já carregada.

**A regra do 5e (seletor de destino) NÃO é a mesma, e é de propósito.** Lá a
desambiguação é **por colisão** — conta rótulos repetidos na lista e só o par
ambíguo ganha `#id`. Isso só funciona **onde existe uma lista**. Nos cards há um
elemento só; não há com quem colidir, então a regra é **fixa**.

**Consumidores do `ItemLabel`, medidos em `770ce71`:**

| Ponto | Arquivo | Variante |
|---|---|---|
| `describeOrigin()` | `Link.php` | completa |
| `describeDestination()` | `Link.php` | completa |
| `displayNameOf()` (pendentes) | `Link.php` | completa |
| `displaySearchResults()` | `MapController.php` | completa |
| Aba do mapa | `MapController.php` | curta |
| Seletor único (acima de `MAX_TABS`) | `MapController.php` | curta |
| Cabeçalho do card da grade | `MapController.php` | curta |
| Cabeçalho do card de anexos | `MapController.php` | curta |
| Cabeçalho do painel da porta | `MapController.php` | curta |
| `renderTrailChip()` | `MapController.php` | curta |
| `displayFeedsCard()` | `MapController.php` | curta |
| `$item_names` (painel) | `Dashboard.php` | completa |

O ponto do `Dashboard` é **um só** e alimenta os cards "Equipamentos mais
ocupados" **e** "Atividade recente".

⚠️ **Duas coisas ficaram de fora, de propósito:**

1. **O seletor de destino** continua no formato do 5e — `CTO 01 (CTO) #35`, com
   parêntese e sem ponto médio. Uniformizar é decisão de produto, não correção.
2. **O nome da localização sai como `completename`** (caminho da árvore):
   `PTO 4 · Shopping Ventura > DGO Cristian · #27`. Informativo, mas comprido, e
   o `>` compete com o `·`. A alternativa é usar só a folha. **Decisão pendente**,
   a ser tomada olhando a produção, que tem árvore de verdade.

### Comentário do elemento

`DgoIdentity::applyComment()` é o **ponto único**, usado pelo POST clássico e
pelo `ajax/dgocomment.php` (lição 47). Grava o campo `comment` **nativo** do
`PassiveDCEquipment`, então aparece no Histórico do ativo.

**Desde o 5g-1b** ele devolve **`denied => true`** quando a recusa é de
permissão de sessão — só nesse ramo. O endpoint repassa a chave; o JS a usa.
**A regra continua num lugar só** — o endpoint não checa direito, de propósito.

### Auto-save — os dois JS, ambos corrigidos

**`public/dgoplus.js`** (440 linhas) — o painel da porta. Desde o 5g-1 o
`.catch()` distingue 403 de queda de rede; `permissionDenied` é estado do módulo.

**`public/dgoplus-identity.js`** (362 linhas) — o comentário do ativo. **Desde o
5g-1b:** o `Error` carrega `status`; `data.denied === true` liga
`permissionDenied` e guarda a frase do PHP em `deniedText`; o 403 é tratado
**antes** do `fallbackOnFailure`.

⚠️ **Lido no código:** depois de `permissionDenied` ficar verdadeiro, o `save()`
**sai antes do `fetch`**. Logo, **uma única linha `POST … dgocomment.php`** é o
resultado esperado, e o status dela é **200**, não 403 (lição 154).

**Princípio, do bloco 4a:** o formulário continua sendo um POST completo e
válido; se o JS não carregar, o botão Salvar recarrega a página.

`mount()` e `mountComment()` **saem na entrada** se não acharem o
`[data-...-flag]`, e esse elemento só é impresso para quem tem escrita — **sem o
direito, o JS nem se instala** (lição 151).

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

**Search options do Port:** 1 `code`, 2 `name`, 3 `itemtype`, 5 `tube_num`,
6 `fiber_num`, 7 `comment`, 8 Localização (`nosearch`), 9 `is_no_coupler`,
10 `kind`, 11 documentado por, 12 `date_documented`, **19 `date_mod`**,
121 `date_creation`.

**`Port::getReportUrl(array $params)`** é o **ponto único da URL do relatório**
desde o PAINEL-1a.

⚠️ **Observado em tela (28/08):** passando `searchtype=equals` num campo
`datatype string`, a tela renderiza **"contém"**.

⚠️ **A busca do mapa é GLOBAL**, medida em tela em 28/08: com `Outlet Porto Belo`
selecionado, ela trouxe `CLM 001 / CTO 02 · shopping estação · #4`. Não é defeito
— é o desenho, e é o que justifica o rótulo completo ali.

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

**O acoplamento a `datacenter` acabou.** Os dois greps que provam isso:

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

O cartão usa o formulário do **core** (`Document_Item::showForItem`), e ele
pergunta se o usuário pode **atualizar o ativo** — daí o `datacenter` UPDATE
(lição 134).

⚠️ **Mas essa não é a única porta (lição 148):** `CommonDBTM::add()` não faz
checagem de direito. **O que falta não é permissão, é tela** — candidato **5i**.

### Arquivos

**31 no repositório** — 28 do plugin + 3 em `docs/`.

```
dgoplus/
├── setup.php              hooks, menu, botão da ficha, JS — 269 linhas
├── hook.php               instalação / desinstalação
├── README.md              165 linhas
├── logo.png
├── docs/                  README.md, contexto-dgoplus.md, roadmap-dgoplus.md
├── ajax/                  port.php (123), dgocomment.php (52)
├── public/                dgoplus.js (440), dgoplus-identity.js (362), qrcode.js, dgoplus-mark.svg
├── front/                 map.php, port.php (26), pending.php, config.form.php
└── src/                   14 arquivos
    ├── Install.php        schema, direitos, migrações
    ├── Setting.php        papéis e Tipos de cada papel
    ├── ItemLabel.php      **NOVO (5e-2a)** — ponto único do rótulo — 166 linhas
    ├── Port.php           porta; applyInput, parentIsReachable, getReportUrl — 1120
    ├── Link.php           vínculo; propose, upstreamLevels, hierarchyAllows — 1235
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (rightname = plugin_dgoplus_port)
    ├── MapController.php  a tela do mapa — 3531 linhas
    ├── Dashboard.php      o painel — 1282 linhas
    ├── Pending.php        página de vínculos pendentes (4d)
    ├── DgoIdentity.php    identidade, QR e comentário — 381 linhas
    ├── PurgeCleaner.php   limpeza na purga do ativo (3q)
    ├── ProfileTab.php     aba de direitos no Perfil — 184 linhas
    └── MapPage.php        entrada de menu
```

**Impressões digitais do 1.3.19**, medidas pelo assistente nesta sessão no
commit `770ce71`:

```
7f16625e63466e506f0bca1f6b11f6e0  setup.php                    (269 linhas)
63421003891b3524bc9a96b1ab7dcb99  public/dgoplus.js            (440 linhas)
d58fdb6b783801190a79eb1ace005fca  public/dgoplus-identity.js   (362 linhas)
f8d60d99db81dc8958e67424a844351f  src/ItemLabel.php            (166 linhas)
52ab95366b20809e952972c1c1a9b823  src/Port.php                 (1120 linhas)
9a7634edb132423b73bd9357e36b9230  src/Link.php                 (1235 linhas)
44894d0bfdf8c8b08c729cbbf00048bb  src/MapController.php        (3531 linhas)
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

⚠️ **Lacuna.** O código cita lições numeradas até a **113**; a lista integral vive
no documento original, não recuperado. **Medido em 28/08:** o `grep` no código
devolve **30 lições distintas**, e **todas já estão listadas abaixo**. **O caminho
barato da dívida 3 está esgotado.**

| # | Lição |
|---|---|
| 3 | Em `front/` e `ajax/` do GLPI 11 a sessão, o autoload e o `$CFG_GLPI` já estão de pé |
| 5 | `getEntitiesRestrictCriteria()` devolve array para ser **somado** |
| 12 | `$_SERVER['PHP_SELF']` está morto no GLPI 11 para montar URL |
| 13 | Montagem de URL num lugar só, nunca espalhada |
| 14 | **Falha silenciosa custa mais que falha barulhenta.** O mapa mentindo é o defeito mais caro |
| 16 | **Estado vazio nunca fica mudo** |
| 20 | Componente que não cabe na coluna some sem avisar — vai em largura total |
| 21 | Só classes CSS que existem nos templates do 11.0.6 |
| 23 | Vermelho do projeto em alfa para o fundo da célula sem acoplador |
| 27 | `outline` seria cortado pelo `overflow` |
| 31 | `ALTER` repetido sem guarda devolve erro 1060 |
| 32 | **União de Tipos sempre por id, nunca por nome.** ⚠️ **Generalizada em 28/08:** referência a ativo é `itemtype` + `id`; nome é rótulo |
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
| 114 | **Homologação pode estar atrás do `master` sem ninguém ter errado.** Aviso antigo no log também não é estado atual — a fonte é a tela Configuração → Plugins |
| 115 | `-P` maiúsculo para a porta, no `scp` |
| 116 | **Bump de versão no `setup.php` conta como mudança de instalação.** `--force` + `activate` |
| 117 | ~~`can($items_id, READ)` acopla o plugin ao direito do pai~~ ✅ cumprida pelos 5f-3a/b |
| 118 | ~~`$can_write` com CREATE~~ ✅ corrigida pelo 5f-1a |
| 119 | **Mensagem de permissão que não nomeia o direito custa horas.** Delimitada pela 153 |
| 120 | Anexar documento a um ativo exige `datacenter` UPDATE. Ver 134 |
| 121 | **`joinparams.beforejoin` apontando para a própria tabela gera 1054** |
| 122 | **Não existe `sql-errors.log` no 11.0.6** |
| 123 | **O direito Documentos fica na aba Gerência do perfil** |
| 124 | A homologação também pode estar À FRENTE do `master`. *Encerrada pelo clone* |
| 125 | **`jointype` inexistente não dá erro: cai no `default`** |
| 126 | **O sandbox do assistente tem PHP.** `apt-get update` e `install` em dois comandos |
| 127 | **O CSV exportado do GLPI abre torto no Excel por duplo clique** |
| 128 | `pscp` pode falhar com `Remote side unexpectedly closed network connection` |
| 129 | **Arquivo remontado a partir do `master` + descrição NÃO é verificação** |
| 130 | **O GitHub é canônico** |
| 131 | Upload pela web do GitHub cria arquivo novo em silêncio. *Aposentada pelo `git push`* |
| 132 | **`raw.githubusercontent.com` tem cache de CDN** |
| 133 | ✅ Corrigida pelo 5g-1 e pelo 5g-1b |
| 134 | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core |
| 135 | **O direito "Data centers" também fica na aba Gerência** |
| 136 | **A raiz web efetiva vem de `conf-enabled/glpi.conf`** |
| 137 | **Com o clone Git, `git diff` É a conferência do bloco** |
| 138 | **O escopo real de um bloco de permissão está no PONTO ÚNICO** |
| 139 | **O envio é `scp`, não `pscp` — e o servidor recusa senha** |
| 140 | **Nome final colide na pasta Downloads e o `scp` manda o ANTIGO em silêncio** |
| 141 | **Número previsto de `git diff --stat` sai de comando** |
| 142 | **As requisições caem no `other_vhosts_access.log`** |
| 143 | **Com `docs/` versionado, o HEAD avança sem o código mudar** |
| 144 | **Número de linha em documento é ponteiro, não fato** |
| 145 | **Documento também é entrega** |
| 146 | **Elemento novo nasce com 64 posições e encolher exige DELETE** |
| 147 | **`actionSaveEntryObs` grava a OBS do ELEMENTO.** Nome de método não é especificação |
| 148 | **O formulário de anexo do core exige UPDATE, mas a API não** |
| 149 | **O navegador barra download de `.js`; o `.php` passa** |
| 150 | **`grep -c` conta LINHAS que contêm, não ocorrências** |
| 151 | **Tirar o direito e RECARREGAR não produz 403: produz tela somente-leitura** |
| 152 | **Item de escopo escrito no roadmap NÃO é decisão de produto tomada** |
| 153 | **Mensagem de permissão permanente na moldura é ruído; contextual é ajuda** |
| 154 | **Defeito descrito por analogia com outro arquivo é dedução, não leitura** |
| 155 | **Todo número que vai para a entrega sai de comando, sem exceção** |
| 156 | **Classe CSS não confirmada não entra** |
| 157 | **Caminho de arquivo em comando nunca vai abreviado** |
| 158 | **Roteiro de teste também se confere contra o código** |
| 159 | **Passo de roteiro que troca de elemento tem que dizer COMO chegar lá** |
| 160 | **Dado de DADOS da homologação não é pré-condição de teste até ser relido em tela.** Código dura; dado de teste, não |
| 161 | **Antes de purgar elemento "de teste", ler as quatro entradas** |
| **162** | **Função se identifica pelo CHAMADOR, não pelo comentário ao lado.** O assistente leu o comentário vizinho da `displayNameOf()` ("uma consulta por pendência") e a descreveu como servindo à *trilha*; ela serve à **página de pendentes**. O comentário explica *por que* o código é assim; só o `grep` do chamador diz *para quem* ele serve. **Custo: uma pergunta desnecessária ao usuário** |
| **163** | **Contar linhas removidas de diff com `^-[^-]` descarta as linhas EM BRANCO removidas** — o `-` fica sozinho e o padrão não casa. Previsto 28, saiu 31. O certo é `grep -c "^-"` menos 1 (o cabeçalho `---`), ou rodar `git diff --stat` na própria cópia |
| **164** | **Inventário feito por `grep` só encontra a forma que o padrão descreve.** O 5e-2b foi entregue como "seis pontos, tela do mapa encerrada" a partir do padrão `fields['name'] ?: ('#'`. Havia uma **sétima** família, o `displayFeedsCard()`, que escreve a mesma regra com `$row['name']` e um `if` — e apareceu na tela do usuário, duplicada, logo depois do bloco. **Regra duplicada raramente é duplicada literalmente.** Antes de declarar "são N pontos", rodar também o padrão largo (aqui, `\['name'\]` puro) e triar o resultado à mão |

**Armadilhas do GLPI 11 que valem como regra permanente:**

- **CSRF**: o core valida POST sozinho — nunca `Session::checkCSRF` manual.
- **Iterator: `COUNT` + `GROUPBY` juntos descartam os campos do `SELECT`.**
- Todo `WHERE`/`ORDER` com JOIN precisa de **coluna qualificada**.
- **Filtro nunca pode sumir**: lista de ids vazia vira `[0]`.
- JSON em `<script type="application/json">` exige as flags HEX.
- Endpoint `ajax/` **não se testa pela URL direta**.
- `php -l` não pega incompatibilidade de assinatura com a classe-pai.
- `Dropdown::showFromArray` renderiza **select2**, que esconde o `<select>` real.
- **`Dropdown::getDropdownName('glpi_locations', $id)` devolve o `completename`**
  (caminho da árvore, com `>`), não a folha. Devolve `&nbsp;` para id inexistente.
- Classes com namespace `Glpi\` moram em `src/Glpi/...` no repositório do core.
- **`Session::checkRight` lança; devolver array não lança.**
- **`find()` sem lista de campos traz a LINHA INTEIRA** — então `locations_id`
  está disponível de graça em toda consulta em lote do plugin.

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
| 5a | Escopo Localização → Piso no seletor de destino | Fechado e validado, 1.3.1 |
| 5h | JOIN da coluna Localização no relatório | Fechado, 1.3.2 |
| DOC / GIT-1 / GIT-2 / REL / REL-2 | `docs/`, clone, push, tags e Releases | Fechados (27/08) |
| 5f-1a … 5f-3b | A frente de permissões inteira | Fechados e validados, até 1.3.8 |
| 5g-1 | Auto-save da porta distingue 403 de falha de rede | Fechado e validado, 1.3.9 |
| 5g-2 / 5g-2b | Telas nomeiam o direito; dicas saem da moldura | Fechados e validados, 1.3.11 |
| 5g-1b | Auto-save do comentário não reenvia recusa | **Entregue, 1.3.12.** ⚠️ **A recusa continua NÃO exercitada** |
| 5g-3 | Nota de permissões na aba DGO+ do perfil | Fechado e validado, 1.3.13 |
| PAINEL-1a | "Ver todos" em Atividade recente | Fechado e validado, 1.3.13 |
| README | Reescrito | Fechado, 1.3.13 |
| 2b | Nota vira card abaixo da matriz | Fechado e validado, 1.3.14 |
| 5b | Seletor de piso lista só pisos com elemento | Fechado e validado, 1.3.15 |
| 5e | Desambiguação por colisão no seletor de destino | Fechado e validado, 1.3.15 |
| 5c | Trilha parte da entrada, não do elemento | Fechado e validado, 1.3.16 |
| **5e-2a** | **`ItemLabel`; `describeOrigin`/`describeDestination`** | ✅ **FECHADO E VALIDADO EM TELA (28/08), 1.3.17, `e48d7a4`** |
| **5e-2b** | **Abas, cabeçalhos, seletor único e chip da trilha** | ✅ **FECHADO E VALIDADO EM TELA (28/08), 1.3.18, `67248dd`.** ⚠️ Entregue como completo e não era — ver lição 164 |
| **5e-2c** | **Card Alimenta, busca, pendentes e painel** | ✅ **FECHADO E VALIDADO EM TELA (28/08), 1.3.19, `770ce71`.** Fecha de fato o 5e-2 |

---

## 6. Dívidas conhecidas

1. ~~README desatualizado~~ ✅ **QUITADA.**
2. **Sem catálogo de tradução**: interface pt-BR fixa. ⚠️ **Decisão de produto
   pendente:** demanda real ou higiene? Tocaria os 28 arquivos.
3. **Lista integral de lições (1–113)** não incorporada. ⚠️ **O caminho barato
   está esgotado.** Resta buscar o documento original nas conversas antigas.
4. ~~Sem tag nem Release~~ ✅ **QUITADA (27/08).**
5. **A skill `glpi-plugin-teckcomp` está desatualizada**: host, usuário, porta,
   `pscp` → `scp`, e a ordem de entrega com `git diff`. **Aprovada** — bloco SKILL.
6. ~~Texto fala de "Desmontar" sem o botão existir~~ ✅ **QUITADA.**
7. **NOVA — inconsistência de formato entre o seletor de destino e o resto.**
   `CTO 01 (CTO) #35` versus `CTO 01 · #35`. Deliberada, não urgente.

---

## 7. Medições de campo

⚠️ **Existem DUAS bases, e confundi-las é o erro mais caro desta seção.**
⚠️ **Tudo aqui é retrato datado, não estado atual** (lição 160).

### Produção — o parque real (28/08, não relido nesta sessão)

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

### Homologação — lido em tela em 28/08, ao fim da sessão

**Painel, cards de topo (imagem da sessão):**

- Equipamentos mais ocupados: `CINELASER · Plaza Campos Gerais · #25` (3/3),
  `TESTE01 · shopping palladium · #6` (4/6), `DGO 01 · Outlet Porto Belo · #34`
  (5/16), `CLM 001 · shopping estação · #2` (2/32),
  `CTO02 · Plaza Campos Gerais · #21` (1/16).
- **Vínculos pendentes: 2.**
  `CTO01 · Plaza Campos Gerais · #20` F1.01 → `PTO 4 · Shopping Ventura > DGO
  Cristian · #27` E3, por cristian.b, 8 dias.
  `DIO 001 · shopping palladium · #7` F1.02 → `CTO 001 · shopping palladium ·
  #8` E2, por Claudio Morett, 5 dias.

### `Outlet Porto Belo`, piso `MALL - PORTO BELO` — o cenário de teste

**Estado ao FIM de 28/08, com todos os ids agora LIDOS em tela:**

| Elemento | id | Papel | Estado |
|---|---|---|---|
| `DGO 01 - PORTO BELO` | **33** | DGO | badge **1 de 16**. F1.01 → `CTO 01 #35` E2 (confirmado, Claudio Morett) |
| `DGO 01` | **34** | DGO | badge **5 de 16**. F1.01 → `CTO 01 #35` E1 (confirmado, cristian.b). F1.02 `1202`, F1.03 `1214`, F1.05 `2153-01…`, **F1.06 `2153` → `CTO 01 #36` E3 (confirmado)** |
| `CTO 01` | **35** | CTO | 16 portas. E1 ← `DGO 01 #34` F1.01; E2 ← `DGO 01 - PORTO BELO #33` F1.01 |
| `CTO 01` | **36** | CTO | **a ex-`CTO TESTE 5f2b`.** 64 portas. **E3 ← `DGO 01 #34` F1.06** |
| `DGO 01` | **37** | DGO | **criado em 28/08** ao testar a trava inexistente. 64 portas, 0 documentadas |
| `PISO VAZIO TESTE` | — | — | piso sem elementos, criado para o 5b. **Pode ser removido** |

✅ **As pendências 12 e 14 do v15 estão RESPONDIDAS:** a F1.06 "sem acoplador" da
`DGO 01 #34` alimenta a **E3 da `CTO 01 #36`**. A pendência 8 (purgar a `#36`)
tem, portanto, alvo e pré-condição nomeados: **desmontar essa E3 primeiro**.

Perfil de teste: **Tecnicos N1, ID 12**; usuário `teste.001`.

---

## 8. Decisões negativas registradas

| Ideia | Decisão | Motivo |
|---|---|---|
| Atribuição de piso em lote | **Descartada** | O piso não vai ser preenchido em massa |
| Esconder o seletor de piso | **Descartada** | A meia-medida (5b) resolveu |
| Alerta de salto de degrau só no JS | **Descartada** | Exceção que passa batido vira topologia errada |
| Proporção do splitter como campo estruturado | **Descartada** | Já cabe no OBS |
| Splitter como papel na hierarquia | **Descartada** | É componente da caixa, não elo da cadeia |
| Importação CSV de portas | **Adiada** | Não há fonte de dados |
| Criar Localização pelo direito do DGO+ | **Descartada** | `Location` é dropdown do GLPI inteiro |
| Excluir elemento pelo direito do DGO+ | **Descartada** | Purgar ativo é do admin |
| Corrigir a acentuação do CSV no plugin | **Descartada** | O relatório é tela do core |
| Anexo pelo técnico pelo formulário do core | **Descartada** | O formulário é do core |
| Anexo pelo técnico por formulário próprio | ⚠️ **REABERTA** | Lição 148. Candidato **5i** |
| Documentos versionados dentro do repositório | **Descartada** | O histórico é o Git |
| Exigir DELETE para recusar vínculo | **Descartada (4c)** | Recusar e confirmar são a mesma resposta |
| `pscp` como veículo de envio | **Descartada** | Não autentica (lição 139) |
| Tirar `$dgo` da assinatura de `canWriteComment` | **Descartada** | Fora do escopo |
| Grade padrão por papel | **Descartada** | A solução é operacional |
| Entradas na conta da ocupação geral | **Descartada** | Mudaria o significado dos 44,9% |
| Item de roadmap para o estado do perfil de teste | **Descartada** | É do administrador |
| "Ver todos" no cartão de pendentes | **Descartada** | Já existe |
| Dica de permissão abaixo da grade / na faixa de busca | **Descartadas (5g-2b)** | Lição 153 |
| Commit único para "fechar tudo de uma vez" | **Descartada** | `git revert` jogaria fora dez mudanças |
| `checkRight(UPDATE)` dentro do `ajax/dgocomment.php` | **Descartada (5g-1b)** | Criaria uma segunda sede da regra |
| Igualar os Tipos dos dois `CTO 01` | **Descartada** | Dado desatualizado. Lição 160 |
| **Trava de duplicados no DGO+ (o antigo 5e-2c)** | **CANCELADA (28/08)** | ⚠️ **Decisão do usuário.** O princípio *"não deve existir itens duplicados na mesma localização"* passa a valer como **regra operacional**; a limpeza dos duplicados existentes é feita direto na produção. Pesou também que a trava nunca pegaria a ficha nativa, o `datainjection` nem o SQL. **O software apenas sinaliza — ver 5e-2d** |
| **Desambiguação por colisão nos cards de vínculo** | **Impossível, não descartada** | Colisão só se detecta onde há LISTA; no card há um destino só. Por isso a regra ali é fixa |
| **Rótulo do 5e-2 no seletor de destino** | **Fora do escopo (28/08)** | O seletor tem regra própria (por colisão, com papel). Uniformizar é decisão de produto — dívida 7 |
| Trava de duplicados que barre também na ficha nativa | **Impossível** | A ficha, o `datainjection` e o SQL não passam pelo plugin |
| **Purgar a `CTO 01 #36` como estava planejado** | **Suspensa** | ⚠️ Pré-condição: desmontar a E3 antes. Lição 161 |

### Decisões de produto vigentes

**BADGE-C · variante C** — a badge do elemento mostra **dois contadores lado a
lado**: `0/16 grade` e `2/4 entradas`. Não mistura os números e **não mexe na
ocupação geral**. A linha de entradas só aparece para papéis que podem receber.
⚠️ Escopo medido: toca `Port::statsForDgo()`, `MapController::renderBadges()` e
`ajax/port.php`.

**Contador de entradas nos cards do painel · SEPARADO** — mesma contagem do
BADGE-C, em outro lugar; por isso os dois andam no mesmo bloco.

**5d · confirmar em dois tempos** — vínculo que pula degrau não grava na
primeira tentativa. ⚠️ **O trabalho real é fazer destino e entrada sobreviverem
ao redirect.** ⚠️ Duas limitações aceitas: o marcador viaja no POST e pode ser
forjado; e nada fica registrado depois.

**5e-2d · selo de fora de conformidade** — ⚠️ **NOVO, aprovado em 28/08.**
Elementos que **colidem em nome dentro da mesma localização** ganham marca
visual (amarelo ou vermelho). Não impede nada: sinaliza o que já existe.

⚠️ **Escopo medido nesta sessão, e o detalhe importa:** a `getDgosAtLocation()`
filtra por `locations_id` **e também por piso e por papel** quando esses filtros
estão ativos. Calcular o selo a partir da lista já carregada sairia de graça, mas
**mentiria por omissão** — dois homônimos em pisos diferentes não apareceriam
juntos e nenhum receberia selo. Um selo que some conforme o filtro é a falha
silenciosa da lição 14. **A implementação faz UMA consulta própria por carga de
tela, escopada só por `locations_id`.**

Falta decidir: a cor, onde a marca aparece (aba? cabeçalho? os dois?), e o texto
do `title`.

---

## 9. Próximo passo imediato

1. **5g-1b — o último bloco entregue e não exercitado.** Sentada própria, porque
   mexe em permissão de perfil no meio da sessão:
   - abrir o mapa com o usuário de teste e **deixar a aba aberta**;
   - tirar **ATUALIZAR** de "Portas de DGO" do perfil **Tecnicos N1 (ID 12)**
     — sem recarregar a aba (lição 151);
   - digitar no comentário do elemento e sair do campo **3×**;
   - **esperado:** a frase de recusa se repete e sai **uma única linha**
     `POST … 200 … dgocomment.php` em
     `/var/log/apache2/other_vhosts_access.log`. **O status é 200, não 403**;
   - **devolver o direito ao perfil ao fim.**
2. **5e-2d — o selo**, com o escopo já medido na seção 8. Falta só a decisão
   visual.
3. **Higiene, com pré-condição:** desmontar a **E3 da `CTO 01 #36`** (alimentada
   pela F1.06 da `DGO 01 #34`) e só então purgá-la; remover o piso
   `PISO VAZIO TESTE`; decidir o destino do `DGO 01 #37`, criado no teste.
4. **Decisão pendente:** a localização no rótulo sai como `completename`
   (`Shopping Ventura > DGO Cristian`). Manter, ou usar só a folha?
5. **Commit — bloco 5d**, confirmação em dois tempos.
6. **Commit — BADGE-C + contador de entradas separado.** ⚠️ **A pendência 15 é
   pré-requisito de leitura**: antes de mexer no contador, saber por que porta
   com vínculo e sem nome já conta como documentada.
7. **SKILL**, **5h-2**, **5i**, e o **bloco de deploy em produção** (com rollback).
8. **Frente shopmap** — bloqueada até o repositório abrir ou o arquivo da busca
   chegar. Ver a seção 1.
9. **REV** — revisão competitiva, ao fim de tudo.

---

## 10. O que correu mal do lado do assistente

**O padrão se manteve: erro em texto e em inventário, nunca em código gravado.**

**Sessão de 28/08 (três blocos), três erros:**

| # | Erro | Quem pegou | Vira |
|---|---|---|---|
| 1 | Descrever a `displayNameOf()` como servindo à trilha, lendo o comentário vizinho em vez do chamador | O próprio assistente, ao medir | **Lição 162** |
| 2 | Prever 28 remoções no `git diff --stat`; saíram 31, por causa de `^-[^-]` não casar linha em branco | O `git diff --stat` real | **Lição 163** |
| 3 | **O mais caro:** declarar a tela do mapa encerrada com "seis pontos" quando havia sete. O `displayFeedsCard()` escapou do padrão de `grep` | **A tela do usuário**, com `CTO · CTO 01` duplicado | **Lição 164** |

**Sessão de validação anterior:** passo de roteiro sem dizer como trocar de tela
(159); passo construído sobre dado desatualizado (160).

**Por que os erros ficam no texto e no inventário:** o código passa por `php -l`,
`node --check`, `md5`, `diff -rq` e `git diff` — cinco validadores. O texto ao
redor e a *conta* dos pontos não passam por nenhum. As lições 158 a 164 são a
tentativa de dar a essas duas coisas o mesmo tipo de conferência que o código já
tem.

**O que o processo provou de novo:** nenhum dos três erros gravou dado errado,
nenhum quebrou a homologação, e o terceiro custou **um bloco extra**, não uma
correção de emergência — porque o roteiro pediu a tela que o revelou.
