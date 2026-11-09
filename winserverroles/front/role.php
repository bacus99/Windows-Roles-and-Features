<?php
/**
 * List all Windows Server roles across all computers.
 * Accessible from the GLPI plugin menu.
 */

// No include of GLPI's inc/includes.php: the kernel has already booted before a plugin script runs (GLPI 11 and 12); the relative path fails when the plugin lives in a marketplace directory outside the GLPI root.

Session::checkRight(PluginWinserverrolesRole::$rightname, READ);

Html::header(
    PluginWinserverrolesRole::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'plugins',
    'winserverroles',
    'role'
);

Search::show('PluginWinserverrolesRole');

Html::footer();
