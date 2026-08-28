# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v17 — 28/08/2026. Sucede o v16, do mesmo dia.
> A mudança que justifica a versão nova: **o último débito de prova do projeto
> foi pago** (5g-1b exercitado em campo), **o 5e-2d foi ao ar pela metade**, e
> **uma decisão de rótulo que estava aberta desde o v15 fechou**.
> Versão **1.3.20**, `master` em **`fbf1952`**.

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
| 7 | Permissões | **Maior achado.** Frente inteira **RESOLVIDA e EXERCITADA** |
| 8 | Relatório | **Bug** (erro 1054) → 5h **RESOLVIDO** |

**A frente de permissões da Fase 5 está fechada e agora também PROVADA em
campo.** Os dois greps que a guardam:

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
| 5e-2a / 5e-2b / 5e-2c | O `ItemLabel` e seus 12 consumidores | até 1.3.19 |
| **5e-2d-1** | **Selo de nome duplicado: aba + cabeçalho da grade** | **`bb0a591`** |
| **5e-2d-1b** | **Bump para 1.3.20 — bloco de reparo (lição 165)** | **1.3.20, `fbf1952`** |

### ✅ Validado em tela nesta sessão

| Bloco | O que se provou | Evidência |
|---|---|---|
| **5g-1b** | Recusa de permissão trava o auto-save do comentário | Três blur com textos diferentes → **uma** linha `POST … 200 … dgocomment.php` |
| **5e-2d-1** | Selo acende no par certo, não acende sozinho e **sobrevive ao filtro de piso** | Seis passos, seis telas |
| **5e-2d-1b** | Versão volta a identificar o código | Tela de Plugins em **1.3.20** |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.** Primeira vez desde o 1.3.12.

Resta um passo solto, não um bloco: **o tooltip do ícone da aba** (passo 2 do
roteiro do 5e-2d-1) nunca foi conferido. Trinta segundos na próxima sessão.

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
| 8 | Limpar a ex-`CTO TESTE 5f2b` (`CTO 01 #36`) | ⚠️ **LIBERADA.** O cenário já serviu ao teste do 5e-2d-1. Pré-condição: desmontar a E3 primeiro |
| 11 | Por que a DGO 01 mudou de portas documentadas? | **Aberta.** Baixo risco |
| 12 | F1.06 da DGO 01 "sem acoplador" E com vínculo | ✅ Alimenta a **E3 da `CTO 01 #36`** |
| 13 | `PISO VAZIO TESTE` e o papel trocado da `CTO 01` | ⚠️ Resta remover o piso. **Não visto em tela nesta sessão** |
| 14 | Quem alimenta a E3 da `CTO 01 #36`? | ✅ A **F1.06 da `DGO 01 #34`** |
| **15** | Porta de grade com vínculo e sem nome conta como documentada? | ✅ **RESPONDIDA (28/08, por código).** **SIM.** `applyInput` linha 512 não apaga porta com vínculo; `statsForDgo` só conta linhas e desconta sem-acoplador. **Destrava o BADGE-C** |
| 16 | O shopmap guarda o vínculo por NOME ou por `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado |
| **17** | **Alguma localização da homologação tem 9+ elementos?** | **Nova (28/08).** Decide se o 5e-2d-2 é testável em homologação. `Outlet Porto Belo` = 5, `shopping palladium` = 4 |
| **18** | **As 9 localizações da produção têm localização-pai?** | **Aberta, e agora de baixa prioridade** — deixou de importar quando o `completename` foi confirmado |

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
| 7 | **Seletor de destino usa formato próprio** (`CTO 01 (CTO) #35`) | ⚠️ **Reduzida:** com o `completename` confirmado, é só sobre o seletor |
| **8** | **NOVA — o seletor único (9+ elementos) não sinaliza colisão** | É o 5e-2d-2. **Pesa na produção**, que tem 159 elementos |

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
| ~~Rótulo com localização em FOLHA~~ | ✅ **RESOLVIDO: fica `completename`.** Ver Parte F |
| **Uniformizar o formato do seletor de destino com o `ItemLabel`** | Dívida 7 |
| **Reaproveitar o `normalizeName()` do 5e-2d na frente shopmap** | Ele já faz `DGO 001` casar com `DGO001` |

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Trinta e seis** ideias avaliadas e
recusadas com motivo. **Não ressuscitar sem fato novo.**

