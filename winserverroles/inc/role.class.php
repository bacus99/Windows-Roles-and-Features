<?php
/**
 * PluginWinserverrolesRole — Windows Server roles tab for Computer items.
 *
 * Data flow:
 *   1. GLPI Agent Perl module (Win32/WinServerRoles.pm) emits each installed
 *      role as a SOFTWARES inventory entry, name = "[WinServerRole] <Display>",
 *      structured payload pipe-encoded in COMMENTS:
 *         NAME|DISPLAYNAME|INSTALLED|SUBFEATURES
 *   2. Standard GLPI inventory imports them into glpi_softwares and links
 *      them to the computer via glpi_items_softwareversions.
 *   3. This class queries that linkage to render the tab — no separate
 *      roles table needed (GLPI 11 inventory pipeline does not load plugin
 *      setup.php during the inventory POST, so a custom section + a
 *      pre_inventory handler does not work).
 */
class PluginWinserverrolesRole extends CommonDBTM {

    /** Prefix the agent module uses for software entries representing roles. */
    public const NAME_PREFIX = '[WinServerRole]';

    public static $rightname = 'plugin_winserverroles';

    // -------------------------------------------------------------------------
    // CommonDBTM overrides
    // -------------------------------------------------------------------------

    public static function getIcon(): string {
        return 'ti ti-server';
    }

    public static function getTypeName($nb = 0): string {
        return _n('Windows Server Role', 'Windows Server Roles', $nb, 'winserverroles');
    }

    /**
     * No backing table — the tab reads live from glpi_softwares joined to
     * glpi_items_softwareversions.  CommonDBTM still expects a table name,
     * so we point at glpi_softwares (the de-facto source of truth).  Search
     * options that need glpi_items_softwareversions reference it explicitly.
     */
    public static function getTable($classname = null): string {
        return 'glpi_softwares';
    }

    // -------------------------------------------------------------------------
    // Tab on Computer
    // -------------------------------------------------------------------------

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array {
        if ($item instanceof Computer && Session::haveRight(self::$rightname, READ)) {
            $count = self::countRolesForComputer($item->getID());
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
        }
        return '';
    }

    /**
     * Count [WinServerRole] software entries linked to a computer.
     */
    private static function countRolesForComputer(int $computersId): int {
        global $DB;

        $row = $DB->request([
            'COUNT'      => 'cnt',
            'FROM'       => 'glpi_items_softwareversions AS isv',
            'INNER JOIN' => [
                'glpi_softwareversions AS sv' => [
                    'ON' => ['sv' => 'id', 'isv' => 'softwareversions_id'],
                ],
                'glpi_softwares AS s' => [
                    'ON' => ['s' => 'id', 'sv' => 'softwares_id'],
                ],
            ],
            'WHERE'      => [
                'isv.itemtype'  => 'Computer',
                'isv.items_id'  => $computersId,
                'isv.is_deleted' => 0,
                ['s.name' => ['LIKE', self::NAME_PREFIX . '%']],
            ],
        ])->current();

        return $row ? (int) $row['cnt'] : 0;
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ): bool {
        if ($item instanceof Computer) {
            self::showForComputer($item);
        }
        return true;
    }

    // -------------------------------------------------------------------------
    // Display
    // -------------------------------------------------------------------------

