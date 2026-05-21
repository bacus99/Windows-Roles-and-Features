<?php
/**
 * Plugin install / uninstall lifecycle.
 *
 * GLPI 11: $DB->query/doQuery/queryOrDie are all banned when called directly
 * from plugin code. Raw SQL must be queued via $migration->addPostQuery() so
 * it executes from within Migration::executeMigration() — the only allowed context.
 */

/**
 * Install creates no tables: roles are read live from glpi_softwares
 * (entries prefixed "[WinServerRole]") joined to glpi_items_softwareversions.
 * Only profile rights need to be registered.
 *
 * If a previous version of the plugin created glpi_plugin_winserverroles_roles,
 * we drop it here so upgrades come out clean.
 */
function plugin_winserverroles_install(): bool {
    global $DB;

    $migration = new Migration(PLUGIN_WINSERVERROLES_VERSION);

    // Clean up the legacy roles table from pre-1.0 installations that used
    // a custom WIN_SERVER_ROLES inventory section + pre_inventory handler.
    $legacyTable = 'glpi_plugin_winserverroles_roles';
    if ($DB->tableExists($legacyTable)) {
        $migration->addPostQuery("DROP TABLE `{$legacyTable}`");
    }

    $migration->executeMigration();

    // Register the right with every profile (0 = no access by default).
    ProfileRight::addProfileRights([PluginWinserverrolesRole::$rightname]);

    // Grant the same value as 'computer' to profiles that already have
    // computer access — viewing roles is meaningless without computer read.
    $iterator = $DB->request([
        'SELECT' => ['profiles_id', 'rights'],
        'FROM'   => 'glpi_profilerights',
        'WHERE'  => ['name' => 'computer'],
    ]);
    foreach ($iterator as $row) {
        if ($row['rights'] > 0) {
            $DB->update('glpi_profilerights', ['rights' => $row['rights']], [
                'profiles_id' => $row['profiles_id'],
                'name'        => PluginWinserverrolesRole::$rightname,
            ]);
        }
    }

    return true;
}

function plugin_winserverroles_uninstall(): bool {
    global $DB;

    $migration   = new Migration(PLUGIN_WINSERVERROLES_VERSION);
    $legacyTable = 'glpi_plugin_winserverroles_roles';

    if ($DB->tableExists($legacyTable)) {
        $migration->addPostQuery("DROP TABLE `{$legacyTable}`");
    }

    $migration->executeMigration();

    ProfileRight::deleteProfileRights([PluginWinserverrolesRole::$rightname]);

    return true;
}
