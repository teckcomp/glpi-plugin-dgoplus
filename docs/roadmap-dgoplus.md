# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v16 — 28/08/2026. Sucede o v15, do mesmo dia.
> A mudança que justifica a versão nova: **o 5e-2 inteiro foi ao ar**, em três
> blocos, e no caminho **uma decisão de produto foi cancelada** e **um bloco
> novo nasceu**. Duas pendências de investigação fecharam.
> Versão **1.3.19**, `master` em **`770ce71`**.

---

## Parte A — resultado da revisão (histórico, não mexer)

| # | Passo | Decisão |
|---|---|---|
| 1 | Estado do ambiente | Homologação estava em 1.3.0. Corrigido pelo 5-sync. Lição 114 |
| 2 | Bloco 5a | **Aprovado.** Poda em cascata funciona nos três seletores |
| 3 | Ciclo do vínculo | **Aprovado.** Modelo sem lixeira honrado. Ajuste 5c ✅ |
| 4 | Papéis | **Aprovado.** Quatro degraus bastam; splitter fora |
| 5 | Escopo Localização → Piso | Piso fica; lote descartado; meia-medida → 5b ✅ |
| 6 | A grade no dia a dia | **Aprovado.** Digitação um a um é o fluxo certo |
| 7 | Permissões | **Maior achado.** Frente inteira **RESOLVIDA** |
| 8 | Relatório | **Bug** (erro 1054) → 5h **RESOLVIDO** |

**A frente de permissões da Fase 5 está fechada.** Os dois greps que a guardam:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

---

## Parte B — Fase 5

### Concluído

| Bloco | O que fez | Versão / commit |
|---|---|---|
| 5a | Escopo Localização → Piso no seletor de destino | 1.3.1 |
| 5h | JOIN da coluna Localização no relatório | 1.3.2 |
| DOC, GIT-1, GIT-2, REL, REL-2 | `docs/`, clone Git, tags e Releases | 27/08 |
| 5f-1a … 5f-3b | A frente de permissões inteira | até 1.3.8 |
| 5g-1 | Auto-save da porta distingue 403 de rede | 1.3.9 |
| 5g-2 / 5g-2b | Telas nomeiam o direito; dicas saem da moldura | 1.3.11 |
| 5g-1b | Auto-save do comentário não reenvia recusa | 1.3.12 |
| 5g-3 + PAINEL-1a + README | Nota no perfil; "Ver todos"; README reescrito | 1.3.13 |
| 2b | Nota vira card abaixo da matriz | 1.3.14 |
| 5b + 5e | Poda do seletor de piso; desambiguação por colisão | 1.3.15 |
| 5c | Trilha parte da entrada, não do elemento | 1.3.16 |
| **5e-2a** | **`src/ItemLabel.php`; os dois `describe*` do `Link`** | **1.3.17, `e48d7a4`** |
| **5e-2b** | **Abas, cabeçalhos, seletor único, chip da trilha** | **1.3.18, `67248dd`** |
| **5e-2c** | **Card Alimenta, busca, pendentes, painel** | **1.3.19, `770ce71`** |

### ✅ Validado em tela nesta sessão

| Bloco | O que se provou | Evidência |
|---|---|---|
| **5e-2a** | Seção Alimenta, `title` da célula e card da entrada passaram a dizer `nome · localização · #id` | `E1 de CTO 01 · Outlet Porto Belo · #35` |
| **5e-2b** | Quatro abas homônimas viraram quatro rótulos distintos | `DGO 01 · #34`, `DGO 01 · #37`, `CTO 01 · #35`, `CTO 01 · #36` |
| **5e-2c** | Card Alimenta, busca global, pendentes e os dois cards do painel | Sete passos, sete telas |

**O 5e-2 está fechado.** O nome de elemento tem **um dono só**: `ItemLabel`.

### ⚠️ Entregue mas NÃO exercitado — resta um