As desta sessão:

- **Rótulo com localização em FOLHA — DESCARTADA.** E a variante **RAIZ**
  também, e a de **trocar o separador `>` por `/`**. **Decisão do usuário: fica
  o `completename`, exatamente como o core devolve.** No único caso real
  conhecido (`Shopping Ventura > DGO Cristian`), a folha apagaria a unidade.
- **Selo só na aba ativa — descartada.** Acende em todas as abas do par, porque
  o par é a informação.
- **Mexer no `MAX_TABS = 8` — descartada.** ✅ Confirmado pelo usuário: o
  dropdown deve mesmo aparecer a partir do nono elemento.
- **Selo nos cards de trilha, Alimenta, anexos e painel da porta — fora do
  escopo.** Os dois primeiros atravessam localização; os dois últimos repetiriam
  a marca do cabeçalho na mesma página.
- **Commit único para "fechar tudo de uma vez" — reafirmada.** Diante do pedido
  de fazer tudo numa tacada: vários blocos numa sessão, sim; num commit, não.

---

## Parte G — a frente shopmap (fora deste repositório)

⚠️ **`teckcomp/glpi-plugin-shopmap` está PRIVADO** — 404 anônimo conferido em
28/08. **O assistente não lê esse código.**

O problema é o mesmo do 5e: a lista de "Vincular ativo (nome)" mostra **nome +
itemtype apenas**. A regra que vale para os dois plugins:

> **Referência a ativo é `itemtype` + `id`. Nome é rótulo, nunca chave.**

Sugestões registradas: rótulo explícito para ativo removido; filtro de entidade
na busca; normalizar espaços (`DGO001` casar com `DGO 001`); aceitar `#id`
digitado; e, se hoje o vínculo for por nome, **relatório em três baldes antes de
migrar** (resolve para 1 / para 2+ / para 0).

⚠️ **Novidade útil:** o `MapController::normalizeName()`, escrito no 5e-2d-1, já
resolve a normalização de espaços e caixa. É código pronto para essa frente.

**Destravar exige** ou tornar o repositório público, ou receber o arquivo que
monta a lista de busca. É a pendência 16.

---

## O que muda com a produção crescendo

| Bloco | Como se comporta quando a base cresce |
|---|---|
| ~~5e-2~~ | ✅ **RESOLVIDO** |
| **5e-2d-1** | ✅ **No ar.** Melhora de valor com escala |
| **5e-2d-2** | ⚠️ **É JUSTAMENTE onde a escala pesa:** localização com 9+ elementos usa o seletor único, e hoje ele **não sinaliza nada**. Na produção, com 159 elementos em 9 localizações, esse é o caso comum |
| **5d** | Não piora. É correção de regra |
| **5h-2** | **Melhora com escala** |
| **BADGE-C**, contador de entradas | Neutros. Qualidade de leitura |
| ~~Rótulo com `completename`~~ | Decidido: fica. O custo com árvore funda foi aceito |
| **Deploy em produção** | Piora com o tempo |

---

## Próximo passo imediato

1. **Tooltip da aba** — o único passo de roteiro solto. Trinta segundos.
2. **5e-2d-2 — decisão antes do código:** validar em homologação (exige achar
   localização com 9+ elementos — pendência 17) ou validar na produção. **Sem
   uma das duas, o bloco não sai.**
3. **Higiene, agora liberada:** desmontar a **E3 da `CTO 01 #36`**, purgá-la,
   remover o `PISO VAZIO TESTE`, decidir o `DGO 01 #37`. ⚠️ **Purgar `#36` e
   `#37` deixa a homologação sem nenhum par homônimo** — e portanto sem cenário
   para reteste do 5e-2d-1.
4. **SKILL** — barato, e para de custar em toda sessão.
5. **Commit — bloco 5d**, confirmação em dois tempos. ⚠️ Mexe no
   `Link::propose()`.
6. **Commit — BADGE-C + contador de entradas separado.** ✅ A pendência 15, que
   era pré-requisito, está respondida.
7. Depois: **5h-2**, **5i**, e o **deploy em produção**.
8. **Frente shopmap** — bloqueada pela pendência 16.
9. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.
