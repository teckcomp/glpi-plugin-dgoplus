# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v15 — 28/08/2026. Substitui o v14 integralmente.
> Emitido ao fim de uma **sessão de validação**: nenhuma linha de código mudou.
> Versão **1.3.16**, `master` em **`e1fce73`**.
>
> **O que o v15 traz de novo em relação ao v14:**
>
> 1. **Dois dos três blocos "entregues e não exercitados" fecharam em tela:**
>    o **5e** e o **5c**. Sobra o 5g-1b.
> 2. **A pendência 4 tem resposta.** Os dois `CTO 01` são ativos distintos,
>    ids **35** e **36**, ambos de papel **CTO**.
> 3. **A `#36` não está morta.** Tem a **E3 ocupada** — purgá-la sem desmontar
>    antes destruiria um vínculo confirmado em silêncio.
> 4. **O v14 errava sobre o papel da `CTO 01`.** A tela mostra os dois como CTO.
> 5. **Três lições novas: 159, 160, 161.**
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

⚠️ **E há uma terceira pergunta, que o v15 acrescenta:** *"como estão os DADOS
da homologação?"* — elementos, papéis, vínculos, portas ocupadas. Essa resposta
**só vale se lida em tela na sessão** (lição 160). O contexto guarda o retrato,
e o retrato envelhece mais rápido que o código.

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
| `master` em 28/08 (fim da sessão de validação) | commit **`e1fce73`**, versão **1.3.16** |
| Versão em homologação | **1.3.16** — ✅ **conferida em tela** (Configuração → Plugins) e por `grep` no `setup.php` do servidor |
| **Paridade** | ✅ **Provada nesta sessão**: `git status --short` no servidor voltou **vazio** e o `git log -1` bateu com o `ls-remote` |
| Arquivos no repositório | **30** (27 do plugin + 3 em `docs/`) |
| GLPI | 11.0.6, Debian, `/var/www/html/glpi`, Apache como `www-data` |
| **Homologação** | **`177.87.230.179`, porta SSH `2078`, usuário `resolutto`** |
| URL externa do GLPI | `http://177.87.230.179:2077/` |
| **Autenticação SSH** | **Chave.** O servidor **recusa senha**. Lição 139 |
| PC do usuário | **Windows, com OpenSSH** (`ssh`/`scp`), **sem Git local** e **sem PuTTY em uso** |
| Assistente | Não tem SSH nem token. Prepara e valida → o usuário aplica, confere por `git diff`, commita e testa |

O shell do servidor está logado como **root** (`root@debian`). O console do GLPI
recusa root puro, então todo comando de console vai com `sudo -u www-data`.

⚠️ **O `e1fce73` só mexe em `docs/`.** O código do 1.3.16 é o do `02b64d5` —
conferido nesta sessão por `diff -rq` entre os dois tarballs: **só os dois
arquivos de `docs/` diferem**. É a lição 143 acontecendo de novo.

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
git diff --stat && git diff         # <<< a conferência do bloco
git add -A && git commit -m "..." && git push
sudo -u www-data php /var/www/html/glpi/bin/console cache:clear
systemctl restart apache2
```

**Conferência rápida de estado, no começo de toda sessão** (rodada e aprovada em
28/08):

```bash
cd /var/www/html/glpi/plugins/dgoplus
git status --short && git log -1 --oneline && grep PLUGIN_DGOPLUS_VERSION setup.php
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

**Ruído conhecido no `php-errors.log`, sem relação com defeito:**

- `Plugin "dgoplus" version changed. It has been deactivated…` — é a lição 116.
  ⚠️ **Aparece também vindo do `public/index.php`, não só do `bin/console`**, e
  fica no log depois de resolvido. **Aviso no log não é estado atual: a fonte da
  verdade é a tela Configuração → Plugins** (lição 114).
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
`v1.3.2`, `v1.3.8`. As versões **1.3.3 a 1.3.16 não têm tag**: são degraus
internos da Fase 5. A próxima tag sai quando a Fase 5 fechar.