| Bloco | O que falta | Por que não foi feito |
|---|---|---|
| **5g-1b** | Tirar ATUALIZAR **com a aba aberta**, digitar 3× no comentário, e conferir **uma única linha** `POST … 200 … dgocomment.php` | Exige tirar direito no meio da sessão (lição 151). Sentada própria |

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ SIM. Candidato 5i |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ `itemtype_item_revert` + `specific_itemtype` |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ tem |
| 4 | Os dois elementos homônimos são ativos distintos? | ✅ Sim, ids 35 e 36, ambos CTO |
| 5 | Existe clone Git no servidor? | ✅ criado |
| 6 | A "Falha ao salvar" da F1.02 foi 403? | ✅ SIM, corrigida pelo 5g-1 |
| 7 | O Histórico registra o técnico como autor? | Dada como ok pelo usuário, sem tela |
| 8 | Limpar a ex-`CTO TESTE 5f2b` (`CTO 01 #36`) | ⚠️ **Alvo e pré-condição agora NOMEADOS:** desmontar a E3, que é alimentada pela F1.06 da `DGO 01 #34` |
| 11 | Por que a DGO 01 mudou de portas documentadas? | **Aberta.** Baixo risco |
| **12** | F1.06 da DGO 01 "sem acoplador" E com vínculo | ✅ **RESPONDIDA (28/08, em tela).** Ela alimenta a **E3 da `CTO 01 #36`** |
| 13 | `PISO VAZIO TESTE` e o papel trocado da `CTO 01` | ⚠️ Metade caiu. Resta remover o piso |
| **14** | Quem alimenta a E3 da `CTO 01 #36`? | ✅ **RESPONDIDA (28/08, em tela).** A **F1.06 da `DGO 01 #34`** |
| 15 | Porta de grade com vínculo e sem nome conta como documentada? | **Aberta.** Não conferido em `Port::statsForDgo()` |
| **16** | **O shopmap guarda o vínculo por NOME ou por `itemtype`+`id`?** | **Nova (28/08).** Decide o tamanho da frente shopmap: só tela, ou tela + migração. ⚠️ **Bloqueada** — repositório privado |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | ~~README desatualizado~~ | ✅ quitada |
| 2 | **Sem catálogo de tradução.** ⚠️ Decisão de produto antes | Grande; não cabe num bloco |
| 3 | **Lista integral de lições (1–113).** ⚠️ Caminho barato esgotado | Só pelo documento original |
| 4 | ~~Sem tag/Release~~ | ✅ quitada |
| 5 | **Skill `glpi-plugin-teckcomp` desatualizada** | Bloco SKILL |
| 6 | ~~Texto fala de "Desmontar" sem o botão~~ | ✅ quitada |
| **7** | **NOVA — o seletor de destino usa formato próprio** (`CTO 01 (CTO) #35`) enquanto o resto usa `CTO 01 · #35` | Pequena, deliberada |

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
| Aviso de vínculo pendente que envelhece | Achado de 28/08 |
| PAINEL-1b · "Ver todos" em "Equipamentos mais ocupados" | ⚠️ **Medido:** a tela de destino **não existe** |
| **Bloco de deploy da Fase 5 em produção** | ⚠️ **Não é opcional, só não tem data.** 4 documentadores reais mudam de permissão |
| Elemento "fora dos papéis configurados" | 1 elemento em produção |
| 5i · anexo por formulário próprio | ⚠️ **Medido:** reescrever o cartão inteiro |
| Aviso na purga de elemento com vínculo vivo | O `PurgeCleaner` faz a faxina calado (lição 14) |
| **Rótulo do elemento com localização em FOLHA, não `completename`** | ⚠️ **Novo (28/08).** Hoje sai `PTO 4 · Shopping Ventura > DGO Cristian · #27`. Decidir olhando a produção |
| **Uniformizar o formato do seletor de destino com o `ItemLabel`** | Dívida 7 |

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Trinta** ideias avaliadas e recusadas
com motivo. **Não ressuscitar sem fato novo.**

A mais importante desta sessão:

- **Trava de duplicados no DGO+ — CANCELADA.** Decisão do usuário. O princípio
  *"não deve existir itens duplicados na mesma localização"* vira **regra
  operacional**, corrigida direto na produção. O software **apenas sinaliza**,
  pelo 5e-2d. Pesou que a trava nunca pegaria a ficha nativa, o `datainjection`
  nem o SQL.

E duas de escopo, registradas para não voltarem como defeito:

- **Desambiguação por colisão nos cards de vínculo — impossível**, não recusada
  por gosto: colisão só se detecta onde há lista, e no card há um destino só.
- **O seletor de destino fica fora do `ItemLabel`** — tem regra própria, do 5e.

---

## Parte G — a frente shopmap (fora deste repositório)

⚠️ **`teckcomp/glpi-plugin-shopmap` está PRIVADO** — 404 anônimo conferido em
28/08. **O assistente não lê esse código.**

O problema é o mesmo do 5e: a lista de "Vincular ativo (nome)" mostra **nome +
itemtype apenas**. A regra fechada nesta sessão vale para os dois plugins:

> **Referência a ativo é `itemtype` + `id`. Nome é rótulo, nunca chave.**

Sugestões registradas: rótulo explícito para ativo removido; filtro de entidade
na busca; normalizar espaços (`DGO001` casar com `DGO 001`); aceitar `#id`
digitado; e, se hoje o vínculo for por nome, **relatório em três baldes antes de
migrar** (resolve para 1 / para 2+ / para 0).

**Destravar exige** ou tornar o repositório público, ou receber o arquivo que
monta a lista de busca. É a pendência 16.

---

## O que muda com a produção crescendo

| Bloco | Como se comporta quando a base cresce |
|---|---|
| ~~5e-2~~ | ✅ **RESOLVIDO.** Era o pior de todos |
| **5e-2d** | **Melhora de valor com escala:** 159 elementos em 9 localizações produzem mais colisões que 37 em homologação |
| **5d** | Não piora. É correção de regra |
| **5h-2** | **Melhora com escala** |
| **BADGE-C**, contador de entradas | Neutros. Qualidade de leitura |
| **Rótulo com `completename`** | **Piora com escala:** árvore de localização mais funda = rótulo mais longo |
| **Deploy em produção** | Piora com o tempo |

---

## Próximo passo imediato

1. **5g-1b — o último não exercitado.** Sentada própria; ver a seção 9 do contexto.
2. **5e-2d — o selo de fora de conformidade.** Aprovado, escopo medido (uma
   consulta por carga de tela, escopada só por `locations_id`, **nunca** a partir
   da lista já filtrada por piso e papel). Falta a decisão visual: cor, onde
   aparece, texto do `title`.
3. **Higiene, com pré-condição:** desmontar a **E3 da `CTO 01 #36`** e só então
   purgá-la; remover o `PISO VAZIO TESTE`; decidir o destino do `DGO 01 #37`.
4. **Decisão pendente:** localização no rótulo — `completename` ou folha?
5. **Commit — bloco 5d**, confirmação em dois tempos. ⚠️ Mexe no
   `Link::propose()`. O trabalho real é fazer destino e entrada sobreviverem ao
   redirect.
6. **Commit — BADGE-C + contador de entradas separado.** ⚠️ **A pendência 15 é
   pré-requisito de leitura.**
7. **SKILL** — barato, e para de custar em toda sessão.
8. Depois: **5h-2**, **5i**, e o **deploy em produção**.
9. **Frente shopmap** — bloqueada pela pendência 16.
10. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.
