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
 * Tests du fournisseur de confidentialité.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;

/**
 * Export et suppression des données SimHub d'un étudiant et d'un membre du personnel.
 *
 * @covers \local_simhub\privacy\provider
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var \stdClass Étudiant. */
    protected $etudiant;

    /** @var \stdClass Gestionnaire référent de l'atelier. */
    protected $referent;

    /** @var atelier Atelier du scénario. */
    protected $atelier;

    /**
     * Un étudiant avec une séance, un référent d'atelier.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->etudiant = $this->getDataGenerator()->create_user();
        $this->referent = $this->getDataGenerator()->create_user();

        $this->setUser($this->referent);
        $this->atelier = new atelier(0, (object) ['numero' => 'P1', 'nomcourt' => 'Atelier', 'statut' => atelier::STATUT_ACTIF,
            'envcode' => '', 'referentuserid' => $this->referent->id]);
        $this->atelier->create();

        $this->setUser($this->etudiant);
        session::demarrer($this->etudiant->id, $this->atelier->get('id'))->terminer();
    }

    /**
     * Contextes et utilisateurs trouvés.
     *
     * @return void
     */
    public function test_contextes_et_utilisateurs(): void {
        $system = \context_system::instance();
        $this->assertEquals([$system->id], provider::get_contexts_for_userid($this->etudiant->id)->get_contextids());
        $this->assertEquals([$system->id], provider::get_contexts_for_userid($this->referent->id)->get_contextids());

        $userlist = new userlist($system, 'local_simhub');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing(
            [(int) $this->etudiant->id, (int) $this->referent->id],
            array_map('intval', $userlist->get_userids())
        );
    }

    /**
     * Export des séances de l'étudiant et des références du personnel.
     *
     * @return void
     */
    public function test_export(): void {
        $system = \context_system::instance();
        $this->export_context_data_for_user($this->etudiant->id, $system, 'local_simhub');
        $data = writer::with_context($system)->get_data([get_string('privacy:metadata:local_simhub_session', 'local_simhub')]);
        $this->assertCount(1, $data->sessions);

        writer::reset();
        $this->export_context_data_for_user($this->referent->id, $system, 'local_simhub');
        $data = writer::with_context($system)->get_data([get_string('privacy:metadata:personnel', 'local_simhub')]);
        $this->assertArrayHasKey('local_simhub_atelier', (array) $data->references);
    }

    /**
     * La suppression efface les séances et la référence, mais conserve la fiche atelier.
     *
     * @return void
     */
    public function test_suppression(): void {
        global $DB;
        $system = \context_system::instance();
        provider::delete_data_for_user(new approved_contextlist($this->etudiant, 'local_simhub', [$system->id]));
        $this->assertFalse($DB->record_exists('local_simhub_session', ['userid' => $this->etudiant->id]));

        provider::delete_data_for_users(new approved_userlist($system, 'local_simhub', [$this->referent->id]));
        $fiche = $DB->get_record('local_simhub_atelier', ['id' => $this->atelier->get('id')], '*', MUST_EXIST);
        $this->assertNull($fiche->referentuserid);
        $this->assertEquals(0, $fiche->usermodified);
    }
}
