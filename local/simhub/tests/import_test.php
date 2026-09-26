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
 * Imports CSV et XLSX (§12.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub;

use local_simhub\local\atelier_importer;
use local_simhub\local\liaison_importer;
use local_simhub\local\tableur;
use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;

/**
 * Tests des imports.
 *
 * @covers \local_simhub\local\tableur
 * @covers \local_simhub\local\liaison_importer
 */
#[\PHPUnit\Framework\Attributes\CoversClass(tableur::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(liaison_importer::class)]
final class import_test extends \advanced_testcase {
    /**
     * Écrit un classeur XLSX.
     *
     * @param array $lignes
     * @return string Chemin du fichier.
     */
    protected function xlsx(array $lignes): string {
        $chemin = make_request_directory() . '/test.xlsx';
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($chemin);
        foreach ($lignes as $ligne) {
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($ligne));
        }
        $writer->close();
        return $chemin;
    }

    /**
     * Import des ateliers depuis un XLSX, puis de leur localisation et de leurs ressources.
     *
     * @return void
     */
    public function test_xlsx_localisation_ressources(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $csv = tableur::vers_csv($this->xlsx([
            ['Numéro', 'Nom', 'Durée (min)', 'Catégorie'],
            ['A01', 'Suture', 20, 'Gestes de base'],
            ['A02', "Pose de\ncathéter", 15, ''],
        ]), 'ateliers.xlsx', ';');
        $res = atelier_importer::importer($csv, ';', 'ENVX');
        $this->assertEquals(2, $res['crees'], implode(' ', $res['erreurs']));
        $a1 = atelier::get_record(['numero' => 'A01']);
        $this->assertEquals(20, $a1->get('dureeindicative'));
        $this->assertEquals('Gestes de base', $a1->get('categorie'));

        $res = liaison_importer::importer_localisation("numero;salle;poste\nA01;Salle 2;P7\nZZ;Salle 1;P1\n", ';', 'ENVX');
        $this->assertEquals(1, $res['majs']);
        $this->assertCount(1, $res['erreurs'], 'Atelier inconnu signalé');
        $a1 = atelier::get_record(['numero' => 'A01']);
        $this->assertEquals('Salle 2', $a1->get('salle'));
        $this->assertEquals('P7', $a1->get('codeposte'));

        $contenu = "numero;titre;url;type;visibilite\n"
            . "A01;Vidéo suture;https://exemple.org/v;Vidéo;etudiant\n"
            . "A01;Source Word;https://exemple.org/s.docx;source_editable;etudiant\n"
            . "A01;Sans lien;;video;\n";
        $res = liaison_importer::importer_ressources($contenu, ';', 'ENVX');
        $this->assertEquals(2, $res['crees']);
        $this->assertCount(1, $res['erreurs']);
        $source = ressource::get_record(['titre' => 'Source Word']);
        $this->assertEquals(ressource::VISIBILITE_INTERNE, $source->get('visibilite'), 'Source éditable toujours interne');
        $this->assertEquals('video', ressource::get_record(['titre' => 'Vidéo suture'])->get('type'));

        $res = liaison_importer::importer_ressources($contenu, ';', 'ENVX');
        $this->assertEquals(2, $res['majs'], 'Réimport sans doublon');
    }

    /**
     * Rattachement par nom abrégé de cours et atelier retrouvé malgré un autre code établissement.
     *
     * @return void
     */
    public function test_rattachement_par_nom_abrege(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $cours = $this->getDataGenerator()->create_course(['shortname' => 'UC-CHIR-A2']);
        $a = new atelier(0, (object) ['numero' => 'B01', 'nomcourt' => 'Bandage', 'statut' => 'actif', 'envcode' => 'ENVA']);
        $a->create();

        $res = liaison_importer::importer_rattachements("numero;uc;obligatoire\nB01;UC-CHIR-A2;oui\n", ';', 'AUTRE');
        $this->assertEquals(1, $res['crees'], implode(' ', $res['erreurs']));
        $this->assertTrue($DB->record_exists(
            'local_simhub_rattachement',
            ['atelierid' => $a->get('id'), 'courseid' => $cours->id]
        ));

        $res = liaison_importer::importer_parcours("parcours;numero;obligatoire\nChirurgie A2;B01;x\n", ';', 'AUTRE');
        $this->assertEquals(1, $res['parcourscrees']);
        $this->assertEquals(1, $DB->get_field('local_simhub_parc_atelier', 'obligatoire', ['atelierid' => $a->get('id')]));
    }
}
