# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v28 — 19/09/2026. Sucede o v27. A mudança que justifica a
> versão nova: **bloco 6c fechado** (1.3.31 / `7e69608` — caixa empilhada,
> editável, Remover função, Alimenta agregado; bloco único por decisão do
> dono). `position` do 6b-1 provado. Próximo: decidir "Agrupar existente".
> Produção segue em 1.3.28. Lição 174.

---

## Parte A — resultado da revisão (histórico, não mexer)

Tabela do v17 mantida integralmente. Frente de permissões da Fase 5 fechada;
os dois greps de guarda:

```bash
grep -rn -- '->can($items_id, READ)' src/ ajax/ front/ | wc -l    # 0
grep -rc 'PassiveDCEquipment::$rightname' src/ | grep -v ':0'     # nada
```

---

## Parte B — Fase 5: ENCERRADA E EM PRODUÇÃO

### Concluído (código — histórico integral no contexto §5)

| Marco | Estado | Versão / referência |
|---|---|---|
| Blocos 5a … PAINEL-2b | Fechados e validados | até 1.3.26 |
| 5h-2 + 5i (aplicação única) | Fechado e validado | 1.3.27, `c74e32a` |
| 5i-2 endpoint | Fechado e validado | 1.3.28, `4923cab` |
| docs v24 | Commitados, paridade md5 | `f30e931` |
| **Tag + Release `v1.3.28`** | **PUBLICADA (05/09)** — tag `e59a338` → `f30e931`; zip 187 KB, sha256 `673bf286…48fe` provado em 4 fontes | GitHub Releases |
| **DEPLOY EM PRODUÇÃO** | **FEITO E VALIDADO (05/09)** — 1.3.1 → 1.3.28 | roteiro 6/6 ✅ |

### ✅ O deploy em números (05/09, 4ª sessão)

| Item | O que se provou | Evidência |
|---|---|---|
| Salto medido | 13 arquivos + 2 novos + `docs/`; `Install.php` idêntico desde 1.3.1; zero DDL fora dele | tarballs v1.3.1 × v1.3.28 + grep |
| Artefato | sha256 do zip idêntico em servidor de produção, PC, GitHub e download do Release | conferido na sessão |
| Linha de base | Painel EXATO antes/depois: 185 elementos (10/73/101/1), 61 sem doc, 2556/5712 (44,7%), 3156 livres, 10 localizações | screenshots |
| Novidades vivas | Pastilhas `45/740` + `695/740` (soma fecha 740); badges no `#187`; relatório filtrando por Localização; 3 anexos reais no `#187` | screenshots |
| Log | Só ruído conhecido; `CacheClearCommand` CRITICAL promovido a conhecido (2 ambientes) | terminal |
| Rollback | Armado (dump 397 KB + tar + pasta `-old`) e NÃO usado | terminal |

### Fechados sem código / cancelados

Tabela do v24 mantida (5g-3 quitado; PAINEL-1a já entregue; 5e-2d-2 e
5i-2 "cadeado" descartados antes de aplicar). **Nada novo.**

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.**

---

## Parte B-2 — Fase 6: CAIXA COMPOSTA (aberta em 19/09)

Modelo: 4 papéis + agrupamento; "composto" é selo (contexto §3-A). Mockup
aprovado: https://claude.ai/artifact/99PzctwaUoCGz55m4Ueohy

| Bloco | Conteúdo | Estado |
|---|---|---|
| **6a** | Tabela `_boxes`, `src/Box.php`, `PurgeCleaner` limpando agrupamento | ✅ **Fechado** — 1.3.29, `82990e6`, roteiro 6/6 (homologação) |
| **6b-1** | Botão `⇄ Adicionar função` + modal → `add_function`; `Box::attach` (ponto único de `_boxes`) e `Box::hostRefusal`; `createElement` (ponto único de criação); herança de localização/piso da caixa; marca `⇄ Função de…` no membro | ✅ **Fechado** — 1.3.30, `dc33df9`. 4 itens não verificados em tela (position, log, mensagem verde, Histórico) — position se prova no 6c |
| **6c** | Página da caixa: selo `⇄ Caixa composta · N funções`, soma dos badges (`Box::statsForBox`), seções por função na ordem de `position`, cada uma editável (Piso, E1–E4, OBS, células, fileira/coluna), "Remover função" (= detach + lixeira nativa), "Alimenta" agregado; URL de função reescrita para a caixa (`fn=`); ids únicos por página; AJAX devolve a soma | ✅ **Fechado** — 1.3.31, `7e69608`, **bloco único por decisão do dono**; roteiro 7/7 + SQL exata; harness 42/42 + jsdom 8/8 |
| **Agrupar existente** | Aba "Agrupar existente" no modal — elemento já cadastrado entra na caixa via `Box::attach` (pronto; falta tela + mockup) | **PRÓXIMO — A DECIDIR** (antes do deploy F6: produção tem as metades cadastradas separadas) |
| Vínculo interno | Selo "vínculo interno" (Alimenta/célula/E quando os dois lados são da mesma caixa) + checkbox do modal → `Link::propose` | Aberto — caso real `#47`/`#48` na caixa `#59` |
| 6d | Marca ⇄ nas abas do grupo do papel (a aba JÁ abre a caixa, pela reescrita do 6c); busca/QR de função abrem a caixa rolada | Aberto (parcial) |
| Deploy F6 | Pela tag, ao fim de 6d. **Primeiro deploy com DDL desde a 1.3.1** — roteiro ganha `SHOW CREATE TABLE` | Aguarda 6d |

