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
 * Code de séance (§7.3).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub;

use local_simhub\record\seancecode;

/**
 * Tests du code de séance.
 *
 * @covers \local_simhub\record\seancecode
 */
#[\PHPUnit\Framework\Attributes\CoversClass(seancecode::class)]
final class seancecode_test extends \advanced_testcase {
    /**
     * Un code fraîchement généré est valable, quelles que soient la casse et les espaces
     * de la salle ou du code saisi.
     */
    public function test_code_genere_valable(): void {
        $this->resetAfterTest();

        $code = seancecode::generer('Salle 2', 2);
        $this->assertSame(seancecode::VALIDE, seancecode::verifier('Salle 2', $code->code));
        $this->assertSame(seancecode::VALIDE, seancecode::verifier(' salle  2 ', ' ' . strtolower($code->code)));
    }

    /**
     * Les refus donnent leur raison : code inconnu, expiré ou généré pour une autre salle.
     */
    public function test_raisons_de_refus(): void {
        $this->resetAfterTest();

        $this->assertSame(seancecode::INCONNU, seancecode::verifier('Salle 2', 'ZZZZZZ'));
        $this->assertSame(seancecode::INCONNU, seancecode::verifier('Salle 2', ''));

        $expire = seancecode::generer('Salle 2', 2, -1);
        $this->assertSame(seancecode::EXPIRE, seancecode::verifier('Salle 2', $expire->code));

        $autre = seancecode::generer('Salle 3', 2);
        $this->assertSame(seancecode::AUTRE_SALLE, seancecode::verifier('Salle 2', $autre->code));
        $this->assertSame(seancecode::AUTRE_SALLE, seancecode::verifier(null, $autre->code));
        $this->assertArrayHasKey($autre->id, seancecode::get_en_cours());
        $this->assertArrayNotHasKey($expire->id, seancecode::get_en_cours());
    }

    /**
     * Le code n'est demandé que si le contrôle est activé, hors du réseau de la salle.
     */
    public function test_est_requis(): void {
        $this->resetAfterTest();

        set_config('controlepresenceactif', 0, 'local_simhub');
        $this->assertFalse(seancecode::est_requis());
        set_config('controlepresenceactif', 1, 'local_simhub');
        $this->assertTrue(seancecode::est_requis());
    }
}
