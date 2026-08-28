# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v12 — 28/08/2026. Sucede o v11, de 27/08.
> A mudança que justifica a versão nova: **o 5g-1 fechou**, e com ele o pior
> defeito da frente de permissões — o auto-save que dizia "Falha ao salvar" para
> uma recusa de direito, e ainda reenviava. Provado por log: **um único 403 para
> três disparos**.
> Números verificados em `f94dbe5`, versão **1.3.9**.
>
> **Itens novos nesta versão:** BADGE-C (decidido), PAINEL-1, REV, e a separação
> produção × homologação, que muda como as prioridades se leem.

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
| 7 | Permissões | **Maior achado.** → 5f-1a/1b, 5f-2a/2b, 5f-3a/3b **RESOLVIDOS**; **5g-1 RESOLVIDO**; faltam **5g-2** e **5g-3** |
| 8 | Relatório | **Bug** (erro 1054, lição 121) → Bloco 5h **RESOLVIDO e COMMITADO** |

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

**5f-1b · Propor vínculo exige UPDATE** — fechado e validado em tela + log
(27/08), 1.3.4, `a690010`.

**5f-2a · Comentário do elemento exige o direito do plugin** — fechado e validado
nas duas pontas (27/08), 1.3.5, `1114077`.

**5f-2b · Criar elemento pelo mapa exige só o direito do plugin** — fechado e
validado em tela (27/08), 1.3.6, `04ac8fd`.

**5f-3a · Caminho da porta larga o `datacenter` READ** — fechado e validado em
tela + log (27/08), 1.3.7, `72d4e55`. Criou `Port::parentIsReachable()`.

**5f-3b · OBS, vínculo e comentário largam o `datacenter` READ** — fechado e
validado em tela (27/08), 1.3.8, `0005c90`. **A lição 117 está cumprida**, e o
menu "Dispositivos passivos" sumiu.

**Os dois greps que fecham a frente 5f, rodados de novo em `f94dbe5`:**

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

**5g-1 · Auto-save distingue 403 de falha de rede** — **fechado e validado em
tela + log (28/08), 1.3.9, `f94dbe5`.**

`+39 −3` em dois arquivos. O `Error` do `!response.ok` passou a carregar
`status`; o 403 é tratado antes do `fallbackOnFailure` (o `form.submit()` não
roda, tomaria o mesmo 403 e perderia o texto digitado); `permissionDenied` é
estado do módulo, não da célula, porque o direito é da sessão; e com ele ligado
`save()` não reenvia, mas **repete a mensagem** (lição 16).

A mensagem que o usuário vê agora:

> Sem permissão para documentar portas. Exige «Atualizar» em «Portas de DGO»
> (Administração → Perfis → aba DGO+).

**A prova, no `other_vhosts_access.log`:**
`POST /plugins/dgoplus/ajax/port.php ... 403 ... edit=1-7` às 11:06:33 — **uma
linha só, para três disparos de `save()`**. E, com o direito devolvido, F1.07
gravou com "Salvo ✓" e a badge da DGO 01 foi de 6/16 para 7/16.

