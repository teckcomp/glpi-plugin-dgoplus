# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v15 — 28/08/2026. Sucede o v14, do mesmo dia.
> A mudança que justifica a versão nova: **uma sessão inteira de validação, sem
> uma linha de código**. O **5e** e o **5c** saíram da lista de "entregues e não
> exercitados"; a pendência 4 tem resposta; e a purga da `#36` ganhou uma
> pré-condição que ninguém tinha visto.
> Versão **1.3.16**, `master` em **`e1fce73`** (só `docs/` à frente do `02b64d5`).

---

## Parte A — resultado da revisão (histórico, não mexer)

| # | Passo | Decisão |
|---|---|---|
| 1 | Estado do ambiente | Homologação estava em 1.3.0. Corrigido pelo 5-sync. Lição 114 |
| 2 | Bloco 5a | **Aprovado.** Poda em cascata funciona nos três seletores |
| 3 | Ciclo do vínculo | **Aprovado.** Modelo sem lixeira honrado. Ajuste 5c ✅ **FEITO E VALIDADO** |
| 4 | Papéis | **Aprovado.** Quatro degraus bastam; splitter fora |
| 5 | Escopo Localização → Piso | Piso fica; lote descartado; meia-medida → 5b ✅ **FEITO** |
| 6 | A grade no dia a dia | **Aprovado.** Digitação um a um é o fluxo certo |
| 7 | Permissões | **Maior achado.** 5f-1a/1b, 5f-2a/2b, 5f-3a/3b, 5g-1, 5g-1b, 5g-2/2b, 5g-3 — **TODOS RESOLVIDOS** |
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
| 5a | Escopo Localização → Piso no seletor de destino | 1.3.1 (23/08) |
| 5h | JOIN da coluna Localização no relatório | 1.3.2, `bd28ffd` |
| DOC, GIT-1, GIT-2, REL, REL-2 | `docs/`, clone Git, tags e Releases | 27/08 |
| 5f-1a … 5f-3b | A frente de permissões inteira | até 1.3.8, `0005c90` |
| 5g-1 | Auto-save da porta distingue 403 de rede | 1.3.9, `f94dbe5` |
| 5g-2 / 5g-2b | Telas nomeiam o direito; dicas saem da moldura | 1.3.11, `560fb64` |
| 5g-1b | Auto-save do comentário não reenvia recusa | 1.3.12, `15d0c30` |
| 5g-3 + PAINEL-1a + README | Nota no perfil; "Ver todos"; README reescrito | 1.3.13, `c9c3546` |
| 2b | Nota vira card abaixo da matriz; volta ao mapa no relatório | 1.3.14, `327c62c` |
| 5b + 5e | Poda do seletor de piso; desambiguação por colisão | 1.3.15, `e3faec0` |
| 5c | Trilha parte da entrada, não do elemento | 1.3.16, `02b64d5` |

### ✅ Validado em tela nesta sessão

| Bloco | O que se provou | Evidência |
|---|---|---|
| **5e** | O seletor de destino mostrou **`CTO 01 (CTO) #35`** e **`CTO 01 (CTO) #36`** — mesmo nome, mesmo papel, mesma localização, mesmo piso, e a colisão produziu o sufixo | Print do seletor aberto |
| **5c** | Elemento com **dois pais distintos**: card **E1** → trilha `DGO · DGO 01`; card **E2** → trilha `DGO · DGO 01 - PORTO BELO`. **Nenhum `+`, nenhum nome vazado entre os cards** | Prints dos dois cards |

⚠️ **Ressalva do 5e:** a metade "quem tem nome único continua sem sufixo" **não
foi exercitada** — o escopo do 5a podou a lista para dois candidatos, os dois
ambíguos. Fica para quando o seletor mostrar lista maior. Não é bloqueio.

### ⚠️ Entregue mas NÃO exercitado — resta um

| Bloco | O que falta | Por que não foi feito |
|---|---|---|
| **5g-1b** | Tirar ATUALIZAR **com a aba aberta**, digitar 3× no comentário, e conferir **uma única linha** `POST … 200 … dgocomment.php` no log do Apache | Exige tirar direito no meio da sessão (lição 151). Sentada própria |

