# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v14 — 28/08/2026. Sucede o v13, do mesmo dia.
> A mudança que justifica a versão nova: **cinco commits fecharam numa sessão
> só** (1.3.11 → 1.3.16), o 5g-1b provou que o v13 descrevia o defeito errado,
> e **quatro decisões de produto novas** entraram.
> Números verificados em `02b64d5`, versão **1.3.16**.

---

## Parte A — resultado da revisão (histórico, não mexer)

| # | Passo | Decisão |
|---|---|---|
| 1 | Estado do ambiente | Homologação estava em 1.3.0. Corrigido pelo 5-sync. Lição 114 |
| 2 | Bloco 5a | **Aprovado.** Poda em cascata funciona nos três seletores |
| 3 | Ciclo do vínculo | **Aprovado.** Modelo sem lixeira honrado. Ajuste: 5c ✅ **FEITO** |
| 4 | Papéis | **Aprovado.** Quatro degraus bastam; splitter fora |
| 5 | Escopo Localização → Piso | Piso fica; lote descartado; meia-medida → 5b ✅ **FEITO** |
| 6 | A grade no dia a dia | **Aprovado.** Digitação um a um é o fluxo certo |
| 7 | Permissões | **Maior achado.** 5f-1a/1b, 5f-2a/2b, 5f-3a/3b, 5g-1, 5g-1b, 5g-2/2b, 5g-3 — **TODOS RESOLVIDOS** |
| 8 | Relatório | **Bug** (erro 1054) → 5h **RESOLVIDO** |

**A frente de permissões da Fase 5 está fechada.** Os dois greps que a guardam,
rodados em `02b64d5`:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

---

## Parte B — Fase 5

### Concluído

| Bloco | O que fez | Versão / commit |
|---|---|---|
| 5a | Escopo Localização → Piso no seletor de destino | 1.3.1 (23/08) |
| 5h | JOIN da coluna Localização no relatório | 1.3.2, `bd28ffd` |
| DOC, GIT-1, GIT-2, REL, REL-2 | `docs/`, clone Git, tags e Releases | 27/08 |
| 5f-1a … 5f-3b | A frente de permissões inteira | até 1.3.8, `0005c90` |
| 5g-1 | Auto-save da porta distingue 403 de rede | 1.3.9, `f94dbe5` |
| 5g-2 / 5g-2b | Telas nomeiam o direito; dicas saem da moldura | 1.3.11, `560fb64` |
| **5g-1b** | **Auto-save do comentário não reenvia recusa** | **1.3.12, `15d0c30`** |
| **5g-3 + PAINEL-1a + README** | **Nota no perfil; "Ver todos"; README reescrito** | **1.3.13, `c9c3546`** |
| **2b** | **Nota vira card abaixo da matriz; volta ao mapa no relatório** | **1.3.14, `327c62c`** |
| **5b + 5e** | **Poda do seletor de piso; desambiguação por colisão** | **1.3.15, `e3faec0`** |
| **5c** | **Trilha parte da entrada, não do elemento** | **1.3.16, `02b64d5`** |

### ⚠️ Entregue mas NÃO exercitado

Três correções estão no `master` sem que o defeito que elas corrigem tenha sido
reproduzido em tela. **Não são "fechadas".**

| Bloco | O que falta | Por que não foi feito |
|---|---|---|
| **5g-1b** | Tirar ATUALIZAR **com a aba aberta**, digitar 3× no comentário, e conferir **uma única linha** `POST … 200 … dgocomment.php` no log do Apache | Exige tirar direito no meio da sessão (lição 151) |
| **5e** | Os dois `CTO 01` devem aparecer como `CTO 01 (PTO) #<id>` no seletor de destino | O usuário olhou as **abas**, que o 5e não toca |
| **5c** | Card E1 mostra só um pai; card E2 mostra só o outro | Exige montar um elemento com **dois pais diferentes** — a homologação não tinha |

**O roteiro do 5c, em detalhe:** desmontar a E2 da `CTO 01`; abrir a
`DGO 01 - PORTO BELO`, porta F1.01, propor para `CTO 01` E2; confirmar na
`CTO 01`. Depois abrir E1 e E2 separadamente.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **SIM.** Reaberta pela lição 148 como candidato 5i |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ `itemtype_item_revert` + `specific_itemtype` |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ tem |
| 4 | Os dois "PTO 001" são ativos distintos? | ⚠️ **Mudou de natureza.** A homologação agora tem **dois `CTO 01` na mesma localização e piso**, criados pelo próprio teste. O 5e existe para isso; a resposta virá quando o passo 7 for exercitado |
| 5 | Existe clone Git no servidor? | ✅ criado |
| 6 | A "Falha ao salvar" da F1.02 foi 403? | ✅ SIM, corrigida pelo 5g-1 |
| 7 | O Histórico registra o técnico como autor? | Dada como ok pelo usuário, sem tela |
| 8 | Limpar `CTO TESTE 5f2b` | **Não feito, e piorou:** foi **renomeado para `CTO 01`** durante o teste do 5e. 64 portas mortas, 3,4% da base |
| 11 | Por que a DGO 01 mudou de portas documentadas? | **Aberta.** Baixo risco |
| **12** | **F1.06 da DGO 01 está "sem acoplador" E com vínculo confirmado ao mesmo tempo** | **Aberta (28/08).** A tela diz que porta sem acoplador *"não pode ser usada"* — e ela está sendo. **Não verificado no código** se `applyInput()` deveria recusar marcar sem-acoplador numa porta já vinculada |
| **13** | **`PISO VAZIO TESTE` e o papel trocado da `CTO 01`** | Sujeira do teste do 5b/5c. Remover |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | ~~README desatualizado~~ | ✅ **quitada (28/08)** |
| 2 | **Sem catálogo de tradução.** ⚠️ Decisão de produto antes: demanda real ou higiene? | Grande; não cabe num bloco |
| 3 | **Lista integral de lições (1–113).** ⚠️ **Caminho barato esgotado** — medido: o código só cita as 30 já registradas | Só pelo documento original |
| 4 | ~~Sem tag/Release~~ | ✅ quitada |
| 5 | **Skill `glpi-plugin-teckcomp` desatualizada** | Bloco SKILL |
| 6 | ~~Texto fala de "Desmontar" sem o botão~~ | ✅ quitada |

