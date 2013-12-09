<?php
/**
 * @package mod_fpdquadern
 * @copyright 2013 Institut Obert de Catalunya
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Albert Gasset <albert@ioc.cat>
 */

namespace mod_fpdquadern;

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/database.php');

class quadern_controller {

    public $cm;
    public $config;
    public $context;
    public $output;
    public $quadern;

    protected $database;

    function __construct($cm) {
        global $DB, $PAGE;

        $this->cm = $cm;
        $this->config = new config();
        $this->context = \context_module::instance($this->cm->id);
        $this->database = new database($DB);

        $conditions = array('id' => $this->cm->instance);
        $this->quadern = $this->database->fetch('quadern', $conditions);

        $this->output = $PAGE->get_renderer('mod_fpdquadern');
        $this->output->set_controller($this);
    }

    function delete_files($filearea, $itemid) {
        $fs = get_file_storage();
        $fs->delete_area_files(
            $this->context->id, 'mod_fpdquadern', $filearea, $itemid);
    }

    function es_admin() {
        return has_capability(
            "mod/fpdquadern:admin", $this->context, null, true);
    }

    function es_alumne() {
        return has_capability(
            "mod/fpdquadern:alumne", $this->context, null, false);
    }

    function es_professor() {
        return has_capability(
            "mod/fpdquadern:professor", $this->context, null, false);
    }

    function es_tutor() {
        return has_capability(
            "mod/fpdquadern:tutor", $this->context, null, false);
    }

    function index_alumnes($groupid=0) {
        global $DB, $USER;

        $groups = array();

        if (groups_get_activity_groupmode($this->cm)) {
            $groups = groups_get_all_groups(
                $this->cm->course, 0, $this->cm->groupingid);
        }

        if (!isset($groups[$groupid])) {
            $groupid = 0;
        }

        $userids = array();
        $alumnes = array();

        if ($this->es_admin()) {
            $userids = get_enrolled_users(
                $this->context, 'mod/fpdquadern:alumne', $groupid, 'u.id');
            $alumnes = $this->quadern->alumnes();
        } else {
            if ($this->es_professor()) {
                $params = array(
                    'quadern_id' => $this->quadern->id,
                    'professor_id' => $USER->id,
                );
                foreach ($this->database->fetch_all('alumne', $params) as $a) {
                    $alumnes[$a->alumne_id] = $a;
                }
            }
            if ($this->es_tutor()) {
                $params = array(
                    'quadern_id' => $this->quadern->id,
                    'tutor_id' => $USER->id,
                );
                foreach ($this->database->fetch_all('alumne', $params) as $a) {
                    $alumnes[$a->alumne_id] = $a;
                }
            }
            if ($this->es_alumne()) {
                $userids[$USER->id] = $USER;
            }
        }

        if ($groupid) {
            $members = groups_get_members($groupid, 'u.id', 'u.id');
            $userids = array_intersect_key($userids, $members);
            $alumnes = array_intersect_key($alumnes, $members);
        }

        foreach (array_keys(array_diff_key($userids, $alumnes)) as $userid) {
            $alumnes[$userid] = $this->quadern->alumne($userid, true);
        }

        $users = $DB->get_records_list(
            'user', 'id', array_keys($alumnes), 'firstname,lastname',
            'id,firstname,lastname,email,picture,imagealt');

        return array($alumnes, $users, $groups);
    }

    function permis() {
        return ($this->es_admin() or
                $this->es_alumne() or
                $this->es_professor() or
                $this->es_tutor());
    }

    function url($accio=null, array $params=null) {
        $url = new \moodle_url('/mod/fpdquadern/view.php');
        $url->param('id', $this->cm->id);
        if ($accio) {
            $url->param('accio', $accio);
        }
        if ($params) {
            $url->params($params);
        }
        return $url;
    }

    private function es_rol($rol, $doanything) {
        return has_capability(
            "mod/fpdquadern:$rol", $this->context, null, $doanything);
    }
}

class alumne_controller extends quadern_controller {

    public $alumne;

