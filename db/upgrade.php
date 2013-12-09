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

    if ($oldversion < 2013120901) {
        $table = new xmldb_table('fpdquadern_alumne_activitats');
        $field = new xmldb_field(
            'comentaris_professor', XMLDB_TYPE_TEXT, null, null,
            null, null, null, 'grau_assoliment');
        $dbman->rename_field($table, $field, 'valoracio_professor');
        upgrade_mod_savepoint(true, 2013120901, 'fpdquadern');
    }

    if ($oldversion < 2013120902) {
        $table = new xmldb_table('fpdquadern_alumne_activitats');
        $field = new xmldb_field(
            'format_comentaris_professor', XMLDB_TYPE_INTEGER, '4', null,
            XMLDB_NOTNULL, null, '0', 'valoracio_professor');
        $dbman->rename_field($table, $field, 'format_valoracio_professor');
        upgrade_mod_savepoint(true, 2013120902, 'fpdquadern');
    }

    return true;
}
