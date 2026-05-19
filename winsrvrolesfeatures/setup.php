<?php
/**
 * Plugin: winsrvrolesfeatures
 * Displays Windows Server roles/features per computer, collected by GLPI Agent.
 *
 * Data flow:
 *   1. GLPI Agent runs the WinServerRoles Perl module on each Windows Server.
 *   2. Module emits each installed role as a SOFTWARES inventory entry with
 *      name prefixed "[WinServerRole]" and the structured payload in COMMENTS.
 *   3. Standard GLPI inventory imports these as software entries (no custom
 *      section, no pre_inventory hook — both are unreliable in GLPI 11).
 *   4. The Computer tab reads live from glpi_softwares joined to
 *      glpi_items_softwareversions, filtered on the "[WinServerRole]" prefix.
 *      No backing plugin table, no scheduled sync, no custom auth endpoint.
 */

define('PLUGIN_WINSRVROLESFEATURES_VERSION', '1.0.0');
define('PLUGIN_WINSRVROLESFEATURES_MIN_GLPI', '11.0.0');
define('PLUGIN_WINSRVROLESFEATURES_MAX_GLPI', '12.0.0');

// Autoload all plugin classes from inc/
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'PluginWinsrvrolesfeatures') !== 0) {
        return;
    }
    $base = strtolower(str_replace('PluginWinsrvrolesfeatures', '', $class));
    $file = __DIR__ . "/inc/{$base}.class.php";
    if (file_exists($file)) {
        require_once $file;
    }
});

include_once __DIR__ . '/hook.php';

// ── Hooks ─────────────────────────────────────────────────────────────────────

function plugin_init_winsrvrolesfeatures(): void {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['winsrvrolesfeatures'] = true;

    // Tab on Computer items.  No other hooks needed — the tab reads live
    // from glpi_softwares, populated by the standard inventory pipeline.
    Plugin::registerClass('PluginWinsrvrolesfeaturesRole', ['addtabon' => ['Computer']]);
}

function plugin_version_winsrvrolesfeatures(): array {
    return [
        'name'         => 'Windows Server Roles and Features',
        'version'      => PLUGIN_WINSRVROLESFEATURES_VERSION,
        'author'       => 'Christian Bernard',
        'license'      => 'GPLv2+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_WINSRVROLESFEATURES_MIN_GLPI,
                'max' => PLUGIN_WINSRVROLESFEATURES_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_winsrvrolesfeatures_check_prerequisites(): bool {
    if (version_compare(GLPI_VERSION, PLUGIN_WINSRVROLESFEATURES_MIN_GLPI, 'lt') ||
        version_compare(GLPI_VERSION, PLUGIN_WINSRVROLESFEATURES_MAX_GLPI, 'ge')) {
        echo sprintf(
            __('This plugin requires GLPI >= %s and < %s', 'winsrvrolesfeatures'),
            PLUGIN_WINSRVROLESFEATURES_MIN_GLPI,
            PLUGIN_WINSRVROLESFEATURES_MAX_GLPI
        );
        return false;
    }
    return true;
}

function plugin_winsrvrolesfeatures_check_config(): bool {
    return true;
}