    function __construct($cm, $alumne_id) {
        parent::__construct($cm);

        $crear = is_enrolled($this->context, null, 'mod/fpdquadern:alumne');
        $this->alumne = $this->quadern->alumne($alumne_id, $crear);
    }

    function accions_pendents() {
        $accions = array();

        if (!$this->alumne->professor_id) {
            $accions[] = array(
                'admin', false,
                "Assignar el professor de l'IOC"
            );
        }

        if (!$this->alumne->tutor_id) {
            $accions[] = array(
                'professor', false,
                "Assignar el tutor del centre de pràctiques"
            );
        }

        if (!$this->alumne->alumne_validat) {
            if (!$this->alumne->dades_alumne_introduides()) {
                $accions[] = array(
                    'alumne', false,
                    "Introduir les dades de l'alumne",
                    $this->quadern->data_dades_generals
                );
            } else {
                $accions[] = array(
                    'professor', false,
                    "Validar les dades de l'alumne",
                    $this->quadern->data_dades_generals
                );
            }
        }

        if (!$this->alumne->centre_validat) {
            if (!$this->alumne->dades_centre_introduides()) {
                $accions[] = array(
                    'tutor', false,
                    "Introduir les dades del centre de pràctiques",
                    $this->quadern->data_dades_generals
                );
            } else {
                $accions[] = array(
                    'professor', false,
                    "Validar les dades del centre de pràctiques",
                    $this->quadern->data_dades_generals
                );
            }
        }

        if (!$this->alumne->tutor_validat) {
            if (!$this->alumne->dades_tutor_introduides()) {
                $accions[] = array(
                    'tutor', false,
                    "Introduir les dades del tutor del centre de pràctiques",
                    $this->quadern->data_dades_generals
                );
            } else {
                $accions[] = array(
                    'professor', false,
                    "Validar les dades del tutor del centre de pràctiques",
                    $this->quadern->data_dades_generals
                );
            }
        }

        foreach (array_keys($this->config->fases) as $num) {
            $fase = $this->alumne->fase($num);
            $activitats = $this->alumne->activitats($num);

            if (!$fase->calendari_validat) {
                if (!$fase->planificacio_introduida()) {
                    $accions[] = array(
                        'tutor', $num,
                        "Introduir la planificació"
                    );
                } else {
                    $accions[] = array(
                        'professor', $num,
                        "Validar la planificació"
                    );
                }
            } elseif (!$fase->calendari_acceptat) {
                $accions[] = array(
                    'alumne', $num,
                    "Acceptar la planificació"
                );
            }

            if (!$activitats) {
                $accions[] = array(
                    'professor', $num,
                    "Assignar les activitats"
                );
            }

            foreach ($activitats as $activitat) {
                if ($activitat->complementaria()) {
                    if (!$activitat->validada) {
                        $accions[] = array(
                            'professor', $num,
                            "Validar l'activitat proposada "  .
                            "<em>{$activitat->titol}</em>"
                        );
                    }
                    if (!$activitat->acceptada) {
                        $accions[] = array(
                            'tutor', $num,
                            "Acceptar l'activitat proposada " .
                            "<em>{$activitat->titol}</em>"
                        );
                    }
                }
            }

            foreach ($this->alumne->dies_seguiment($num, true) as $dia) {
                $accions[] = array(
                    'tutor', $num,
                    "Validar el dia " . userdate($dia->data, '%d/%m/%y') .
                    " del seguiment"
                );
            }

            foreach ($activitats as $activitat) {
                if ($activitat->complementaria() and !$activitat->validada)
                    continue;

                $valoracio = $this->alumne->valoracio($activitat->id);
                $text = "Valorar l'activitat " .
                    "<em>{$activitat->titol}</em>";

                if (!$valoracio->valorada_tutor()) {
                    $accions[] = array(
                        'tutor', $num, $text,
                        $activitat->data_valoracio_tutor,
                    );
                }
                if (!$valoracio->valorada_professor()) {
                    $accions[] = array(
                        'professor', $num, $text,
                        $activitat->data_valoracio_professor,
                    );
                }
                if ($valoracio->valorada_tutor()) {
                    $accions[] = array(
                        'professor', $num,
                        "Validar la valoració de l'activitat " .
                        "<em>{$activitat->titol}</em>",
                        $activitat->data_valoracio_professor,
                    );
                }
            }

            if ($fase->qualificacio === null) {
                $accions[] = array(
                    'professor', $num,
                    "Introduir la qualificació final",
                    $this->quadern->{"data_qualificacio_$num"}
                );
            }
        }

        if ($this->alumne->qualificacio === null) {
            $accions[] = array(
                'professor', false,
                "Introduir la qualificació final de les pràctiques",
                $this->quadern->data_qualificacio_final
            );
        }

        return $accions;
    }

