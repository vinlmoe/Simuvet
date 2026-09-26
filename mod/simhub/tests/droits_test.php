<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Droits limités à l'UC, notes et catégorie SimHub.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simhub;

use local_simhub\local\contexte;
use local_simhub\local\droits;
use local_simhub\local\parcours_helper;
use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\persistent\session;
use local_simhub\record\val_encadrant;

/**
 * Tests des droits d'UC et du calcul des notes.
 *
 * @covers \local_simhub\local\droits
 * @covers \local_simhub\local\parcours_helper
 * @covers \mod_simhub\observer
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_simhub\local\droits::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_simhub\local\parcours_helper::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_simhub\observer::class)]
final class droits_test extends \advanced_testcase {
    /** @var \stdClass[] Utilisateurs du scénario. */
    protected $u = [];

    /** @var \stdClass[] Cours des UC A et B. */
    protected $uc = [];

    /** @var \stdClass[] Instances d'activité des UC A et B. */
    protected $act = [];

    /** @var atelier[] Ateliers 1 et 2. */
    protected $at = [];

    /**
     * Deux UC, deux ateliers : l'atelier 1 est dans les deux UC, l'atelier 2 seulement dans l'UC A.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        require_once($CFG->dirroot . '/mod/simhub/lib.php');
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        foreach (['resp', 'ens', 'respb', 'etu', 'etub'] as $nom) {
            $this->u[$nom] = $gen->create_user();
        }
        $this->uc['a'] = $gen->create_course();
        $this->uc['b'] = $gen->create_course();
        $gen->enrol_user($this->u['resp']->id, $this->uc['a']->id, 'editingteacher');
        $gen->enrol_user($this->u['ens']->id, $this->uc['a']->id, 'teacher');
        $gen->enrol_user($this->u['respb']->id, $this->uc['b']->id, 'editingteacher');
        $gen->enrol_user($this->u['etu']->id, $this->uc['a']->id, 'student');
        $gen->enrol_user($this->u['etu']->id, $this->uc['b']->id, 'student');
        $gen->enrol_user($this->u['etub']->id, $this->uc['b']->id, 'student');

        $this->setAdminUser();
        foreach ([1, 2] as $n) {
            $this->at[$n] = new atelier(0, (object) ['numero' => 'T' . $n, 'nomcourt' => 'Atelier ' . $n,
                'statut' => atelier::STATUT_ACTIF, 'envcode' => '']);
            $this->at[$n]->create();
        }
        $this->act['a'] = $gen->create_module('simhub', ['course' => $this->uc['a']->id, 'grade' => 100]);
        $this->act['b'] = $gen->create_module('simhub', ['course' => $this->uc['b']->id, 'grade' => 20]);

        $pa = $this->parcours('a');
        parcours_helper::ajouter_atelier($pa, $this->at[1]->get('id'), 1, true, null);
        parcours_helper::ajouter_atelier($pa, $this->at[2]->get('id'), 2, true, null);
        parcours_helper::ajouter_atelier($this->parcours('b'), $this->at[1]->get('id'), 1, false, null);
    }

    /**
     * Parcours d'une UC.
     *
     * @param string $uc 'a' ou 'b'.
     * @return parcours
     */
    protected function parcours(string $uc): parcours {
        global $DB;
        return new parcours($DB->get_field('simhub', 'parcoursid', ['id' => $this->act[$uc]->id]));
    }

    /**
     * Note d'un étudiant dans une UC.
     *
     * @param string $uc
     * @param int $userid
     * @return float|null
     */
    protected function note(string $uc, int $userid): ?float {
        $grades = grade_get_grades($this->uc[$uc]->id, 'mod', 'simhub', $this->act[$uc]->id, $userid);
        $grade = $grades->items[0]->grades[$userid]->grade;
        return $grade === null ? null : (float) $grade;
    }

    /**
     * Le parcours de l'UC est lié au cours et à l'activité, ses ateliers sont rattachés au cours.
     *
     * @return void
     */
    public function test_parcours_de_l_uc(): void {
        global $DB;
        $pa = $this->parcours('a');
        $this->assertEquals($this->uc['a']->id, $pa->get('courseid'));
        $this->assertEquals($this->act['a']->cmid, $pa->get('cmid'));
        $this->assertTrue($DB->record_exists(
            'local_simhub_rattachement',
            ['courseid' => $this->uc['a']->id, 'atelierid' => $this->at[2]->get('id')]
        ));
    }

    /**
     * Un enseignant n'a de droits que sur son UC.
     *
     * @return void
     */
    public function test_droits_limites_a_l_uc(): void {
        $this->setUser($this->u['resp']);
        $this->assertTrue(droits::peut_gerer_parcours($this->parcours('a')));
        $this->assertFalse(droits::peut_gerer_parcours($this->parcours('b')));
        $this->assertTrue(droits::peut_editer_grille($this->at[2]->get('id')));

        $this->setUser($this->u['ens']);
        $this->assertFalse(droits::peut_editer_grille($this->at[1]->get('id')), 'Enseignant non éditeur');
        $this->assertTrue(droits::peut_suivre_parcours($this->parcours('a')));
        $this->assertFalse(droits::peut_suivre_parcours($this->parcours('b')));
        $this->assertTrue(droits::peut_valider_seance($this->u['etu']->id, $this->at[1]->get('id')));
        $this->assertFalse(droits::peut_valider_seance($this->u['etub']->id, $this->at[1]->get('id')), 'Étudiant hors UC');
        $this->assertTrue(droits::peut_valider_asv($this->u['etu']->id));
        $this->assertFalse(droits::peut_valider_asv($this->u['etub']->id));
        $vus = array_keys(droits::etudiants_du_parcours($this->parcours('a')));
        $this->assertEquals([(int) $this->u['etu']->id], array_map('intval', $vus));

        $this->setUser($this->u['respb']);
        $this->assertFalse(droits::peut_editer_grille($this->at[2]->get('id')), 'Atelier absent de l\'UC B');
    }

    /**
     * Une séance, sans choix d'UC, met à jour la note de toutes les UC qui contiennent l'atelier.
     *
     * @return void
     */
    public function test_note_dans_toutes_les_uc(): void {
        $this->setUser($this->u['etu']);
        $s = session::demarrer_ou_reprendre($this->u['etu']->id, $this->at[1]->get('id'));
        $s->terminer();
        \local_simhub\event\session_completed::create(['objectid' => $s->get('id'), 'context' => contexte::racine()])
            ->trigger();

        $this->assertEqualsWithDelta(50, $this->note('a', $this->u['etu']->id), 0.01);
        $this->assertEqualsWithDelta(20, $this->note('b', $this->u['etu']->id), 0.01);

        $this->setUser($this->u['ens']);
        parcours_helper::valider_seance($s->get('id'), $this->u['ens']->id, val_encadrant::STATUT_VALIDE);
        $this->assertEquals(session::STATUT_CERTIFIE, (new session($s->get('id')))->get('statut'));

        $this->setUser($this->u['resp']);
        parcours_helper::retirer_atelier($this->parcours('a'), $this->at[2]->get('id'));
        $this->assertEqualsWithDelta(100, $this->note('a', $this->u['etu']->id), 0.01);
    }

    /**
     * Rôles SimHub attribués dans la catégorie et délégation à l'administrateur fonctionnel.
     *
     * @return void
     */
    public function test_categorie_simhub(): void {
        global $DB;
        $cat = $this->getDataGenerator()->create_category();
        set_config('categoryid', $cat->id, 'local_simhub');
        $catctx = \context_coursecat::instance($cat->id);
        $this->assertEquals($catctx->id, contexte::racine()->id);

        $adminf = $this->getDataGenerator()->create_user();
        $gest = $this->getDataGenerator()->create_user();
        $roleid = fn($s) => $DB->get_field('role', 'id', ['shortname' => $s]);
        role_assign($roleid('simhubadminfonctionnel'), $adminf->id, $catctx->id);

        $this->setUser($adminf);
        $this->assertTrue(has_capability('moodle/role:assign', $catctx));
        $this->assertFalse(has_capability('moodle/role:assign', \context_system::instance()));
        [$assignables] = get_assignable_roles($catctx, ROLENAME_SHORT, true);
        $this->assertArrayHasKey($roleid('simhubgestionnairesalle'), $assignables);
        $this->assertArrayNotHasKey($roleid('manager'), $assignables);

        role_assign($roleid('simhubencadrant'), $gest->id, $catctx->id);
        $this->setUser($gest);
        $this->assertTrue(droits::peut_valider_asv($this->u['etub']->id), 'Formateur désigné : tous les étudiants');
        $this->assertFalse(has_capability('local/simhub:validateasvsimulation', \context_system::instance()));
    }

    /**
     * La duplication (sauvegarde puis restauration) recrée le parcours de l'UC et ses ateliers.
     *
     * @return void
     */
    public function test_sauvegarde_restauration(): void {
        global $DB;
        $cm = get_coursemodule_from_id('simhub', $this->act['a']->cmid);
        $nouveau = duplicate_module($this->uc['a'], $cm);

        $instance = $DB->get_record('simhub', ['id' => $nouveau->instance], '*', MUST_EXIST);
        $parcours = new parcours($instance->parcoursid);
        $this->assertNotEquals($this->parcours('a')->get('id'), $parcours->get('id'));
        $this->assertEquals($nouveau->id, $parcours->get('cmid'));
        $this->assertEqualsCanonicalizing(
            [(int) $this->at[1]->get('id'), (int) $this->at[2]->get('id')],
            array_map('intval', array_column($parcours->get_ateliers(), 'atelierid'))
        );
    }

    /**
     * La suppression de l'activité supprime son parcours.
     *
     * @return void
     */
    public function test_suppression(): void {
        global $DB;
        $parcoursid = $this->parcours('b')->get('id');
        course_delete_module($this->act['b']->cmid);
        $this->assertFalse($DB->record_exists('local_simhub_parcours', ['id' => $parcoursid]));
    }

    /**
     * L'activité est achevée quand l'étudiant a réalisé tous les ateliers requis de l'UC.
     *
     * @return void
     */
    public function test_achevement(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($this->u['etu']->id, $course->id, 'student');
        $act = $this->getDataGenerator()->create_module('simhub', ['course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionparcours' => 1]);
        $parcours = new parcours($DB->get_field('simhub', 'parcoursid', ['id' => $act->id]));
        parcours_helper::ajouter_atelier($parcours, $this->at[1]->get('id'), 1, true, null);

        $cm = get_fast_modinfo($course)->get_cm($act->cmid);
        $completion = new \completion_info($course);
        $etat = fn() => $completion->get_data($cm, false, $this->u['etu']->id)->completionstate;
        $this->assertEquals(COMPLETION_INCOMPLETE, $etat());

        $this->setUser($this->u['etu']);
        $s = session::demarrer_ou_reprendre($this->u['etu']->id, $this->at[1]->get('id'));
        $s->terminer();
        \local_simhub\event\session_completed::create(['objectid' => $s->get('id'), 'context' => contexte::racine()])
            ->trigger();
        $this->assertEquals(COMPLETION_COMPLETE, $etat());
    }
}
