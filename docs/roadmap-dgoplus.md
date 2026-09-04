# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v20 — 04/09/2026 (segunda sessão do dia). Sucede o v19.
> A mudança que justifica a versão nova: **as três pendências finalizáveis
> fecharam em tela** — **7** (histórico registra o técnico como autor,
> provado com print), **11** (delta de portas da `DGO 01 #34` explicado linha
> a linha, com autores) e **18** (localizações da produção TÊM pai: raiz
> `Shopping`, lista com 427 linhas). Só a **16** (shopmap) segue viva, e
> bloqueada. **Sessão sem código**: versão **1.3.22**, último commit de código
> `12a7202`; `master` em **`1829021`** (docs v19, já commitados).

---

## Parte A — resultado da revisão (histórico, não mexer)

Tabela do v17 mantida integralmente. A frente de permissões da Fase 5 continua
fechada e provada em campo; os dois greps de guarda:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

---

## Parte B — Fase 5

### Concluído

| Bloco | O que fez | Versão / commit |
|---|---|---|
| 5a … 5e-2d-1b | Tudo do v17, até o selo de duplicado e o bump de reparo | até 1.3.20 |
| 5e-3a | Abas sempre; linha única com rolagem horizontal; mata o seletor único e o `MAX_TABS` | 1.3.21, `28d5079` |
| 5e-3b | Aba ativa centralizada na linha rolável ao carregar (JS, gancho `data-dgoplus-tabs`) | 1.3.22, `12a7202` |
| **docs v19** | Commit dos docs da sessão de higiene | `1829021` (só `docs/`) |
| **SKILL** | Encerrado por decisão (04/09): a skill cadastrada não será trocada | — |

### ✅ Validado em tela nesta sessão (04/09, 2ª sessão)

| Item | O que se provou | Evidência |
|---|---|---|
| **Pendência 11** | Histórico do `DGO 01 #34` (33 linhas): elemento criado por `cristian.b` em 27/08 09:18; F1.01 documentada por ele; F1.02–F1.06 por `teste.001` ao longo de 27/08; ajuste de Claudio em 28/08; entrada E1 em 29/08. Todo o delta de contagem explicado, com autor — mudança legítima de ambiente vivo | Prints do Histórico (2 telas) + contraprova no relatório (carimbo `Teste 001` na `(49)`) |
| **Pendência 7** | O histórico registra o TÉCNICO como autor: `teste.001 (19)` e `cristian.b (29)` nomeados linha a linha. Mecanismo provado também no código: `dohistory` + default `logs_for_parent=true` do core + `user_name` no `glpi_logs` + gravação via `add()/update()` no `applyInput` | Mesmos prints da 11 — o roteiro próprio ficou desnecessário, nada foi gravado |
| **Pendência 18** | Localizações da produção TÊM pai: **427 linhas com várias raízes** — `Shopping` (~42, com as unidades DGO+ como filhas), `Fleury`, `Confiance`, `Padrão`… — e até **três níveis** (`Shopping > Palladium Ctba > Diretoria`). A base é compartilhada com contextos alheios ao DGO+ | Prints de Configurar → Listas suspensas → Localizações (produção) + contagem de shoppings informada pelo usuário |
| Selo/abas (de graça) | Tooltip do `#34` aceso e correto; 8 abas em linha única sem barra; contador do `#34` em 5 | Print do mapa |

### Fatos novos colhidos de graça (2ª sessão de 04/09)

- **Homologação:** `Plaza Campos Gerais` TEM elementos com portas
  documentadas (`Pedro s`, 20/08); localizações `shopping estação` e
  `Shopping Ventura` (com filha `DGO Cristian`) apareceram — nenhum doc as
  conhecia. Relatório com 66 linhas.
- **Portas pré-3s sem carimbo** (04–17/08) no relatório — esperado, o carimbo
  não é retroativo.
- **Nota aberta pequena:** Histórico do `#34` tem 6 adições de grade, contador
  mostra 5 — candidata F1.04 (sem acoplador ou esvaziada). Um olhar fecha;
  não virou pendência numerada.