    function avisar_professor() {
        if (!$this->es_professor()) {
            $this->alumne->avis_professor = time();
            $this->alumne->save();
        }
    }

    function es_alumne() {
        global $USER;
        return parent::es_alumne() and $this->alumne->alumne_id == $USER->id;
    }

    function es_professor() {
        global $USER;
        return (parent::es_professor() and
                $this->alumne->professor_id == $USER->id);
    }

    function es_tutor() {
        global $USER;
        return parent::es_tutor() and $this->alumne->tutor_id == $USER->id;
    }

    function permis_acceptar_activitat_complementaria($activitat) {
        return ($activitat->alumne_id == $this->alumne->alumne_id and
                $this->es_tutor() and !$activitat->acceptada);
    }

    function permis_acceptar_calendari($fase) {
        return ($this->es_alumne() and
                $this->alumne->fase($fase)->calendari_validat);
    }

    function permis_afegir_activitat_complementaria() {
        return $this->es_admin() or $this->es_professor() or $this->es_alumne();
    }

    function permis_editar_activitat_complementaria($activitat) {
        return ($activitat->alumne_id == $this->alumne->alumne_id and
                ($this->es_admin() or $this->es_professor() or
                 $this->es_alumne() and !$activitat->validada and
                 !$activitat->acceptada));
    }

    function permis_editar_calendari($fase) {
        return ($this->es_admin() or $this->es_professor() or
                ($this->es_tutor() and
                 !$this->alumne->fase($fase)->calendari_validat));
    }

    function permis_editar_dades_alumne() {
        return ($this->es_admin() or $this->es_professor() or
                $this->es_alumne() and !$this->alumne->alumne_validat);
    }

    function permis_editar_dades_centre() {
        return ($this->es_admin() or $this->es_professor() or
                $this->es_tutor() and !$this->alumne->centre_validat);
    }

    function permis_editar_dades_tutor() {
        return ($this->es_admin() or $this->es_professor() or
                $this->es_tutor() and !$this->alumne->tutor_validat);
    }

    function permis_editar_professor() {
        return $this->es_admin();
    }

    function permis_editar_qualificacio() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_editar_seguiment($dia) {
        return $this->es_admin() or $this->es_professor() or !$dia->validat;
    }

    function permis_editar_tutor() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_editar_valoracio($valoracio, $activitat) {
        return ((!$activitat->complementaria() or $activitat->validada) and
                ($this->es_admin() or $this->es_professor() or
                 ($this->es_tutor() and !$valoracio->valoracio_validada)));
    }

    function permis_editar_valoracio_tutor() {
        return $this->es_admin() or $this->es_tutor();
    }

    function permis_editar_valoracio_professor() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_seleccionar_activitats() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_validar_activitat_complementaria() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_validar_dades() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_validar_calendari() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_validar_seguiment() {
        return $this->es_admin() or $this->es_professor() or $this->es_tutor();
    }

    function permis_validar_valoracio() {
        return $this->es_admin() or $this->es_professor();
    }

    function permis_veure_totes_accions_pendents() {
        return $this->es_admin() or $this->es_professor() or $this->es_tutor();
    }

    function url_alumne($accio, array $params=null) {
        $params = $params ?: array();
        $params['alumne_id'] = $this->alumne->alumne_id;
        return $this->url($accio, $params);
    }
}

