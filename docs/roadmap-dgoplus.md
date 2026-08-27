# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v7 — 27/08/2026 (sessão da noite). Sucede o v6.
> A mudança que justifica a versão nova: o **5f-1b** fechou, e com ele a **metade
> do problema de permissão da Fase 5** — documentar porta e propor vínculo agora
> cabem em ATUALIZAR. Os dois passos que o 5f-1a deixou sem confirmação foram
> liquidados pelo log do Apache, e o método de entrega ganhou duas travas novas
> (nome de arquivo com o bloco, `md5sum` de `/tmp` obrigatório).

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
| 7 | Permissões | **Maior achado.** Causa era `CREATE` do próprio plugin (lição 118) → **5f-1a e 5f-1b RESOLVIDOS**, faltam 5f-2, 5f-3, 5g |
| 8 | Relatório | **Bug** (erro 1054, lição 121) → Bloco 5h **RESOLVIDO e COMMITADO** |

---

## Parte B — Fase 5

### Concluído

**5a · Escopo Localização → Piso no seletor de destino** — fechado e validado
(23/08), versão 1.3.1.

**5h · Relatório: JOIN da coluna Localização** — fechado, validado e commitado
(27/08), versão 1.3.2, commit `bd28ffd`.

**DOC · `docs/` no repositório** — fechado (27/08), commit `1ded500`.

**GIT-1 e GIT-2 · Git no servidor** — fechados (27/08). A classe inteira de
divergência servidor × repositório saiu de cena.

**REL · Tag `v1.3.2` + Release** — fechada e conferida (27/08), 30 arquivos
idênticos por md5 ao tarball do commit.

**5f-1a · Documentar porta exige UPDATE** — fechado e validado em tela (27/08),
versão 1.3.3, commit `6efab96`. **A lição 118 está morta.**
✅ **Resíduo liquidado pelo 5f-1b** (ver abaixo).

**5f-1b · Propor vínculo exige UPDATE** — **fechado e validado em tela + log
(27/08), versão 1.3.4, commit `a690010`.** Cinco `checkRight(CREATE)` viraram
`UPDATE` — dois em `ensureEntry`, dois em `ensureGrid` e o do ponto único
`Link::propose` — mais a trava da tela e a mensagem, que agora nomeia o direito
que falta e onde ele mora.

Técnico com **ATUALIZAR e sem CRIAR** propôs vínculo de uma célula em branco
(F1.04 da DGO 01 → E2 da CTO 01), confirmou do outro lado, e as duas linhas
nasceram no banco. Proveniência conferida por md5 contra o commit publicado.

**Os dois passos pendentes do 5f-1a, fechados pelo `other_vhosts_access.log`:**

| Requisição | Célula | Status | Veredito |
|---|---|---|---|
| `edit=1-3` | F1.03, já documentada | **200** ×2 | Passo 5 aprovado |
| `edit=1-2` | F1.02, esvaziar sem DELETE | **403** ×7 | Passo 6 aprovado |

**PAGER · `core.pager cat`** — aplicado (27/08).

---

### Prioridade 1 — permissões (a dor operacional)

A ordem importa: o **5g só depois dos outros**, senão documenta a regra antiga e
vira mentira na tela.

⚠️ **Os números de linha abaixo são do commit `6efab96`.** O 5f-1b acrescentou
**+19 linhas ao `Port.php`** e **+6 ao `MapController.php`** — **reconferir em
`a690010` antes de escrever qualquer um destes blocos.**

**5f-2 · Comentário e criação de elemento migram para o direito do plugin**

`DgoIdentity:216`: comentário passa de `datacenter` UPDATE para
`plugin_dgoplus_port` UPDATE. `MapController:412` e `:1522`: criar elemento passa
de `datacenter` CREATE para `plugin_dgoplus_port` CREATE.

Efeito colateral bom e **já visto em tela nesta sessão**: some a tarja "Você tem
permissão apenas de leitura neste ativo" que o técnico vê no cartão de
Comentários.

⚠️ **Candidato a divisão.** São duas áreas independentes — comentário e criação de
elemento. Se o roteiro passar de ~8 passos, parte em `5f-2a` e `5f-2b`, como o
5f-1.

**5f-3 · Remover a exigência de `datacenter` READ**

Os sete pontos `can($items_id, READ)` passam a `Session::haveAccessToEntity()`,
preservando a proteção do 3m sem o acoplamento. **É este bloco que faz
"Dispositivos passivos" sumir do menu do técnico** — o objetivo original.

Os sete pontos, em `6efab96`: `Port.php` **383**, **646**, **751**;
`ajax/port.php` 48; `MapController.php` 949; `Link.php` 689; `DgoIdentity.php` 323.

✅ Pré-requisito respondido: `glpi_passivedcequipments` **tem** `is_recursive`.
✅ **Sem contrapartida pendente:** a pendência do anexo foi decidida (Parte C).

**5g · Nota explicativa na aba DGO+ do perfil — e as mensagens de erro**

Quadro abaixo da matriz, no `ProfileTab`, dizendo o que cada direito cobre e o
que depende de permissão fora do DGO+ (Documentos **+ Data centers UPDATE** para
anexo, Localização, excluir o ativo, configurar papéis). Nasce da lição 119.

⚠️ **Escopo, agora com três frentes:**