    public static function showForComputer(Computer $computer): void {
        global $DB;

        if (!Session::haveRight(self::$rightname, READ)) {
            return;
        }

        // Pull every [WinServerRole] software entry linked to this computer.
        // s.comment carries the structured payload: NAME|DISPLAYNAME|INSTALLED|SUBFEATURES.
        $iterator = $DB->request([
            'SELECT'     => [
                's.id AS software_id',
                's.name AS software_name',
                's.comment AS payload',
                // Inventory does not always populate s.date_mod, but
                // s.date_creation is always set on first import.
                's.date_mod AS date_mod',
                's.date_creation AS date_creation',
            ],
            'FROM'       => 'glpi_items_softwareversions AS isv',
            'INNER JOIN' => [
                'glpi_softwareversions AS sv' => [
                    'ON' => ['sv' => 'id', 'isv' => 'softwareversions_id'],
                ],
                'glpi_softwares AS s' => [
                    'ON' => ['s' => 'id', 'sv' => 'softwares_id'],
                ],
            ],
            'WHERE'      => [
                'isv.itemtype'   => 'Computer',
                'isv.items_id'   => $computer->getID(),
                'isv.is_deleted' => 0,
                ['s.name' => ['LIKE', self::NAME_PREFIX . '%']],
            ],
            'ORDER'      => ['s.name ASC'],
        ]);

        $rows = iterator_to_array($iterator);

        echo '<div class="mb-2 text-muted small">'
            . __('Collected by GLPI Agent during standard inventory.', 'winserverroles')
            . '</div>';

        if (count($rows) === 0) {
            echo '<div class="alert alert-info">'
                . __('No Windows Server roles found for this computer.', 'winserverroles')
                . '</div>';
            return;
        }

        echo '<table class="table table-hover table-striped">';
        echo '<thead class="table-dark"><tr>';
        echo '<th>' . __('Name', 'winserverroles') . '</th>';
        echo '<th>' . __('Display Name', 'winserverroles') . '</th>';
        echo '<th>' . __('Installed', 'winserverroles') . '</th>';
        echo '<th>' . __('Sub-features', 'winserverroles') . '</th>';
        echo '<th>' . __('Last update', 'winserverroles') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            // payload = NAME|DISPLAYNAME|INSTALLED|SUBFEATURES
            $parts        = explode('|', $row['payload'] ?? '', 4);
            $name         = $parts[0] ?? '';
            $displayName  = $parts[1] ?? '';
            $installed    = (int) ($parts[2] ?? 1);
            $subfeaturesS = $parts[3] ?? '';

            // Fallbacks if COMMENTS was stripped or not a role we emitted
            if ($displayName === '') {
                $displayName = preg_replace(
                    '/^' . preg_quote(self::NAME_PREFIX, '/') . '\s*/',
                    '',
                    $row['software_name'] ?? ''
                );
            }

            $subfeaturesPretty = $subfeaturesS !== ''
                ? implode(', ', array_map('trim', explode(';', $subfeaturesS)))
                : '';

            $installedBadge = $installed
                ? '<span class="badge bg-success">' . __('Yes') . '</span>'
                : '<span class="badge bg-secondary">' . __('No') . '</span>';

            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($name, ENT_QUOTES) . '</code></td>';
            echo '<td>' . htmlspecialchars($displayName, ENT_QUOTES) . '</td>';
            echo '<td>' . $installedBadge . '</td>';
            echo '<td class="small">' . htmlspecialchars($subfeaturesPretty, ENT_QUOTES) . '</td>';
            $when = !empty($row['date_mod']) ? $row['date_mod'] : ($row['date_creation'] ?? null);
            echo '<td class="small">' . ($when ? Html::convDateTime($when) : '') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    // -------------------------------------------------------------------------
    // Search options — point at glpi_softwares with the [WinServerRole] prefix
    // -------------------------------------------------------------------------

    public function rawSearchOptions(): array {
        $options = parent::rawSearchOptions();

        // IDs 1000+ — GLPI convention for plugin search options.
        $options[] = [
            'id'       => 1000,
            'table'    => 'glpi_softwares',
            'field'    => 'name',
            'name'     => __('Feature name', 'winserverroles'),
            'datatype' => 'string',
        ];
        $options[] = [
            'id'       => 1002,
            'table'    => 'glpi_softwares',
            'field'    => 'date_mod',
            'name'     => __('Last update', 'winserverroles'),
            'datatype' => 'datetime',
        ];

        return $options;
    }
}
