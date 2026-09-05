# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v24 — 05/09/2026 (3ª sessão do dia). Sucede o v23.
> A mudança que justifica a versão nova: **os blocos de código da Fase 5
> ACABARAM.** 5h-2 + 5i entregues em aplicação única e validados (1.3.27,
> `c74e32a`); 5i-2 endpoint entregue e validado (1.3.28, `4923cab`); 5g-3
> quitado sem código; PAINEL-1a já estava entregue; PAINEL-1b virou decisão
> negativa. O 5i-2 versão "cadeado" foi preparado e descartado antes de
> aplicar — origem da lição 168.

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
| 5a … PAINEL-2b | Tudo do v23 | até 1.3.26 |
| docs v23 | Commit dos docs da 2ª sessão de 05/09 | `4358c98` |
| **5h-2 + 5i** | **Aplicação única (decisão pontual do dono). 5h-2: `nosearch` da Localização caiu — relatório filtra por localização. 5i: anexo por formulário do PLUGIN — ação `attach_document`, direito = Atualizar do DGO+, `Document::add` com `_filename` (não checa direito nativo; tipo de arquivo continua validado pelo core); gates `Document::canView()` da lista caíram** | **1.3.27, `c74e32a`** ✅ |
| **5i-2 endpoint** | **`front/document.send.php` do plugin (arquivo NOVO): ver/baixar anexo pelo mapa exige só o Ler do DGO+ — nada em Gerência → Documentos. Porteiro: Ler + `parentIsReachable` + vínculo doc↔elemento obrigatório. `documentUrl()` foi o único retarget (ponto único). Texto de anexos da aba de perfil reescrito** | **1.3.28, `4923cab`** ✅ |

### ✅ Validado em tela nesta sessão (05/09, 3ª)

| Item | O que se provou | Evidência |
|---|---|---|
| 5h-2 | Relatório filtra por Localização | Validação do usuário |
| 5i | Anexar com direito do plugin; recusa falada no só-leitura; anexo visível fora do DGO+ | Screenshots (lista + frase do cadeado azul) |
| 5i-2 | Miniatura carrega no perfil SEM Documentos · Ler | "validado e ok" |
| Paridade | `4358c98`, `c74e32a`, `4923cab` publicados = entregues | Tarballs md5 na sessão |

### Fechado sem código (05/09, 3ª)

| Item | Motivo |
|---|---|
| **5g-3** | A nota de anexos JÁ EXISTIA no `ProfileTab.php` — roadmap estava desatualizado (lição 160 aplicada a código). Reescrita depois pelo 5i-2 |
| **PAINEL-1a** | Já estava entregue (rodapé na Atividade recente, `Dashboard.php`) |

### ☠️ Cancelado sem aplicar

| Bloco | Motivo |
|---|---|
| 5e-2d-2 | Preparado e descartado antes de aplicar (03/09) |
| **5i-2 "cadeado"** | Preparado e DESCARTADO antes de aplicar (05/09, 3ª) — decorava a exigência nativa em vez de eliminá-la; contrariava o objetivo declarado (lição 168) |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.**

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única viva |
| 20 | Dos 25 vínculos, quantos pendentes × confirmados? | Aberta, opcional — SQL no contexto §7 |
| 21 | Por que a rota `canViewFileFromItem` (READ do ativo) do send.php do core NÃO abriu neste ambiente? | Aberta, opcional — curiosidade de core, sem bloco. Dedução falseada em tela em 05/09 |

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | Sem catálogo de tradução | Grande |
| 3 | Lista integral de lições (1–113) | Só pelo documento original |
| 7 | Seletor de DESTINO usa formato próprio | Mantida por decisão (5e-4, opção A) |

---

## Parte E — estacionamento

Lista do v22 mantida, MENOS o PAINEL-1b (promovido a decisão negativa — §8 do
contexto). Segue: pendente que envelhece não avisa; medição 5e-3a/b com
dezenas de abas; etc.

---

## Parte F — decisões negativas

Ver seção 8 do `contexto-dgoplus.md`. **Novas (05/09, 3ª):** PAINEL-1b não
será feito (sem alvo honesto); ver anexo no mapa nunca mais exige direito
nativo (5i-2 cadeado descartado); exigência "Documento R+U+C + Data centers
UPDATE" para anexar está morta.

---

## O que muda com a produção crescendo

| Bloco | Comportamento com escala |
|---|---|
| 5h-2 | **Melhora com escala** — 427 localizações na produção agora filtram |
| 5i / 5i-2 | Neutro — um formulário e um endpoint por elemento |
| BADGE-C / PAINEL-2b | Neutro (v23) |
| Deploy em produção | Piora com o tempo — reler a produção antes |

---

## Próximo passo imediato

1. **Commit dos docs v24** (`docs/` → sem reinstalação). Código no `4923cab`.
2. **Tag/Release da Fase 5** (candidata `v1.3.28`) — decisão do dono; deploy
   sai da tag.
3. **Deploy em produção** — bloco próprio com rollback; começa RELENDO a
   produção em tela.
4. **Frente shopmap** — bloqueada (pendência 16). Pendências 20 e 21 —
   opcionais.
5. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.