### Outros plugins na mesma base

`fields`, `news`, `behaviors`, **`codexplus` 0.5.2-alpha**, `datainjection`,
`archimap`, `gantt`, `moreticket`, **`projectplus` 1.1.0-beta**, **`shopmap`
0.1.0**, `stab`, `tag`, **`taskplus` 0.2.1-beta**, `tasklists`, `Diagrams` 3.3.14,
`Additional fields` 1.24.4, `Alerts` 1.14.1, `Tag Management` 2.14.6, `Tasks list`
2.1.12.

⚠️ **O `shopmap` tem a mesma trava de nome ambíguo que o 5e trata no DGO+.**

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
arquivo, não há `scp`, não há reinstalação. Tem só o roteiro. Dizer isso na
abertura evita que o usuário procure um zip que não existe.

### Roteiro de teste — o que o 28/08 acrescentou

Além da lição 158 (o roteiro se confere contra o código), a sessão de validação
mostrou duas exigências novas:

- **Todo passo que troca de tela diz COMO chegar lá** (lição 159). "Voltar ao
  elemento X" não basta quando dois elementos têm nomes parecidos.
- **Toda pré-condição de dados é lida em tela antes de virar passo** (lição 160).

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
novo**.

**Padrão de abertura de sessão, provado em 28/08:** `git ls-remote` → baixar o
tarball do HEAD → `diff -rq` contra o commit que o contexto cita → `md5sum` dos
arquivos da seção 3. Custa quatro comandos e diz, sem deduzir nada, se a base
descrita ainda é a base real.

**Número previsto sai de comando, não de olho** (lições 141, 150 e 155).

**Documento é entrega, e entrega tem quatro seções** (lição 145).

**Número de linha citado em documento é ponteiro, não fato** (lição 144).

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
tela em 28/08: a badge da `DGO 01 - PORTO BELO` foi de **0 para 1** logo depois
de a F1.01 dela receber um vínculo, com o campo Nome/Número vazio. Não conferi
qual ramo de `statsForDgo()` produz isso.

### Vínculos

`glpi_plugin_dgoplus_links`: **uma linha, dois lados**. Regras fechadas:

- **Sem `is_deleted`.** Recusa apaga a linha.
- **Pendente já ocupa a porta**, nas duas pontas. ✅ Reconfirmado em tela (28/08):
  a proposta pendente já imprime "A porta já conta como ocupada".
- **Uma porta alimenta um destino só.** ✅ Reconfirmado em tela: porta com vínculo
  não mostra o formulário de proposta, só o destino e o Desmontar; e o
  `Link::propose()` recusaria com *"Esta porta já alimenta um destino."*
- **Hierarquia permissiva**: pode pular nível, nunca subir nem empatar.
  `Link::hierarchyAllows()` compara posição (`$order[$src] < $order[$dst]`), então
  sabe que desceu, **não sabe quanto** — lacuna do 5d.
- **Só vínculo confirmado sobe na trilha** (4e). ✅ Reconfirmado: card pendente
  não imprime a linha Trilha.
- `Link::propose()` é o **ponto único de criação**.
- **Recusar e confirmar pedem o mesmo direito (UPDATE)**, de propósito.
  Desmontar pede DELETE.

**`Link::upstreamLevels($itemtype, $items_id, ?$from_entry_id)`** — desde o 5c o
terceiro parâmetro restringe o **nível 0** a uma entrada. Do nível 1 para cima o
comportamento do 4e continua. Entrada inválida devolve trilha **vazia**, nunca a
do elemento inteiro. Chamador único: `MapController::displayEntryCard()`.

✅ **Validado em tela em 28/08** com um elemento de dois pais distintos — ver a
seção 7.

⚠️ **Pendente que envelhece não avisa ninguém.**

### Comentário do elemento

`DgoIdentity::applyComment()` é o **ponto único**, usado pelo POST clássico e
pelo `ajax/dgocomment.php` (lição 47). Grava o campo `comment` **nativo** do
`PassiveDCEquipment`, então aparece no Histórico do ativo.

