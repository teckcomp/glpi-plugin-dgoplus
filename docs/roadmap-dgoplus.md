# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v11 — 27/08/2026 (fim da sessão da noite). Sucede o v10, emitido
> poucos minutos antes da tag e da Release.
> A mudança que justifica a versão nova: **o 5f acabou**. Com o 5f-3a e o 5f-3b,
> o técnico trabalha no mapa inteiro **sem nenhum direito em Data centers**, e o
> menu "Dispositivos passivos" sumiu — o objetivo que abriu esta frente em
> 23/08. Sobra o **5g**, que é texto de tela.
> Números verificados em `0005c90` (o código; o topo do `master` é `38018e3`,
> que só traz `docs/`).
>
> **O REL-2 saiu junto:** tag `v1.3.8` e Release publicadas, com o zip conferido
> por sha256 contra o commit.

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
| 7 | Permissões | **Maior achado.** → 5f-1a, 5f-1b, 5f-2a, 5f-2b, **5f-3a e 5f-3b RESOLVIDOS**; falta só o **5g** |
| 8 | Relatório | **Bug** (erro 1054, lição 121) → Bloco 5h **RESOLVIDO e COMMITADO** |

---

## Parte B — Fase 5

### Concluído

**5a · Escopo Localização → Piso no seletor de destino** — fechado e validado
(23/08), versão 1.3.1.

**5h · Relatório: JOIN da coluna Localização** — fechado, validado e commitado
(27/08), versão 1.3.2, commit `bd28ffd`.

**DOC · `docs/` no repositório** — fechado (27/08), commit `1ded500`.

**GIT-1 e GIT-2 · Git no servidor** — fechados (27/08). A classe inteira de
divergência servidor × repositório saiu de cena.

**REL · Tag `v1.3.2` + Release** — fechada e conferida (27/08).

**5f-1a · Documentar porta exige UPDATE** — fechado e validado (27/08), versão
1.3.3, commit `6efab96`. **A lição 118 está morta.**

**5f-1b · Propor vínculo exige UPDATE** — fechado e validado em tela + log
(27/08), versão 1.3.4, commit `a690010`. Cinco `checkRight(CREATE)` viraram
`UPDATE`, mais a trava da tela e a mensagem que nomeia o direito.

**5f-2a · Comentário do elemento exige o direito do plugin** — **fechado e
validado em tela nas duas pontas (27/08), versão 1.3.5, commit `1114077`.**

Uma linha decide tudo: `DgoIdentity::canWriteComment` deixou de perguntar
`$dgo->can($items_id, UPDATE)` e passou a perguntar
`Session::haveRight(Port::$rightname, UPDATE)`. Como o método tem **dois
chamadores** — a tela e o ponto único `applyComment` —, POST, AJAX e renderização
mudaram juntos, sem chance de divergir. Mais as duas mensagens, que agora nomeiam
o direito e onde ele mora.

Provado: técnico com **ATUALIZAR e sem CRIAR** comentou pelo auto-save
("Salvo ✓"), o Super-Admin leu o mesmo texto em outra sessão, e **tirando
ATUALIZAR** o campo voltou a `readonly` com a tarja nova. `+28 −10`, previsto no
sandbox e confirmado no servidor. Proveniência fechada por md5.

⚠️ **Resíduo:** o Histórico da ficha do ativo, com `teste.001` como autor, **não
foi conferido**. Pendência 7 da Parte C.

**5f-2b · Criar elemento pelo mapa exige só o direito do plugin** — **fechado e
validado em tela (27/08), versão 1.3.6, commit `04ac8fd`.**

Duas linhas a menos: o `checkRight(PassiveDCEquipment::$rightname, CREATE)` do
`actionCreateDgo` e a metade correspondente do `$can_create` da tela. Com CRIAR
ligado, o técnico viu o formulário aparecer e criou `CTO TESTE 5f2b` (papel CTO),
que nasceu na entidade certa e abriu direto. A aba CTO passou de 1 para 2.

