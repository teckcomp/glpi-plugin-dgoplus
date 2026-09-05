# DGO+ — contexto para novo chat

> Documento único do projeto. **Substituir**, nunca acumular, ao fim de cada sessão
> e sempre que um bloco fechar.
>
> **Versão deste documento:** v22 — 05/09/2026. Substitui o v21 integralmente.
> Versão **1.3.24**, `master` em **`b4437e5`** (bloco 5d, código + bump).
>
> **O que o v22 traz de novo em relação ao v21:**
>
> 1. **O commit dos docs v21 FOI FEITO** antes desta sessão — `master` em
>    `999401c`, provado por tarball no início da sessão (docs contêm o v21;
>    `setup.php` ainda em 1.3.23).
> 2. **Bloco 5d entregue, commitado (`b4437e5`) e VALIDADO em tela:**
>    proposta que PULA degrau na hierarquia não grava na primeira submissão —
>    volta com aviso ("pula degrau") e botão "Confirmar mesmo assim";
>    o segundo tempo grava normalmente. Descida de um degrau segue gravando
>    na primeira, sem aviso (provado em tela: `#39 → #33`). Opção A
>    (destino e entrada preservados no redirect) escolhida em sessão.
> 3. **Pergunta do usuário respondida e registrada:** o 5d **não trava** o
>    vínculo DIO→CTO — é ciência em dois cliques, não proibição. Proibir o
>    pulo de verdade reverteria a "hierarquia permissiva" da Fase 4 e é
>    decisão de produto nova, não aberta.
> 4. **Fato novo da homologação (ambiente vivo, lição 160):** a `F1.02` do
>    `#39` apareceu com vínculo **CONFIRMADO** para `#41 E1` — alguém propôs
>    E confirmou depois do desmonte de 04/09. O cenário fixado do 5d ficou
>    ocupado; o teste rodou com célula livre do `#39` → **`#42 E1`**.
>    O vínculo confirmado da F1.02 **não foi desmontado** no teste (decisão
>    de preservar possível exercício real dos técnicos); estado atual dele:
>    reler em tela.
> 5. **Ruído de log NOVO, candidato à lista de conhecidos:** `glpi.CRITICAL`
>    do `CacheClearCommand` no `cache:clear` do console — backtrace 100%
>    Symfony/console, nenhum frame de plugin, e o comando terminou com
>    "Cache esvaziado com sucesso". Não investigado; observar nas próximas
>    aplicações antes de promover a ruído conhecido.
> 6. **Nenhuma lição nova numerada.** As mais recentes seguem 165 e 166.
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
| **Como estão os DADOS da homologação?** | **Só a tela, lida na sessão** (lição 160). **A homologação é ambiente vivo dos técnicos de campo** — em 05/09 a F1.02 do `#39` apareceu com vínculo confirmado que nenhum doc conhecia |

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
faz parte do bloco** (lição 165). No 5d o `git diff --stat` bateu EXATO com o
previsto por comando (3 files, +158/−9), e a paridade do commit publicado foi
provada por md5 depois do push.

O zip sobrevive só como artefato de Release.

### ⚠️ A skill cadastrada está desatualizada DE PROPÓSITO

**Decisão do usuário (04/09): a skill `glpi-plugin-teckcomp` cadastrada NÃO
será trocada.** Ela ainda descreve `pscp`/PuTTY, `192.168.1.50` e zip — tudo
abolido. Isso é **ruído conhecido**:

- A fonte da verdade do ambiente é o **`SKILL-glpi-plugin-teckcomp.md` na base
  do projeto** (md5 `edc469d2a1f5a9400b330143c0bf3891`), somado a este contexto.
- Quando a skill carregada e o contexto divergirem, **o contexto manda**.
- Não voltar a propor a troca sem fato novo (decisão negativa, seção 8).

---

## 1. Ambiente e acessos

