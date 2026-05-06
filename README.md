# Windows-Roles-and-Features
Adds a Windows Server Roles tab on each Computer item in GLPI.

Roles and features are collected automatically by a GLPI Agent Perl module running on Windows servers and pushed to GLPI via an authenticated HTTP endpoint.

Features:
- Dedicated tab on Computer items listing all installed roles and features
- Push endpoint secured by a configurable token
- Token management and agent installation instructions in the plugin config page
- Compatible with standard GLPI Agent inventory cycles
<img width="1643" height="323" alt="image" src="https://github.com/user-attachments/assets/761430f3-a1dc-438e-92dc-b0428d08c335" />