**Com ele o 5f-2 fecha inteiro**, e o acoplamento explícito ao direito do ativo
acaba: `grep -c 'PassiveDCEquipment::$rightname' src/MapController.php` = **0**.

⚠️ *Passos 5 e 6 do roteiro (lista de Dispositivos passivos; desmarcar CRIAR)
dados como ok pelo usuário, sem tela.*

**5f-3a · Caminho da porta larga o `datacenter` READ** — **fechado e validado em
tela + log (27/08), versão 1.3.7, commit `72d4e55`.**

Criou `Port::parentIsReachable()` — o ponto único da visibilidade do pai — e
trocou por ele os quatro pontos do caminho da porta. Prova: `POST ajax/port.php`
**200** com o perfil sem nenhum direito em Data centers, gravando a F1.05.

**5f-3b · OBS, vínculo e comentário largam o `datacenter` READ** — **fechado e
validado em tela (27/08), versão 1.3.8, commit `0005c90`.**

Os três pontos restantes passaram a chamar o mesmo método. Prova em tela: OBS do
elemento gravada, comentário com "Salvo ✓", vínculo F1.06 → E3 do CTO TESTE 5f2b
proposto e **confirmado**, e o menu **Ativos** exibindo **apenas Dashboard e
DGO+**.

**Os dois greps que fecham a frente:**

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

**SKILL · Atualizar a skill `glpi-plugin-teckcomp`** — host, usuário, porta **e o
comando de envio** (`pscp` → `scp`). Ela ainda manda para `192.168.1.50`, que está
morto, e com um cliente que não autentica neste servidor.

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 1 | Anexo exige `datacenter` UPDATE? | ✅ **Respondida: SIM.** Decisão de produto: **o técnico não anexa**. Lição 134 |
| 2 | Qual `jointype` para tabela polimórfica? | ✅ **Respondida:** `itemtype_item_revert` + `specific_itemtype` obrigatório |
| 3 | `glpi_passivedcequipments` tem `is_recursive`? | ✅ **Respondida:** tem, confirmado no schema do core |
| 4 | Os dois "PTO 001" são ativos distintos? | **Aberta.** Ativos → Dispositivos passivos, conferir ids |
| 5 | Existe clone Git no servidor? | ✅ **Respondida: não existia. Foi criado.** |
| 6 | A "Falha ao salvar" da F1.02 foi 403 por DELETE? | ✅ **Respondida: SIM, sete 403.** Lição 133 confirmada |
| **7** | **O Histórico da ficha do ativo registra o técnico como autor do comentário?** | **Aberta (27/08).** Um passo: abrir DGO 01 → aba Histórico como admin. Esperado: `teste.001` alterando `comment`. Não bloqueia nada; é o efeito colateral do 5f-2a numa tela do core |
| **8** | **Limpar `CTO TESTE 5f2b`** | **Aberta.** Ativo de teste do 5f-2b, grade de 64 posições, **com a entrada E3 confirmada vinda de DGO 01 · F1.06** (teste do 5f-3b). Purgar como admin — o `PurgeCleaner` (3q) leva as linhas do plugin e o vínculo |
| **9** | **O perfil de teste fica com CRIAR?** | **Aberta.** Ficou ligado depois do 5f-2b. Decidir antes do 5f-3, porque o roteiro dele parte de um estado conhecido do perfil |

---

## Parte D — dívidas conhecidas

| # | Dívida | Tamanho |
|---|---|---|
| 1 | **README desatualizado.** `dgoplus-v1.0.0.zip` (38, 45, 56), três tabelas quando são quatro (111, 142), linha 119 sobre portas órfãs | Bloco pequeno, sem risco |
| 2 | **Sem catálogo de tradução** | Bloco médio; decisão de produto antes |
| 3 | **Lista integral de lições (1–113)** não incorporada | Depende de achar o documento antigo |
| 4 | ~~Sem tag/Release~~ ✅ **quitada em 27/08** — `v1.3.8` publicada e conferida | — |
| 5 | **Skill `glpi-plugin-teckcomp` desatualizada** | Bloco SKILL |
| 6 | **Texto fala de "Desmontar" sem o botão existir** | Cabe no 5g |

