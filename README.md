# Windows Roles and Features

Adds a **Windows Server Roles** tab on each Computer item in GLPI 11, listing the Windows roles and features installed on Windows Server hosts.
![Windows Roles and Features](![Windows Roles and Features](roles-and-features-tab.png)

## How it works

1. A small Perl module (`agent/GLPI/Agent/Task/Inventory/Win32/WinServerRoles.pm`) is deployed alongside the GLPI Agent on each Windows Server.
2. During every standard inventory cycle the module runs `Get-WindowsFeature` and emits each **installed** role as a regular `SOFTWARES` entry, with the name prefixed `[WinServerRole]` and a structured payload pipe-encoded in the `COMMENTS` field:
   ```
   NAME|DISPLAYNAME|INSTALLED|SUBFEATURES
   ```
3. Standard GLPI inventory imports these as software entries into `glpi_softwares` and links them to the computer via `glpi_items_softwareversions`.
4. The plugin's Computer tab queries that linkage for entries with the `[WinServerRole]` prefix and renders them as a table with Name, Display Name, Installed, Sub-features and Last update columns.

There is **no custom inventory section, no custom HTTP endpoint, and no shared secret** — everything travels over the GLPI Agent's existing authenticated inventory channel.

## Requirements

- GLPI 11.0.6 or later
- GLPI Agent on each Windows Server
- Windows Server 2012 R2 or later (the agent module requires the `ServerManager` PowerShell module — not available on Desktop SKUs)

## Installation

### Plugin (GLPI server)

1. Download the release ZIP and extract `winserverroles/` into `/usr/share/glpi/plugins/`.
2. In GLPI, go to **Setup → Plugins**, install and enable **Windows Server Roles**.

### Agent module (each Windows Server)

Copy `agent/GLPI/Agent/Task/Inventory/Win32/WinServerRoles.pm` from the release ZIP to:

```
C:\Program Files\GLPI-Agent\perl\agent\GLPI\Agent\Task\Inventory\Win32\WinServerRoles.pm
```

The next agent inventory cycle will start emitting the role entries; they appear on the computer's **Windows Server Roles** tab and in the standard **Software** tab as `[WinServerRole] *` entries.

## License

GPL-3.0-or-later
