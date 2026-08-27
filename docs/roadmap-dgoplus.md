# DGO+ — roadmap

> Companheiro do `contexto-dgoplus-v5.md`. **Substituir**, nunca acumular.
>
> **Versão:** v5 — 27/08/2026. Sucede o v4.
> A mudança que justifica a versão nova: a **dívida de repositório foi liquidada**
> — `master` em 1.3.2, paridade md5 provada nos 27 arquivos — e o projeto ganhou
> uma **regra de governança** (o GitHub é canônico), nascida de um incidente real
> de perda de dados. A Fase 5 volta a ser exclusivamente permissões + refinamento.

---

## Parte A — resultado da revisão (histórico, não mexer)

Os 8 passos da revisão que gerou a Fase 5, com a decisão de cada um.

| # | Passo | Decisão |
|---|---|---|
| 1 | Estado do ambiente | Homologação estava em **1.3.0**, não 1.3.1. Corrigido pelo Bloco 5-sync. Lição 114 |
| 2 | Bloco 5a | **Aprovado.** Poda em cascata funciona nos três seletores |
| 3 | Ciclo do vínculo | **Aprovado.** Modelo sem lixeira honrado. Ajuste: Bloco 5c |
| 4 | Papéis | **Aprovado.** Quatro degraus bastam; splitter fora da hierarquia |
| 5 | Escopo Localização → Piso | Piso **fica**; lote **descartado**; meia-medida → Bloco 5b |
| 6 | A grade no dia a dia | **Aprovado.** Digitação um a um é o fluxo certo |
| 7 | Permissões | **Maior achado.** A causa era `CREATE` do próprio plugin (lição 118) → Blocos 5f-1, 5f-2, 5f-3 e 5g |
| 8 | Relatório | **Bug** (erro 1054, lição 121) → **Bloco 5h — RESOLVIDO e COMMITADO em 27/08** |

---

## Parte B — Fase 5

### Concluído

**5a · Escopo Localização → Piso no seletor de destino** — fechado e validado
(23/08), versão 1.3.1.

**5h · Relatório: JOIN da coluna Localização** — **fechado, validado e commitado
(27/08)**, versão 1.3.2, commit `bd28ffd`. A opção 8 do `Port` passou a juntar por
`glpi_passivedcequipments` com `jointype => 'itemtype_item_revert'` e
`specific_itemtype => PassiveDCEquipment::class`. Coluna exibe, ordena e exporta.

**Dívida de repositório** — **liquidada (27/08)**. `master` e homologação
idênticos por md5 nos 27 arquivos. Ver seção 0 do contexto v5 para a regra que
nasceu daí.

### Prioridade 1 — o que ainda está desprotegido

Nenhum destes é código. Todos são "artefato que existe em um lugar só", que é
exatamente a classe de risco que já custou trabalho uma vez.

**DOC · `docs/` no repositório** — subir `contexto-dgoplus-v5.md` e
`roadmap-dgoplus-v5.md` para uma pasta `docs/` no `master`. É a memória do
projeto — decisões, lições, o porquê de cada bloco — e hoje vive só na base de
conhecimento do chat. Custa dois uploads.

**REL · Tag `v1.3.2` + Release com o zip anexado** — hoje o pacote instalável
existe só na pasta `Downloads` do PC. Com a Release, o artefato de instalação
passa a ser reconstruível a partir do GitHub. Mata também a dívida "release
publicada não conferida", que vem desde o v3.

**GIT · Decidir o fluxo definitivo de commit** — um comando de triagem no
servidor (`ls -la .../.git`, `which git`, `find ... -name .git`) diz se já existe
clone lá. Se existir, commitar passa a ser `git add/commit/push` de dentro do
PuTTY, e a classe inteira de divergência some. Se não existir, a decisão é entre
criar o clone no servidor ou instalar Git for Windows. **Relevante antes do
5f-1**, que toca sete pontos em cinco arquivos — volume em que o commit pela web
começa a doer.

