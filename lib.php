<?php
/**
 * @package mod_fpdquadern
 * @copyright 2013 Institut Obert de Catalunya
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Albert Gasset <albert@ioc.cat>
 */

defined('MOODLE_INTERNAL') || die();

function fpdquadern_add_instance($data, $form=null) {
    global $DB;

    $data->id = $DB->insert_record('fpdquadern', $data);
    fpdquadern_grade_item_update($data);
    fpdquadern_crear_llistes_predeterminades($data->id);

    return $data->id;
}

function fpdquadern_update_instance($data, $form=null) {
    global $DB;

    $data->id = $data->instance;

    $DB->update_record('fpdquadern', $data);
    fpdquadern_grade_item_update($data);

    return true;
}

function fpdquadern_delete_instance($id) {
    global $DB;

    if (!$quadern = $DB->get_record('fpdquadern', array('id' => $id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('fpdquadern', $quadern->id)) {
        return false;
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        return false;
    }

    $context = context_module::instance($cm->id);

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_fpdquadern');

    $DB->delete_records('fpdquadern_activitats', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_alumne', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_alumne_activitats', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_alumne_competenci', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_alumne_fases', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_alumne_seguiment', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_competencies', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern_llistes', array('quadern_id' => $id));
    $DB->delete_records('fpdquadern', array('id'=> $quadern->id));

    return true;
}

function fpdquadern_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_GROUPS: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;
        default: return null;
    }
}

function fpdquadern_extend_settings_navigation($settings, $node) {
    global $PAGE;

    if (!$PAGE->cm or !has_capability('mod/fpdquadern:admin', $PAGE->context)) {
        return;
    }

    $url = new \moodle_url('/mod/fpdquadern/view.php');
    $url->param('id', $PAGE->cm->id);
    $url->param('accio', 'veure_activitats');
    $node->add('Activitats', $url, navigation_node::TYPE_SETTING);

    $url = new \moodle_url($url);
    $url->param('accio', 'veure_competencies');
    $node->add('Competències', $url, navigation_node::TYPE_SETTING);

    $url = new \moodle_url($url);
    $url->param('accio', 'veure_llista');
    $node->add('Llistes desplegables', $url, navigation_node::TYPE_SETTING);
}

function fpdquadern_get_user_grades($quadern, $userid=0) {
    global $DB;

    $grades = array();

    $params = array('quadern_id' => $quadern->id);
    if ($userid) {
        $params['alumne_id'] = $userid;
    }

    $records = $DB->get_records('fpdquadern_alumne', $params);
    foreach ($records as $record) {
        $grades[$record->alumne_id] = (object) array(
            'userid' => $record->alumne_id,
            'rawgrade' => $record->qualificacio,
        );
    }

    return $grades;
}

function fpdquadern_update_grades($quadern, $userid=0, $nullifnone=true) {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');

    if ($quadern->grade == 0) {
        fpdquadern_grade_item_update($quadern);
    } else if ($grades = fpdquadern_get_user_grades($quadern, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        fpdquadern_grade_item_update($quadern, $grades);
    } else if ($userid and $nullifnone) {
        $grade = new \stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = NULL;
        fpdquadern_grade_item_update($quadern, $grade);
    } else {
        fpdquadern_grade_item_update($quadern);
    }
}

function fpdquadern_grade_item_update($quadern, $grades=null) {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');

    $params = array('itemname' => $quadern->name);
    if (isset($quadern->cmidnumber)) {
        $params['idnumber'] = $quadern->cmidnumber;
    } elseif ($cm = get_coursemodule_from_instance('fpdquadern', $quadern->id)) {
        $params['idnumber'] = $cm->idnumber;
    }

    if ($quadern->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $quadern->grade;
        $params['grademin']  = 0;
    } else if ($quadern->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$quadern->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/fpdquadern', $quadern->course, 'mod', 'fpdquadern',
                        $quadern->id, 0, $grades, $params);
}

function fpdquadern_scale_used($id, $scaleid) {
    global $DB;
    $record = $DB->get_record('fpdquadern', array('id' => $id, 'grade' => -$scaleid));
    return !empty($record) && !empty($scaleid);
}

function fpdquadern_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid and $DB->record_exists('fpdquadern', array('grade' => -$scaleid));
}

function fpdquadern_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'fpdquadernheader', get_string('modulenameplural', 'fpdquadern'));
    $mform->addElement('checkbox', 'reset_fpdquadern_all', 'Suprimeix tots els quaderns');
}