**Desde o 5g-1b** ele devolve **`denied => true`** quando a recusa é de
permissão de sessão — só nesse ramo, porque só ele autoriza travar a página
inteira. O endpoint repassa a chave; o JS a usa. **A regra continua num lugar
só** — o endpoint não checa direito, de propósito.

### Auto-save — os dois JS, ambos corrigidos

**`public/dgoplus.js`** (440 linhas) — o painel da porta. Desde o 5g-1 o
`.catch()` distingue 403 de queda de rede; `permissionDenied` é estado do módulo.

**`public/dgoplus-identity.js`** (362 linhas) — o comentário do ativo. **Desde o
5g-1b:** o `Error` carrega `status`; `data.denied === true` liga
`permissionDenied` e guarda a frase do PHP em `deniedText`; o 403 é tratado
**antes** do `fallbackOnFailure`.

⚠️ **Lido no código em 28/08, para o roteiro do 5g-1b:** depois de
`permissionDenied` ficar verdadeiro, o `save()` **sai antes do `fetch`** e só
repinta a frase. Logo, **uma única linha `POST … dgocomment.php`** no
`other_vhosts_access.log` é mesmo o resultado esperado, por mais vezes que o
usuário digite. O status dessa única linha é **200**, não 403 (lição 154).

**Princípio, do bloco 4a:** o formulário continua sendo um POST completo e
válido; se o JS não carregar, o botão Salvar recarrega a página.

`mount()` e `mountComment()` **saem na entrada** se não acharem o
`[data-...-flag]`, e esse elemento só é impresso para quem tem escrita — **sem o
direito, o JS nem se instala** (lição 151).

### Permissão na tela — a regra do 5g-2b

**O painel da porta nomeia o direito; a moldura do mapa fica calada.**

Mensagem de permissão só aparece para quem **esbarrou na recusa**. O lugar de
explicar direito ao administrador é a **aba DGO+ do perfil** — feito no 5g-3.

### Nome de elemento na tela — o que o 5e cobre e o que não cobre

✅ **O 5e cobre o seletor de destino**, e só ele. A desambiguação é **por
colisão**: o rótulo é `nome (PapelLabel)`, e só quando esse rótulo inteiro se
repete é que entra o sufixo ` #<id>`.

⚠️ **Consequência que o roteiro tem que respeitar:** dois elementos de mesmo nome
e **papéis diferentes** produzem rótulos distintos e **não** ganham sufixo. Isso
é correto por desenho, e não deve ser lido como defeito.

⚠️ **Pontos de impressão que continuam ambíguos** — medidos em tela em 28/08,
com os dois `CTO 01` existindo:

| Onde | O que imprime | O que falta |
|---|---|---|
| Seção **Alimenta** da porta de origem | `E2 de CTO 01` | de qual `CTO 01` |
| **Faixa de entradas** do elemento | `E1 F1.01` / `E2 F1.01` | de qual elemento vem cada uma |
| **Abas** do mapa | duas abas `CTO 01` | qual é qual |

São matéria do **5e-2**, que está aprovado e **não detalhado**.

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
desde o PAINEL-1a. Aceita os parâmetros do motor de busca em forma de **array**.

⚠️ **Observado em tela (28/08):** passando `searchtype=equals` num campo
`datatype string`, a tela renderiza **"contém"**.

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
rodados em `02b64d5`, que é o mesmo código do `e1fce73`:

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
checagem de direito. **O que falta não é permissão, é tela** — candidato **5i**.

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

