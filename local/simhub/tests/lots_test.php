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
 * Filtres étudiants, séances trop courtes, réseau de la salle et exports.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub;

use local_simhub\local\exporteur;
use local_simhub\local\parcours_helper;
use local_simhub\local\reseau;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\rattachement;
use local_simhub\record\val_encadrant;

/**
 * Tests des lots 4 à 8.
 *
 * @covers \local_simhub\local\exporteur
 * @covers \local_simhub\local\reseau
 * @covers \local_simhub\persistent\session
 */
#[\PHPUnit\Framework\Attributes\CoversClass(exporteur::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(reseau::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(session::class)]
final class lots_test extends \advanced_testcase {
    /**
     * Crée un atelier actif de 20 minutes.
     *
     * @param string $numero
     * @return atelier
     */
    protected function atelier(string $numero): atelier {
        $a = new atelier(0, (object) ['numero' => $numero, 'nomcourt' => 'Atelier ' . $numero, 'statut' => atelier::STATUT_ACTIF,
            'envcode' => '', 'dureeindicative' => 20, 'categorie' => 'Gestes de base']);
        $a->create();
        return $a;
    }

    /**
     * Une séance terminée trop vite est signalée, sans être bloquée.
     *
     * @return void
     */
    public function test_seance_trop_courte(): void {
        $this->resetAfterTest();
        $etu = $this->getDataGenerator()->create_user();
        $a = $this->atelier('C1');

        $s = session::demarrer($etu->id, $a->get('id'));
        $s->terminer();
        $this->assertEquals(0, $s->get('dureesuspecte'), 'Sans réglage, rien n\'est signalé');

        set_config('dureeminpct', 30, 'local_simhub');
        $this->assertTrue(session::est_trop_courte($a->get('id'), 5 * MINSECS));
        $this->assertFalse(session::est_trop_courte($a->get('id'), 7 * MINSECS));
        $s = session::demarrer($etu->id, $a->get('id'));
        $s->terminer();
        $this->assertEquals(1, $s->get('dureesuspecte'));
        $this->assertEquals(session::STATUT_REALISE, $s->get('statut'), 'Non bloquant');
        $this->assertStringContainsString('20', parcours_helper::motif_a_valider($s->to_record()));
    }

    /**
     * Un refus remet l'atelier à refaire ; il ne compte plus comme réalisé.
     *
     * @return void
     */
    public function test_refus_remet_a_refaire(): void {
        $this->resetAfterTest();
        $etu = $this->getDataGenerator()->create_user();
        $a = $this->atelier('R1');
        $s = session::demarrer($etu->id, $a->get('id'));
        $s->terminer();
        $this->setAdminUser();
        parcours_helper::valider_seance($s->get('id'), get_admin()->id, val_encadrant::STATUT_REFUSE);
        $this->assertEquals('areprendre', parcours_helper::statut_atelier($etu->id, $a->get('id')));
    }

    /**
     * Reconnaissance des plages d'adresses de la salle.
     *
     * @return void
     */
    public function test_reseau_de_la_salle(): void {
        $this->resetAfterTest();
        $this->assertFalse(reseau::dans_la_salle('192.168.10.5'), 'Non paramétré');
        set_config('reseauxsalle', "192.168.10.0/24\n10.2.3.4-50, 172.16.", 'local_simhub');
        $this->assertTrue(reseau::dans_la_salle('192.168.10.5'));
        $this->assertTrue(reseau::dans_la_salle('10.2.3.40'));
        $this->assertTrue(reseau::dans_la_salle('172.16.8.1'));
        $this->assertFalse(reseau::dans_la_salle('192.168.11.5'));
    }

    /**
     * Filtre de statut personnel et restriction des statuts d'atelier accessibles.
     *
     * @return void
     */
    public function test_filtres(): void {
        $this->resetAfterTest();
        $_GET = ['statutperso' => 'realise', 'statut' => 'archive'];
        $filtre = \local_simhub\local\atelier_filter::from_request();
        $this->assertEquals('realise', $filtre->statutperso);
        $this->assertEquals(\local_simhub\local\atelier_filter::STATUT_VISIBLES, $filtre->statut, 'Archivés refusés');
        $_GET = ['statutperso' => 'nimportequoi'];
        $this->assertEquals('', \local_simhub\local\atelier_filter::from_request()->statutperso);
        $_GET = [];
    }

    /**
     * Exports par UC, par cohorte et par étudiant.
     *
     * @return void
     */
    public function test_exports(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $etu = $gen->create_user(['lastname' => 'Martin']);
        $ens = $gen->create_user();
        $uc = $gen->create_course();
        $gen->enrol_user($etu->id, $uc->id, 'student');
        $gen->enrol_user($ens->id, $uc->id, 'editingteacher');
        $cohorte = $gen->create_cohort();
        cohort_add_member($cohorte->id, $etu->id);
        $a1 = $this->atelier('E1');
        $a2 = $this->atelier('E2');
        rattachement::creer($a1->get('id'), ['courseid' => $uc->id]);
        rattachement::creer($a2->get('id'), ['courseid' => $uc->id, 'cohortid' => $cohorte->id]);
        session::demarrer($etu->id, $a1->get('id'))->terminer();

        [$entetes, $lignes] = exporteur::suivi_uc($uc->id);
        $this->assertCount(1, $lignes, 'L\'enseignant n\'apparaît pas');
        $this->assertEquals('Martin', $lignes[0][0]);
        $this->assertEquals(50, end($lignes[0]));
        $this->assertCount(6, $entetes);

        [, $lignes] = exporteur::suivi_cohorte($cohorte->id);
        $this->assertCount(1, $lignes);
        $this->assertEquals(0, end($lignes[0]), 'Seul l\'atelier E2 concerne la cohorte');

        [, $lignes] = exporteur::historique_etudiant($etu->id);
        $this->assertCount(1, $lignes);
        $this->assertEquals('E1', $lignes[0][0]);
    }
}
