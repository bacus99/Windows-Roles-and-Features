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
 * If a previous version of the plugin created legacy tables,
 * we drop them here so upgrades come out clean.
 */
function plugin_winsrvrolesfeatures_install(): bool {
    global $DB;

    $migration = new Migration(PLUGIN_WINSRVROLESFEATURES_VERSION);

    // Clean up legacy tables from pre-1.0 installations.
    foreach (['glpi_plugin_winserverroles_roles', 'glpi_plugin_winsrvrolesfeatures_roles'] as $legacyTable) {
        if ($DB->tableExists($legacyTable)) {
            $migration->addPostQuery("DROP TABLE `{$legacyTable}`");
        }
    }

    $migration->executeMigration();

    // Register the right with every profile (0 = no access by default).
    ProfileRight::addProfileRights([PluginWinsrvrolesfeaturesRole::$rightname]);

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
                'name'        => PluginWinsrvrolesfeaturesRole::$rightname,
            ]);
        }
    }

    return true;
}

function plugin_winsrvrolesfeatures_uninstall(): bool {
    global $DB;

    $migration   = new Migration(PLUGIN_WINSRVROLESFEATURES_VERSION);

    foreach (['glpi_plugin_winserverroles_roles', 'glpi_plugin_winsrvrolesfeatures_roles'] as $legacyTable) {
        if ($DB->tableExists($legacyTable)) {
            $migration->addPostQuery("DROP TABLE `{$legacyTable}`");
        }
    }

    $migration->executeMigration();

    ProfileRight::deleteProfileRights([PluginWinsrvrolesfeaturesRole::$rightname]);

    return true;
}