✅ **O roteiro do 5g-1b já foi conferido contra o código nesta sessão** (lição
158): depois da primeira recusa o `save()` sai antes do `fetch`, então uma linha
só é mesmo o esperado — e o status é **200**, não 403.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **SIM.** Reaberta pela lição 148 como candidato 5i |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ `itemtype_item_revert` + `specific_itemtype` |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ tem |
| **4** | **Os dois elementos homônimos são ativos distintos?** | ✅ **RESPONDIDA (28/08, em tela).** São dois `CTO 01` distintos, ids **35** e **36**, **ambos de papel CTO**, na mesma localização e piso. A `#35` é a original (E1 ← `DGO 01`); a `#36` é a ex-`CTO TESTE 5f2b` renomeada |
| 5 | Existe clone Git no servidor? | ✅ criado |
| 6 | A "Falha ao salvar" da F1.02 foi 403? | ✅ SIM, corrigida pelo 5g-1 |
| 7 | O Histórico registra o técnico como autor? | Dada como ok pelo usuário, sem tela |
| 8 | Limpar a ex-`CTO TESTE 5f2b` (hoje `CTO 01 #36`) | ⚠️ **BLOQUEADA, e por bom motivo.** A `#36` **tem a E3 ocupada** — não é "64 portas mortas". Purgar sem desmontar antes destrói um vínculo confirmado em silêncio (lição 161) |
| 11 | Por que a DGO 01 mudou de portas documentadas? | **Aberta.** Baixo risco. ⚠️ E a sessão mexeu de novo: F1.04 desmontada, badge 6 → 5 |
| 12 | F1.06 da DGO 01 "sem acoplador" E com vínculo confirmado ao mesmo tempo | **Aberta.** ⚠️ **Ganhou pista:** a E3 ocupada da `#36` pode ser o destino dessa F1.06. **Teste barato:** abrir a F1.06 da `DGO 01` e ler o nome do destino na seção Alimenta. Não verificado |
| 13 | `PISO VAZIO TESTE` e o papel trocado da `CTO 01` | ⚠️ **Metade caiu.** O papel **não está trocado** — a tela mostra os dois `CTO 01` como CTO. Resta só remover o piso |
| **14** | **Quem alimenta a E3 da `CTO 01 #36`?** | **Nova (28/08).** É pré-condição da pendência 8 |
| **15** | **Porta de grade com vínculo e sem nome conta como documentada?** | **Nova (28/08).** A badge da `DGO 01 - PORTO BELO` foi de 0 para 1 com o campo Nome vazio. Não conferido em `Port::statsForDgo()` |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | ~~README desatualizado~~ | ✅ **quitada (28/08)** |
| 2 | **Sem catálogo de tradução.** ⚠️ Decisão de produto antes: demanda real ou higiene? | Grande; não cabe num bloco |
| 3 | **Lista integral de lições (1–113).** ⚠️ **Caminho barato esgotado** | Só pelo documento original |
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
| Aviso de vínculo pendente que envelhece | Achado de 28/08 |
| PAINEL-1b · "Ver todos" em "Equipamentos mais ocupados" | ⚠️ **Medido:** a tela de destino **não existe** |
| **Bloco de deploy da Fase 5 em produção** | ⚠️ **Não é opcional, só não tem data.** 4 documentadores reais mudam de permissão |
| Elemento "fora dos papéis configurados" | 1 elemento em produção |
| 5i · anexo por formulário próprio | ⚠️ **Medido:** reescrever o cartão inteiro. **Não é pequeno** |
| **Aviso na purga de elemento com vínculo vivo** | **Novo (28/08).** O `PurgeCleaner` faz a faxina calado — e a lição 14 diz que falha silenciosa é o defeito mais caro. ⚠️ Não é bloco definido: pode ser tela do core, o que mudaria tudo |

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Vinte e seis** ideias avaliadas e
recusadas com motivo. **Não ressuscitar sem fato novo.**

Duas entraram em 28/08 nesta sessão de validação:

- **Igualar os Tipos dos dois `CTO 01` para o teste do 5e** — passo construído
  sobre dado desatualizado do v14; a tela mostrou que os dois já eram CTO.
  Lição 160.
- **Purgar a `CTO 01 #36` como estava planejado** — **suspensa, não descartada**:
  ganhou a pré-condição de desmontar a E3 antes. Lição 161.

---

## O que muda com a produção crescendo

| Bloco | Como se comporta quando a base cresce |
|---|---|
| **5e-2** | **Pior de todos.** ⚠️ **E a sessão deu prova de tela:** com dois `CTO 01`, o rótulo `E2 de CTO 01` da seção Alimenta, a faixa de entradas e as abas do mapa **não dizem qual dos dois**. Com 300 elementos, vínculo errado vira topologia errada |
| **5d** | Não piora. É correção de regra |
| **5h-2** | **Melhora com escala** — filtrar por localização vale mais com 9 localizações |
| **BADGE-C**, contador de entradas | Neutros. Qualidade de leitura |
| **Deploy em produção** | Piora com o tempo: quanto mais gente documentando, maior o risco da troca de permissão |

---

## Próximo passo imediato

1. **5g-1b — o último não exercitado.** Roteiro já conferido contra o código;
   ver a seção 9 do contexto. Sentada própria, porque mexe em permissão de perfil
   no meio da sessão e termina devolvendo o direito.
2. **Higiene, agora com pré-condição:** desmontar a E3 da `CTO 01 #36` **antes**
   de purgá-la; remover o `PISO VAZIO TESTE`; e de passagem responder às
   pendências 14 e 12 abrindo a F1.06 da `DGO 01`.
3. **Commit 4b — bloco 5d**, confirmação em dois tempos. ⚠️ Mexe no
   `Link::propose()`, o ponto único de criação de vínculo. O trabalho real é
   fazer destino e entrada sobreviverem ao redirect.
4. **Commit 5 — BADGE-C + contador de entradas separado.** Mesma contagem em
   dois lugares, por isso no mesmo bloco. Toca `Port::statsForDgo()`,
   `MapController::renderBadges()`, `ajax/port.php` e `Dashboard.php`.
   ⚠️ **A pendência 15 é pré-requisito de leitura**: antes de mexer no contador,
   saber por que porta com vínculo e sem nome já conta como documentada.
5. **5e-2 — DISCUTIR ANTES DE CODAR.** Aprovado, não detalhado. Agora com três
   pontos de ambiguidade **medidos em tela**, não só no código.
6. **SKILL** — barato, e para de custar em toda sessão.
7. Depois: **5h-2**, **5i**, e o **deploy em produção**.
8. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.
