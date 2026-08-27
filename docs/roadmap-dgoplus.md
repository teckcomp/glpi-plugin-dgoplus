# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v6 — 27/08/2026 (sessão da tarde). Sucede o v4 e o v5.
> A mudança que justifica a versão nova: **a prioridade 1 inteira foi liquidada**
> — documentos no repositório, clone Git no servidor, Release publicada — e o
> **5f-1a** entregou a primeira correção de permissão da Fase 5. A fase volta a
> ser exclusivamente permissões + refinamento, agora com um fluxo de entrega que
> não depende mais de upload manual.

---

## Parte A — resultado da revisão (histórico, não mexer)

Os 8 passos da revisão que gerou a Fase 5, com a decisão de cada um.

| # | Passo | Decisão |
|---|---|---|
| 1 | Estado do ambiente | Homologação estava em **1.3.0**, não 1.3.1. Corrigido pelo 5-sync. Lição 114 |
| 2 | Bloco 5a | **Aprovado.** Poda em cascata funciona nos três seletores |
| 3 | Ciclo do vínculo | **Aprovado.** Modelo sem lixeira honrado. Ajuste: Bloco 5c |
| 4 | Papéis | **Aprovado.** Quatro degraus bastam; splitter fora da hierarquia |
| 5 | Escopo Localização → Piso | Piso **fica**; lote **descartado**; meia-medida → Bloco 5b |
| 6 | A grade no dia a dia | **Aprovado.** Digitação um a um é o fluxo certo |
| 7 | Permissões | **Maior achado.** Causa era `CREATE` do próprio plugin (lição 118) → **5f-1a RESOLVIDO**, faltam 5f-1b, 5f-2, 5f-3, 5g |
| 8 | Relatório | **Bug** (erro 1054, lição 121) → Bloco 5h **RESOLVIDO e COMMITADO** |

---

## Parte B — Fase 5

### Concluído

**5a · Escopo Localização → Piso no seletor de destino** — fechado e validado
(23/08), versão 1.3.1.

**5h · Relatório: JOIN da coluna Localização** — fechado, validado e commitado
(27/08), versão 1.3.2, commit `bd28ffd`.

**DOC · `docs/` no repositório** — **fechado (27/08), commit `1ded500`.** Contexto
e roadmap vivem em `docs/`, sem sufixo de versão no nome.

**GIT-1 e GIT-2 · Git no servidor** — **fechados (27/08).** Não existia clone; foi
criado sobre os arquivos do servidor, com `git status` limpo de saída. Push
autenticado por token fine-grained, credencial persistida em
`/root/.git-credentials`. **A classe inteira de divergência servidor × repositório
saiu de cena.**

**REL · Tag `v1.3.2` + Release** — **fechada e conferida (27/08).** Zip gerado por
`git archive` a partir da tag; o assistente baixou o anexo e provou que os **30
arquivos são idênticos por md5** ao tarball do commit.

**5f-1a · Documentar porta exige UPDATE** — **fechado e validado em tela (27/08)**,
versão 1.3.3, commit `6efab96`. `Port::applyInput` deixou de exigir CREATE para
criar linha, e a trava da tela passou a perguntar a mesma coisa. Técnico com
ATUALIZAR e **sem CRIAR** documentou uma célula em branco e o valor persistiu.
**A lição 118 está morta.**

⚠️ **Resíduo:** os passos 5 (editar célula já documentada) e 6 (esvaziar sem
DELETE) não foram confirmados explicitamente. Confirmar no 5f-1b.

---

### Prioridade 1 — permissões (a dor operacional)

A ordem importa: o **5g só depois dos outros**, senão documenta a regra antiga e
vira mentira na tela.

✅ **Números de linha reconferidos em 27/08** contra o commit `6efab96` — o 5f-1a
deslocou o `Port.php` em +12 linhas e o `MapController.php` em +4.

