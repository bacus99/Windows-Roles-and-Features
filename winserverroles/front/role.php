<?php
/**
 * List all Windows Server roles across all computers.
 * Accessible from the GLPI plugin menu.
 */

include('../../../inc/includes.php');

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
