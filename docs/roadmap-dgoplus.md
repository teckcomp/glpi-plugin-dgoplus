# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v10 — 27/08/2026 (sessão da noite, quarta parte). Sucede o v9.
> A mudança que justifica a versão nova: **o 5f acabou**. Com o 5f-3a e o 5f-3b,
> o técnico trabalha no mapa inteiro **sem nenhum direito em Data centers**, e o
> menu "Dispositivos passivos" sumiu — o objetivo que abriu esta frente em
> 23/08. Sobra o **5g**, que é texto de tela.
> Números verificados em `0005c90`.

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

**PAGER · `core.pager cat`** — aplicado (27/08).

---

### Prioridade 1 — permissões (a dor operacional)

A ordem importa: o **5g só depois dos outros**, senão documenta a regra antiga e
vira mentira na tela.

**Sobrou o 5g, e só ele.** Todo o resto desta frente está em produção de teste
desde a 1.3.8.

⚠️ Números de linha citados abaixo valem para `0005c90` — **reconferir por
`grep -n` antes de escrever** (lição 144).

**5g · Nota explicativa na aba DGO+ do perfil — e as mensagens de erro**

Quadro abaixo da matriz, no `ProfileTab`, dizendo o que cada direito cobre e o
que depende de permissão fora do DGO+ (Documentos **+ Data centers UPDATE** para
anexo, Localização, excluir o ativo, configurar papéis). Nasce da lição 119.

⚠️ **Escopo, com três frentes:**

1. **O 403 do auto-save.** Lição 133, confirmada com prova: o `ajax/port.php`
   responde 403 e o usuário lê "Falha ao salvar. Use o botão Salvar" —
   indistinguível de erro de rede. O `dgoplus.js` precisa tratar o 403
   separadamente e nomear o direito faltante.
2. **A insistência.** Sete 403 seguidos para uma ação só. Depois do primeiro 403
   naquela célula, **parar de reenviar**.
3. **Texto que fala de ação indisponível.** O painel diz "Desmontar remove o
   vínculo dos dois lados" mesmo quando o botão não existe por falta de DELETE.
4. **Formulário de criar elemento some sem dizer por quê** (achado do 5f-2b).
5. **Botões "Nova fileira"/"Nova coluna" de remoção** somem por falta de DELETE,
   com o mesmo silêncio.

*As mensagens do 5f-1b e do 5f-2a já nasceram no padrão que o 5g generaliza:
"… exige a permissão «Atualizar» em «Portas de DGO» (Administração → Perfis →
aba DGO+)". Serve de modelo.*

⚠️ **Candidato a divisão:** as frentes 1 e 2 são JS (`dgoplus.js`), as 3 a 5 são
texto em PHP, e a nota do perfil é `ProfileTab`. Três áreas independentes —
avaliar `5g-1` (JS do auto-save), `5g-2` (textos das telas) e `5g-3` (nota no
perfil).

---

### Prioridade 2 — refinamento

**5h-2 · Habilitar o filtro por Localização no relatório**

Remover `'nosearch' => true` da opção 8. Um atributo, um teste (filtrar por uma
localização e conferir a contagem). ⚠️ Vale checar o comportamento com
`forcegroupby => true`, que joga o critério para `HAVING`.

**5b · Piso lista só os pisos com candidato**

`refreshFloors()` (`public/dgoplus.js`) passa a cruzar cada piso com os
candidatos, como `refreshDst()` já faz. Só JS; Ctrl+F5.

**5c · Trilha da entrada, não do elemento**

`Link::upstreamLevels()` é chamado com o elemento, então o card da "Entrada E2"
mostra a cadeia que chega pela E1. **Consumidor único.**
⚠️ *A linha do consumidor no `MapController` foi deslocada três vezes desde o
`bd28ffd`. Localizar por `grep -n upstreamLevels`, não por número.*

**5d · Aceite no servidor para salto de degrau**

`propose()` recusa o salto na primeira tentativa e devolve o motivo nomeando o
degrau pulado; a tela reexibe com aceite explícito. A distância está a uma
subtração: `$order[$dst] - $order[$src] > 1`.

Mexe no **ponto único de criação** — bloco mais delicado da fase.

**5e · Desambiguar nomes repetidos na lista de destino** ⚠️ *depende de confirmação*

Aparecem dois "PTO 001 (PTO)" na lista. Se forem ativos distintos de mesmo nome,
escolher o errado cria topologia errada que nenhuma validação pega.
**Antes de executar: confirmar se são ativos diferentes ou o mesmo duplicado.**

---

**5i · Anexo pelo técnico, por formulário próprio** ⚠️ *candidato, sem escopo medido*

Nasceu da **lição 148**: o formulário de anexo é do core e pergunta pelo
`datacenter` UPDATE (`CommonDBRelation.php:659`), mas `CommonDBTM::add()`
(`CommonDBTM.php:1286`) **não checa direito nenhum**. Um endpoint do plugin pode
criar `Document` + `Document_Item` com a checagem do DGO+ — mesmo desenho de
porta, vínculo e comentário. O perfil de teste já tem o direito `document`.

**Antes de dimensionar:** ler o mecanismo de upload do GLPI (`Document::isValidDoc`,
`files/_uploads`, `_filename` / `_prefix_filename`) e decidir se excluir anexo
entra. Enquanto isso não for lido, qualquer estimativa é chute.

### Prioridade 3 — higiene

**REL-2 · Tag `v1.3.8` + Release** — três comandos no `ssh`. **A 1.3.8 É o marco**: é a versão em que o
técnico trabalha sem direito em Data centers. As versões 1.3.3 a 1.3.7 ficam sem Release, e tudo
bem: a Release é artefato de instalação, não registro de histórico (isso é o Git).

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
| 4 | **Sem tag/Release da 1.3.3 à 1.3.8** | Bloco REL-2 |
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
motivo — piso em lote, splitter como papel, importação CSV, anexo pelo técnico,
documentos versionados no repositório, DELETE para recusar vínculo, `pscp` como
veículo de envio, mudar a assinatura de `canWriteComment`. **Não ressuscitar sem
fato novo.**

---

## Próximo passo imediato

1. **Bloco 5g** — o único que falta na frente de permissões, e o primeiro desta
   fase que é sobre **o que a tela diz**, não sobre o que ela deixa fazer.
   **Decidir antes se vai partido** em 5g-1 (JS do auto-save), 5g-2 (textos) e
   5g-3 (nota no perfil).
2. **REL-2** — tag `v1.3.8` + Release. A Fase 5 tem um marco agora.
3. **Higiene**: pendências 7, 8 e 9 — Histórico do ativo, purgar o
   `CTO TESTE 5f2b`, estado do perfil de teste.
4. Depois, na ordem que fizer sentido: **5h-2** (um atributo), **5b**, **5c**,
   **5d**, **5e**, e o candidato **5i**.
