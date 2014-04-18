<?php
/**
 * @package mod_fpdquadern
 * @copyright 2013 Institut Obert de Catalunya
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Albert Gasset <albert@ioc.cat>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(__DIR__.'/config.php');

class mod_fpdquadern_mod_form extends moodleform_mod {
 
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', 'Nom', array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_intro_editor(false, 'Descripció');

        $config = new mod_fpdquadern\config;
        foreach ($config->fases as $num => $nom) {
            $name = "durada_fase_$num";
            $label = "Durada $nom";
            $mform->addElement('text', $name, $label, array('size' => 4));
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, 0);
        }

        $mform->addElement('header', 'dates_limit', "Dates límit");
        $dates = array(
            'data_dades_generals' => "Dades generals",
            'data_qualificacio_1' => "Qualificació 1",
            'data_qualificacio_2' => "Qualificació 2",
            'data_qualificacio_3' => "Qualificació 3",
            'data_qualificacio_final' => "Qualificació final",
        );
        foreach ($dates as $name => $label) {
            $options = array('optional' => true);
            $mform->addElement('date_selector', $name, $label, $options);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, 0);
        }

        $mform->addElement('header', 'centre_estudis', "Centre d'estudis");
        $fields = array(
            'nom_centre_estudis' => 'Nom',
            'codi_centre_estudis' => 'Codi de centre',
            'adreca_centre_estudis' => 'Adreça',
        );
        foreach ($fields as $name => $label) {
            $mform->addElement('text', $name, $label, array('size' => 32));
            $mform->setType($name, PARAM_TEXT);
            $mform->addRule($name, get_string('maximumchars', '', 255),
                           'maxlength', 255, 'client');
        }

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
