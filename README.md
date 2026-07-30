# DGO+

Plugin do **GLPI 11** para documentar o mapa de portas de DGOs e CTOs
(distribuidores ópticos e caixas de terminação).

Cada DGO é um ativo nativo **Dispositivo passivo** do GLPI. O plugin acrescenta
a ele uma grade de portas — tubo × fibra, nas cores da sequência ABNT/EIA — onde
se registra o que está conectado em cada posição.

---

## Recursos

- **Grade de portas** sobre o ativo nativo, com as cores de fibra ABNT/EIA
- **Três estados por porta**: documentada, sem acoplador e livre
- **Escopo Localização → Piso**, o piso sendo um intitulado próprio do plugin
- **Salvamento automático** ao sair do campo, com retorno para POST clássico se
  o JavaScript não carregar
- **Anexos por porta**, pelo Documento nativo do GLPI
- **Relatório de portas** com a busca nativa, exportável como qualquer listagem
- **Busca global** por número de loja, nome ou observação, em todas as DGOs
- **Painel** com ocupação por localização e as DGOs mais cheias
- **Configuração de quais Tipos** de dispositivo passivo representam uma DGO
- **Atalho "Abrir no mapa DGO+"** na ficha do ativo
- **DGO habilitável na Análise de impacto** do GLPI
- **Direito próprio** (`plugin_dgoplus_port`), matriz de 4 níveis, sem PURGE

## Requisitos

| | |
|---|---|
| GLPI | 11.0.0 a 11.9.99 — validado contra o 11.0.6 |
| PHP | 8.2 ou superior |
| Banco | MySQL 8 / MariaDB 10.6+ |

## Instalação

Baixe o `dgoplus-v1.0.0.zip` da
[última release](../../releases/latest) — **não** o "Source code" gerado
automaticamente pelo GitHub, porque o GLPI exige que a pasta do plugin se chame
`dgoplus`.

```bash
cd /var/www/html/glpi/plugins
unzip -o dgoplus-v1.0.0.zip
chown -R www-data:www-data dgoplus
sudo -u www-data php ../bin/console cache:clear
sudo -u www-data php ../bin/console plugin:install dgoplus
sudo -u www-data php ../bin/console plugin:activate dgoplus
```

Ou direto pela linha de comando:

```bash
cd /var/www/html/glpi/plugins
curl -L -O https://github.com/teckcomp/glpi-plugin-dgoplus/releases/latest/download/dgoplus-v1.0.0.zip
unzip -o dgoplus-v1.0.0.zip
chown -R www-data:www-data dgoplus
```

> **O `plugin:activate` não é opcional.** O `plugin:install` responde
> *"já pode ser ativado"*, não *"ativado"*.

Conferência:

```bash
mysql <banco> -e "SELECT directory, version, state FROM glpi_plugins WHERE directory='dgoplus';"
```

`state = 1` é ativado. `2` é não instalado e `4` é não ativado.

## Configuração após instalar

**1. Criar o Tipo de dispositivo passivo** que representa uma DGO, em
Configurar → Listas suspensas → Tipo de dispositivo passivo.

**2. Marcar esse Tipo** em Configurar → Plugins → DGO+ → Configurar.

> Lista de Tipos **vazia significa filtro desligado**: todo dispositivo passivo
> passa a ser tratado como DGO, inclusive patch panels e calhas.

**3. Conceder os direitos**, em Administração → Perfis. Por instalação nova, o
plugin concede `plugin_dgoplus_port` apenas a perfis que já têm `config`
READ|UPDATE — o que muitas vezes é só o Super-Admin.

| Direito | Onde fica | Para quê |
|---|---|---|
| `plugin_dgoplus_port` | aba **DGO+** | o mapa de portas |
| `datacenter` | Gestão → **Data centers** | o ativo da DGO |
| `document` | Gestão → **Documento** | anexos |
| `logs` | Administração → **Histórico** | aba Histórico da ficha |

> **Quem grava porta precisa dos dois primeiros.** O direito do plugin governa o
> mapa; o `datacenter` governa o ativo. Com `plugin_dgoplus_port` sozinho, a
> pessoa abre a tela e digita, mas o salvamento é recusado.
>
> O `datacenter` é compartilhado com Rack, Enclosure e PDU — não há como
> restringi-lo só a dispositivos passivos. Conceda **15**, nunca 31: o 31 é
> `ALLSTANDARDRIGHT` e inclui PURGE.

**4. Sair e entrar no GLPI.** Direito só entra na sessão no login; recarregar a
página não basta.

**5. Ativos → DGO+** abre o mapa.

## Avisos

**Desativar não perde nada. Desinstalar perde tudo.**

Desativar tira o plugin do menu e mantém os dados. O `plugin:uninstall` executa
`Install::uninstall()`, que remove as três tabelas do plugin e a configuração —
sem confirmação e sem lixeira. Com dado dentro, faça o dump antes:

```bash
mysqldump <banco> glpi_plugin_dgoplus_ports glpi_plugin_dgoplus_panels \
  glpi_plugin_dgoplus_floors > dgoplus-portas.sql
```

**Purgar o ativo de uma DGO não apaga as portas dela** — as linhas ficam órfãs
no banco, apontando para um ativo que não existe mais. Correção prevista para a
próxima versão.

## Estrutura

```
dgoplus/
├── setup.php              inicialização, hooks e o botão da ficha do ativo
├── hook.php               instalação / desinstalação
├── ajax/port.php          endpoint do salvamento automático
├── public/                JS e a marca em SVG
├── front/                 mapa, relatório e tela de configuração
└── src/
    ├── Install.php        schema e direitos
    ├── Setting.php        quais Tipos são DGO
    ├── Port.php           a porta documentada e a regra única de gravação
    ├── Panel.php          dimensões da grade e vínculo com o piso
    ├── Floor.php          o piso (intitulado do plugin)
    ├── MapController.php  a tela do mapa
    └── Dashboard.php      o painel
```

Três tabelas: `glpi_plugin_dgoplus_ports`, `_panels` e `_floors`. A configuração
vive em `glpi_configs`, contexto `plugin:dgoplus`.

## Idioma

Interface em **pt-BR**. Não há catálogo de tradução nesta versão.

## Licença

GPL-3.0 ou posterior.

## Autor

[Teckcomp I.T. Services](https://www.teckcomp.com.br)