| | |
|---|---|
| Produto | **DGO+** (`dgoplus`), plugin do GLPI 11 |
| Repositório | `github.com/teckcomp/glpi-plugin-dgoplus`, branch **`master`** — **público** |
| `master` em 05/09 | commit **`b4437e5`** (5d), versão **1.3.24** — push visto na sessão |
| Último commit de CÓDIGO | o próprio `b4437e5` |
| Versão em homologação | **1.3.24** — aplicada, reinstalada e ativada em 05/09 |
| **Paridade** | ✅ Provada por md5 na sessão: `Link.php`, `MapController.php` e `setup.php` do commit publicado = arquivos entregues |
| Arquivos no repositório | **31** (28 do plugin + 3 em `docs/`) |
| **`docs/` no repositório** | `contexto-dgoplus.md`, `roadmap-dgoplus.md`, `README.md` — **nomes SEM versão; a versão vive no cabeçalho**. Conteúdo atual: **v21** (commit `999401c`); o v22 entra por cima |
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
o último commit de CÓDIGO pode ser anterior. Normal (lição 143). Após o commit
dos docs v22, o HEAD será de docs e o código seguirá no `b4437e5`.

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
⚠️ **Candidato novo (05/09, não investigado):** `glpi.CRITICAL` do
`CacheClearCommand` no `cache:clear` do console — backtrace só Symfony, o
comando termina com sucesso. Observar antes de promover a conhecido.

### Topologia web

Apache 80/443 interno; externo por `177.87.230.179:2077`.
`DocumentRoot /var/www/html/glpi/public` via `conf-enabled/glpi.conf`. Nada de
`plugins/` é alcançável como arquivo pelo navegador.

### Release

`v1.3.8` publicada em 27/08 (zip 177 KB, sha256 `34e1fd…ef16`); `v1.3.2`
continua publicada. Tags: `v1.0.0` … `v1.3.2`, `v1.3.8`. **As versões 1.3.3 a
1.3.24 não têm tag** — degraus internos da Fase 5. Próxima tag quando a Fase 5
fechar.

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
> (Regra que também motivou o 5e-4: id sempre visível no seletor de destino.)

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

⚠️ **Sessão de VALIDAÇÃO não é entrega de bloco** — só roteiro.
⚠️ **Bloco sem cenário de teste na homologação não é entregue.**
⚠️ **Decisão vigente pode ser REABERTA pelo usuário** — o gatilho previsto é
vontade do dono; o assistente prova no código o comportamento atual ANTES de
mexer. Em 05/09 o inverso também aconteceu: a pergunta "isso trava o vínculo
direto?" foi respondida COM o código lido na sessão (é ciência, não bloqueio),
e a decisão não foi reaberta.

### Roteiro de teste — exigências acumuladas

- Se confere contra o código antes de sair (lição 158). Na sessão do 5d:
  `propose`, `hierarchyAllows`, `skipWarning`, o formulário Alimenta e os dois
  módulos do `dgoplus.js` (poda e entradas ocupadas) foram lidos ANTES de
  qualquer afirmação — inclusive o `rebuild(keepValue)` que prova que a poda
  inicial preserva a pré-seleção.
- Todo passo que troca de tela diz COMO chegar lá (lição 159) — a busca do
  mapa NÃO é caminho para elemento (procura portas). Caminho certo: escopo na
  Localização + clique na aba.
- Toda pré-condição de dados é lida em tela antes de virar passo (lição 160) —
  em 05/09 isso salvou o roteiro: o par fixado (`F1.02`) apareceu ocupado e o
  cenário mudou para célula livre → `#42 E1` sem custo.
- Passo que prevê "não muda" também é passo (provado: `#39 → #33` gravou na
  primeira, sem aviso).
- Passo que prova a decisão de projeto vem nomeado como tal.

### Nome de arquivo entregue leva o bloco

`Link-5d.php`, `MapController-5d.php`, `setup-5d.php`. O `cp` renomeia
(lição 140). Docs versionam no nome do arquivo ENTREGUE
(`contexto-dgoplus-v22.md`); no repositório o `cp` grava sem versão
(`docs/contexto-dgoplus.md`).

### O repositório é público — usar isso por padrão

```
git ls-remote https://github.com/teckcomp/glpi-plugin-dgoplus.git refs/heads/master
https://codeload.github.com/teckcomp/glpi-plugin-dgoplus/tar.gz/<sha>
```

Preferir `codeload` com SHA ao `raw` (lição 132); `api.github.com` bate no
limite anônimo. **Padrão:** baixar tarball do commit atual, editar cópia,
validar, provar por `diff -rq` que só o escopo mudou; depois do push, baixar o
commit publicado e provar paridade por md5 (feito no 5d).