abstract class quadern_view extends quadern_controller {

    function __construct(array $urlparams=null) {
        global $PAGE;

        $id = required_param('id', PARAM_INT);
        $accio = optional_param('accio', '', PARAM_ALPHAEXT);

        if (!$cm = get_coursemodule_from_id('fpdquadern', $id)) {
            print_error('invalidcoursemodule');
        }

        require_login($cm->course, false, $cm);

        parent::__construct($cm);

        $PAGE->set_url($this->url($accio, $urlparams));
        $PAGE->set_title(format_string($this->quadern->name));
        $PAGE->set_heading(format_string($PAGE->course->fullname));
    }
}

abstract class alumne_view extends alumne_controller {

    function __construct(array $urlparams=null) {
        global $PAGE;

        $id = required_param('id', PARAM_INT);
        $alumne_id = required_param('alumne_id', PARAM_INT);
        $accio = optional_param('accio', '', PARAM_ALPHAEXT);

        if (!$cm = get_coursemodule_from_id('fpdquadern', $id)) {
            print_error('invalidcoursemodule');
        }

        require_login($cm->course, false, $cm);

        parent::__construct($cm, $alumne_id);

        $PAGE->set_url($this->url_alumne($accio, $urlparams));
        $PAGE->set_title(format_string($this->quadern->name));
        $PAGE->set_heading(format_string($PAGE->course->fullname));
        $PAGE->navbar->add(fullname($this->alumne->alumne()));
    }
}

class veure_activitats_view extends quadern_view {

    function __construct() {
        parent::__construct();

        if (!$this->es_admin()) {
            print_error('nopermissiontoshow');
        }

        echo $this->output->index_activitats();
    }
}

class afegir_activitat_view extends quadern_view {

    function __construct() {
        parent::__construct();

        if (!$this->es_admin()) {
            print_error('nopermissiontoshow');
        }

        $activitat = $this->database->create('activitat', array(
            'quadern_id' => $this->quadern->id,
            'alumne_id' => 0,
        ));

        $form = new activitat_form($this, $activitat);

        if ($form->is_cancelled()) {
            redirect($this->url('veure_activitats'));
        } else if ($data = $form->get_data()) {
            $activitat->update((array) $data);
            $activitat->save();
            $form->save_files($data, $activitat->id);
            $activitat->update((array) $data);
            $activitat->save();
            redirect($this->url('veure_activitats'));
        }

        echo $this->output->formulari_activitat($form);
    }
}

class editar_activitat_view extends quadern_view {

    function __construct() {
       $id = required_param('activitat_id', PARAM_INT);

       parent::__construct(array('activitat_id' => $id));

        if (!$this->es_admin()) {
            print_error('nopermissiontoshow');
        }

        $activitat = $this->quadern->activitat($id);

        $form = new activitat_form($this, $activitat);

        if ($form->is_cancelled()) {
            redirect($this->url('veure_activitats'));
        } else if ($data = $form->get_data()) {
            $form->save_files($data, $activitat->id);
            $activitat->update((array) $data);
            $activitat->save();
            redirect($this->url('veure_activitats'));
        }

        echo $this->output->formulari_activitat($form);
    }
}

class suprimir_activitat_view extends quadern_view {

    function __construct() {
        $id = required_param('activitat_id', PARAM_INT);

        parent::__construct();

        if (!$this->es_admin()) {
            print_error('nopermissiontoshow');
        }

        $activitat = $this->quadern->activitat($id);

        if ($activitat->assignada($id)) {
            redirect($this->url('veure_activitats'));
        }

        if (optional_param('confirm', false, PARAM_BOOL)) {
            require_sesskey();
            $activitat->delete();
            $this->delete_files('descripcio_activitat', $id);
            redirect($this->url('veure_activitats'));
        }

        echo $this->output->confirmacio_suprimir_activitat($activitat);
    }
}

class veure_alumnes_view extends quadern_view {

