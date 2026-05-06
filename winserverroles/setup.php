<?php
/**
 * Plugin: winserverroles
 * Displays Windows Server roles/features per computer, collected by GLPI Agent.
 *
 * Data flow:
 *   1. GLPI Agent runs the WinServerRoles Perl module on each Windows Server.
 *   2. Module emits each installed role as a SOFTWARES inventory entry with
 *      name prefixed "[WinServerRole]" and the structured payload in COMMENTS.
 *   3. Standard GLPI inventory imports these as software entries (no custom
 *      section, no pre_inventory hook — both are unreliable in GLPI 11).
 *   4. A CLI scheduled task (PluginWinserverrolesRole::cronSyncRoles) runs
 *      hourly, scans glpi_softwares for the prefix, and syncs to the plugin's
 *      roles table.  CLI context guarantees plugin classes are loaded.
 */

define('PLUGIN_WINSERVERROLES_VERSION', '1.0.0');
define('PLUGIN_WINSERVERROLES_MIN_GLPI', '11.0.0');
define('PLUGIN_WINSERVERROLES_MAX_GLPI', '12.0.0');

// Autoload all plugin classes from inc/
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'PluginWinserverroles') !== 0) {
        return;
    }
    $base = strtolower(str_replace('PluginWinserverroles', '', $class));
    $file = __DIR__ . "/inc/{$base}.class.php";
    if (file_exists($file)) {
        require_once $file;
    }
});

include_once __DIR__ . '/hook.php';

// ── Hooks ─────────────────────────────────────────────────────────────────────

function plugin_init_winserverroles(): void {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['winserverroles'] = true;

    // Tab on Computer items.
    Plugin::registerClass('PluginWinserverrolesRole', ['addtabon' => ['Computer']]);

    // CronTask::register hooks (scheduled task runs in CLI context where
    // plugin classes are autoloaded).
    $PLUGIN_HOOKS['cron']['winserverroles'] = [
        'syncRoles' => 3600,
    ];
}

function plugin_version_winserverroles(): array {
    return [
        'name'         => 'Windows Server Roles and Features',
        'version'      => PLUGIN_WINSERVERROLES_VERSION,
        'author'       => 'Christian Bernard',
        'license'      => 'GPLv2+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_WINSERVERROLES_MIN_GLPI,
                'max' => PLUGIN_WINSERVERROLES_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_winserverroles_check_prerequisites(): bool {
    if (version_compare(GLPI_VERSION, PLUGIN_WINSERVERROLES_MIN_GLPI, 'lt') ||
        version_compare(GLPI_VERSION, PLUGIN_WINSERVERROLES_MAX_GLPI, 'ge')) {
        echo sprintf(
            __('This plugin requires GLPI >= %s and < %s', 'winserverroles'),
            PLUGIN_WINSERVERROLES_MIN_GLPI,
            PLUGIN_WINSERVERROLES_MAX_GLPI
        );
        return false;
    }
    return true;
}

function plugin_winserverroles_check_config(): bool {
    return true;
}