**Número previsto sai de comando** (lições 141, 150, 155, 163).

### O core do GLPI também é legível

`github.com/glpi-project/glpi`, tag `11.0.6`; classes `Glpi\` em
`src/Glpi/...`; schema em `install/mysql/glpi-empty.sql`. ⚠️ **O CSS do tema
NÃO é legível por esse caminho** (lição 156). Para arquivo de TAG (estável),
`raw.githubusercontent.com` serve — o problema do raw é commit recente.

### O sandbox do assistente TEM PHP e Node

`php -l` (8.3.6) e `node --check` (v22.22.2). `apt-get update` e
`apt-get install -y php-cli` em dois comandos (lição 126). ⚠️ O sandbox pode
nascer SEM o php-cli instalado — instalar faz parte do preparo, não é erro.

### Práticas abolidas

Lista integral mantida (lições 114–166), sem adição em 05/09.
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
`Setting::getRoleLabel()` = `strtoupper` do papel (verificado no 5d).
⚠️ Produção mostra 1 elemento fora dos papéis.

### Portas

Uma tabela, dois `kind`: `KIND_GRID` (tubo × fibra, ABNT/EIA) e `KIND_ENTRY`
(E1–E4, `tube_num = 0`). Chave única `(itemtype, items_id, tube_num,
fiber_num)`, `kind` fora (lição 112). **`Port::applyInput()` é o ponto único
de gravação** — faz o `checkRight(UPDATE)` que lança o 403. Grade padrão 4×16
= 64 posições (lição 146). Porta sem acoplador não conta como documentada.

Porta de grade **com vínculo e sem nome CONTA como documentada** (exercitado
2× em 04/09). `statsForDgo` conta linhas da grade (`is_deleted=0`) e desconta
as sem-acoplador. Porta esvaziada e apagada some da contagem (V1 de 04/09).

**Carimbo de documentação (bloco 3s):** `documentStamp()` é o ponto único de
`users_id_documenter`/`date_documented`; carimba só quando o VALOR do código
muda. Não é retroativo (portas pré-3s sem carimbo no relatório: esperado).

### Histórico — mecanismo provado (v20, íntegro)

`Port` estende `CommonDBChild` com `dohistory = true`; sem `$logs_for_parent`
declarado vale o default `true` do core — toda gravação de porta gera linha no
Histórico do elemento pai, com **`user_name` do usuário logado** (técnico
aparece como autor; provado em tela com `cristian.b` e `teste.001`).
`history_blacklist = ['users_id_documenter', 'date_documented']`. `Link` tem
`dohistory = true` próprio.

### Vínculos

`glpi_plugin_dgoplus_links`: uma linha, dois lados. Sem `is_deleted` — recusa
E desmonte apagam. Pendente já ocupa a porta. Uma porta alimenta um destino
só. Hierarquia permissiva: pode pular nível, nunca subir nem empatar;
`hierarchyAllows()` só compara ordem (`<`).

**Bloco 5d (1.3.24) — o pulo de degrau exige ciência em dois tempos:**

- **`Link::skipWarning(?$src_role, ?$dst_role): ?string`** é o PONTO ÚNICO da
  medida do pulo e da frase do aviso. Devolve a frase quando a diferença de
  ordem é > 1 (`"Este vínculo pula degrau na hierarquia: DIO alimenta CTO sem
  passar por DGO."`; DIO→PTO lista `DGO, CTO`); devolve `null` na descida de
  um degrau e em papel inválido/fora de ordem (problema do `hierarchyAllows`,
  que roda antes).
- **`Link::propose()`** ganhou o parâmetro `skip_ack` e a chave `needs_ack`
  no retorno (contrato: `{ok, error, id, needs_ack}`). A checagem roda
  **DEPOIS de todas as recusas de leitura e ANTES de materializar** —
  `needs_ack` só aparece quando o resto passaria, e o primeiro tempo não
  deixa rastro no banco (regra do 4c-2 intacta).
- **`actionProposeLink()`**: `needs_ack` não é erro — redireciona carregando
  `skip_dst`/`skip_slot` na URL, sem mensagem de sessão.