function fpdquadern_reset_userdata($data) {
    global $CFG, $DB;

    $status = array();

    if (empty($data->reset_fpdquadern_all)) {
        return $status;
    }

    $quaderns = $DB->get_records('fpdquadern', array('course' => $data->courseid));

    foreach ($quaderns as $id => $quadern) {
        $DB->delete_records('fpdquadern_alumne', array('quadern_id' => $id));
        $DB->delete_records('fpdquadern_alumne_activitats', array('quadern_id' => $id));
        $DB->delete_records('fpdquadern_alumne_fases', array('quadern_id' => $id));
        $DB->delete_records('fpdquadern_alumne_seguiment', array('quadern_id' => $id));
        $select = 'quadern_id = :id AND alumne_id != 0';
        $DB->delete_records_select('fpdquadern_activitats', $select, array('id' => $id));

        $cm = get_coursemodule_from_instance('fpdquadern', $id, $data->courseid, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $fs = get_file_storage();
        foreach (array('alumne', 'professor', 'tutor') as $rol) {
            $fs->delete_area_files(
                $context->id, 'mod_fpdquadern', "valoracio_activitat_$rol"
            );
        }

        if (empty($data->reset_gradebook_grades)) {
            fpdquadern_grade_item_update($quadern, 'reset');
        }

        $status[] = array(
            'component' => get_string('modulenameplural', 'fpdquadern'),
            'item'=> 'Suprimeix tots els quaderns',
            'error'=>false,
        );
    }

    return $status;
}

function fpdquadern_pluginfile(
    $course, $cm, $context, $filearea, $args, $forcedownload, $options=array()
) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'. implode('/', $args) .'/' : '/';

    if ($filearea == 'valoracio_activitat_alumne'
        or $filearea == 'valoracio_activitat_tutor'
        or $filearea == 'valoracio_activitat_professor') {

        $conditions = array('id' => $itemid);
        $alumne_id = $DB->get_field(
            'fpdquadern_alumne_activitats', 'alumne_id', $conditions);

        $conditions = array(
            'quadern_id' => $cm->instance,
            'alumne_id' => $alumne_id,
        );
        $alumne = $DB->get_record(
            'fpdquadern_alumne', $conditions, '*', MUST_EXIST);

        if (!has_capability('mod/fpdquadern:admin', $context) and
            $alumne->alumne_id != $USER->id and
            $alumne->professor_id != $USER->id and
            $alumne->tutor_id != $USER->id) {
            return false;
        }
    }

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id, 'mod_fpdquadern', $filearea, $itemid,
        $filepath, $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, 86400, 0, false, $options);
}

function fpdquadern_crear_llistes_predeterminades($quadern_id) {
    global $CFG, $DB;

    require_once(__DIR__ . '/locallib.php');
    require_once($CFG->libdir . '/csvlib.class.php');

    foreach (array_keys(mod_fpdquadern\llista_view::$llistes) as $llista) {
        $iid = csv_import_reader::get_new_iid('mod_fpdquadern');
        $cir = new csv_import_reader($iid, 'mod_fpdquadern');
        $content = file_get_contents(__DIR__ . '/db/' . $llista . '.csv');
        $cir->load_csv_content($content, 'utf-8', 'comma');
        $cir->init();

        while ($row = $cir->next()) {
            $record = (object) array(
                'quadern_id' => $quadern_id,
                'llista' => $llista,
                'codi' => $row[0],
                'nom' => $row[1],
                'grup' => count($row) > 2 ? $row[2] : '',
            );
            $DB->insert_record('fpdquadern_llistes', $record);
        }

        $cir->close();
        $cir->cleanup();
    }
}
