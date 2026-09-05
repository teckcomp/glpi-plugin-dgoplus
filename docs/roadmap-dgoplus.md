# DGO+ — roadmap

> Companheiro do `contexto-dgoplus.md`. **Substituir**, nunca acumular.
>
> **Versão:** v25 — 05/09/2026 (4ª sessão do dia — release + deploy).
> Sucede o v24. A mudança que justifica a versão nova: **a Fase 5 foi
> RELEASED e DEPLOYADA em produção.** Tag `v1.3.28` + Release publicadas;
> produção migrada de 1.3.1 para 1.3.28 com roteiro de 6 passos aprovado
> integral e rollback não usado. Lição 169 registrada.

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

## Parte C — pendências de investigação

| # | Pergunta | Situação |
|---|---|---|
| 16 | shopmap guarda vínculo por NOME ou `itemtype`+`id`? | ⚠️ **Bloqueada** — repositório privado. Única viva |
| 20 | Dos 25 vínculos da HOMOLOGAÇÃO, quantos pendentes × confirmados? | Aberta, opcional — SQL no contexto §7 (banco `glpi`; produção é `glpidb`) |
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

Ver seção 8 do `contexto-dgoplus.md`. Nenhuma nova na 4ª sessão. **Novas
decisões POSITIVAS registradas:** deploy sai da tag/Release, nunca do master
solto; backups do deploy só saem por ordem do dono.

---

## Ambientes — quadro pós-deploy

| | Homologação | Produção |
|---|---|---|
| Versão DGO+ | 1.3.28 | **1.3.28** ✅ |
| Acesso SSH | `-p 2078` | `-p 2022` (mesmo IP, mesma chave) |
| Implantação | clone git no plugin | **pasta solta, zip da Release** |
| Banco | `glpi` | `glpidb` |
| Dados (retrato 05/09) | 41 elementos, 2165 portas, 25/164 entradas | 185 elementos, 5712 portas, 45/740 entradas |

---

## Próximo passo imediato

1. **Commit dos docs v25** (`docs/` na homologação → sem reinstalação).
2. **Janela de estabilização** — uso real pelos técnicos; depois, decisão do
   dono sobre a limpeza dos backups (dívida 9).
3. **REV — revisão competitiva**: PRÓXIMA FRENTE DE TRABALHO (liberada pelo
   dono para pós-validação). Avaliar softwares similares de documentação de
   planta óptica passiva e listar recursos candidatos a adaptação.
4. **Frente shopmap** — bloqueada (pendência 16). Pendências 20 e 21 —
   opcionais.

> A numeração de fases do roadmap antigo não corresponde à numeração de blocos.
