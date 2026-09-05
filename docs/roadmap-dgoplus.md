# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v23 — 05/09/2026 (2ª sessão do dia). Sucede o v22.
> A mudança que justifica a versão nova: **BADGE-C e PAINEL-2/2b entregues e
> validados** (contadores de grade e entradas no cabeçalho da grade — 1.3.25,
> `e5d01fc`; entradas como pastilha nos cartões do painel, variante B —
> 1.3.26, `bf8281b`). O cartão próprio "Entradas ocupadas" do PAINEL-2 viveu
> um bloco e foi substituído pela variante B após o dono ver em tela —
> origem da lição 167 (mockup antes de tela nova).

---

## Parte A — resultado da revisão (histórico, não mexer)

Tabela do v17 mantida integralmente. Frente de permissões da Fase 5 fechada;
os dois greps de guarda:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

---

## Parte B — Fase 5

### Concluído

| Bloco | O que fez | Versão / commit |
|---|---|---|
| 5a … 5d | Tudo do v22 (até confirmação em dois tempos) | até 1.3.24 |
| docs v22 | Commit dos docs da 1ª sessão de 05/09 | `0ef7bf3` |
| **BADGE-C (+PAINEL-2)** | **Dois contadores no cabeçalho da grade (`5/64 grade` azul · `1/4 entradas` verde, tooltips), "sem acoplador" intacto; `statsForDgo` vira ponto único incluindo entradas; cartão "Entradas ocupadas" no painel (substituído no bloco seguinte). Bloco único por decisão do dono** | **1.3.25, `e5d01fc`** ✅ |
| **PAINEL-2b** | **Variante B: pastilhas `25/164 entradas ocupadas` (Ocupação geral) e `139/164 entradas livres` (Portas livres) via helper `entriesPill()`; cartão próprio removido; layout de 4 cartões restaurado (xl-4). Frações do 4d intocadas** | **1.3.26, `bf8281b`** ✅ |

### ✅ Validado em tela nesta sessão (05/09, 2ª)

| Item | O que se provou | Evidência |
|---|---|---|
| BADGE-C badges | Contadores de grade e entradas no cabeçalho; AJAX preserva os dois; "outras validações ok" | Validação do usuário |
| PAINEL-2 cartão | Renderizou correto (25 de 164) mas NÃO era o desenho desejado → substituído | Screenshot antes/depois |
| PAINEL-2b | 4 cartões restaurados, pastilhas nos dois KPIs | "validado e ok" |
| Paridade | `e5d01fc` e `bf8281b` publicados = entregues, md5 a md5 | Tarballs na sessão |

### Fatos novos colhidos de graça (05/09, 2ª)

- **25 entradas ocupadas no escopo total** (25/164) — os técnicos criaram
  ~dezenas de vínculos que nenhum doc conhecia. Quebra pendente×confirmado
  **NÃO verificada** (SQL preparada, não rodada — retrato opcional p/ v24).
- Grade: 43/2165 documentadas (era 42 em 04/09 — alguém documentou +1).
- `cache:clear` rodou 2× sem o `glpi.CRITICAL` aparecer na SAÍDA do console
  (log não inspecionado para isso) — candidato segue em observação.

### ☠️ Cancelado sem código

| Bloco | Motivo |
|---|---|
| 5e-2d-2 | Preparado e descartado antes de aplicar (03/09) |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.** (O cartão do PAINEL-2 foi exercitado E substituído — não é dívida.)

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única viva |
| 20 | Dos 25 vínculos, quantos pendentes × confirmados? | Aberta, opcional — SQL pronta no contexto §7 |

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | Sem catálogo de tradução | Grande |
| 3 | Lista integral de lições (1–113) | Só pelo documento original |
| 7 | Seletor de DESTINO usa formato próprio | Mantida por decisão (5e-4, opção A) |

---

## Parte E — estacionamento

Lista do v22 mantida integralmente (inclui PAINEL-1b, pendente que envelhece,
medição 5e-3a/b com dezenas de abas etc.). Sem entrada nova.

---

## Parte F — decisões negativas

Ver seção 8 do `contexto-dgoplus.md`. **Nova (05/09, 2ª):** cartão PRÓPRIO de
entradas no painel foi entregue, visto e REJEITADO pelo dono — não repropor;
a forma vigente é a pastilha dentro dos dois KPIs (variante B).

---

## O que muda com a produção crescendo

| Bloco | Comportamento com escala |
|---|---|
| BADGE-C | Neutro — dois números por elemento, qualquer volume |
| PAINEL-2b | Neutro-positivo — denominador acompanha 4×elementos |
| 5h-2 | **Melhora com escala** — 427 localizações na produção |
| Deploy em produção | Piora com o tempo — reler a produção antes |

---

## Próximo passo imediato

1. **Commit dos docs v23** (`docs/` → sem reinstalação). Código segue no
   `bf8281b`.
2. **5h-2** — remover `nosearch` da Localização no relatório (prioridade
   elevada pelas 427 localizações da produção).
3. **5g-3** (nota de permissões de anexo na aba de perfil), **5i** (anexo por
   formulário próprio), **PAINEL-1** ("Ver todos" nos cartões), **deploy em
   produção** (com rollback; começa relendo a produção em tela).
4. **Frente shopmap** — bloqueada pela pendência 16.
5. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.
