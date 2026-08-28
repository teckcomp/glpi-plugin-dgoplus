# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v13 — 28/08/2026. Sucede o v12, do mesmo dia.
> A mudança que justifica a versão nova: **o 5g-2 fechou e o 5g-2b desfez metade
> dele**, por decisão de produto tomada com a tela na frente. E a pendência 10
> foi respondida: **o 5g-1b existe**, e é o próximo bloco.
> Números verificados em `560fb64`, versão **1.3.11**.

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
| 7 | Permissões | **Maior achado.** → 5f-1a/1b, 5f-2a/2b, 5f-3a/3b, **5g-1 e 5g-2/2b RESOLVIDOS**; faltam **5g-1b** (novo) e **5g-3** |
| 8 | Relatório | **Bug** (erro 1054, lição 121) → Bloco 5h **RESOLVIDO** |

---

## Parte B — Fase 5

### Concluído

**5a · Escopo Localização → Piso no seletor de destino** — fechado e validado
(23/08), 1.3.1.

**5h · Relatório: JOIN da coluna Localização** — fechado (27/08), 1.3.2,
`bd28ffd`.

**DOC · `docs/` no repositório** — fechado (27/08), `1ded500`.

**GIT-1 e GIT-2 · Git no servidor** — fechados (27/08). A classe inteira de
divergência servidor × repositório saiu de cena.

**REL e REL-2 · Tags `v1.3.2` e `v1.3.8` + Releases** — fechadas e conferidas
por sha256 (27/08).

**5f-1a · Documentar porta exige UPDATE** — fechado (27/08), 1.3.3, `6efab96`.
**A lição 118 está morta.**

**5f-1b · Propor vínculo exige UPDATE** — fechado e validado (27/08), 1.3.4,
`a690010`.

**5f-2a · Comentário do elemento exige o direito do plugin** — fechado e validado
(27/08), 1.3.5, `1114077`.

**5f-2b · Criar elemento pelo mapa exige só o direito do plugin** — fechado e
validado (27/08), 1.3.6, `04ac8fd`.

**5f-3a · Caminho da porta larga o `datacenter` READ** — fechado e validado
(27/08), 1.3.7, `72d4e55`. Criou `Port::parentIsReachable()`.

**5f-3b · OBS, vínculo e comentário largam o `datacenter` READ** — fechado e
validado (27/08), 1.3.8, `0005c90`. **A lição 117 está cumprida.**

**Os dois greps que fecham a frente 5f, rodados de novo em `560fb64`:**

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

**5g-1 · Auto-save da porta distingue 403 de falha de rede** — fechado e validado
em tela + log (28/08), 1.3.9, `f94dbe5`. `+39 −3`. A prova foi uma **única linha
`POST … 403 … edit=1-7`** no `other_vhosts_access.log` para três disparos de
`save()`.

**5g-2 · Telas nomeiam o direito que falta** — **fechado e validado em tela
(28/08), 1.3.10, `8da0634`.** `+56 −10`, um arquivo de código.

**5g-2b · Dicas de permissão saem da moldura do mapa** — **fechado e validado em
tela (28/08), 1.3.11, `560fb64`.** `+3 −35`, só remoção.

Os dois formam um par, e o par é a história inteira:

| Ponto | 5g-2 | 5g-2b |
|---|---|---|
| Tarja do painel da porta | nomeia **Atualizar** em **Portas de DGO** | **fica** |
| Dica do vínculo confirmado sem DELETE | deixa de mandar "Desmontar" e nomeia **Excluir** | **fica** |
| Dica abaixo da grade | criada | **removida** |
| Dica na faixa de busca | criada | **removida** |

⚠️ **As duas removidas estavam no escopo escrito do 5g-2** (o "próximo passo" do
contexto v12 as listava). Foram recusadas assim que apareceram em tela. Virou a
**lição 152** (escopo escrito não é decisão tomada) e a **lição 153** (a regra:
o painel da porta nomeia o direito; a moldura do mapa fica calada).

