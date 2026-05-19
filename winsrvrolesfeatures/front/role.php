<?php
/**
 * List all Windows Server roles across all computers.
 * Accessible from the GLPI plugin menu.
 */

include('../../../inc/includes.php');

Session::checkRight(PluginWinsrvrolesfeaturesRole::$rightname, READ);

Html::header(
    PluginWinsrvrolesfeaturesRole::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'plugins',
    'winsrvrolesfeatures',
    'role'
);

Search::show('PluginWinsrvrolesfeaturesRole');

Html::footer();