---

## Parte E — estacionamento

| Ideia | Fonte |
|---|---|
| Endpoint AJAX para o vínculo, chamando o mesmo `Link::propose()` | Comentário no `Link.php` |
| Vínculo porta ↔ chamado | Roadmap original (Fase 4) |
| Notificações nativas em evento de porta | Roadmap original (Fase 5) |
| Widgets no dashboard nativo | Roadmap original (Fase 6) |
| Colunas novas no relatório (papel, piso, estado do vínculo) | Passo 8 |
| Comentário do elemento com carimbo de autor na tela | Observação do 5f-2a |
| **Aviso de vínculo pendente que envelhece** | Achado de 28/08: um pendente há 7 dias |
| **PAINEL-1b · "Ver todos" em "Equipamentos mais ocupados"** | ⚠️ **Medido:** a tela de destino **não existe** — ocupação é por elemento, o relatório é por porta. Seria criar tela |
| **Bloco de deploy da Fase 5 em produção** | ⚠️ **Não é opcional, só não tem data.** 4 documentadores reais mudam de permissão. Precisa de rollback próprio |
| **Elemento "fora dos papéis configurados"** | 1 elemento em produção |
| **5i · anexo por formulário próprio** | ⚠️ **Medido:** reescrever o cartão inteiro (`Document_Item::showForItem` + dois `Document::canView()`). **Não é pequeno** |

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Vinte e quatro** ideias avaliadas e
recusadas com motivo. **Não ressuscitar sem fato novo.**

Três entraram em 28/08 nesta sessão:

- **Commit único para "fechar tudo de uma vez"** — `git revert` jogaria fora as
  dez mudanças sem dizer qual quebrou. Contraproposta aceita e executada.
- **`checkRight` dentro do `ajax/dgocomment.php`** — criaria segunda sede da regra.
- **Trava de duplicados que cubra a ficha nativa** — impossível, não descartada:
  a ficha, o `datainjection` e o SQL não passam pelo plugin.

---

## O que muda com a produção crescendo

| Bloco | Como se comporta quando a base cresce |
|---|---|
| **5e-2** | **Pior de todos.** Nome ambíguo com 159 candidatos já é escolha às cegas; com 300, vínculo errado vira topologia errada |
| **5d** | Não piora. É correção de regra |
| **5h-2** | **Melhora com escala** — filtrar por localização vale mais com 9 localizações |
| **BADGE-C**, contador de entradas | Neutros. Qualidade de leitura |
| **Deploy em produção** | Piora com o tempo: quanto mais gente documentando, maior o risco da troca de permissão |

---

## Próximo passo imediato

1. **Exercitar as três correções entregues e não validadas** — 5g-1b, 5e e 5c.
   Ver a Parte B.
2. **Commit 4b — bloco 5d**, confirmação em dois tempos. ⚠️ Mexe no
   `Link::propose()`, o ponto único de criação de vínculo. O trabalho real é
   fazer destino e entrada sobreviverem ao redirect.
3. **Commit 5 — BADGE-C + contador de entradas separado.** Mesma contagem em
   dois lugares, por isso no mesmo bloco. Toca `Port::statsForDgo()`,
   `MapController::renderBadges()` (público, o AJAX reescreve), `ajax/port.php`
   e `Dashboard.php`.
4. **5e-2 — DISCUTIR ANTES DE CODAR.** Aprovado, não detalhado. Ver a seção 8
   do contexto: falta decidir como o rótulo desambigua em cada tela, onde
   aplicar (8 pontos de impressão), o que a trava faz com os duplicados que já
   existem, e o texto da recusa.
5. **Higiene**: purgar o `CTO TESTE 5f2b` (hoje `CTO 01`), remover o
   `PISO VAZIO TESTE`, devolver o papel da `CTO 01`, e olhar as pendências 11 e 12.
6. **SKILL** — barato, e para de custar em toda sessão.
7. Depois: **README** ✅ feito, **5h-2**, **5i**, e o **deploy em produção**.
8. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.