**5f-1b · Propor vínculo passa a exigir UPDATE do plugin**

Par natural do 5f-1a e o bloco que devolve o seletor de vínculo ao técnico. Hoje
ele vê "Sem vínculo. Propor um vínculo exige permissão de criação."

Pontos, **verificados**:

| Arquivo | Linha | O quê |
|---|---|---|
| `src/Port.php` | 664, 685 | `ensureEntry`: restaurar e criar linha de entrada |
| `src/Port.php` | 771, 803 | `ensureGrid`: restaurar e criar linha de grade |
| `src/Link.php` | 431 | `propose()` — **o ponto único de criação** |
| `src/MapController.php` | 3183 | a trava da tela |
| `src/MapController.php` | 3185 | o texto "exige permissão de criação", que passa a mentir |

⚠️ **Lição 138:** `ensureGrid`/`ensureEntry` têm **um chamador só** (`Link::propose`)
— confirmado por grep. Isso torna a troca segura e é o que permitiu partir o
5f-1 em duas metades sem separar a tela do ponto único.

**5f-2 · Comentário e criação de elemento migram para o direito do plugin**

`DgoIdentity:216`: comentário passa de `datacenter` UPDATE para
`plugin_dgoplus_port` UPDATE. `MapController:412` e `:1522`: criar elemento passa
de `datacenter` CREATE para `plugin_dgoplus_port` CREATE.

Efeito colateral bom: some a tarja "Você tem permissão apenas de leitura neste
ativo" que o técnico vê hoje no cartão de Comentários.

**5f-3 · Remover a exigência de `datacenter` READ**

Os sete pontos `can($items_id, READ)` passam a `Session::haveAccessToEntity()`,
preservando a proteção do 3m sem o acoplamento. **É este bloco que faz
"Dispositivos passivos" sumir do menu do técnico** — o objetivo original.

Os sete pontos, verificados em `6efab96`: `Port.php` **383**, **646**, **751**;
`ajax/port.php` 48; `MapController.php` 949; `Link.php` 689; `DgoIdentity.php` 323.

✅ Pré-requisito respondido: `glpi_passivedcequipments` **tem** `is_recursive`.

✅ **Sem contrapartida pendente:** a pendência do anexo foi decidida (ver Parte C).

**5g · Nota explicativa na aba DGO+ do perfil**

Quadro abaixo da matriz, no `ProfileTab`, dizendo o que cada direito cobre e o que
depende de permissão fora do DGO+ (Documentos **+ Data centers UPDATE** para
anexo, Localização, excluir o ativo, configurar papéis). Nasce da lição 119.

⚠️ **Escopo ampliado pela lição 133:** incluir também a mensagem do auto-save.
Hoje um 403 de permissão chega ao usuário como **"Falha ao salvar. Use o botão
Salvar."**, indistinguível de erro de rede. O `dgoplus.js` precisa tratar o 403
separadamente e nomear o direito faltante.

---

### Prioridade 2 — refinamento

**5h-2 · Habilitar o filtro por Localização no relatório**

Remover `'nosearch' => true` da opção 8. Um atributo, um teste (filtrar por uma
localização e conferir a contagem). ⚠️ Vale checar o comportamento com
`forcegroupby => true`, que joga o critério para `HAVING`.

**5b · Piso lista só os pisos com candidato**

`refreshFloors()` (`public/dgoplus.js:342`) passa a cruzar cada piso com os
candidatos, como `refreshDst()` já faz. Só JS; Ctrl+F5.

**5c · Trilha da entrada, não do elemento**

`MapController:2497` chama `Link::upstreamLevels()` com o elemento, então o card
da "Entrada E2" mostra a cadeia que chega pela E1. **Consumidor único.**
⚠️ *Números de linha desta seção são do commit `bd28ffd`; o 5f-1a deslocou o
`MapController` em +4 a partir da linha 2946 — reconferir antes de escrever.*