### ☠️ Cancelado sem código

| Bloco | Motivo |
|---|---|
| 5e-2d-2 | Preparado e descartado ANTES de aplicar (03/09) |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.**

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 7 | Histórico registra o técnico como autor? | ✅ **FEITA (04/09, 2ª sessão).** Provada com print: `teste.001` e `cristian.b` nomeados no Histórico do `#34`. Mecanismo provado no código (dohistory, logs_for_parent default true, user_name no glpi_logs) |
| 11 | Por que a DGO 01 mudou de portas documentadas? | ✅ **FEITA (04/09, 2ª sessão).** O elemento nasceu em 27/08 e foi documentado em ritmo de campo — delta explicado linha a linha com autores. Mudança legítima de ambiente vivo |
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única pendência viva |
| 18 | As localizações da produção têm pai? | ✅ **FEITA (04/09, 2ª sessão).** SIM: 427 localizações com várias raízes (`Shopping` ~42 — as do DGO+ —, `Fleury`, `Confiance`, `Padrão`…), até três níveis |

Pendências 1–6, 8, 12, 13, 14, 15, 17, 19 dos docs anteriores: todas
respondidas, mantidas lá como histórico.

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | **Sem catálogo de tradução.** Decisão de produto antes | Grande |
| 3 | **Lista integral de lições (1–113)** | Só pelo documento original |
| 5 | ~~Skill desatualizada~~ | ✅ Quitada por decisão (04/09) |
| 7 | **Seletor de DESTINO usa formato próprio** (`CTO 01 (CTO) #35`) | Última inconsistência de formato — candidata a bloco pequeno |

---

## Parte E — estacionamento

Lista do v17 mantida (endpoint AJAX de vínculo; porta ↔ chamado; notificações;
widgets; colunas novas no relatório; carimbo de autor no comentário; pendente
que envelhece; PAINEL-1b — tela de destino não existe; deploy da Fase 5 —
obrigatório, sem data; elemento fora dos papéis; 5i — reescrever o cartão;
aviso na purga com vínculo vivo; uniformizar o seletor de destino com o
`ItemLabel`; `normalizeName()` na frente shopmap; sombra/indicador de "há mais
abas" além da barra nativa). **Nova entrada (04/09, 2ª sessão):** avaliar o
efeito do `completename` de produção nos rótulos do `ItemLabel` — prefixo
`Shopping > ` nas unidades DGO+ e árvore com até três níveis; fato novo
registrado, decisão do `completename` NÃO reaberta.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. Todas as anteriores permanecem
(inclusive: não trocar a skill cadastrada; não voltar sem fato novo). Nenhuma
nova na 2ª sessão de 04/09.

---

## O que muda com a produção crescendo

| Bloco | Comportamento com escala |
|---|---|
| 5e-3a/5e-3b | ✅ É a resposta à escala. ⚠️ Não medido com dezenas de abas reais |
| 5d | Não piora. Correção de regra |
| 5h-2 | **Melhora com escala — e subiu de valor: a produção tem 427 localizações cadastradas, a maioria alheia ao DGO+** (fato de 04/09) |
| BADGE-C, contador de entradas | Neutros. Qualidade de leitura |
| Deploy em produção | Piora com o tempo — 4 documentadores reais mudam de permissão |

---

## Próximo passo imediato

1. **Commit dos docs v20** (`docs/` → sem reinstalação). Entra por cima do
   v19 (`1829021`).
2. **Commit — bloco 5d**, confirmação em dois tempos. ⚠️ Mexe no
   `Link::propose()`.
3. **Commit — BADGE-C + contador de entradas separado.**
4. **5h-2**, **5i**, e o **bloco de deploy em produção** (com rollback —
   começa por reler a produção em tela).
5. **Frente shopmap** — bloqueada pela pendência 16.
6. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (`mapadgo`) **não** corresponde à
> numeração de blocos atual.