### ⚠️ Entregue mas NÃO exercitado

**Nenhum.** (6c exercitado em 7 passos + SQL; do 6b-1 restam só mensagem verde e linha do Histórico como não verificados, e o `position` foi provado no 6c.)

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única viva |
| 20 | Dos 25 vínculos da HOMOLOGAÇÃO, quantos pendentes × confirmados? | Aberta, opcional — SQL no contexto §7 (**banco `glpidb` nos dois ambientes**, lição 170) |
| 21 | Por que a rota `canViewFileFromItem` (READ do ativo) do send.php do core NÃO abriu? | Aberta, opcional — curiosidade de core, sem bloco |

---

## Parte D — dívidas conhecidas

| # | Dívida | Situação |
|---|---|---|
| 2 | Sem catálogo de tradução | Grande |
| 3 | Lista integral de lições (1–113) | Só pelo documento original |
| 7 | Seletor de DESTINO usa formato próprio | Mantida por decisão (5e-4, opção A) |
| **9 (nova, operacional)** | **Limpeza pós-deploy da produção**: `/root/dgoplus-tabelas-pre-1328.sql` · `/root/dgoplus-1.3.1-bak.tar.gz` · `plugins/dgoplus-1.3.1-old` · `/tmp/dgoplus-v1.3.28.zip` | Aguarda janela de estabilização + ordem do dono |

---

## Parte E — estacionamento

Lista do v24 mantida: pendente que envelhece não avisa; medição 5e-3a/b com
dezenas de abas (a produção rodou usável com 19+ abas na Jockey Plaza, mas
medição formal segue estacionada); etc.

---

## Parte F — decisões negativas

Ver seção 8 do `contexto-dgoplus.md`. **Reafirmada em 19/09 (pós-6b-1): cada função no grupo do seu papel.** **Três novas em 19/09:** grade
duplicada no mesmo ativo; quinto papel "COMPOSTO"; #id da caixa nas abas. **Novas
decisões POSITIVAS registradas:** deploy sai da tag/Release, nunca do master
solto; backups do deploy só saem por ordem do dono; **6c em bloco único
(pontual, não vira padrão); "Remover função" = lixeira nativa; página da
função = página da caixa.**

---

## Ambientes — quadro pós-deploy

| | Homologação | Produção |
|---|---|---|
| Versão DGO+ | **1.3.31** (6c) | **1.3.28** (Fase 5) |
| Acesso SSH | `-p 2078` | `-p 2022` (mesmo IP, mesma chave) |
| Implantação | clone git no plugin | **pasta solta, zip da Release** |
| Banco | `glpidb` (lido 19/09) | `glpidb` |
| Dados (retrato 05/09) | 41 elementos, 2165 portas, 25/164 entradas | 185 elementos, 5712 portas, 45/740 entradas |

---

## Próximo passo imediato

1. **Commit dos docs v28** (`docs/` na homologação → sem reinstalação).
2. **Decidir "Agrupar existente"** (mockup antes do código) → vínculo
   interno → 6d → deploy da Fase 6 pela tag (com DDL; plano no contexto §9).
3. Estabilização/limpeza de backups (dívida 9), **REV**, shopmap
   (pendência 16 bloqueada), pendências 20 e 21 — inalterados. Purgar a
   caixa de teste `#59/#60` e o `#61` da lixeira por ordem do dono.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.
