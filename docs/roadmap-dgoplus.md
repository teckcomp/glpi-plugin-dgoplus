# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v19 — 04/09/2026. Sucede o v18, de 03/09.
> A mudança que justifica a versão nova: **sessão de higiene fechada** — as
> pendências **19** (F1.06 limpa) e **13** (`PISO VAZIO TESTE` removido)
> encerraram em tela, a **dívida 5 quitou POR DECISÃO** (a skill cadastrada
> não será trocada), e a homologação revelou uma localização que nenhum doc
> conhecia (`Plaza Campos Gerais`). **Sessão sem código**: versão **1.3.22**,
> `master` em **`12a7202`**, inalterados.

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
| **SKILL** | `SKILL.md` reescrito e entregue (03/09) | ✅ **ENCERRADO POR DECISÃO (04/09):** a skill cadastrada NÃO será trocada; o arquivo atualizado vive na base do projeto e prevalece |

### ✅ Validado em tela nesta sessão (04/09)

| Item | O que se provou | Evidência |
|---|---|---|
| **Pendência 19** | F1.06 da `DGO 01 #34` limpa: código `2153` presente, SEM vínculo, E3 `livre` no seletor — faxina do `PurgeCleaner` na purga da `#36` confirmada | Print do painel da porta |
| **Pendência 13** | `PISO VAZIO TESTE` existia (`Outlet Porto Belo`), provado vazio pelo escopo cheio do filtro, e **removido** — lista administrativa voltou a 3 pisos | Prints da lista antes/depois + "Operação realizada com sucesso" |
| Selo pós-purga (de graça) | Pares `DGO 01` (#34/#37) e `CTO 01` (#35/#38) acesos; únicos sem selo; 8 abas em linha única sem barra | Print do mapa |

### ☠️ Cancelado sem código

| Bloco | Motivo |
|---|---|
| 5e-2d-2 | Preparado e descartado ANTES de aplicar (03/09); nada chegou ao repositório |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.**

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 7 | Histórico registra o técnico como autor? | Dada como ok pelo usuário, sem tela |
| 11 | Por que a DGO 01 mudou de portas documentadas? | Aberta. Baixo risco |
| 13 | `PISO VAZIO TESTE` | ✅ **FEITA (04/09).** Localizado na tela administrativa de Pisos, provado vazio, removido. Lista final: `L1`, `L2`, `MALL - PORTO BELO` |
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ Bloqueada — repositório privado |
| 18 | As 9 localizações da produção têm pai? | Aberta, baixa prioridade |
| 19 | A F1.06 da `DGO 01 #34` ficou limpa após a purga da `#36`? | ✅ **FEITA (04/09).** Código `2153` presente, sem vínculo, E3 livre. `PurgeCleaner` aprovado |

Pendências 1–6, 8, 12, 14, 15, 17 dos docs anteriores: todas respondidas,
mantidas lá como histórico.

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | **Sem catálogo de tradução.** Decisão de produto antes | Grande |
| 3 | **Lista integral de lições (1–113)** | Só pelo documento original |
| 5 | ~~Skill desatualizada~~ | ✅ **QUITADA POR DECISÃO (04/09):** a skill cadastrada fica como está; a fonte da verdade do ambiente é o `SKILL-glpi-plugin-teckcomp.md` na base do projeto. O conteúdo antigo da skill (`pscp`, `192.168.1.50`, zip) vira **ruído conhecido** — contexto e práticas abolidas prevalecem |
| 7 | **Seletor de DESTINO usa formato próprio** (`CTO 01 (CTO) #35`) — revisto em tela em 04/09, segue vivo | Última inconsistência de formato — candidata a bloco pequeno |

---

## Parte E — estacionamento

Lista do v17 mantida (endpoint AJAX de vínculo; porta ↔ chamado; notificações;
widgets; colunas novas no relatório; carimbo de autor no comentário; pendente
que envelhece; PAINEL-1b — tela de destino não existe; deploy da Fase 5 —
obrigatório, sem data; elemento fora dos papéis; 5i — reescrever o cartão;
aviso na purga com vínculo vivo; uniformizar o seletor de destino com o
`ItemLabel`; `normalizeName()` na frente shopmap; sombra/indicador de "há mais
abas" além da barra nativa).

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. As de 03/09 (MAX_TABS revogada;
sufixo na `<option>` morto; `scrollIntoView`, múltiplas linhas, lista lateral
e dropdown com limite maior descartados) permanecem. Nova, de 04/09:

- **Atualizar a skill `glpi-plugin-teckcomp` cadastrada — DESCARTADA por
  decisão do usuário.** A skill fica com o conteúdo antigo; o retrato correto
  do ambiente vive na base do projeto e no próprio contexto. **Não voltar a
  propor a troca sem fato novo.**

---

## O que muda com a produção crescendo

| Bloco | Comportamento com escala |
|---|---|
| 5e-3a/5e-3b | ✅ É a resposta à escala (159 elementos em 9 localizações viram linha rolável). ⚠️ Não medido com dezenas de abas reais |
| 5d | Não piora. Correção de regra |
| 5h-2 | Melhora com escala |
| BADGE-C, contador de entradas | Neutros. Qualidade de leitura |
| Deploy em produção | Piora com o tempo — 4 documentadores reais mudam de permissão |

---

## Próximo passo imediato

1. **Commit dos docs v19** (`docs/` → sem reinstalação). O v18 nunca foi
   commitado; o v19 entra direto sobre o v17.
2. **Commit — bloco 5d**, confirmação em dois tempos. ⚠️ Mexe no
   `Link::propose()`.
3. **Commit — BADGE-C + contador de entradas separado.**
4. **5h-2**, **5i**, e o **bloco de deploy em produção** (com rollback —
   começa por reler a produção em tela; retrato atual é de 28/08).
5. **Frente shopmap** — bloqueada pela pendência 16.
6. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (`mapadgo`) **não** corresponde à
> numeração de blocos atual.