1. **O 403 do auto-save.** Lição 133, **confirmada com prova**: o `ajax/port.php`
   responde 403 e o usuário lê "Falha ao salvar. Use o botão Salvar" —
   indistinguível de erro de rede. O `dgoplus.js` precisa tratar o 403
   separadamente e nomear o direito faltante.
2. **A insistência.** Sete 403 seguidos para uma ação só: o auto-save reenvia a
   cada blur, e permissão não muda entre uma tentativa e outra. Depois do
   primeiro 403 naquela célula, **parar de reenviar**.
3. **Texto que fala de ação indisponível.** O painel diz "Desmontar remove o
   vínculo dos dois lados" mesmo quando o botão não existe por falta de DELETE.

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

`Link::upstreamLevels()` é chamado com o elemento, então o card da "Entrada E2"
mostra a cadeia que chega pela E1. **Consumidor único.**
⚠️ *Linha era `MapController:2497` no `bd28ffd`; deslocada duas vezes desde então
(+4 pelo 5f-1a, +6 pelo 5f-1b). Reconferir em `a690010`.*

**5d · Aceite no servidor para salto de degrau**

`propose()` recusa o salto na primeira tentativa e devolve o motivo nomeando o
degrau pulado; a tela reexibe com aceite explícito. A distância está a uma
subtração: `$order[$dst] - $order[$src] > 1`.

Mexe no **ponto único de criação** — bloco mais delicado da fase. O 5f-1b já
tocou o `propose()` e o deixou com o `checkRight` no lugar certo, então o caminho
está limpo.

**5e · Desambiguar nomes repetidos na lista de destino** ⚠️ *depende de confirmação*

Aparecem dois "PTO 001 (PTO)" na lista. Se forem ativos distintos de mesmo nome,
escolher o errado cria topologia errada que nenhuma validação pega.
**Antes de executar: confirmar se são ativos diferentes ou o mesmo duplicado.**

---

### Prioridade 3 — higiene

**REL-2 · Tag `v1.3.4` + Release** — três comandos no `ssh`. Fazer quando a Fase 5
tiver um marco, não a cada sub-bloco. O 1.3.3 fica sem Release, e tudo bem: a
Release é artefato de instalação, não registro de histórico (isso é o Git).

**SKILL · Atualizar a skill `glpi-plugin-teckcomp`** — host, usuário, porta **e o
comando de envio** (`pscp` → `scp`). Ela ainda manda para `192.168.1.50`, que está
morto, e com um cliente que não autentica neste servidor.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **Respondida (27/08): SIM.** Virou decisão de produto: **o técnico não anexa**. Lição 134 |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ **Respondida:** `itemtype_item_revert` + `specific_itemtype` obrigatório |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ **Respondida:** tem, confirmado no schema do core |
| 4 | Os dois "PTO 001" são ativos distintos? | **Aberta.** Ativos → Dispositivos passivos, conferir ids |
| 5 | Existe clone Git no servidor? | ✅ **Respondida (27/08): não existia. Foi criado.** |
| 6 | A "Falha ao salvar" da F1.02 foi 403 por DELETE? | ✅ **Respondida (27/08): SIM, 403 — sete deles.** Provado no `other_vhosts_access.log`. Lição 133 confirmada |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | **README desatualizado.** `dgoplus-v1.0.0.zip` (38, 45, 56), três tabelas quando são quatro (111, 142), linha 119 sobre portas órfãs | Bloco pequeno, sem risco |
| 2 | **Sem catálogo de tradução** | Bloco médio; decisão de produto antes |
| 3 | **Lista integral de lições (1–113)** não incorporada | Depende de achar o documento antigo |
| 4 | **Sem tag/Release do 1.3.3 e do 1.3.4** | Bloco REL-2 |
| 5 | **Skill `glpi-plugin-teckcomp` desatualizada** | Bloco SKILL |
| 6 | **Texto fala de "Desmontar" sem o botão existir** | Cabe no 5g |

*(As dívidas do v6 — passos 5 e 6 do 5f-1a, `core.pager` — foram liquidadas em
27/08.)*

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
| **Formulário próprio de upload de anexo** | **Sem dono:** a decisão de 27/08 foi que o técnico não anexa |
| Colunas novas no relatório (papel, piso, estado do vínculo) | Passo 8 — o caminho para "Piso" é o mesmo do 5h |
| Rotina periódica de conferência md5 servidor × `master` | **Obsoleta:** `git status` faz isso melhor |
| `git pull` no servidor como forma de aplicar bloco | Nasceu do GIT-2 — evitaria o `scp` quando o assistente puder commitar, mas ele não tem token nem deve ter |
| **Badge da CTO contar entradas, não só grade** | Observação do teste do 5f-1b: "CTO 01 · 0 de 16 documentadas" com E1 e E2 ocupadas. **Pode ser correto de propósito** — decidir antes de mexer |

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. Quatorze ideias avaliadas e recusadas com
motivo — piso em lote, splitter como papel, importação CSV, anexo pelo técnico,
documentos versionados no repositório, DELETE para recusar vínculo, `pscp` como
veículo de envio. **Não ressuscitar sem fato novo.**

---

## Próximo passo imediato

1. **Bloco 5f-2** — comentário e criação de elemento migram para o direito do
   plugin. **Reconferir as linhas em `a690010` antes de escrever.** Avaliar se
   parte em 5f-2a (comentário) e 5f-2b (criar elemento).
2. **5f-3** → **5g**, nesta ordem.
3. **5h-2** cabe em qualquer intervalo: é um atributo.
