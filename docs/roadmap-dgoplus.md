# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v22 — 05/09/2026. Sucede o v21.
> A mudança que justifica a versão nova: **bloco 5d entregue e validado**
> (confirmação em dois tempos na proposta com pulo de degrau — versão
> **1.3.24**, commit **`b4437e5`**), com o "não muda" provado em tela
> (descida de um degrau grava na primeira, sem aviso) e um fato novo de
> ambiente vivo: o par de teste fixado (`#39 F1.02 → #41 E1`) apareceu
> OCUPADO por vínculo confirmado criado pelos técnicos; o teste rodou com
> célula livre do `#39` → `#42 E1`.

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
| 5a … 5e-3b | Tudo do v17 até as abas roláveis centradas | até 1.3.22 |
| 5e-4 | `#id` em TODO elemento do seletor de destino (opção A) | 1.3.23, `0be3216` |
| docs v21 | Commit dos docs da 3ª sessão de 04/09 | `999401c` |
| **5d** | **Pulo de degrau não grava na primeira: `skipWarning()` (ponto único da frase), `needs_ack` no `propose()`, redirect com `skip_dst`/`skip_slot`, formulário preservado com aviso e "Confirmar mesmo assim" (opção A)** | **1.3.24, `b4437e5`** ✅ validado em tela |

### ✅ Validado em tela nesta sessão (05/09)

| Item | O que se provou | Evidência |
|---|---|---|
| **5d — primeiro tempo** | Proposta DIO→CTO NÃO grava: volta na mesma célula com pílula "pula degrau", frase `DIO alimenta CTO sem passar por DGO`, destino/entrada pré-selecionados, botão "Confirmar mesmo assim" | Validação do usuário (que inclusive só viu o aviso na segunda olhada — o aviso aparece) |
| **5d — nada gravou** | E1 do destino continuou livre entre os dois tempos | Tela do destino |
| **5d — segundo tempo** | "Confirmar mesmo assim" grava pendente, ocupando as duas pontas | "vinculo montado corretamente" |
| **5d — não muda** | `#39 → #33` (DIO→DGO, um degrau) gravou NA PRIMEIRA, sem aviso | Confirmação do usuário |
| **Paridade** | `Link.php`, `MapController.php`, `setup.php` do commit publicado = entregues, md5 a md5 | Tarball do `b4437e5` na sessão |
| **Limpeza** | Vínculos de teste desmontados; log sem erro do plugin | Tail do php-errors.log |

### Fatos novos colhidos de graça (05/09)

- ⚠️ **`#39 F1.02 → #41 E1` está OCUPADO por vínculo CONFIRMADO** — criado e
  confirmado pelos técnicos após 04/09. Preservado (possível exercício real).
  Consequência: o "par de teste fixado" do 5d deixou de existir; par DIO→CTO
  livre se escolhe em tela na hora.
- **Ruído de log candidato:** `glpi.CRITICAL` do `CacheClearCommand` no
  `cache:clear` (backtrace só Symfony; comando termina com sucesso). Observar
  antes de promover a conhecido.

### ☠️ Cancelado sem código

| Bloco | Motivo |
|---|---|
| 5e-2d-2 | Preparado e descartado ANTES de aplicar (03/09) |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.**

---

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única pendência viva |

Pendências 1–15, 17–19: respondidas, mantidas nos docs anteriores como
histórico.

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | **Sem catálogo de tradução.** Decisão de produto antes | Grande |
| 3 | **Lista integral de lições (1–113)** | Só pelo documento original |
| 7 | **Seletor de DESTINO usa formato próprio** (`nome (PAPEL) #id`) | Mantida por decisão (opção A do 5e-4). Reabrir é do usuário |

---

## Parte E — estacionamento

Lista do v17 mantida (endpoint AJAX de vínculo; porta ↔ chamado; notificações;
widgets; colunas novas no relatório; carimbo de autor no comentário; pendente
que envelhece; PAINEL-1b; elemento fora dos papéis; aviso na purga com vínculo
vivo; uniformizar o seletor de destino com o `ItemLabel` [= dívida 7];
`normalizeName()` na frente shopmap; sombra/indicador de "há mais abas";
efeito do `completename` de produção nos rótulos; medir 5e-3a/b com
localização de dezenas de abas). **Entrada nova (05/09):** se algum dia o
pulo de degrau tiver que ser PROIBIDO (não só ciência), é decisão de produto
nova — registrada como esclarecimento na seção 8 do contexto.

---

## Parte F — decisões negativas

Ver a seção 8 do `contexto-dgoplus.md`. **Esclarecimento novo de 05/09:**
o 5d é ciência, não bloqueio — o vínculo direto DIO→CTO continua permitido
(perguntado e respondido em tela). Todas as demais permanecem.

---

## O que muda com a produção crescendo

| Bloco | Comportamento com escala |
|---|---|
| 5e-3a/5e-3b | ✅ É a resposta à escala. ⚠️ Não medido com dezenas de abas |
| 5e-4 | Neutro-positivo: id sempre visível independe da quantidade |
| **5d** | ✅ **Entregue.** Neutro com escala — correção de regra; pulo vira dois cliques em qualquer volume |
| 5h-2 | **Melhora com escala** — 427 localizações na produção, maioria alheia ao DGO+ |
| BADGE-C, contador de entradas | Neutros. Qualidade de leitura |
| Deploy em produção | Piora com o tempo — 4 documentadores reais mudam de permissão |

---

## Próximo passo imediato

1. **Commit dos docs v22** (`docs/` → sem reinstalação). Entra por cima do
   v21; código segue no `b4437e5`.
2. **Bloco BADGE-C** — contadores de grade e entradas separados. "Antes" já
   provado 3× (04/09). ⚠️ Reler o cenário de dados em tela: a E1 do `#41`
   agora está ocupada por vínculo confirmado — candidata natural a caso de
   teste do contador de entradas.
3. **5h-2** (remover `nosearch` da Localização no relatório), **5g-3** (nota
   de permissões de anexo na aba de perfil), **5i** (anexo por formulário
   próprio), **PAINEL-1** ("Ver todos" nos cartões), e o **bloco de deploy em
   produção** (com rollback — começa por reler a produção em tela).
4. **Frente shopmap** — bloqueada pela pendência 16.
5. **REV** — revisão competitiva, ao fim de tudo.

> A numeração de fases do roadmap antigo (`mapadgo`) **não** corresponde à
> numeração de blocos atual.
