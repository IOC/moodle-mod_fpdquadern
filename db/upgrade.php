<?php

/**
 * @package mod_fpdquadern
 * @copyright 2013 Institut Obert de Catalunya
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Albert Gasset <albert@ioc.cat>
 */

function xmldb_fpdquadern_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013120900) {
        $table = new xmldb_table('fpdquadern_alumne_activitats');
        $fieldnames = array(
            'valoracio_alumne',
            'format_valoracio_alumne',
            'data_valoracio_alumne',
        );

        foreach ($fieldnames as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2013120900, 'fpdquadern');
    }

    return true;
}