**A dívida 6 está quitada** — nenhum texto manda usar botão que não existe.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **Respondida: SIM.** Reaberta pela lição 148 como candidato 5i |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ **Respondida:** `itemtype_item_revert` + `specific_itemtype` obrigatório |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ **Respondida:** tem |
| 4 | Os dois "PTO 001" são ativos distintos? | **Aberta.** Produção tem **1 PTO só**, então o caso está na homologação (6 PTOs) ou não é sobre papel PTO. ⚠️ **A mesma trava existe no ShopMap**, o que faz do 5e um **padrão de desambiguação**, não um remendo local |
| 5 | Existe clone Git no servidor? | ✅ **Respondida: foi criado** |
| 6 | A "Falha ao salvar" da F1.02 foi 403 por DELETE? | ✅ **Respondida: SIM.** Corrigida pelo 5g-1 |
| 7 | O Histórico da ficha do ativo registra o técnico como autor do comentário? | **Dada como ok pelo usuário (28/08), sem tela** |
| 8 | Limpar `CTO TESTE 5f2b` | **Não feito.** O vínculo F1.06 → E3 continuava confirmado na tela de 28/08, então o ativo existe. **64 portas mortas em 1889 — 3,4% da homologação** |
| ~~9~~ | ~~O perfil de teste fica com CRIAR?~~ | ❌ **Fechada como decisão negativa (28/08):** é decisão do administrador de cada ambiente |
| ~~10~~ | ~~A lição 133 também vale para o `ajax/dgocomment.php`?~~ | ✅ **RESPONDIDA: SIM (28/08), por leitura do código.** O `dgoplus-identity.js` perde o status na string do `Error`, não distingue 403 no `.catch()`, chama `form.submit()` no fallback e exibe a mesma *"Falha ao salvar. Use o botão Salvar."* **Virou o bloco 5g-1b** |
| **11** | **Por que a DGO 01 mudou de portas documentadas?** | **Aberta (28/08).** O v12 registrava F1.07 com `2153` e badge 7/16; a tela mostra **F1.07 livre, F1.02 com `1202` e badge 6/16**. Saldo igual, portas diferentes, causa não registrada. Baixo risco — mas é exatamente o tipo de divergência silenciosa que o projeto trata como cara |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | **README desatualizado.** Vira porta de entrada curta, com instalação apontando para a **página de Releases** (nunca versão fixa — foi assim que envelheceu) e links para `docs/` | Bloco pequeno, sem risco |
| 2 | **Sem catálogo de tradução.** ⚠️ **Decisão de produto antes:** demanda real ou higiene? Tocaria os 27 arquivos | Grande; não cabe num bloco |
| 3 | **Lista integral de lições (1–113)** não incorporada. **Dois caminhos:** `grep -rn "lição"` no código (parcial, barato) ou buscar o documento nas conversas antigas | Investigação |
| 4 | ~~Sem tag/Release~~ ✅ **quitada em 27/08** | — |
| 5 | **Skill `glpi-plugin-teckcomp` desatualizada** — host, usuário, porta, `pscp` → `scp`, e a ordem de entrega com `git diff`. **Aprovada em 28/08** | Bloco SKILL |
| 6 | ~~Texto fala de "Desmontar" sem o botão existir~~ | ✅ **quitada pelo 5g-2 (28/08)** |

---

## Parte E — estacionamento

Candidatos, **nenhum comprometido**, com a fonte declarada.

| Ideia | Fonte |
|---|---|
| Endpoint AJAX para o vínculo, chamando o mesmo `Link::propose()` | Comentário no próprio `Link.php` |
| Vínculo porta ↔ chamado, pelo `itemtype_link`/`items_id_link` | Roadmap original (Fase 4) |
| Notificações nativas em evento de porta | Roadmap original (Fase 5) |
| Widgets no dashboard nativo do GLPI | Roadmap original (Fase 6) |
| Colunas novas no relatório (papel, piso, estado do vínculo) | Passo 8 — o caminho para "Piso" é o mesmo do 5h |
| `git pull` no servidor como forma de aplicar bloco | Nasceu do GIT-2 |
| Comentário do elemento com carimbo de autor na própria tela | Observação do 5f-2a |
| **Aviso de vínculo pendente que envelhece** | **Achado de 28/08:** a homologação tinha um pendente há **7 dias** (`CTO01 → PTO 4 · E3`), segurando duas portas. Não é defeito — pendente ocupa a porta de propósito —, mas ninguém é avisado |
| **Bloco de deploy da Fase 5 em produção** | ⚠️ **Não é opcional, só não tem data.** Quando o direito `plugin_dgoplus_port` substituir o `datacenter` nos perfis de produção, são 4 documentadores reais que mudam de permissão. Precisa de plano de rollback próprio |
| **Elemento "fora dos papéis configurados"** | Observação do painel de produção (28/08): **1 elemento** hoje. Papel nulo não participa da hierarquia |

### Itens decididos ou levantados em 28/08