- **Formulário Alimenta (segundo tempo):** valida `skip_dst` pelo MESMO
  `skipWarning()` (forjado/sem pulo → nada acende); pré-seleciona destino,
  entrada e a localização do destino (o JS poda por localização e o
  `rebuild(keepValue)` preserva a seleção — lido no `dgoplus.js`); renderiza
  pílula `bg-yellow-lt` "pula degrau" + `form-hint` com a frase, hidden
  `skip_ack=1` e botão **"Confirmar mesmo assim"**.
- ⚠️ **Limitações ACEITAS (decisão de produto):** o marcador é forjável no
  POST; nada fica registrado depois do segundo tempo; trocar o destino para
  OUTRO pulo depois do primeiro tempo grava sem novo aviso. **É ciência, não
  bloqueio nem auditoria** — pergunta respondida em 05/09: o vínculo direto
  DIO→CTO continua permitido.

Só confirmado sobe na trilha (4e). **`Link::propose()` é o ponto único de
criação.** Recusar e confirmar pedem UPDATE; desmontar pede DELETE.
`upstreamLevels(…, ?$from_entry_id)` restringe o nível 0 a uma entrada (5c).
⚠️ Pendente que envelhece não avisa ninguém.

⚠️ **Dois "confirmar" distintos — não confundir:** o Confirmar/Recusar no
destino é o fluxo do pendente (Fase 4), depois do vínculo gravado. O
"Confirmar mesmo assim" do 5d é NA PROPOSTA, antes de gravar. Coexistem.

### O rótulo de elemento — `src/ItemLabel.php`

Ponto único do nome em tela. `forRow`/`forItem` = `nome · localização · #id`;
`shortForRow` = `nome · #id`. Localização via `Dropdown::getDropdownName` —
`completename` (árvore com `>`), cache estático.

✅ **Decisão fechada (28/08): `completename` FICA.** Nome vazio imprime `sem
nome`; ausente devolve `elemento #%d`.

**Consumidores (medidos no `fbf1952`; 5e-4 e 5d NÃO mexeram neles):**
`MapController.php` 8, `Link.php` 6, `Dashboard.php` 1.

⚠️ **O seletor de DESTINO continua fora do `ItemLabel`** — formato próprio
`nome (PAPEL) #id`. É a dívida 7, **mantida por decisão (opção A do 5e-4)**.

### O seletor de destino — 5e-4 + 5d

Montagem em `MapController.php` (região ~3570 no `b4437e5`), select nativo
escrito à mão (select2 esconderia opção podada por JS). **TODO candidato leva
`#id`** — `nome (PAPEL) #id`; sem nome: `#id (PAPEL)` sem repetir. O `value`
do `<option>` sempre foi o id. **Novidade do 5d:** no segundo tempo do pulo,
o destino, a entrada e a localização voltam pré-selecionados (atributo
`selected` condicional; ver seção Vínculos).

### O selo de nome duplicado — 5e-2d-1

Três peças no `MapController`: `duplicateNamesAt()` (consulta própria,
memorizada), `normalizeName()`, `renderDuplicateMark()` (ícone na aba, pílula
`bg-orange-lt`; `DUP_COLOR = '#D68A3A'`). A consulta é própria de propósito —
o selo não pode sumir com filtro de piso/papel. Nome vazio nunca acende.

### Abas — o único modo de exibição (5e-3a e 5e-3b)

Todos os elementos são abas, agrupadas por papel, linha única sem quebra com
rolagem horizontal nativa só no overflow; módulo IIFE no `dgoplus.js`
centraliza a ativa via `scrollLeft` (nunca `scrollIntoView`). Formato
`shortForRow` + selo + contagem. ⚠️ Não medido com dezenas de abas — e a
homologação JÁ TEM localizações maiores; medir quando abrir uma.

### Comentário do elemento

`DgoIdentity::applyComment()` é o ponto único (POST clássico e
`ajax/dgocomment.php`). `denied => true` só na recusa; frase da tela vem do
PHP.

### Auto-save — os dois JS

`public/dgoplus.js` (475 linhas) e `public/dgoplus-identity.js` (362).
403 ≠ rede; recusa não reenvia (5g-1b). `save()` sai cedo em
`current === lastSaved`. Sem o direito, o JS nem se instala (lição 151).
O 5d NÃO tocou nos JS — a preservação do segundo tempo é toda no PHP e os
módulos existentes já se comportam (poda com `keepValue`; o módulo de
entradas só move a seleção se a preservada estiver ocupada).