⚠️ **O primeiro roteiro deste bloco estava errado** e não tinha como passar —
virou a lição 151, que é o achado mais durável da sessão.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **Respondida: SIM.** Reaberta pela lição 148 como candidato 5i |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ **Respondida:** `itemtype_item_revert` + `specific_itemtype` obrigatório |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ **Respondida:** tem |
| 4 | Os dois "PTO 001" são ativos distintos? | **Aberta — e agora mais urgente.** Produção tem **1 PTO só**, então o caso está na homologação (6 PTOs) ou não é sobre papel PTO. ⚠️ **A mesma trava existe no ShopMap**, o que faz do 5e um **padrão de desambiguação**, não um remendo local |
| 5 | Existe clone Git no servidor? | ✅ **Respondida: foi criado** |
| 6 | A "Falha ao salvar" da F1.02 foi 403 por DELETE? | ✅ **Respondida: SIM.** E **corrigida pelo 5g-1** |
| 7 | O Histórico da ficha do ativo registra o técnico como autor do comentário? | **Dada como ok pelo usuário (28/08), sem tela.** Um passo se quiser confirmar: DGO 01 → aba Histórico como admin, esperando `teste.001` alterando `comment` |
| 8 | Limpar `CTO TESTE 5f2b` | **Dada como ok pelo usuário (28/08), sem tela.** ⚠️ Mas o vínculo F1.06 → E3 **aparecia confirmado na tela de 28/08**, então o ativo provavelmente ainda existe. **São 64 portas mortas em 1889 — 3,4% da homologação.** Conferir |
| ~~9~~ | ~~O perfil de teste fica com CRIAR?~~ | ❌ **Fechada como decisão negativa (28/08):** é decisão do administrador de cada ambiente, conforme a necessidade. Não é pendência de projeto |
| **10** | **A lição 133 também vale para o `ajax/dgocomment.php`?** | **Aberta (28/08).** É outro arquivo e outro caminho de JS; o 5g-1 só cobriu o `ajax/port.php`. Se valer, vira **5g-1b**. Descobre-se ao abrir o 5g-2 |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | **README desatualizado.** **Escopo decidido em 28/08:** vira porta de entrada curta, com instalação apontando para a **página de Releases** (nunca versão fixa — foi assim que envelheceu) e links para `docs/` | Bloco pequeno, sem risco |
| 2 | **Sem catálogo de tradução.** ⚠️ **Decisão de produto antes:** demanda real ou higiene? Tocaria os 27 arquivos, então só funciona partido por arquivo ou por tela, e o JS precisa de mecanismo próprio | Grande; não cabe num bloco |
| 3 | **Lista integral de lições (1–113)** não incorporada. **Dois caminhos:** `grep -rn "lição"` no código (parcial, barato) ou buscar o documento nas conversas antigas | Investigação |
| 4 | ~~Sem tag/Release~~ ✅ **quitada em 27/08** | — |
| 5 | **Skill `glpi-plugin-teckcomp` desatualizada** — host, usuário, porta, `pscp` → `scp`, e a ordem de entrega com `git diff`. **Aprovada em 28/08** | Bloco SKILL |
| 6 | **Texto fala de "Desmontar" sem o botão existir** | Cabe no 5g-2 |

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
| **Aviso de vínculo pendente que envelhece** | **Achado de 28/08:** a homologação tinha um pendente há **7 dias** (`CTO01 → PTO 4 · E3`, por cristian.b), segurando duas portas nas duas pontas. Não é defeito — pendente ocupa a porta de propósito —, mas ninguém é avisado. O cartão de pendentes já mostra a idade; falta a decisão de o que fazer com ela |
| **Bloco de deploy da Fase 5 em produção** | ⚠️ **Não é opcional, só não tem data.** Quando o direito `plugin_dgoplus_port` substituir o `datacenter` nos perfis de produção, são 4 documentadores reais que mudam de permissão. Precisa de plano de rollback próprio |
| **Elemento "fora dos papéis configurados"** | Observação do painel de produção (28/08): **1 elemento** hoje. Papel nulo não participa da hierarquia. Com o parque crescendo, tende a crescer junto |

### Itens novos, decididos ou levantados em 28/08

**BADGE-C · Badge do elemento com grade e entradas separadas** — **decidido**
(variante C, escolhida com apoio visual). Dois contadores lado a lado:
`0/16 grade` e `2/4 entradas`. Não mistura os números e **não mexe na ocupação
geral** — somar entradas ao total mudaria o significado dos 44,9% da produção
(ver decisão negativa no contexto). A linha de entradas só aparece para papéis
que podem receber (`dgo`, `cto`, `pto`) — um DIO não recebe —, e **some por
regra, não por acaso** (lição 16).
⚠️ **Nada foi lido no código ainda:** `Dashboard.php` e `MapController.php` não
foram abertos. O bloco começa pela leitura, e o tamanho é expectativa.