    function __construct() {
        parent::__construct();

        $groupid = optional_param('group', 0, PARAM_INT);

        list($alumnes, $users, $groups) = $this->index_alumnes($groupid);

        if (count($alumnes) > 1 or $groupid or $this->es_admin()) {
            echo $this->output->pagina_index_alumnes(
                $alumnes, $users, $groups, $groupid);
        } else if (count($alumnes) == 1) {
            $alumne_id = key($alumnes);
            redirect($this->url(
                'veure_alumne', array('alumne_id' => $alumne_id)));
        } else {
            print_error('nopermissiontoshow');
        }
    }
}

class veure_alumne_view extends alumne_view {

    function __construct() {
        parent::__construct();

        $date = usergetdate(time());
        $today = make_timestamp($date['year'], $date['mon'], $date['mday']);

        $accio = 'veure_dades';
        $faseactual = false;

        foreach (array_keys($this->config->fases) as $num) {
            $fase = $this->alumne->fase($num);
            if ($fase->data_inici and $today >= $fase->data_inici) {
                $accio = 'veure_calendari';
                $faseactual = $num;
            }
            if ($fase->data_final and $today > $fase->data_final) {
                $accio = 'veure_qualificacio';
                $faseactual = false;
            }
        }

        if ($this->es_professor()) {
            $this->alumne->acces_professor = time();
            $this->alumne->save();
        }
        redirect($this->url_alumne(
            $accio, array('fase' => $faseactual)));
    }
}

class veure_dades_view extends alumne_view {

    function __construct() {
        parent::__construct();

        echo $this->output->dades();
    }

    function tutors_inscrits() {
        return get_enrolled_users($this->context, 'mod/fpdquadern:tutor');
    }
}

abstract class editar_dades_view extends alumne_view {

    function __construct() {
        $permisfunc = static::$permisfunc;
        $outputfunc = static::$outputfunc;
        $formclass = __NAMESPACE__ . '\\' . static::$formclass;

        parent::__construct();

        if (!$this->$permisfunc()) {
            print_error('nopermissiontoshow');
        }

        $form = new $formclass($this, true);

        if ($form->is_cancelled()) {
            redirect($this->url_alumne('veure_dades'));
        } else if ($data = $form->get_data()) {
            $this->alumne->update((array) $data);
            $this->alumne->save();
            $this->avisar_professor();
            redirect($this->url_alumne('veure_dades'));
        }

        echo $this->output->$outputfunc($form);
    }
}

class editar_dades_alumne_view extends editar_dades_view {

    static $permisfunc = 'permis_editar_dades_alumne';
    static $formclass = 'dades_alumne_form';
    static $outputfunc = 'formulari_dades_alumne';
}

class editar_dades_professor_view extends editar_dades_view {

    static $permisfunc = 'permis_editar_professor';
    static $formclass = 'dades_professor_form';
    static $outputfunc = 'formulari_dades_professor';

    function professors_inscrits() {
        return get_enrolled_users($this->context, 'mod/fpdquadern:professor');
    }
}

class editar_dades_centre_view extends editar_dades_view {

    static $permisfunc = 'permis_editar_dades_centre';
    static $formclass = 'dades_centre_practiques_form';
    static $outputfunc = 'formulari_dades_centre';
}

class editar_dades_tutor_view extends editar_dades_view {

    static $permisfunc = 'permis_editar_dades_tutor';
    static $formclass = 'dades_tutor_form';
    static $outputfunc = 'formulari_dades_tutor';

    function tutors_inscrits() {
        return get_enrolled_users($this->context, 'mod/fpdquadern:tutor');
    }
}

abstract class fase_view extends alumne_view {

    protected $fase;

    function __construct(array $urlparams=null) {
        $this->fase = required_param('fase', PARAM_INT);

        $urlparams = $urlparams ?: array();
        $urlparams['fase'] = $this->fase;

        parent::__construct($urlparams);

        if (!isset($this->config->fases[$this->fase])) {
            print_error('nopermissiontoshow');
        }
    }

    function url_fase($accio, array $params=null) {
        $params = $params ?: array();
        $params['fase'] = $this->fase;
        return $this->url_alumne($accio, $params);
    }
}