**BADGE-C · Badge do elemento com grade e entradas separadas** — **decidido**
(variante C). Dois contadores lado a lado: `0/16 grade` e `2/4 entradas`. Não
mistura os números e **não mexe na ocupação geral**. A linha de entradas só
aparece para papéis que podem receber (`dgo`, `cto`, `pto`) — um DIO não recebe
—, e **some por regra, não por acaso** (lição 16).
⚠️ **Nada foi lido no código ainda:** `Dashboard.php` e `MapController.php` não
foram abertos. O bloco começa pela leitura, e o tamanho é expectativa.

**PAINEL-1 · "Ver todos" nos cartões do painel** — **dois** cartões:
"Equipamentos mais ocupados" e "Atividade recente".
⚠️ **Pergunta que decide o tamanho:** nenhum dos dois tem tela de destino
conhecida. Se não existir listagem completa, o bloco vira **"criar duas telas"**.
Investigar antes de prometer.

**REV · Revisão competitiva** — **ao fim de todas as correções**, levantar
softwares de papel similar (gestão de rede óptica, OSP, FTTx) e avaliar recursos
que valha adaptar. Não é bloco de código: é sessão de estudo com saída em lista
de candidatos, cada um com fonte e motivo.

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Vinte e uma** ideias avaliadas e
recusadas com motivo. **Não ressuscitar sem fato novo.**

Seis entraram em 28/08, as duas últimas nesta sessão:

- **Grade padrão por papel** — a solução é operacional, não de código.
- **Entradas na ocupação geral (badge "variante B")** — mudaria o significado da
  métrica de investimento.
- **Item de roadmap para o estado do perfil de teste** — é do administrador.
- **"Ver todos" em vínculos pendentes** — já existe.
- **Dica de permissão abaixo da grade** — banner permanente para estado que não é
  erro (lição 153).
- **Dica de permissão na faixa de busca** — idem; o lugar é a aba do perfil.

⚠️ **Uma decisão negativa foi ressuscitada com fato novo, e é assim que o
mecanismo deve funcionar:** "anexo pelo técnico" era negativa até a **lição 148**
mostrar que a trava é do formulário do core, não do modelo. Virou o candidato
**5i**. O que a Parte F proíbe é reabrir por **esquecimento** — não por
descoberta.

---

## O que muda com a produção crescendo

Existe um parque real de 159 elementos e 4944 portas, e ele vai crescer. **Os
blocos não são iguais em relação a escala:**

| Bloco | Como se comporta quando a base cresce |
|---|---|
| **5g-1b** | **Piora com o número de gente.** Comentário que diz "falha ao salvar" para uma recusa de direito, e reenvia, é o mesmo defeito que o 5g-1 provou caro |
| **5g-3** | Piora junto: cada administrador que não sabe o que conceder abre um chamado |
| **5e** | **Pior de todos.** Nome ambíguo com 159 candidatos já é escolha às cegas; com 300, vínculo errado vira topologia errada — o defeito caro (lição 14) |
| **5b** | Piora junto: seletor de piso listando piso sem candidato |
| **5c**, **5d** | **Não pioram.** São correção de regra |
| **5h-2** | Não piora — **melhora com escala.** Filtrar por localização vale mais com 9 localizações |
| **BADGE-C**, **PAINEL-1** | Neutros. Qualidade de leitura, não de operação |

---

## Próximo passo imediato

1. **5g-1b** — o auto-save do **comentário do elemento**
   (`public/dgoplus-identity.js`, 306 linhas, e `ajax/dgocomment.php`, 45). Mesmo
   defeito do 1.3.8, mesma correção do 5g-1. ⚠️ **Roteiro pela lição 151**: o
   `mountComment()` também sai na entrada sem `[data-dgoplus-dgo-flag]`, então o
   direito tem que ser retirado **com a aba já aberta**. Prova no
   `other_vhosts_access.log`, filtrando `dgocomment.php`.
2. **5g-3** — nota na aba DGO+ do perfil (`src/ProfileTab.php`). É o destino de
   toda informação de permissão que **não** cabe na tela do técnico (lição 153),
   incluindo o *"para usar anexos é necessário `document` READ+UPDATE+CREATE e
   `datacenter` UPDATE"* pedido pelo usuário.
3. **Higiene** — conferir e purgar o `CTO TESTE 5f2b` (64 portas mortas, 3,4% da
   homologação), e olhar a pendência 11 (a DGO 01 trocou de portas).
4. **SKILL** — barato, e para de custar em toda sessão.
5. Depois, na ordem que fizer sentido: **README**, **5h-2**, **BADGE-C**,
   **PAINEL-1**, **5b**, **5c**, **5d**, **5e** (com o ShopMap junto) e **5i**.
6. **REV** — ao fim de tudo.