**PAINEL-1 · "Ver todos" nos cartões do painel** — **dois** cartões:
"Equipamentos mais ocupados" e "Atividade recente". *Vínculos pendentes ficou de
fora: já tem "Abrir a fila completa", do 4d.*
⚠️ **Pergunta que decide o tamanho:** nenhum dos dois tem tela de destino
conhecida. Se não existir listagem completa, o bloco deixa de ser "acrescentar
link" e vira **"criar duas telas"** — de pequeno para médio. Investigar antes de
prometer.

**REV · Revisão competitiva** — **ao fim de todas as correções**, levantar
softwares e aplicativos de papel similar (gestão de rede óptica, OSP, FTTx) e
avaliar recursos que valha adaptar ao DGO+. Não é bloco de código: é uma sessão
de estudo com saída em lista de candidatos, cada um com fonte e motivo.

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Dezenove** ideias avaliadas e recusadas
com motivo. **Não ressuscitar sem fato novo.**

Quatro entraram em 28/08:

- **Grade padrão por papel** — a lição 146 é real, mas a solução é operacional:
  o administrador concede DELETE a quem concede CRIAR.
- **Entradas na ocupação geral (badge "variante B")** — mudaria o significado da
  métrica de investimento.
- **Item de roadmap para o estado do perfil de teste** — é do administrador de
  cada ambiente.
- **"Ver todos" em vínculos pendentes** — já existe.

⚠️ **Uma decisão negativa foi ressuscitada com fato novo, e é assim que o
mecanismo deve funcionar:** "anexo pelo técnico" era negativa até a **lição 148**
mostrar que a trava é do formulário do core, não do modelo. Virou o candidato
**5i**. O que a Parte F proíbe é reabrir por **esquecimento** — não por
descoberta.

---

## O que muda com a produção crescendo

A informação de que existe um parque real de 159 elementos e 4944 portas, e que
ele vai crescer, reordena o que sobrou. **Os blocos não são iguais em relação a
escala:**

| Bloco | Como se comporta quando a base cresce |
|---|---|
| **5g-2 / 5g-3** | **Pioram com o número de gente.** Cada tarja muda é uma pessoa parada sem saber por quê |
| **5e** | **Pior de todos.** Nome ambíguo com 159 candidatos já é escolha às cegas; com 300, vínculo errado vira topologia errada — o defeito caro (lição 14) |
| **5b** | Piora junto: seletor de piso listando piso sem candidato |
| **5c**, **5d** | **Não pioram.** São correção de regra |
| **5h-2** | Não piora — **melhora com escala.** Filtrar por localização vale mais com 9 localizações |
| **BADGE-C**, **PAINEL-1** | Neutros. Qualidade de leitura, não de operação |

---

## Próximo passo imediato

1. **5g-2** — as tarjas que ainda não nomeiam o direito. Confirmado em tela
   (28/08): comentário e vínculo **já nomeiam**; o painel da porta ainda diz
   *"Você tem permissão apenas de leitura nesta porta."* — a última tarja muda.
   Junto: o texto do "Desmontar" sem botão, o formulário de criar elemento que
   some sem explicação, os botões de fileira/coluna no mesmo silêncio.
   **Ao abrir, responder a pendência 10** (o `ajax/dgocomment.php` tem o mesmo
   defeito do 133?).
2. **5g-3** — nota na aba DGO+ do perfil.
3. **Higiene** — conferir e purgar o `CTO TESTE 5f2b` (64 portas mortas, 3,4% da
   homologação).
4. **SKILL** — barato, e para de custar em toda sessão.
5. Depois, na ordem que fizer sentido: **5h-2**, **BADGE-C**, **PAINEL-1**,
   **5b**, **5c**, **5d**, **5e** (com o ShopMap junto) e **5i**.
6. **REV** — ao fim de tudo.
