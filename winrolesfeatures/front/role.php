<?php
/**
 * List all Windows Server roles across all computers.
 * Accessible from the GLPI plugin menu.
 */

include('../../../inc/includes.php');

Session::checkRight(PluginWinrolesfeaturesRole::$rightname, READ);

Html::header(
    PluginWinrolesfeaturesRole::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'plugins',
    'winrolesfeatures',
    'role'
);

Search::show('PluginWinrolesfeaturesRole');

Html::footer();
