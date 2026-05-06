<?php
/**
 * Plugin install / uninstall lifecycle.
 *
 * GLPI 11: $DB->query/doQuery/queryOrDie are all banned when called directly
 * from plugin code. Raw SQL must be queued via $migration->addPostQuery() so
 * it executes from within Migration::executeMigration() — the only allowed context.
 */

function plugin_winserverroles_install(): bool {
    global $DB;

    $migration  = new Migration(PLUGIN_WINSERVERROLES_VERSION);
    $rolesTable = PluginWinserverrolesRole::getTable();

    if (!$DB->tableExists($rolesTable)) {
        $migration->addPostQuery(
            "CREATE TABLE `{$rolesTable}` (
                `id`           int unsigned NOT NULL AUTO_INCREMENT,
                `computers_id` int unsigned  NOT NULL DEFAULT 0,
                `name`         varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `displayname`  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `description`  text         COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `installed`    tinyint(1)   NOT NULL DEFAULT 1,
                `subfeatures`  text         COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `date_mod`     timestamp    NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `computers_id` (`computers_id`),
                KEY `name` (`name`(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    $migration->executeMigration();

    // Register the right with every profile (0 = no access by default)
    ProfileRight::addProfileRights([PluginWinserverrolesRole::$rightname]);

    // Give the same rights as 'computer' to profiles that already have computer access
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

    $migration  = new Migration(PLUGIN_WINSERVERROLES_VERSION);
    $rolesTable = PluginWinserverrolesRole::getTable();

    if ($DB->tableExists($rolesTable)) {
        $migration->addPostQuery("DROP TABLE `{$rolesTable}`");
    }

    $migration->executeMigration();

    ProfileRight::deleteProfileRights([PluginWinserverrolesRole::$rightname]);

    return true;
}