**5d · Aceite no servidor para salto de degrau**

`propose()` recusa o salto na primeira tentativa e devolve o motivo nomeando o
degrau pulado; a tela reexibe com aceite explícito. A distância está a uma
subtração: `$order[$dst] - $order[$src] > 1`.

Mexe no **ponto único de criação** — bloco mais delicado da fase. **Fazer depois
do 5f-1b**, que já toca o `propose()`.

**5e · Desambiguar nomes repetidos na lista de destino** ⚠️ *depende de confirmação*

Aparecem dois "PTO 001 (PTO)" na lista. Se forem ativos distintos de mesmo nome,
escolher o errado cria topologia errada que nenhuma validação pega.
**Antes de executar: confirmar se são ativos diferentes ou o mesmo duplicado.**

---

### Prioridade 3 — higiene

**REL-2 · Tag `v1.3.3` + Release** — agora custa três comandos no PuTTY. Fazer
quando a Fase 5 tiver um marco, não a cada sub-bloco.

**PAGER · `git config --global core.pager cat`** — uma linha, evita o paginador
prender o `git diff`. Cabe em qualquer bloco.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **Respondida (27/08): SIM.** Provado em tela. Virou decisão de produto: **o técnico não anexa** — supervisor cobre. Lição 134 |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ **Respondida:** `itemtype_item_revert` + `specific_itemtype` obrigatório |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ **Respondida:** tem, confirmado no schema do core |
| 4 | Os dois "PTO 001" são ativos distintos? | **Aberta.** Ativos → Dispositivos passivos, conferir ids |
| 5 | Existe clone Git no servidor? | ✅ **Respondida (27/08): não existia. Foi criado.** |
| 6 | A "Falha ao salvar" da F1.02 foi 403 por DELETE? | **Aberta.** Repetir com F12 na aba Rede: 403 confirma; 500 é outra coisa |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | **README desatualizado.** `dgoplus-v1.0.0.zip` (38, 45, 56), três tabelas quando são quatro (111, 142), linha 119 sobre portas órfãs. Agora há Release real para apontar | Bloco pequeno, sem risco |
| 2 | **Sem catálogo de tradução** | Bloco médio; decisão de produto antes |
| 3 | **Lista integral de lições (1–113)** não incorporada | Depende de achar o documento antigo |
| 4 | **Sem tag/Release do 1.3.3** | Bloco REL-2 |
| 5 | **Passos 5 e 6 do 5f-1a sem confirmação** | Dois cliques no 5f-1b |
| 6 | **`core.pager` não configurado** | Uma linha |

*(As dívidas do v5 — documentos fora do repositório, clone Git não localizado,
Release inexistente — foram todas liquidadas em 27/08.)*

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
| **Formulário próprio de upload de anexo** | **Sem dono:** a decisão de 27/08 foi que o técnico não anexa. Só ressuscita com fato novo da operação |
| Colunas novas no relatório (papel, piso, estado do vínculo) | Passo 8 — o caminho para "Piso" é o mesmo do 5h |
| Rotina periódica de conferência md5 servidor × `master` | **Obsoleta:** `git status` faz isso melhor |
| `git pull` no servidor como forma de aplicar bloco | Nasceu do GIT-2 — evitaria o `pscp` quando o assistente puder commitar, mas ele não tem token nem deve ter |

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. Doze ideias avaliadas e recusadas com
motivo — piso em lote, splitter como papel, importação CSV, anexo pelo técnico,
documentos versionados no repositório. **Não ressuscitar sem fato novo.**

---

## Próximo passo imediato

1. **Bloco 5f-1b** — propor vínculo passa a exigir UPDATE. Pontos já mapeados e
   verificados. Confirmar de passagem os passos 5 e 6 pendentes do 5f-1a.
2. **5f-2** → **5f-3** → **5g**, nesta ordem.
3. **5h-2** cabe em qualquer intervalo: é um atributo.