class veure_calendari_view extends fase_view {

    function __construct() {
        parent::__construct();

        echo $this->output->calendari($this->alumne->fase($this->fase));
    }
}

class editar_calendari_view extends fase_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_editar_calendari($this->fase)) {
            print_error('nopermissiontoshow');
        }

        $fase = $this->alumne->fase($this->fase);
        $form = new calendari_form($this, $fase, true);

        if ($form->is_cancelled()) {
            redirect($this->url_fase('veure_calendari'));
        } else if ($data = $form->get_data()) {
            $fase->update((array) $data);
            $fase->calendari_acceptat = false;
            $fase->save();
            $this->avisar_professor();
            redirect($this->url_fase('veure_calendari'));
        }

        echo $this->output->formulari_calendari($this->fase, $form);
    }
}

class acceptar_calendari_view extends fase_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_acceptar_calendari($this->fase)) {
            print_error('nopermissiontoshow');
        }

        require_sesskey();

        $fase = $this->alumne->fase($this->fase);
        $fase->calendari_acceptat = true;
        $fase->save();
        $this->avisar_professor();

        redirect($this->url_fase('veure_calendari'));
    }
}

class afegir_seguiment_view extends fase_view {

    function __construct() {
        parent::__construct();

        $dia = $this->database->create('seguiment', array(
            'quadern_id' => $this->quadern->id,
            'alumne_id' => $this->alumne->alumne_id,
            'fase' => $this->fase,
        ));

        $form = new seguiment_form($this, $dia);

        if ($form->is_cancelled()) {
            redirect($this->url_fase('veure_calendari'));
        } else if ($data = $form->get_data()) {
            $dia->update((array) $data);
            $dia->save();
            $this->avisar_professor();
            redirect($this->url_fase('veure_calendari'));
        }

        echo $this->output->formulari_seguiment($this->fase, $form);
    }
}

class editar_seguiment_view extends alumne_view {

    function __construct() {
        $dia_id = required_param('dia_id', PARAM_INT);

        parent::__construct(array('dia_id' => $dia_id));

        $dia = $this->alumne->dia_seguiment($dia_id);

        if (!$this->permis_editar_seguiment($dia)) {
            print_error('nopermissiontoshow');
        }

        $form = new seguiment_form($this, $dia);

        if ($form->is_cancelled()) {
            redirect($this->url_alumne(
                'veure_calendari', array('fase' => $dia->fase)));
        } else if ($data = $form->get_data()) {
            $dia->update((array) $data);
            $dia->save();
            $this->avisar_professor();
            redirect($this->url_alumne(
                'veure_calendari', array('fase' => $dia->fase)));
        }

        echo $this->output->formulari_seguiment($dia->fase, $form);
    }
}

class suprimir_seguiment_view extends alumne_view {

    function __construct() {
        $dia_id = required_param('dia_id', PARAM_INT);
        $confirm = optional_param('confirm', false, PARAM_BOOL);

        parent::__construct(array('dia_id' => $dia_id));

        $dia = $this->alumne->dia_seguiment($dia_id);

        if (!$this->permis_editar_seguiment($dia)) {
            print_error('nopermissiontoshow');
        }

        if ($confirm) {
            require_sesskey();
            $dia->delete();
            $this->avisar_professor();
            redirect($this->url_alumne(
                'veure_calendari', array('fase' => $dia->fase)));
        }

        echo $this->output->confirmacio_suprimir_seguiment($dia);
    }
}

class validar_seguiment_view extends alumne_view {

    function __construct() {
        $dia_id = required_param('dia_id', PARAM_INT);

        parent::__construct(array('dia_id' => $dia_id));
        $dia = $this->alumne->dia_seguiment($dia_id);

        if (!$this->permis_validar_seguiment($dia)) {
            print_error('nopermissiontoshow');
        }

        require_sesskey();

        $dia->validat = true;
        $dia->save();
        $this->avisar_professor();

        redirect($this->url_alumne(
            'veure_calendari', array('fase' => $dia->fase)));
    }
}

class validar_seguiment_ajax extends alumne_view {