### Busca e relatório — tabela polimórfica

Jointypes válidos no 11.0.6; para a porta, `itemtype_item_revert` +
`specific_itemtype`. Search options do Port: 1 code, 2 name, 3 itemtype,
5 tube, 6 fiber, 7 comment, 8 Localização (`nosearch`), 9 no_coupler, 10 kind,
11 documentado por, 12 date_documented, 19 date_mod, 121 date_creation.
`Port::getReportUrl()` é o ponto único da URL. **A busca do mapa é GLOBAL e
busca PORTAS** (código/nome/observação) — termo de elemento devolve 0 com
estado vazio falando.

### Schema e direitos

Quatro tabelas: `_ports`, `_panels`, `_floors`, `_links`. Direito
`plugin_dgoplus_port`, matriz de 4 níveis = 15; aba **DGO+**, linha **"Portas
de DGO"**. Tabela de exigências inalterada (READ vê; UPDATE documenta, comenta
e mexe em vínculo; DELETE esvazia/desmonta; CREATE cria; entidade trava toda
gravação; anexos = `document` R+U+C **e `datacenter` UPDATE**; papéis =
`config` UPDATE). `parentIsReachable()` falha fechado.

⚠️ **A Fase 5 ainda não chegou à produção.** Deploy com plano de rollback —
bloco próprio, sem data. Começa por RELER a produção em tela.

### Anexos

Cartão usa formulário do core → exige `datacenter` UPDATE (lição 134). Mas
`CommonDBTM::add()` não checa direito (lição 148) — candidato **5i**.

### Arquivos

**31 no repositório** (28 + 3 em `docs/`).

**Impressões digitais do 1.3.24** (commit `b4437e5`; os três primeiros
conferidos por md5 na sessão contra o publicado; os demais herdados do 1.3.23,
inalterados pelo `diff -rq` do preparo):

```
d59c88c05481f24aa30d4c97496b95eb  setup.php                    (269 linhas)
9a7634edb132423b73bd9357e36b9230→ b61cb5d74230088b7e7c02ffb35ddff2  src/Link.php (1310 linhas)
b68a10857d45aad1ca57e1636c6033bc  src/MapController.php        (3702 linhas)
3d9daa717ad679a9091fbd548ad92191  public/dgoplus.js            (475 linhas)
d58fdb6b783801190a79eb1ace005fca  public/dgoplus-identity.js   (362 linhas)
f8d60d99db81dc8958e67424a844351f  src/ItemLabel.php            (166 linhas)
52ab95366b20809e952972c1c1a9b823  src/Port.php                 (1120 linhas)
36ecd197f374c180a42ef7bbccc47b8c  src/DgoIdentity.php          (381 linhas)
d2baa8fdfdfe4d54cfdd784f59b0443a  src/Dashboard.php            (1282 linhas)
f4d2f1d2773e81bfb6486e15371ef816  src/ProfileTab.php           (184 linhas)
b0e4f1837feab5a54d42868e8d88a4b7  ajax/port.php                (123 linhas)
dae5e817600bfdb6db3345cfa0383ea0  ajax/dgocomment.php           (52 linhas)
4b1c3380384313d07614738dbc52bbd5  front/port.php                (26 linhas)
9e68cde24dfd0694f1bf4bc4fdbffd9f  README.md                    (165 linhas)
```

(A linha do `Link.php` registra a troca: o md5 antigo do 1.3.23 à esquerda,
o vigente à direita. Vigentes: `b61cb5d…` Link, `b68a108…` MapController,
`d59c88c…` setup.)

---

## 4. Lições aprendidas

⚠️ Lacuna 1–113 mantida (dívida 3). A tabela de lições 3–166 permanece
integralmente válida — **nenhuma lição nova numerada em 05/09**. As mais
recentes:

| # | Lição |
|---|---|
| 165 | **Bump de versão no `setup.php` faz PARTE do bloco de código** |
| 166 | **Antes de afirmar a consequência de uma alternativa, escrever o resultado por extenso** |