**Impressões digitais do 1.3.16** — baixadas do GitHub e medidas pelo assistente
**nesta sessão**, no commit `e1fce73`, idênticas às do `02b64d5`:

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
| 114 | **Homologação pode estar atrás do `master` sem ninguém ter errado.** ⚠️ **Ampliada em 28/08:** aviso antigo no log também não é estado atual — a fonte é a tela Configuração → Plugins |
| 115 | `-P` maiúsculo para a porta, no `pscp` e no `scp` |
| 116 | **Bump de versão no `setup.php` conta como mudança de instalação.** `--force` + `activate`. *O warning no log é o sintoma, e ele PERMANECE no arquivo depois de resolvido* |
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
| 133 | ✅ Corrigida pelo 5g-1 no `dgoplus.js` e pelo 5g-1b no `dgoplus-identity.js` |
| 134 | **Anexo a ativo exige UPDATE no ativo.** O formulário é do core |
| 135 | **O direito "Data centers" também fica na aba Gerência** |
| 136 | **A raiz web efetiva vem de `conf-enabled/glpi.conf`** |
| 137 | **Com o clone Git, `git diff` É a conferência do bloco** |
| 138 | **O escopo real de um bloco de permissão está no PONTO ÚNICO** |
| 139 | **O envio é `scp`, não `pscp` — e o servidor recusa senha** |
| 140 | **Nome final colide na pasta Downloads e o `scp` manda o ANTIGO em silêncio** |
| 141 | **Número previsto de `git diff --stat` sai de comando** |
| 142 | **As requisições caem no `other_vhosts_access.log`.** O `Referer` traz `edit=<tubo>-<fibra>` |
| 143 | **Com `docs/` versionado, o HEAD avança sem o código mudar.** ✅ Aconteceu de novo em 28/08 (`02b64d5` → `e1fce73`, só `docs/`) |
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
| 154 | **Defeito descrito por analogia com outro arquivo é dedução, não leitura.** O `ajax/port.php` responde 403 porque `applyInput()` usa `checkRight` (que lança); o `ajax/dgocomment.php` responde **200** porque `applyComment()` devolve `ok:false`. **Ler o endpoint antes de descrever o defeito** |
| 155 | **Previsão de `grep -c` errada mesmo com o método certo à mão.** Todo número que vai para a entrega sai de comando, sem exceção |
| 156 | **Classe CSS não confirmada não entra.** Na dúvida, usar o recipiente que o próprio projeto já provou |
| 157 | **Caminho de arquivo em comando nunca vai abreviado.** Todo comando entregue tem que ser colável como está |
| 158 | **Roteiro de teste também se confere contra o código.** O roteiro descreve telas: se a tela não foi lida, o passo é chute |
| **159** | **Passo de roteiro que troca de elemento tem que dizer COMO chegar lá.** O roteiro do 5c mandava "voltar à `DGO 01 - PORTO BELO`, F1.01" logo depois de um passo na `DGO 01` — e as duas têm uma F1.01. O usuário ficou na tela errada, tentou propor de uma porta que já tinha vínculo e recebeu a recusa correta do `Link::propose()`, que pareceu defeito. **Custo: uma rodada.** A regra: todo passo que muda de tela nomeia o controle a clicar (a aba, o botão) e um traço que confirme que chegou (o cabeçalho, a badge). ⚠️ **E note a ironia:** o roteiro sofreu do mesmo mal que o 5e-2 existe para curar |
| **160** | **Dado de DADOS da homologação, vindo do contexto, não é pré-condição de teste até ser relido em tela.** O v14 registrava que a `CTO 01` "mudou de papel para PTO", e sobre isso o assistente construiu um passo 0.3 mandando igualar os Tipos — que era desnecessário: a tela mostrou os dois `CTO 01` sob a badge `CTO 2` e os dois rótulos com `(CTO)`. **Código dura; dado de teste, não.** O contexto é fonte da verdade sobre o CÓDIGO, nunca sobre o estado do banco |
| **161** | **Antes de purgar elemento "de teste", ler as quatro entradas.** A `CTO 01 #36` (ex-`CTO TESTE 5f2b`) estava registrada como "64 portas mortas" — e tem a **E3 ocupada**. O `PurgeCleaner` faz a faxina em silêncio, então a purga levaria um vínculo confirmado sem uma linha de aviso (lição 14). **Purga de elemento passa a exigir a leitura da faixa de entradas antes** |

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
- **`Session::checkRight` lança; devolver array não lança.**

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
| 5g-1b | Auto-save do comentário não reenvia recusa | **Entregue (28/08), 1.3.12, `15d0c30`.** ⚠️ **A recusa continua NÃO exercitada** — é o último da fila de validação |
| 5g-3 | Nota de permissões na aba DGO+ do perfil | Fechado e validado em tela (28/08), 1.3.13 + 2b |
| PAINEL-1a | "Ver todos" em Atividade recente | Fechado e validado em tela (28/08), 1.3.13 |
| README | Reescrito | Fechado (28/08), 1.3.13 |
| 2b | Nota vira card abaixo da matriz; relatório ganha volta ao mapa | Fechado e validado (28/08), 1.3.14, `327c62c` |
| 5b | Seletor de piso lista só pisos com elemento | Fechado e validado em tela (28/08), 1.3.15, `e3faec0` |
| **5e** | **Desambiguação por colisão no seletor de destino** | ✅ **FECHADO E VALIDADO EM TELA (28/08), 1.3.15.** Seletor mostrou `CTO 01 (CTO) #35` e `CTO 01 (CTO) #36`. ⚠️ A metade "nome único fica sem sufixo" não foi exercitada: a lista tinha só esses dois candidatos |
| **5c** | **Trilha parte da entrada, não do elemento** | ✅ **FECHADO E VALIDADO EM TELA (28/08), 1.3.16, `02b64d5`.** Cenário de dois pais montado e conferido nos dois cards |

