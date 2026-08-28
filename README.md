# DGO+

Plugin do **GLPI 11** para mapear portas ópticas e a topologia de vínculos entre
DGOs, CTOs e PTOs.

Cada elemento é um ativo nativo **Dispositivo passivo** (`PassiveDCEquipment`) do
GLPI. O plugin acrescenta a ele uma grade de portas — tubo × fibra, nas cores da
sequência ABNT/EIA —, o escopo de Localização e Piso, e os vínculos que ligam um
elemento ao próximo.

---

## Recursos

- **Grade de portas** sobre o ativo nativo, com as cores de fibra ABNT/EIA
- **Três estados por porta**: documentada, sem acoplador e livre
- **Entradas E1–E4** por elemento, separadas da grade e fora da conta de ocupação
- **Vínculos entre elementos**, com proposta, confirmação, recusa e desmontagem
- **Hierarquia de papéis** DIO → DGO → CTO → PTO: o vínculo desce degrau, nunca sobe
- **Trilha de alimentação**: de onde vem o sinal que chega a um elemento
- **Escopo Localização → Piso**, o piso sendo um intitulado próprio do plugin
- **Salvamento automático** ao sair do campo, com retorno para POST clássico se
  o JavaScript não carregar
- **Anexos por porta e por elemento**, pelo Documento nativo do GLPI
- **Relatório de portas** com a busca nativa, exportável como qualquer listagem
- **Busca global** por número de loja, nome ou observação, em todos os elementos
- **Painel** com ocupação por localização, elementos mais cheios, vínculos
  pendentes e atividade recente
- **QR de identidade** do elemento, para impressão
- **Configuração de quais Tipos** de dispositivo passivo representam cada papel
- **Atalho "Abrir no mapa DGO+"** na ficha do ativo
- **Elemento habilitável na Análise de impacto** do GLPI
- **Direito próprio** (`plugin_dgoplus_port`), matriz de 4 níveis, sem PURGE

## Requisitos

| | |
|---|---|
| GLPI | 11.0.0 a 11.9.99 — validado contra o 11.0.6 |
| PHP | 8.2 ou superior |
| Banco | MySQL 8 / MariaDB 10.6+ |

## Instalação

Baixe o zip da **[última release](../../releases/latest)** — **não** o
"Source code" gerado automaticamente pelo GitHub, porque o GLPI exige que a
pasta do plugin se chame `dgoplus`.

```bash
cd /var/www/html/glpi/plugins
unzip -o dgoplus-<versão>.zip
chown -R www-data:www-data dgoplus
sudo -u www-data php ../bin/console cache:clear
sudo -u www-data php ../bin/console plugin:install dgoplus
sudo -u www-data php ../bin/console plugin:activate dgoplus
```

> **O `plugin:activate` não é opcional.** O `plugin:install` responde
> *"já pode ser ativado"*, não *"ativado"*.

Conferência:

```bash
mysql <banco> -e "SELECT directory, version, state FROM glpi_plugins WHERE directory='dgoplus';"
```

`state = 1` é ativado. `2` é não instalado e `4` é não ativado.

**Ao atualizar de uma versão anterior**, o GLPI desativa o plugin sozinho ao
detectar a versão nova. Rode `plugin:install --force dgoplus` e depois
`plugin:activate dgoplus`.

## Configuração após instalar

**1. Criar os Tipos de dispositivo passivo** que representam cada papel, em
Configurar → Listas suspensas → Tipo de dispositivo passivo.

**2. Associar cada Tipo ao seu papel** em Configurar → Plugins → DGO+ →
Configurar.

> Elemento com Tipo não associado fica **fora da hierarquia**: aparece no mapa,
> mas não participa de vínculo.

**3. Conceder o direito**, em Administração → Perfis → aba **DGO+**. A própria
aba explica o que cada nível concede. Por instalação nova, o plugin concede
`plugin_dgoplus_port` apenas a perfis que já têm `config` READ|UPDATE — o que
muitas vezes é só o Super-Admin.

O direito do plugin **basta** para ver o mapa, documentar portas, criar
elementos e trabalhar com vínculos. Anexos são a exceção: usam o formulário
nativo do GLPI e exigem `document` (Ler + Atualizar + Criar) e `datacenter`
(Atualizar), ambos na aba Gerência do perfil.

**4. Sair e entrar no GLPI.** Direito só entra na sessão no login; recarregar a
página não basta.

**5. Ativos → DGO+** abre o mapa.

## Avisos

**Desativar não perde nada. Desinstalar perde tudo.**

Desativar tira o plugin do menu e mantém os dados. O `plugin:uninstall` executa
`Install::uninstall()`, que remove as quatro tabelas do plugin e a configuração
— sem confirmação e sem lixeira. Com dado dentro, faça o dump antes:

```bash
mysqldump <banco> glpi_plugin_dgoplus_ports glpi_plugin_dgoplus_panels \
  glpi_plugin_dgoplus_floors glpi_plugin_dgoplus_links > dgoplus-portas.sql
```

**Purgar o ativo de um elemento leva junto as portas e os vínculos dele.** O
plugin escuta a purga do ativo e limpa as suas próprias tabelas, inclusive na
purga forçada.

## Estrutura

```
dgoplus/
├── setup.php              inicialização, hooks e o botão da ficha do ativo
├── hook.php               instalação / desinstalação
├── docs/                  contexto e roadmap do projeto
├── ajax/                  endpoints do salvamento automático
├── public/                JS, biblioteca de QR e a marca em SVG
├── front/                 mapa, relatório, pendências e configuração
└── src/
    ├── Install.php        schema, direitos e migrações
    ├── Setting.php        quais Tipos são de cada papel
    ├── Port.php           a porta e o ponto único de gravação
    ├── Link.php           o vínculo e o ponto único de criação
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (intitulado do plugin)
    ├── MapController.php  a tela do mapa
    ├── Dashboard.php      o painel
    ├── Pending.php        a fila de vínculos pendentes
    ├── DgoIdentity.php    identidade, QR e comentário do elemento
    ├── PurgeCleaner.php   limpeza na purga do ativo
    ├── ProfileTab.php     aba de direitos no Perfil
    └── MapPage.php        entrada de menu
```

Quatro tabelas: `glpi_plugin_dgoplus_ports`, `_panels`, `_floors` e `_links`. A
configuração vive em `glpi_configs`, contexto `plugin:dgoplus`.

## Documentação

O estado do projeto, as decisões tomadas e as recusadas, e o roadmap vivem em
[`docs/`](docs/):

- [`docs/contexto-dgoplus.md`](docs/contexto-dgoplus.md) — arquitetura, ambiente,
  permissões e lições aprendidas
- [`docs/roadmap-dgoplus.md`](docs/roadmap-dgoplus.md) — o que está fechado, o
  que está aberto e o que foi descartado

## Idioma

Interface em **pt-BR**. Não há catálogo de tradução nesta versão.

## Licença

GPL-3.0 ou posterior.

## Autor

[Teckcomp I.T. Services](https://www.teckcomp.com.br)
