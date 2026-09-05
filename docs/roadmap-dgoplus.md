# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v21 — 04/09/2026 (terceira sessão do dia). Sucede o v20.
> A mudança que justifica a versão nova: **bloco 5e-4 entregue e validado**
> (`#id` em todo elemento do seletor de destino — versão **1.3.23**, commit
> **`0be3216`**), **três validações fechadas em tela** (V1: nota do `#34`
> resolvida — F1.04 esvaziada/apagada; V2: "antes" do 5d provado — pulo de
> degrau grava na primeira; V3: "antes" do BADGE-C provado — badge só grade),
> e **retrato novo da homologação** (41 elementos, PTOs existem, 9
> localizações). Docs v20 já estavam commitados (`1486d78`) antes da sessão.

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
| 5e-3b | Aba ativa centralizada na linha rolável ao carregar | 1.3.22, `12a7202` |
| docs v19 | Commit dos docs da sessão de higiene | `1829021` |
| docs v20 | Commit dos docs das pendências 7/11/18 | `1486d78` |
| **5e-4** | **`#id` em TODO elemento do seletor de destino** — revoga "sufixo só na colisão" (5e) por decisão do usuário; opção A (formato `nome (PAPEL) #id` mantido); elemento sem nome sai `#id (PAPEL)` sem repetir | **1.3.23, `0be3216`** ✅ validado em tela |
| SKILL | Encerrado por decisão (04/09): a skill cadastrada não será trocada | — |

### ✅ Validado em tela nesta sessão (04/09, 3ª sessão)

| Item | O que se provou | Evidência |
|---|---|---|
| **V1 — nota do `#34`** | Contador 5 CORRETO: das 6 adições do Histórico, a **F1.04 está `livre`** na grade (esvaziada e apagada, não sem-acoplador). Nota fechada sem virar pendência | Print da grade do #34 |
| **V2 — "antes" do 5d** | Proposta **DIO→CTO pulando o DGO grava na PRIMEIRA confirmação**, direto como pendente ocupando as duas pontas — nenhuma segunda tela. `hierarchyAllows()` só compara ordem, lido no código. De graça: trilha exercitada com confirmado (`DIO · #39 → E1 · aqui`); F1.02 com vínculo e sem nome contou como documentada (2→3→2 após desmonte) | Prints painel #39, cartão #41 (pendente e confirmado); código `Link.php` |
| **V3 — "antes" do BADGE-C** | Badge é SÓ grade: `#34` com E1 ocupada mostra `5 de 16`; `#41` com E1 ocupada mostra `0 de 64` — entradas mudas. `renderBadges()` lido no código | Prints de 3 cartões |
| **5e-4 (o bloco)** | As 7 opções da Outlet com `#id`, inclusive nomes únicos (`TESTE 5e2d2 A (CTO) #41`, `DGO 01 - PORTO BELO (DGO) #33`); propor/desmontar inalterados | Validação do usuário em tela |
| Busca do mapa | Procura PORTAS, não elementos — "#39" e "DIO 001 · #39" devolvem 0 com estado vazio falando. Comportamento documentado | 2 prints |

### Fatos novos colhidos de graça (3ª sessão de 04/09)

- **Homologação cresceu MUITO:** painel geral com **41 elementos** (DIO 6,
  DGO 16, CTO 13, **PTO 6** — PTOs existem!), 18 sem documentação, **2165
  portas** (42 doc., 1,9%), 1 porta na lixeira, **9 localizações com
  elementos** — `A+`, `Bio qualquer > bio001` e `Shopping itajai/Bigode - 000`
  eram desconhecidas dos docs; a 9ª ficou fora do print. Outlet inalterada
  (8 elementos).
- ⚠️ Consequência para 5e-3a/b: já EXISTEM localizações maiores que a Outlet
  na homologação — medir as abas com dezenas quando uma for aberta.

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
| 7 | Histórico registra o técnico como autor? | ✅ FEITA (04/09, 2ª sessão) |
| 11 | Por que a DGO 01 mudou de portas documentadas? | ✅ FEITA (04/09, 2ª sessão) |
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única pendência viva |
| 18 | As localizações da produção têm pai? | ✅ FEITA (04/09, 2ª sessão) |

Pendências 1–6, 8, 12–15, 17, 19: respondidas, mantidas nos docs anteriores
como histórico. A nota aberta do `#34` (6 adições × contador 5) **fechou no
V1** sem nunca ter sido numerada.

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | **Sem catálogo de tradução.** Decisão de produto antes | Grande |
| 3 | **Lista integral de lições (1–113)** | Só pelo documento original |
| 5 | ~~Skill desatualizada~~ | ✅ Quitada por decisão (04/09) |
| 7 | **Seletor de DESTINO usa formato próprio** (`nome (PAPEL) #id`) | **Mantida por decisão no 5e-4 (opção A)** — a opção B (nascer do `ItemLabel::shortForRow`) foi oferecida e recusada. Reabrir é do usuário |

---

## Parte E — estacionamento

Lista do v17 mantida (endpoint AJAX de vínculo; porta ↔ chamado; notificações;
widgets; colunas novas no relatório; carimbo de autor no comentário; pendente
que envelhece; PAINEL-1b; deploy da Fase 5 — obrigatório, sem data; elemento
fora dos papéis; 5i — reescrever o cartão; aviso na purga com vínculo vivo;
uniformizar o seletor de destino com o `ItemLabel` [= dívida 7]; 
`normalizeName()` na frente shopmap; sombra/indicador de "há mais abas";
efeito do `completename` de produção nos rótulos). **Nova entrada (04/09, 3ª
sessão):** medir 5e-3a/b com localização de dezenas de abas — a homologação já
tem candidatas.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Mudança na 3ª sessão de 04/09:**
a decisão "sufixo só na colisão" (5e) foi **REVOGADA pelo usuário** e
substituída por "`#id` sempre" (5e-4, opção A). Todas as demais permanecem.

---

## O que muda com a produção crescendo

| Bloco | Comportamento com escala |
|---|---|
| 5e-3a/5e-3b | ✅ É a resposta à escala. ⚠️ Não medido com dezenas de abas — homologação já tem candidatas |
| 5e-4 | Neutro-positivo: id sempre visível independe da quantidade |
| 5d | Não piora. Correção de regra |
| 5h-2 | **Melhora com escala** — 427 localizações na produção, maioria alheia ao DGO+ |
| BADGE-C, contador de entradas | Neutros. Qualidade de leitura |
| Deploy em produção | Piora com o tempo — 4 documentadores reais mudam de permissão |

---

## Próximo passo imediato

1. **Commit dos docs v21** (`docs/` → sem reinstalação). Entra por cima do
   v20; código segue no `0be3216`.
2. **Bloco 5d** — confirmação em dois tempos na proposta com pulo de degrau.
   ⚠️ Mexe no `Link::propose()`. Cenário de teste fixado: `#39 F1.02 → #41 E1`.
3. **Bloco BADGE-C** — contadores de grade e entradas separados.
4. **5h-2**, **5i**, e o **bloco de deploy em produção** (com rollback —
   começa por reler a produção em tela).
5. **Frente shopmap** — bloqueada pela pendência 16.
6. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (`mapadgo`) **não** corresponde à
> numeração de blocos atual.