---

## 6. Dívidas conhecidas

1. ~~README desatualizado~~ ✅ **QUITADA (28/08).**
2. **Sem catálogo de tradução**: interface pt-BR fixa. ⚠️ **Decisão de produto
   pendente:** demanda real ou higiene? Tocaria os 27 arquivos.
3. **Lista integral de lições (1–113)** não incorporada. ⚠️ **O caminho barato
   está esgotado.** Resta buscar o documento original nas conversas antigas.
4. ~~Sem tag nem Release~~ ✅ **QUITADA (27/08).**
5. **A skill `glpi-plugin-teckcomp` está desatualizada**: host, usuário, porta,
   `pscp` → `scp`, e a ordem de entrega com `git diff`. **Aprovada** — bloco SKILL.
6. ~~Texto fala de "Desmontar" sem o botão existir~~ ✅ **QUITADA (28/08).**

---

## 7. Medições de campo

⚠️ **Existem DUAS bases, e confundi-las é o erro mais caro desta seção.**
⚠️ **E vale a lição 160: tudo nesta seção é retrato datado, não estado atual.**

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

### Homologação — a base de teste

Números do painel, lidos em 28/08 **antes** da sessão de validação:

| | |
|---|---|
| Elementos | **36** — DIO 5, DGO 14, CTO 11, PTO 6. **nenhum na lixeira** |
| Portas | **1889** — 36 documentadas, 1853 livres |
| Ocupação geral | **1,9%** |
| Localizações | **9** |

⚠️ A sessão de validação mexeu na base: a `DGO 01` perdeu uma porta documentada
(F1.04, desmontada) e a `DGO 01 - PORTO BELO` ganhou uma (F1.01). **O total de 36
provavelmente não mudou, mas isso é dedução — o painel não foi relido.**

### `Outlet Porto Belo`, piso `MALL - PORTO BELO` — o cenário de teste

**Estado ao FIM da sessão de validação de 28/08, lido em tela:**

