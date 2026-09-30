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
 * Validation ASV sur animal vivant : pas d'auto-validation (§9.3).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub;

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valanimal;
use local_simhub\record\asv_valsim;

/**
 * Le lien part chez le validateur, jamais chez l'étudiant, et l'étudiant ne se valide pas lui-même.
 *
 * @covers \local_simhub\record\asv_valanimal
 * @covers \local_simhub\record\asv_valsim
 */
#[\PHPUnit\Framework\Attributes\CoversClass(asv_valanimal::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(asv_valsim::class)]
final class asv_valanimal_test extends \advanced_testcase {
    /** @var string Signature factice acceptée par asv_valanimal::signature_valide(). */
    const SIGNATURE = 'data:image/png;base64,iVBORw0KGgo'
        . 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    /**
     * Crée un acte ASV.
     *
     * @return asv_acte
     */
    protected function acte(): asv_acte {
        $acte = new asv_acte(0, (object) ['code' => 'INJ', 'nom' => 'Injection IM', 'niveau' => 'A1', 'envcode' => '']);
        $acte->create();
        return $acte;
    }

    /**
     * Le lien est envoyé par e-mail au validateur et une nouvelle adresse invalide l'ancien lien.
     */
    public function test_lien_envoye_au_validateur(): void {
        $this->resetAfterTest();
        $etudiant = $this->getDataGenerator()->create_user(['email' => 'etu@example.com']);
        $acteid = $this->acte()->get('id');
        $sink = $this->redirectEmails();

        $demande = asv_valanimal::creer_demande($etudiant->id, $acteid, 'veto@example.com');
        $this->assertTrue($demande->envoye);
        $this->assertSame('veto@example.com', $demande->emailvalidateur);
        $mails = $sink->get_messages();
        $this->assertCount(1, $mails);
        $this->assertSame('veto@example.com', $mails[0]->to);
        $this->assertStringContainsString($demande->token, $mails[0]->body);

        // Une seule demande active : la nouvelle adresse remplace l'ancien lien.
        $nouvelle = asv_valanimal::creer_demande($etudiant->id, $acteid, 'autre@example.com');
        $this->assertFalse(asv_valanimal::get_par_token($demande->token));
        $this->assertEquals($nouvelle->id, asv_valanimal::get_demande_en_attente($etudiant->id, $acteid)->id);
        $sink->close();
    }

    /**
     * L'étudiant ne peut pas s'envoyer le lien à lui-même.
     */
    public function test_adresse_etudiant_refusee(): void {
        $this->resetAfterTest();
        $etudiant = $this->getDataGenerator()->create_user(['email' => 'etu@example.com']);
        $acteid = $this->acte()->get('id');

        $this->assertFalse(asv_valanimal::email_acceptable($etudiant->id, ' ETU@example.com '));
        $this->assertFalse(asv_valanimal::email_acceptable($etudiant->id, 'pas-une-adresse'));
        $this->assertTrue(asv_valanimal::email_acceptable($etudiant->id, 'veto@example.com'));

        $this->expectException(\invalid_parameter_exception::class);
        asv_valanimal::creer_demande($etudiant->id, $acteid, 'etu@example.com');
    }

    /**
     * Connecté avec son compte, l'étudiant ne peut pas valider sa propre demande ; le validateur externe, si.
     */
    public function test_autovalidation_refusee(): void {
        $this->resetAfterTest();
        $etudiant = $this->getDataGenerator()->create_user(['email' => 'etu@example.com']);
        $acteid = $this->acte()->get('id');
        $this->redirectEmails();
        $demande = asv_valanimal::creer_demande($etudiant->id, $acteid, 'veto@example.com');

        $this->assertFalse(asv_valanimal::valider($demande->token, 'Etu', 'Emma', true, self::SIGNATURE, (int) $etudiant->id));
        $this->assertFalse(asv_valanimal::est_valide($etudiant->id, $acteid));

        $this->assertTrue(asv_valanimal::valider($demande->token, 'Veto', 'Vincent', true, self::SIGNATURE));
        // Signée, la validation n'est acquise qu'après le contrôle interne.
        $this->assertFalse(asv_valanimal::est_valide($etudiant->id, $acteid));
        $this->assertNotNull(asv_valanimal::controler($demande->id, get_admin()->id, true));
        $this->assertTrue(asv_valanimal::est_valide($etudiant->id, $acteid));
    }

    /**
     * Contrôle interne : un rejet n'est pas une validation, une signature ne se contrôle
     * qu'une fois, et le lien groupé passe aussi par ce contrôle.
     */
    public function test_controle_interne(): void {
        $this->resetAfterTest();
        $etudiant = $this->getDataGenerator()->create_user(['email' => 'etu@example.com', 'lastname' => 'Martin']);
        $autre = $this->getDataGenerator()->create_user();
        $acteid = $this->acte()->get('id');
        $this->redirectEmails();
        $demande = asv_valanimal::creer_demande($etudiant->id, $acteid, 'veto@example.com');

        $this->assertTrue(asv_valanimal::valider($demande->token, 'Martin', 'Paul', true, self::SIGNATURE));
        $a_controler = asv_valanimal::get_a_controler();
        $this->assertArrayHasKey($demande->id, $a_controler);
        $this->assertContains(
            get_string('asv_indice_nom', 'local_simhub'),
            asv_valanimal::indices($a_controler[$demande->id], \core_user::get_user($etudiant->id))
        );

        $rejet = asv_valanimal::controler($demande->id, get_admin()->id, false, 'Signataire douteux');
        $this->assertEquals(asv_valanimal::STATUT_REJETE, $rejet->statut);
        $this->assertNull(asv_valanimal::controler($demande->id, get_admin()->id, true), 'Déjà contrôlée');
        $this->assertFalse(asv_valanimal::est_valide($etudiant->id, $acteid));

        // Lien groupé : pas d'e-mail, même contrôle ; l'étudiant connecté ne signe pas pour lui-même.
        $lot = asv_valanimal::creer_lot($acteid, [$etudiant->id, $autre->id]);
        $ids = array_keys(asv_valanimal::get_lot($lot->lottoken));
        $this->assertCount(2, $ids);
        $signees = asv_valanimal::valider_lot($lot->lottoken, $ids, 'Veto', 'Vincent', true, self::SIGNATURE, (int) $etudiant->id);
        $this->assertCount(1, $signees, 'Seule la demande de l\'autre étudiant est signée');
        $this->assertEquals($autre->id, reset($signees)->userid);
        $this->assertFalse(asv_valanimal::est_valide($autre->id, $acteid));
        asv_valanimal::controler(reset($signees)->id, get_admin()->id, true);
        $this->assertTrue(asv_valanimal::est_valide($autre->id, $acteid));
    }

    /**
     * Un encadrant ne valide pas sa propre simulation.
     */
    public function test_autovalidation_simulation_refusee(): void {
        $this->resetAfterTest();
        $encadrant = $this->getDataGenerator()->create_user();
        $etudiant = $this->getDataGenerator()->create_user();
        $acteid = $this->acte()->get('id');

        $this->assertNotEmpty(asv_valsim::valider($etudiant->id, $acteid, $encadrant->id));

        $this->expectException(\moodle_exception::class);
        asv_valsim::valider($encadrant->id, $acteid, $encadrant->id);
    }
}