### Prioridade 2 — permissões (a dor operacional)

Os três primeiros mudam o significado do direito; o quarto documenta o resultado
na tela. **A ordem importa**: o 5g só depois dos outros, senão documenta a regra
antiga e vira mentira na tela.

⚠️ **Pré-requisito ainda aberto: a pendência 1** (anexo exige `datacenter`
UPDATE?). Ela decide o desenho final e é teste de tela, sem código.

✅ **Números de linha reconferidos em 27/08** contra o commit `bd28ffd` — o v4
avisava que o 5h os havia deslocado. Estão na seção 3 do contexto v5.

**5f-1 · Documentar porta e vínculo passam a exigir UPDATE do plugin**

`MapController:2946` (`$found ? UPDATE : CREATE` → sempre UPDATE) e **`:3179`**
(propor vínculo: CREATE → UPDATE), mais o que `ensureEntry`/`ensureGrid` exigem.
Resolve a lição 118: preencher célula da grade deixa de ser "criar".

**5f-2 · Comentário e criação de elemento migram para o direito do plugin**

`DgoIdentity:216`: comentário passa de `datacenter` UPDATE para
`plugin_dgoplus_port` UPDATE. `MapController:412` e **`:1522`**: criar elemento
passa de `datacenter` CREATE para `plugin_dgoplus_port` CREATE.

**5f-3 · Remover a exigência de `datacenter` READ**

Os sete pontos `can($items_id, READ)` passam a `Session::haveAccessToEntity()`,
preservando a proteção do 3m sem o acoplamento. **É este bloco que faz
"Dispositivos passivos" sumir do menu do técnico** — o objetivo original.

Os sete pontos, verificados: `Port.php` **383**, **634**, **739**;
`ajax/port.php` 48; `MapController.php` 949; `Link.php` 689; `DgoIdentity.php` 323.

✅ Pré-requisito **respondido**: `glpi_passivedcequipments` **tem** `is_recursive`
(schema `glpi-empty.sql` do 11.0.6).

**5g · Nota explicativa na aba DGO+ do perfil**

Quadro abaixo da matriz, no `ProfileTab`, dizendo o que cada direito cobre e o
que depende de permissão fora do DGO+ (Documentos, Localização, excluir o ativo,
configurar papéis). Nasce da lição 119.

### Prioridade 3 — refinamento

**5h-2 · Habilitar o filtro por Localização no relatório**

Remover `'nosearch' => true` da opção 8. Ficou de fora do 5h de propósito: um
bloco, uma mudança. Agora que o join está provado, é a menor entrega possível —
um atributo, um teste (filtrar por uma localização e conferir a contagem). **Bom
candidato para estrear a ordem nova** (commit → zip nascido do commit → aplicar).

⚠️ Vale checar o comportamento com `forcegroupby => true`, que joga o critério
para `HAVING`.

**5b · Piso lista só os pisos com candidato**

`refreshFloors()` (`public/dgoplus.js:342`) passa a cruzar cada piso com os
candidatos, como `refreshDst()` já faz. Some o piso que leva a "nenhum elemento
neste escopo". Só JS; Ctrl+F5.

**5c · Trilha da entrada, não do elemento**

`MapController:2497` chama `Link::upstreamLevels()` (definida em `Link.php:735`)
com o elemento, então o card da "Entrada E2" mostra a cadeia que chega pela E1.
**Consumidor único**, o que torna a troca segura.

**5d · Aceite no servidor para salto de degrau**

`propose()` recusa o salto na primeira tentativa e devolve o motivo nomeando o
degrau pulado; a tela reexibe com aceite explícito. A distância está a uma
subtração: `$order[$dst] - $order[$src] > 1`.

Mexe no **ponto único de criação** — bloco mais delicado da fase.

**5e · Desambiguar nomes repetidos na lista de destino** ⚠️ *depende de confirmação*