| Elemento | id | Papel | Estado |
|---|---|---|---|
| `DGO 01` | — | DGO | badge **5 de 16**. F1.01 → `CTO 01 #35` E1 (confirmado, cristian.b 27/08). F1.02 `1202`, F1.03 `1214`, F1.05 `2153-01…`, F1.06 `2153`. **F1.04 agora livre** |
| `DGO 01 - PORTO BELO` | — | DGO | badge **1 de 16**. F1.01 → `CTO 01 #35` E2 (confirmado, Claudio Morett 28/08) |
| `CTO 01` | **35** | **CTO** | **a verdadeira.** E1 ← `DGO 01` F1.01; E2 ← `DGO 01 - PORTO BELO` F1.01. E3 e E4 livres. 0 de 16 na grade |
| `CTO 01` | **36** | **CTO** | **a ex-`CTO TESTE 5f2b`, renomeada.** ⚠️ **E3 OCUPADA** — origem não verificada. E1, E2 e E4 livres |
| `PISO VAZIO TESTE` | — | — | piso sem elementos, criado para o 5b. **Pode ser removido** |

⚠️ **Correções que o v15 faz no v14:**

- O v14 dizia que a `CTO 01` **"mudou de papel (CTO → PTO)"**. **A tela desmente:**
  as duas aparecem sob a badge `CTO 2` e os dois rótulos do seletor trazem
  `(CTO)`. O item de higiene "devolver o papel da `CTO 01`" **não existe mais**.
- O v14 dizia que a `#36` eram **"64 portas mortas"**. **Não estão mortas:** a E3
  tem vínculo. Ver a lição 161.

**Vínculos pendentes conhecidos** (do v14, ⚠️ não relidos nesta sessão):
`CTO01 → PTO 4 · E3` (cristian.b) e `DIO 001 → CTO 001 · E2` (Claudio Morett).

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
| Commit único para "fechar tudo de uma vez" | **Descartada (28/08)** | `git revert` jogaria fora dez mudanças sem dizer qual quebrou |
| `checkRight(UPDATE)` dentro do `ajax/dgocomment.php` | **Descartada (28/08, 5g-1b)** | Criaria uma segunda sede da regra |
| Trava de duplicados que barre também na ficha nativa | **Impossível, não descartada** | A ficha, o `datainjection` e o SQL não passam pelo plugin |
| **Igualar os Tipos dos dois `CTO 01` para o teste do 5e** | **Descartada (28/08)** | Passo construído sobre dado desatualizado do v14; a tela mostrou que os dois já eram CTO. Lição 160 |
| **Purgar a `CTO 01 #36` como estava planejado** | **Suspensa, não descartada** | ⚠️ **Pré-condição nova:** desmontar a E3 antes. Lição 161 |

### Decisões de produto tomadas em 28/08 (mantidas)

**BADGE-C · variante C** — a badge do elemento mostra **dois contadores lado a
lado**: `0/16 grade` e `2/4 entradas`. Não mistura os números e **não mexe na
ocupação geral**. A linha de entradas só aparece para papéis que podem receber
(`Setting::roleReceivesFeed()`), e **some por regra, não por acaso**.
⚠️ Escopo medido: toca `Port::statsForDgo()`, `MapController::renderBadges()`
(público, o AJAX reescreve) e `ajax/port.php`.

**Contador de entradas nos cards do painel · SEPARADO** — os cards "Ocupação
geral" e "Portas livres" ganham contagem de entradas **à parte**. É a mesma
contagem do BADGE-C, em outro lugar — por isso os dois andam no mesmo bloco.

**5d · confirmar em dois tempos** — vínculo que pula degrau não grava na
primeira tentativa: devolve "isso pula os níveis X e Y, confirma?" e um segundo
envio grava. ⚠️ **O trabalho real é fazer destino e entrada sobreviverem ao
redirect** — hoje ele leva só `edit=<tubo>-<fibra>`. ⚠️ **Duas limitações
aceitas:** o marcador viaja no POST e pode ser forjado; e nada fica registrado
depois.

**5e-2 · rótulo único + trava de duplicados** — ⚠️ **APROVADO MAS NÃO
DETALHADO. O usuário pediu explicitamente para discutir antes de qualquer
código.** O que já está fechado: a trava existe, e o critério é **nome +
localização**, independente de papel e de piso. O que falta discutir: **como** o
rótulo desambigua em cada tela, **onde** aplicar, **o que a trava faz com os
duplicados que já existem**, e **o texto da recusa**.

