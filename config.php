<?php
/**
 * @package mod_fpdquadern
 * @copyright 2013 Institut Obert de Catalunya
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Albert Gasset <albert@ioc.cat>
 */

namespace mod_fpdquadern;

defined('MOODLE_INTERNAL') || die();

class config {

    function __construct() {

        $this->centre_estudis = (object) array(
            'nom' => "Institut Obert de Catalunya",
            'codi' => "08045203",
            'adreca' => "Av. del Paral·lel, 71 08029 Barcelona",
        );

        $this->fases = array(
            1 => "Pràcticum I",
            2 => "Pràcticum II",
            3 => "Pràcticum III",
        );

        $this->escala_avaluacio_professor = array(
            0 => '',
            1 => 'Negativa',
            2 => 'Baixa',
            3 => 'Correcta',
            4 => 'Bona',
            5 => 'Molt bona',
        );

        $this->escala_grau_assoliment = array(
            0 => '',
            1 => 'No assolit',
            2 => 'Baix',
            3 => 'Correcte',
            4 => 'Bo',
            5 => 'Molt bo',
        );

        $this->especialitats_docents = array(
            0 => '',
            601 => "601 Cuina i pastisseria",
            603 => "603 Estètica",
            604 => "604 Fabricació i instal·lació de fusteria i mobles",
            606 => "606 Instal·lacions electrotècniques",
            609 => "609 Manteniment de vehicles",
            611 => "611 Mecanització i manteniment de màquines",
            612 => "612 Oficina de projectes de construcció",
            617 => "617 Patronatge i confecció",
            618 => "618 Perruqueria",
            623 => "623 Producció en arts gràfiques",
            626 => "626 Serveis de restauració",
            628 => "628 Soldadures",
            999 => "Ensenyaments esportius",
        );

        $this->titols_equivalents = array(
            '' => array(0 => ''),
            "Cuina i pastisseria" => array(
                60101 => "Tècnic superior en restauració",
                60102 => "Tècnic especialista en hostaleria",
            ),
            "Estètica" => array(
                60301 => "Tècnic superior en estètica",
                60302 => "Tècnic especialista en estètica",
            ),
            "Fabricació i instal·lació de fusteria i mobles" => array(
                60401 => "Tècnic superior en producció de fusta i moble",
                60402 => "Tècnic superior en desenvolupament de productes en fusteria i moble",
                60403 => "Tècnic especialista en construcció industrial de fusta",
                60404 => "Tècnic especialista ebenista",
                60405 => "Tècnic especialista en fusta",
                60406 => "Tècnic especialista modelista de fosa",
                60407 => "Tècnic especialista en disseny i fabricació de mobles",
            ),
            "Manteniment vehicles" => array(
                60901 => "Tècnic superior en automoció",
                60902 => "Tècnic especialista en mecànica i electricitat de l’automòbil",
                60903 => "Tècnic especialista en automoció",
                60904 => "Tècnic especialista en manteniment de màquines i equips de construcció i obres",
            ),
            "Mecanitazació i manteniment de màquines" => array(
                61101 => "Tècnic superior en producció per mecanització",
                61102 => "Tècnic especialista en muntatge i construcció de maquinària",
                61103 => "Tècnic especialista en micromecànica de màquines eines",
                61104 => "Tècnic especialista en micromecànica d’instruments",
                61105 => "Tècnic especialista instrumentista en sistemes de mesura",
                61106 => "Tècnic especialista en utillatges i muntatges mecànics",
                61107 => "Tècnic especialista mecànic d’armes",
                61108 => "Tècnic especialista en fabricació mecànica",
                61109 => "Tècnic especialista en màquines eines",
                61110 => "Tècnic especialista en matriceria i motllos",
                61111 => "Tècnic especialista en control de qualitat",
                61112 => "Tècnic especialista en micromecànica i rellotgeria",
            ),
            "Patronantge i confecció" => array(
                61701 => "Tècnic superior en processos de confecció industrial",
                61702 => "Tècnic superior en patronatge",
                61703 => "Tècnic especialista en confecció industrial de peces exteriors",
                61704 => "Tècnic especialista en confecció industrial de peces interiors",
                61705 => "Tècnic especialista en confecció a mida de senyora",
                61706 => "Tècnic especialista en producció en indústries de la confecció",
                61707 => "Tècnic especialista en sastreria i modisteria",
                61708 => "Tècnic especialista en confecció de teixits",
            ),
            "Perruqueria" => array(
                61801 => "Tècnic superior en assessoria d’imatge personal",
                61802 => "Tècnic especialista en perruqueria",
            ),
            "Producció en arts gràfiques" => array(
                62301 => "Tècnic superior en producció en indústries d’arts gràfiques",
                62302 => "Tècnic especialista en composició",
                62303 => "Tècnic especialista en enquadernació",
                62304 => "Tècnic especialista en impressió",
                62305 => "Tècnic especialista en processos gràfics",
                62306 => "Tècnic especialista en reproducció fotomecànica",
                62307 => "Tècnic especialista en composició d’arts gràfiques",
            ),
            "Serveis de restauració" => array(
                62601 => "Tècnic superior en restauració",
                62602 => "Tècnic especialista en hostaleria",
            ),
            "Soldadura" => array(
                62801 => "Tècnic superior en construccions metàl·liques",
                62802 => "Tècnic especialista en construccions metàl·liques i soldador",
                62803 => "Tècnic especialista en soldadura",
                62804 => "Tècnic especialista en fabricació soldada",
                62805 => "Tècnic especialista en caldereria en xapa estructural",
                62806 => "Tècnic especialista en construcció naval",
                62807 => "Tècnic especialista traçador naval",
            ),
            "Ensenyaments esportius" => array(
                99901 => "Tècnic esportiu superior – Atletisme",
                99902 => "Tècnic esportiu – Busseig esportiu amb escafandre autònom",
                99903 => "Tècnic esportiu – Espeleologia",
                99904 => "Tècnic esportiu superior – Hípica",
                99905 => "Tècnic esportiu superior – Vela amb aparell lliure",
                99906 => "Tècnic esportiu superior – Handbol",
                99907 => "Tècnic esportiu superior – Salvament i socorrisme",
                99908 => "Tècnic esportiu superior – Basquetbol",
                99909 => "Tècnic esportiu – Esgrima",
                99910 => "Tècnic esportiu – Judo i defensa personal",
                99911 => "Tècnic esportiu superior – Esquí alpí",
                99912 => "Tècnic esportiu superior – Esquí de fons",
                99913 => "Tècnic esportiu superior – Surf de neu",
                99914 => "Tècnic esportiu superior – Futbol",
                99915 => "Tècnic esportiu superior – Futbol sala",
                99916 => "Tècnic esportiu superior – Alta muntanya",
                99917 => "Tècnic esportiu superior – Escalada",
                99918 => "Tècnic esportiu superior – Esquí de muntanya",
            ),
        );

        $this->tipus_centre = array(
            0 => '',
            1 => 'Públic',
            2 => 'Concertat',
            3 => 'Privat',
        );
    }
}
