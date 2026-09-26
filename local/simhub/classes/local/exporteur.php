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
 * Exports tabulaires (§12.3) : CSV, XLSX et ODS.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;

/**
 * Construction des tableaux de suivi et envoi dans le format demandé.
 */
class exporteur {
    /** Formats proposés : clé du paramètre => format Moodle (null : CSV maison). */
    const FORMATS = ['csv' => null, 'xlsx' => 'excel', 'ods' => 'ods'];

    /**
     * Envoie le tableau au navigateur puis termine la requête.
     *
     * Le CSV reste au séparateur « ; » avec BOM UTF-8 : c'est ce qu'Excel en français ouvre
     * directement avec les accents. XLSX et ODS passent par l'API dataformat de Moodle.
     *
     * @param string $nom Nom du fichier, sans extension.
     * @param string $format Clé de FORMATS.
     * @param array $entetes
     * @param array $lignes
     * @return void
     */
    public static function envoyer(string $nom, string $format, array $entetes, array $lignes): void {
        $dataformat = self::FORMATS[$format] ?? null;
        if ($dataformat) {
            \core\dataformat::download_data($nom, $dataformat, $entetes, new \ArrayIterator($lignes));
            exit;
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nom . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        foreach (array_merge([$entetes], $lignes) as $ligne) {
            fputcsv($out, $ligne, ';', '"', '');
        }
        fclose($out);
        exit;
    }

    /**
     * Libellé du statut personnel d'un étudiant sur un atelier.
     *
     * @param int $userid
     * @param int $atelierid
     * @return string
     */
    protected static function statut(int $userid, int $atelierid): string {
        return get_string('statutperso_' . parcours_helper::statut_atelier($userid, $atelierid), 'local_simhub');
    }

    /**
     * Tableau étudiants × ateliers, avec l'avancement.
     *
     * @param array $users Enregistrements utilisateur.
     * @param int[] $atelierids
     * @param callable $avancement fn(int $userid): int pourcentage.
     * @return array [entêtes, lignes]
     */
    public static function tableau_suivi(array $users, array $atelierids, callable $avancement): array {
        $entetes = [get_string('lastname'), get_string('firstname'), get_string('email')];
        foreach ($atelierids as $aid) {
            $a = new atelier($aid);
            $entetes[] = $a->get('numero') . ' ' . $a->get('nomcourt');
        }
        $entetes[] = get_string('export_avancement', 'local_simhub');

        $lignes = [];
        foreach ($users as $user) {
            $ligne = [$user->lastname, $user->firstname, $user->email ?? ''];
            foreach ($atelierids as $aid) {
                $ligne[] = self::statut((int) $user->id, (int) $aid);
            }
            $ligne[] = $avancement((int) $user->id);
            $lignes[] = $ligne;
        }
        return [$entetes, $lignes];
    }

    /**
     * Suivi d'une UC : inscrits du cours (hors enseignants) et ateliers rattachés au cours.
     *
     * @param int $courseid
     * @return array [entêtes, lignes]
     */
    public static function suivi_uc(int $courseid): array {
        global $DB;

        $coursecontext = \context_course::instance($courseid);
        $atelierids = array_map('intval', $DB->get_fieldset_select(
            'local_simhub_rattachement',
            'DISTINCT atelierid',
            'courseid = ?',
            [$courseid]
        ));
        $users = array_filter(
            get_enrolled_users($coursecontext, '', 0, 'u.*', 'u.lastname, u.firstname', 0, 0, true),
            fn($u) => !has_capability('moodle/course:manageactivities', $coursecontext, $u)
        );
        return self::tableau_suivi($users, $atelierids, fn($uid) => self::pct($uid, $atelierids));
    }

    /**
     * Suivi d'une cohorte : ses membres, les ateliers qui lui sont rattachés et ceux des
     * parcours qui lui sont destinés.
     *
     * @param int $cohortid
     * @return array [entêtes, lignes]
     */
    public static function suivi_cohorte(int $cohortid): array {
        global $DB;

        $atelierids = $DB->get_fieldset_select('local_simhub_rattachement', 'DISTINCT atelierid', 'cohortid = ?', [$cohortid]);
        foreach (parcours::get_records(['cohortid' => $cohortid]) as $p) {
            $atelierids = array_merge($atelierids, array_column($p->get_ateliers(), 'atelierid'));
        }
        $atelierids = array_values(array_unique(array_map('intval', $atelierids)));
        $users = $DB->get_records_sql(
            "SELECT u.* FROM {user} u JOIN {cohort_members} cm ON cm.userid = u.id
              WHERE cm.cohortid = ? AND u.deleted = 0 ORDER BY u.lastname, u.firstname",
            [$cohortid]
        );
        return self::tableau_suivi($users, $atelierids, fn($uid) => self::pct($uid, $atelierids));
    }

    /**
     * Historique d'un étudiant : une ligne par séance.
     *
     * @param int $userid
     * @return array [entêtes, lignes]
     */
    public static function historique_etudiant(int $userid): array {
        global $DB;

        $format = get_string('strftimedatetimeshort', 'langconfig');
        $entetes = [
            get_string('champ_numero', 'local_simhub'),
            get_string('champ_nomcourt', 'local_simhub'),
            get_string('session_demarree_le', 'local_simhub'),
            get_string('export_fin', 'local_simhub'),
            get_string('champ_statut', 'local_simhub'),
            get_string('export_validation', 'local_simhub'),
        ];
        $lignes = [];
        foreach (\local_simhub\persistent\session::get_pour_etudiant($userid) as $s) {
            $a = new atelier($s->get('atelierid'));
            $val = $DB->get_records('local_simhub_val_encadrant', ['sessionid' => $s->get('id')], 'datevalidation DESC', '*', 0, 1);
            $val = reset($val);
            $lignes[] = [
                $a->get('numero'),
                $a->get('nomcourt'),
                userdate($s->get('timestart'), $format),
                $s->get('timeend') ? userdate($s->get('timeend'), $format) : '',
                get_string('session_statut_' . $s->get('statut'), 'local_simhub'),
                $val ? get_string('session_val_' . $val->statut, 'local_simhub') . ' — ' . userdate($val->datevalidation, $format)
                    : '',
            ];
        }
        return [$entetes, $lignes];
    }

    /**
     * Pourcentage d'ateliers réalisés ou validés.
     *
     * @param int $userid
     * @param int[] $atelierids
     * @return int
     */
    protected static function pct(int $userid, array $atelierids): int {
        if (!$atelierids) {
            return 0;
        }
        $faits = array_filter(
            $atelierids,
            fn($aid) => in_array(parcours_helper::statut_atelier($userid, $aid), ['realise', 'valide'], true)
        );
        return (int) round(100 * count($faits) / count($atelierids));
    }

    /**
     * Liens de téléchargement dans chaque format.
     *
     * @param array $params Paramètres de manage/export.php, hors format.
     * @return string HTML
     */
    public static function liens(array $params): string {
        $liens = [];
        foreach (array_keys(self::FORMATS) as $format) {
            $liens[] = \html_writer::link(
                new \moodle_url('/local/simhub/manage/export.php', $params + ['format' => $format]),
                get_string('export_format_' . $format, 'local_simhub'),
                ['class' => 'btn btn-sm btn-outline-secondary mr-1 me-1']
            );
        }
        return \html_writer::span(get_string('export_telecharger', 'local_simhub') . ' ', 'mr-1 me-1') . implode('', $liens);
    }
}