Aparecem dois "PTO 001 (PTO)" na lista. Se forem ativos distintos de mesmo nome,
escolher o errado cria topologia errada que nenhuma validação pega.

**Antes de executar: confirmar se são ativos diferentes ou o mesmo duplicado.**
Se for o mesmo aparecendo duas vezes, é bug e sobe para a prioridade 1.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? (lição 120) | **Aberta.** Marcar UPDATE em Data centers no perfil Tecnicos N1 e ver se o formulário de envio aparece. **É o próximo passo de teste** |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ **Respondida (27/08):** `itemtype_item_revert` + `specific_itemtype` obrigatório |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ **Respondida (27/08):** tem, confirmado no schema do core 11.0.6 |
| 4 | Os dois "PTO 001" são ativos distintos? | **Aberta.** Ativos → Dispositivos passivos, conferir ids |
| 5 | Existe clone Git no servidor? | **Aberta.** Um `ls` responde. Ver bloco GIT |

Se a resposta da 1 for "sim", vira decisão de produto: ou o técnico ganha
`datacenter` UPDATE (e volta a ver o menu, contrariando o 5f-3), ou fica sem
anexar, ou entra um bloco grande de Fase 6 para o plugin escrever o próprio
formulário de upload.

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | **README desatualizado.** `dgoplus-v1.0.0.zip` (38, 45, 56), três tabelas quando são quatro (111, 142), linha 119 sobre portas órfãs | Bloco pequeno, sem risco |
| 2 | **Sem catálogo de tradução** | Bloco médio; decisão de produto antes |
| 3 | **Lista integral de lições (1–113)** não incorporada | Depende de achar o documento antigo |
| 4 | **Sem tag/Release do 1.3.2; zip só no `Downloads`** | Bloco REL, prioridade 1 |
| 5 | **Clone Git no servidor não localizado** | Bloco GIT, prioridade 1 |
| 6 | **Documentos fora do repositório** | Bloco DOC, prioridade 1 — em resolução |

*(A dívida "5h aplicado mas não commitado", que era a nº 1 do v4, foi liquidada.)*

---

## Parte E — estacionamento

Candidatos, **nenhum comprometido**, com a fonte declarada.

| Ideia | Fonte |
|---|---|
| Endpoint AJAX para o vínculo, chamando o mesmo `Link::propose()` | Comentário no próprio `Link.php` |
| Vínculo porta ↔ chamado, pelo `itemtype_link`/`items_id_link` do schema | Roadmap original (Fase 4) |
| Notificações nativas em evento de porta | Roadmap original (Fase 5) |
| Widgets no dashboard nativo do GLPI | Roadmap original (Fase 6) |
| Atualizar o README | Dívida 1 — escopo fechado |
| Formulário próprio de upload de anexo | Depende da pendência 1 |
| Colunas novas no relatório (papel, piso, estado do vínculo) | Passo 8 — agora que o join polimórfico está resolvido, o caminho para "Piso" é o mesmo do 5h |
| Rotina periódica de conferência md5 servidor × `master` | Nasceu da sessão de 27/08; hoje é manual, um comando |

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus-v5.md`. Dez ideias avaliadas e recusadas com
motivo — piso em lote, splitter como papel, importação CSV, corrigir a acentuação
do CSV pelo plugin. **Não ressuscitar sem fato novo.**

---

## Próximo passo imediato

1. **Bloco DOC** — subir `docs/` com o contexto v5 e este roadmap.
2. **Pendência 1** (anexo) — teste de tela, decide o desenho final das permissões.
3. **Bloco GIT** — um `ls` no servidor decide o fluxo antes do 5f-1.
4. Seguir com **5f-1 → 5f-2 → 5f-3 → 5g**, nesta ordem. O 5h-2 cabe em qualquer
   intervalo: é um atributo, e é o candidato natural para estrear a ordem
   commit → zip → aplicar.
