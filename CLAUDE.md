# CLAUDE.md — Windows Server Roles and Features (GLPI 11)

## Shared conventions

Conventions for all of my GLPI 11 plugins live in [`../GLPI-Shared/`](../GLPI-Shared/CLAUDE.md). **Read those rules first** for any task: versioning, namespacing, hooks, DB API, validation workflow, migrations, AJAX endpoints, build/release. This file only covers what's specific to *this* project.

## Project scope

Displays the **Windows Server roles/features installed on each computer** as a tab on the Computer form, collected by the standard GLPI Agent inventory. Like NetstatConnections, the repo has **two components**:

1. **Plugin GLPI 11** (`winserverroles/`, PHP) — a Computer tab that reads **live from `glpi_softwares`** (joined to `glpi_items_softwareversions`), filtered on the `[WinServerRole]` name prefix. **No plugin tables, no custom endpoint, no secrets, no cron.** The GLPI-Shared PHP rules apply here.
2. **GLPI Agent inventory module** (`agent/perl/.../WinServerRoles.pm`, Perl) — runs `Get-WindowsFeature` via PowerShell (ServerManager module) and emits each **installed** role as a SOFTWARES inventory entry: name `[WinServerRole] <DisplayName>`, publisher `Microsoft Windows Server`, pipe-delimited payload in COMMENTS (`NAME|DISPLAYNAME|INSTALLED|SUBFEATURES`). **Perl, outside GLPI** — GLPI-Shared PHP rules don't apply; only: read before modify, minimal/reversible changes, never trust raw input.

Design rationale (from `setup.php` header): custom inventory sections and the `pre_inventory` hook are unreliable in GLPI 11, so the data rides the standard SOFTWARES pipeline instead.

## Architecture

```
Windows Roles and Features/
├── winserverroles/                  GLPI 11 plugin (PHP)
│   ├── setup.php                    version, tab registration on Computer
│   ├── hook.php                     install/uninstall — no tables (drops pre-1.0 legacy table);
│   │                                registers profile right `plugin_winserverroles`
│   ├── inc/role.class.php           PluginWinserverrolesRole — the Computer tab
│   │                                (getTable() = glpi_softwares, payload parser, search options)
│   ├── front/role.php               list view of roles across computers
│   ├── locales/                     en_US, fr_FR
│   └── winserverroles.xml           catalog manifest
├── agent/perl/agent/GLPI/Agent/Task/Inventory/Win32/
│   └── WinServerRoles.pm            agent module — deploy to
│                                    C:\Program Files\GLPI-Agent\perl\agent\GLPI\Agent\Task\Inventory\Win32\
├── plugin.xml                       repo-level manifest
└── README.md
```

## Points specific to this project

- **The plugin PHP lives in the `winserverroles/` subfolder**, not the repo root — paths in CI and build scripts must account for that.
- **Legacy class style, kept on purpose:** classes use the `PluginWinserverroles*` prefix + `inc/*.class.php` autoload, not the `GlpiPlugin\` namespace. Don't refactor to namespaces without an explicit request — `Plugin::registerClass` and the autoloader depend on the current names.
- **The COMMENTS payload is the contract** between the Perl module and the tab: `NAME|DISPLAYNAME|INSTALLED|SUBFEATURES`, pipe-delimited. Changing either side means changing both, and old inventory rows keep the old shape — the tab already has fallbacks for stripped/absent COMMENTS; preserve them.
- **Agent module constraints:** PowerShell is invoked with `-EncodedCommand` (UTF-16LE Base64) to avoid quoting bugs; `Import-Module ServerManager` fails on desktop SKUs (module exits cleanly); only `INSTALLED` roles are emitted. Requires Windows Server 2012 R2+.
- **No custom endpoint, no shared secret** — data flows through the agent's normal authenticated inventory channel. Keep it that way; it's the plugin's main selling point.
- **No local PHP or Perl** on the dev machine; `php -l` / `perl -c` run on the GLPI server / CI. Use PowerShell (the Bash tool fails here).

## Global rules (reminder)

The non-negotiables from [`../GLPI-Shared/CLAUDE.md`](../GLPI-Shared/CLAUDE.md) apply: GLPI 11 first, read before modify, minimal/reversible changes, preserve behavior, reuse GLPI mechanisms, never trust raw input. 95% validation workflow: [`../GLPI-Shared/rules/glpi-validation.md`](../GLPI-Shared/rules/glpi-validation.md).
