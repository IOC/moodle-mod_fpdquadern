<?php
/**
 * @package mod_fpdquadern
 * @copyright 2013 Institut Obert de Catalunya
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Albert Gasset <albert@ioc.cat>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
 
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

        $durades = array(
            'durada_fase_1' => "Durada Pràcticum I",
            'durada_fase_2' => "Durada Pràcticum II",
            'durada_fase_3' => "Durada Pràcticum III",
        );
        foreach ($durades as $name => $label) {
            $mform->addElement('text', $name, $label, array('size' => 4));
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, 0);
        }

        $dates = array(
            'data_dades_generals' => "Data límit dades generals",
            'data_qualificacio_1' => "Data límit qualificació 1",
            'data_qualificacio_2' => "Data límit qualificació 2",
            'data_qualificacio_3' => "Data límit qualificació 3",
            'data_qualificacio_final' => "Data límit qualificació final",
        );
        foreach ($dates as $name => $label) {
            $options = array('optional' => true);
            $mform->addElement('date_selector', $name, $label, $options);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, 0);
        }

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