Reforços de 05/09 (sem número novo): a lição 166 aplicada de novo — as frases
do `skipWarning` foram simuladas por extenso para TODAS as combinações de
papéis antes de codificar; a lição 160 salvou o roteiro (par fixado apareceu
ocupado, cenário trocado em tela sem custo); e o desenho do 5d nasceu de uma
regra de arquitetura, não de gosto — a frase do aviso vive num único método
consumido pela recusa E pela tela, matando a divergência silenciosa na raiz.

**Armadilhas permanentes do GLPI 11**: lista integral mantida.

---

## 5. Estado por bloco

Blocos 1 a 5e-4: fechados e validados (até 1.3.23). **5d fechado e validado
(1.3.24, `b4437e5`)** — roteiro completo em 05/09: primeiro tempo sem gravar
(aviso, pré-seleção, botão trocado), segundo tempo pendente, "não muda"
provado (`#39 → #33` na primeira, sem aviso), limpeza feita, log limpo.

**Nenhum bloco está no estado "entregue e não exercitado".**

---

## 6. Dívidas conhecidas

1. ~~README~~ ✅ quitada.
2. **Sem catálogo de tradução** — decisão de produto pendente.
3. **Lista integral de lições (1–113)** — só pelo documento original.
4. ~~Tag/Release~~ ✅ quitada.
5. ~~Skill desatualizada~~ ✅ quitada por decisão (04/09).
6. ~~"Desmontar" sem botão~~ ✅ quitada.
7. **Seletor de DESTINO fora do `ItemLabel`** — formato próprio
   `nome (PAPEL) #id`. **Mantida por decisão no 5e-4 (opção A).** Reabrir é
   do usuário.
8. ~~Seletor único sem marca de colisão~~ ✅ quitada por remoção (03/09).

---

## 7. Medições de campo

⚠️ **Duas bases; tudo aqui é retrato datado** (lição 160). **A homologação é
ambiente vivo dos técnicos** — reler SEMPRE.

### Produção (retratos de 28/08 e 04/09 — NÃO relidos em 05/09)

- **Elementos/portas (28/08):** 159 elementos (DIO 3, DGO 67, CTO 88, PTO 1;
  2 na lixeira; 1 fora dos papéis); 4944 portas (2220 doc., 44,9%); 9
  localizações COM elementos. Documentadores: Claudio Morett, Kayan Lucas,
  Pedro s, cristian.b.
- **Localizações (04/09):** 427 linhas, VÁRIAS raízes (`Shopping` ~42,
  `Fleury`, `Confiance`, `Padrão`…), até três níveis. Base compartilhada com
  contextos alheios ao DGO+.

### Homologação — painel geral (04/09 — NÃO relido em 05/09)

**41 elementos**: DIO 6, DGO 16, CTO 13, PTO 6; 18 sem porta registrada;
**2165 portas**, 42 documentadas (1,9%), 1 na lixeira; **9 localizações com
elementos** (tabela integral no v21; `A+`, `Bio qualquer > bio001` e
`Shopping itajai/Bigode - 000` eram desconhecidas dos docs).

### `Outlet Porto Belo` — 8 elementos (lido PARCIALMENTE em 05/09)

| Elemento | id | Papel | Selo | Obs |
|---|---|---|---|---|
| `DIO 001` | 39 | DIO | — | ⚠️ **F1.02 com vínculo CONFIRMADO → `#41 E1`** (visto em tela 05/09 — alguém propôs e confirmou após 04/09; NÃO desmontado no teste, preservado como possível exercício real). Célula livre usada no teste do 5d e desmontada. Contadores NÃO relidos após o teste |
| `DGO 01 - PORTO BELO` | 33 | DGO | — | Recebeu o vínculo do passo "não muda" (gravou na primeira); desmontado ao fim |
| `DGO 01` | 34 | DGO | ⚠ par com #37 | 5 doc (retrato de 04/09) |
| `DGO 01` | 37 | DGO | ⚠ par com #34 | 1 doc. **FICA — treinamento** |
| `CTO 01` | 35 | CTO | ⚠ par com #38 | 1 doc |
| `CTO 01` | 38 | CTO | ⚠ par com #35 | 0 doc |
| `TESTE 5e2d2 A` | 41 | CTO | — | ⚠️ **E1 OCUPADA pelo vínculo confirmado da F1.02 do #39** (05/09). **FICA — treinamento** |
| `TESTE 5e2d2 B` | 42 | CTO | — | E1 usada no teste do 5d e liberada no desmonte. **FICA — treinamento** |