    function __construct() {
        $dia_id = required_param('dia_id', PARAM_INT);

        parent::__construct(array('dia_id' => $dia_id));
        $dia = $this->alumne->dia_seguiment($dia_id);

        if (!$this->permis_validar_seguiment($dia)) {
            print_error('nopermissiontoshow');
        }

        require_sesskey();

        $dia->validat = true;
        $dia->save();
        $this->avisar_professor();

        echo json_encode(array('ok' => true));
    }
}

class veure_activitats_alumne_view extends fase_view {

    function __construct() {
        parent::__construct();

        echo $this->output->activitats_alumne($this->fase);
    }
}

class seleccionar_activitats_view extends fase_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_seleccionar_activitats()) {
            print_error('nopermissiontoshow');
        }

        $activitats = $this->quadern->activitats($this->fase);

        $form = new activitats_alumne_form($this, $activitats);

        if ($form->is_cancelled()) {
            redirect($this->url_fase('veure_activitats_alumne'));
        } else if ($data = $form->get_data()) {
            foreach ($activitats as $a) {
                $assignada = $a->assignada($this->alumne->alumne_id);
                if ($data->{"activitat_{$a->id}"}) {
                    if (!$assignada) {
                        $valoracio = $this->database->create('valoracio', array(
                            'quadern_id' => $this->quadern->id,
                            'alumne_id' => $this->alumne->alumne_id,
                            'activitat_id' => $a->id,
                        ));
                        $valoracio->save();
                    }
                } else {
                    if ($assignada) {
                        $valoracio = $this->alumne->valoracio($a->id);
                        $valoracio->delete();
                    }
                }
            }
            $this->avisar_professor();
            redirect($this->url_fase('veure_activitats_alumne'));
        }

        echo $this->output->formulari_activitats_alumne($this->fase, $form);
    }
}

abstract class activitat_view extends alumne_view {

    protected $activitat;

    function __construct(array $urlparams=null) {
        $activitat_id = required_param('activitat_id', PARAM_INT);

        $urlparams = $urlparams ?: array();
        $urlparams['activitat_id'] = $activitat_id;

        parent::__construct($urlparams);

        $this->activitat = $this->quadern->activitat($activitat_id);
    }

    function url_activitat($accio, array $params=null) {
        $params = $params ?: array();
        $params['activitat_id'] = $this->activitat->id;
        return $this->url_alumne($accio, $params);
    }

    function url_fase($accio, array $params=null) {
        $params = $params ?: array();
        $params['fase'] = $this->activitat->fase;
        return $this->url_alumne($accio, $params);
    }
}

class editar_valoracio_view extends activitat_view {

    function __construct() {
        parent::__construct();

        $valoracio = $this->alumne->valoracio($this->activitat->id);

        if (!$this->permis_editar_valoracio($valoracio, $this->activitat)) {
            print_error('nopermissiontoshow');
        }

        $form = new valoracio_form($this, $this->activitat, $valoracio, true);

        if ($form->is_cancelled()) {
            redirect($this->url_fase('veure_activitats_alumne'));
        } else if ($data = $form->get_data()) {
            $form->save_files($data, $valoracio->id);
            $valoracio->update((array) $data);
            if ($this->es_professor() and $valoracio->valorada_professor()) {
                $valoracio->data_valoracio_professor = time();
            }
            if ($this->es_tutor() and $valoracio->valorada_tutor()) {
                $valoracio->data_valoracio_tutor = time();
            }
            $valoracio->save();
            $this->avisar_professor();
            redirect($this->url_fase('veure_activitats_alumne'));
        }

        echo $this->output->formulari_valoracio($this->activitat, $form);
    }
}

