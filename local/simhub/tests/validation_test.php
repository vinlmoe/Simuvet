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
 * Listes de validation : séances sans anomalie et rendu des informations utiles.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub;

use local_simhub\local\validation;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;

/**
 * Tests des listes de validation.
 *
 * @covers \local_simhub\local\validation
 */
#[\PHPUnit\Framework\Attributes\CoversClass(validation::class)]
final class validation_test extends \advanced_testcase {
    /**
     * Seules les séances terminées, à présence vérifiée et de durée plausible sont validables
     * d'un clic ; la liste montre la durée réelle face à la durée indicative.
     *
     * @return void
     */
    public function test_seances_sans_anomalie(): void {
        global $DB;
        $this->resetAfterTest();
        $etu = $this->getDataGenerator()->create_user();
        $a = new atelier(0, (object) ['numero' => 'V1', 'nomcourt' => 'Suture', 'statut' => atelier::STATUT_ACTIF,
            'envcode' => '', 'dureeindicative' => 20]);
        $a->create();

        $ok = session::demarrer($etu->id, $a->get('id'));
        $ok->terminer();
        $nonverifiee = session::demarrer($etu->id, $a->get('id'), ['controlepresence' => 'non_verifie']);
        $nonverifiee->terminer();
        $encours = session::demarrer($etu->id, $a->get('id'));

        $sessions = $DB->get_records('local_simhub_session', null, 'id');
        $this->assertEquals([(int) $ok->get('id')], validation::seances_sans_anomalie($sessions));

        $this->setAdminUser();
        $html = validation::seances($sessions, new \moodle_url('/local/simhub/manage/sessions_a_valider.php'));
        $this->assertStringContainsString(get_string('valid_duree_indicative', 'local_simhub', 20), $html);
        $this->assertStringContainsString(get_string('valid_presence_non_verifie', 'local_simhub'), $html);
        $this->assertStringContainsString(get_string('valid_encours', 'local_simhub'), $html);
        $this->assertStringContainsString(get_string('valid_sans_anomalie', 'local_simhub', 1), $html);
        $this->assertNotEmpty($encours->get('id'));
    }
}