⚠️ **O par de teste fixado do 5d (`#39 F1.02 → #41 E1`) ESTÁ OCUPADO** pelo
vínculo confirmado. Cenário substituto exercitado: célula livre do `#39` →
`#42 E1`. Se um bloco futuro precisar de par DIO→CTO livre, reler a tela e
escolher na hora.

**Painel/pendentes:** retrato do v16, não relido. Perfil de teste: `Tecnicos
N1, ID 12`, usuário `teste.001`.

---

## 8. Decisões negativas registradas

Tabela integral do v20/v21 mantida. **Esclarecimento novo (05/09), registrado
para não reabrir sem fato novo:**

- **O 5d NÃO proíbe o pulo de degrau** — é ciência em dois cliques. Proibir
  de verdade (DIO só alimenta DGO) reverteria a "hierarquia permissiva" da
  Fase 4 e é decisão de produto NOVA, não tomada. Perguntado e respondido em
  tela na sessão do 5d.

### Decisões de produto vigentes

- **`completename` FICA (28/08).**
- **`#id` sempre no seletor de destino (5e-4, 04/09)** — `nome (PAPEL) #id`;
  sem nome: `#id (PAPEL)`.
- **5d · confirmar em dois tempos (05/09) — ENTREGUE E VALIDADO.** Opção A:
  destino, entrada e localização preservados no redirect do primeiro tempo.
  Limitações aceitas: marcador forjável no POST; nada registrado depois;
  trocar para OUTRO destino com pulo após o primeiro tempo grava sem novo
  aviso.
- **BADGE-C · variante C** — dois contadores (`0/16 grade` · `2/4 entradas`);
  toca `statsForDgo`, `renderBadges`, `ajax/port.php`. "Antes" provado 3× em
  tela (04/09).
- **Abas sempre, rolagem horizontal** — vigente desde 03/09.
- **Filtro de piso só com pisos ocupados** (bloco 5b).
- **Elementos de treinamento na homologação** — `#37`, `#41`, `#42`
  permanentes; purgá-los exige nova decisão do usuário.

---

## 9. Próximo passo imediato

1. **Commit dos docs v22** no repositório (só `docs/` → sem reinstalação).
   Entra por cima do v21 (commit `999401c`; código no `b4437e5`).
2. **Bloco BADGE-C** — contador de grade e de entradas separados. "Antes" já
   provado; ⚠️ o cenário de dados precisa ser relido em tela (E1 do `#41`
   agora está ocupada por vínculo confirmado — pode até servir de caso de
   teste do contador de entradas).
3. **5h-2** (remover `nosearch` da Localização no relatório — valorizado
   pelas 427 localizações da produção), **5g-3** (nota de permissões de anexo
   na aba de perfil), **5i** (anexo por formulário próprio), **PAINEL-1**
   ("Ver todos" nos cartões do painel), e o **bloco de deploy em produção**
   (com rollback; começa por reler a produção em tela).
4. **Frente shopmap** — bloqueada pela pendência 16 (repositório privado).
5. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.

---

## 10. O que correu mal do lado do assistente

**O padrão se mantém: zero erro em código gravado.** O 5d aplicou de
primeira: md5 conferido, `git diff --stat` batendo exato com o previsto por
comando (3 files, +158/−9), paridade do commit publicado provada por md5,
roteiro completo validado em tela.

**O que o processo provou nesta sessão:** a leitura prévia dos dois módulos
do `dgoplus.js` evitou um defeito de tela (a poda inicial poderia esconder o
destino pré-selecionado — resolvido fazendo a localização inicial acompanhar
o destino, ANTES de qualquer teste); a lição 166 rodou de novo (todas as
frases do aviso simuladas por extenso, incluindo DIO→PTO com dois níveis
pulados); e a mudança de cenário imposta pelo ambiente vivo (F1.02 ocupada)
custou zero retrabalho porque o roteiro exige ler a pré-condição em tela
antes de executar. Deslize interno sem consequência: um encadeamento `&&`
no sandbox do assistente engoliu uma cópia porque `git diff` devolve código 1
quando há diferenças — pego na conferência seguinte, nunca chegou à entrega.