---

## Parte E — estacionamento

Candidatos, **nenhum comprometido**, com a fonte declarada.

| Ideia | Fonte |
|---|---|
| Endpoint AJAX para o vínculo, chamando o mesmo `Link::propose()` | Comentário no próprio `Link.php` |
| Vínculo porta ↔ chamado, pelo `itemtype_link`/`items_id_link` do schema | Roadmap original (Fase 4) |
| Notificações nativas em evento de porta | Roadmap original (Fase 5) |
| Widgets no dashboard nativo do GLPI | Roadmap original (Fase 6) |
| Atualizar o README | Dívida 1 — escopo fechado |
| **Formulário próprio de upload de anexo** | **Sem dono:** a decisão de 27/08 foi que o técnico não anexa |
| Colunas novas no relatório (papel, piso, estado do vínculo) | Passo 8 — o caminho para "Piso" é o mesmo do 5h |
| `git pull` no servidor como forma de aplicar bloco | Nasceu do GIT-2 — evitaria o `scp` quando o assistente puder commitar, mas ele não tem token nem deve ter |
| **Badge da CTO contar entradas, não só grade** | Observação do teste do 5f-1b: "CTO 01 · 0 de 16 documentadas" com E1 e E2 ocupadas. **Pode ser correto de propósito** — decidir antes de mexer |
| **Comentário do elemento com carimbo de autor na própria tela** | Observação do 5f-2a: o Histórico do ativo guarda quem alterou, mas o cartão do mapa não mostra. Só faz sentido depois de responder a pendência 7 |
| **Grade padrão por papel, ou grade escolhida ao criar** | **Achado do teste do 5f-2b (lição 146):** todo elemento novo nasce **4 × 16 = 64**, e encolher exige DELETE. Um técnico com CRIAR e sem DELETE cria uma CTO de 64 posições e não consegue ajustar — o painel passa a dizer "0 de 64" para uma caixa de 8. **Decisão de produto antes de qualquer código:** grade padrão por papel? campo no formulário de criação? deixar como está? |
| **Dizer por que o formulário de criar elemento não aparece** | Observação do 5f-2b: com CRIAR desmarcado o formulário some, sem uma linha explicando. Mesmo padrão que a lição 16 condena. **Cabe no 5g** |

> A numeração de fases do roadmap antigo (do tempo do `mapadgo`) **não**
> corresponde à numeração de blocos atual.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. Quinze ideias avaliadas e recusadas com
motivo — piso em lote, splitter como papel, importação CSV, documentos
versionados no repositório, DELETE para recusar vínculo, `pscp` como veículo de
envio, mudar a assinatura de `canWriteComment`. **Não ressuscitar sem fato novo.**

⚠️ **Uma delas foi ressuscitada com fato novo, e é assim que o mecanismo deve
funcionar:** "anexo pelo técnico" era decisão negativa até a **lição 148** mostrar
que a trava é do formulário do core, não do modelo. Virou o candidato **5i**. O
que a Parte F proíbe é reabrir por esquecimento — não por descoberta.

---

## Próximo passo imediato

1. **Bloco 5g** — o único que falta na frente de permissões, e o primeiro desta
   fase que é sobre **o que a tela diz**, não sobre o que ela deixa fazer.
   **Decidir antes se vai partido** em 5g-1 (JS do auto-save), 5g-2 (textos) e
   5g-3 (nota no perfil).
2. **Higiene**: pendências 7, 8 e 9 — Histórico do ativo, purgar o
   `CTO TESTE 5f2b`, estado do perfil de teste.
3. Depois, na ordem que fizer sentido: **5h-2** (um atributo), **5b**, **5c**,
   **5d**, **5e**, e o candidato **5i**.