class afegir_activitat_complementaria_view extends fase_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_afegir_activitat_complementaria()) {
            print_error('nopermissiontoshow');
        }

        $activitat = $this->database->create('activitat', array(
            'quadern_id' => $this->quadern->id,
            'alumne_id' => $this->alumne->alumne_id,
            'fase' => $this->fase,
        ));

        $form = new activitat_complementaria_form($this, $activitat);

        if ($form->is_cancelled()) {
            redirect($this->url_fase('veure_activitats_alumne'));
        } else if ($data = $form->get_data()) {
            $activitat->update((array) $data);
            $activitat->save();
            $form->save_files($data, $activitat->id);
            $activitat->update((array) $data);
            $activitat->save();
            $valoracio = $this->database->create('valoracio', array(
                'quadern_id' => $this->quadern->id,
                'alumne_id' => $this->alumne->alumne_id,
                'activitat_id' => $activitat->id,
            ));
            $valoracio->save();
            $this->avisar_professor();
            redirect($this->url_fase('veure_activitats_alumne'));
        }

        echo $this->output->formulari_activitat_complementaria(
            $this->fase, $form);
    }
}

class editar_activitat_complementaria_view extends activitat_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_editar_activitat_complementaria($this->activitat)) {
            print_error('nopermissiontoshow');
        }

        $form = new activitat_complementaria_form($this, $this->activitat);

        if ($form->is_cancelled()) {
            redirect($this->url_fase('veure_activitats_alumne'));
        } else if ($data = $form->get_data()) {
            $form->save_files($data, $this->activitat->id);
            $this->activitat->update((array) $data);
            $this->activitat->acceptada = false;
            $this->activitat->save();
            $this->avisar_professor();
            redirect($this->url_fase('veure_activitats_alumne'));
        }

        echo $this->output->formulari_activitat_complementaria(
            $this->activitat->fase, $form);
    }
}

class suprimir_activitat_complementaria_view extends activitat_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_editar_activitat_complementaria($this->activitat)) {
            print_error('nopermissiontoshow');
        }

        if (optional_param('confirm', false, PARAM_BOOL)) {
            require_sesskey();

            $id = $this->activitat->id;

            $this->activitat->delete();
            $valoracio = $this->alumne->valoracio($id);
            $valoracio->delete();

            $this->delete_files('descripcio_activitat', $id);
            $this->delete_files('valoracio_activitat_alumne', $id);
            $this->delete_files('valoracio_activitat_professor', $id);
            $this->delete_files('valoracio_activitat_tutor', $id);

            $this->avisar_professor();

            redirect($this->url_fase('veure_activitats_alumne'));
        }

        echo $this->output->confirmacio_suprimir_activitat_complementaria(
            $this->activitat);
    }
}

class acceptar_activitat_complementaria_view extends activitat_view {

    function __construct() {
        parent::__construct();
        if (!$this->permis_acceptar_activitat_complementaria($this->activitat)) {
            print_error('nopermissiontoshow');
        }

        require_sesskey();
        $this->activitat->acceptada = true;
        $this->activitat->save();
        $this->avisar_professor();

        redirect($this->url_fase('veure_activitats_alumne'));
    }
}

class veure_qualificacio_view extends alumne_view {

    function __construct() {
        parent::__construct();

        echo $this->output->qualificacions_finals();
    }
}

class editar_qualificacio_view extends alumne_view {

    function __construct() {
        parent::__construct();

        if (!$this->permis_editar_qualificacio()) {
            print_error('nopermissiontoshow');
        }

        $form = new qualificacio_form($this, true);

        if ($form->is_cancelled()) {
            redirect($this->url_alumne('veure_qualificacio'));
        } else if ($data = $form->get_data()) {
            foreach (array_keys($this->config->fases) as $num) {
                $fase = $this->alumne->fase($num);
                $fase->qualificacio = $data->{"qualificacio_$num"};
                $fase->save();
            }
            $this->alumne->qualificacio = $data->qualificacio_final;
            $this->alumne->save();
            $this->avisar_professor();

            redirect($this->url_alumne('veure_qualificacio'));
        }

        echo $this->output->formulari_qualificacio($form);
    }
}

class veure_accions_pendents_view extends alumne_view {

    function __construct() {
        $rol = optional_param('rol', false, PARAM_ALPHA);

        parent::__construct();

        echo $this->output->pagina_accions_pendents(
            $rol, $this->accions_pendents());
    }
}