⚠️ **Insumo novo de 28/08, agora medido em tela e não só no código:** os três
pontos ambíguos confirmados são o rótulo `E2 de CTO 01` na seção Alimenta, a
faixa de entradas (`E1 F1.01` / `E2 F1.01`) e as duas abas homônimas do mapa. O
`MapController` imprime o nome em pelo menos **8 pontos** (linhas 1340, 1413,
1683, 1708, 2041, 2764, 2831, 2973, 3048), mais `Dashboard` 271 e `Pending`.
**Oito cópias da regra é o que os pontos únicos existem para evitar** — o caminho
é um método único de rótulo.

---

## 9. Próximo passo imediato

1. **5g-1b — o último não exercitado.** Sentada própria, porque mexe em permissão
   de perfil no meio da sessão:
   - abrir o mapa com o usuário de teste e **deixar a aba aberta**;
   - tirar **ATUALIZAR** de "Portas de DGO" do perfil **Tecnicos N1 (ID 12)**
     — sem recarregar a aba (lição 151);
   - digitar no comentário do elemento e sair do campo **3×**;
   - **esperado:** a frase de recusa se repete e sai **uma única linha**
     `POST … 200 … dgocomment.php` em
     `/var/log/apache2/other_vhosts_access.log`. **O status é 200, não 403**
     (lição 154);
   - **devolver o direito ao perfil ao fim.**
2. **Higiene, agora com pré-condição:**
   - **desmontar a E3 da `CTO 01 #36`** e só então purgá-la (lição 161);
   - remover o piso `PISO VAZIO TESTE`;
   - descobrir quem alimenta essa E3 — abrir a `F1.06` da `DGO 01` e ler o
     destino na seção Alimenta é a hipótese mais barata, ligada à pendência 12.
3. **Commit 4b — bloco 5d**, confirmação em dois tempos.
4. **Commit 5 — BADGE-C + contador de entradas separado**, que são a mesma
   contagem em dois lugares.
5. **5e-2 — DISCUTIR ANTES DE CODAR.** Ver a seção 8, com os três pontos de tela
   já medidos.
6. **SKILL**, **5h-2**, **5i**, e o **bloco de deploy em produção** (com rollback
   próprio).
7. **REV** — revisão competitiva, ao fim de tudo.

---

## 10. O que correu mal do lado do assistente

Seção aberta no v14 porque o usuário perguntou se deveria se preocupar com a
frequência de erros. **O padrão se manteve: erro em texto, nunca em código.**

**Sessão de validação de 28/08 — dois erros, os dois em roteiro:**

| # | Erro | Quem pegou | Vira |
|---|---|---|---|
| 1 | Passo mandando "voltar à `DGO 01 - PORTO BELO`, F1.01" sem dizer como trocar de elemento, logo depois de um passo na `DGO 01` | O usuário, na tela errada | **Lição 159** |
| 2 | Passo 0.3 mandando igualar os Tipos dos dois `CTO 01`, construído sobre um dado do v14 que a tela desmentiu | A própria tela | **Lição 160** |

**Sessão anterior (seis commits), para não perder o histórico:** comentário
citando itens inexistentes; `alert alert-info` sem confirmar comportamento;
`grep -c` previsto de cabeça; passo de roteiro impossível; caminho de log
abreviado; decisão registrada ao contrário. Todos em texto.

**Por que só em texto:** o código passa por `php -l`, `node --check`, md5 e
`git diff` — quatro validadores. O texto ao redor não passa por nenhum. **A
lição 158 e agora a 159 e a 160 são a tentativa de dar ao roteiro o mesmo tipo de
conferência que o código já tem.**

**O que o processo provou de novo:** nenhum dos dois erros gravou dado errado,
nenhum chegou ao `master`, e o segundo custou zero — a tela corrigiu antes de o
passo virar ação.
