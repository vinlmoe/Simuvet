<?php

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Aide à la construction des en-têtes de documents PDF SimHub (attestations, livret ASV,
 * fiches ateliers), avec le logo et le nom de l'établissement paramétrés dans
 * settings.php (§4 "Paramétrable ENVF") plutôt qu'un habillage générique SimHub.
 */
class pdf_helper {

    /**
     * Contenu binaire du logo configuré, si présent et dans un format que TCPDF peut
     * intégrer directement (PNG/JPEG). Un logo SVG peut être téléversé (accepté par le
     * réglage) mais n'est pas rasterisé ici : TCPDF ne l'intègre pas nativement via
     * Image(), donc l'en-tête retombe alors sur le nom de l'établissement seul.
     *
     * @return string|null
     */
    public static function get_logo_content(): ?string {
        $fs = get_file_storage();
        $context = \context_system::instance();

        $files = $fs->get_area_files($context->id, 'local_simhub', 'logo', 0, 'filepath, filename', false);
        $file = reset($files);
        if (!$file) {
            return null;
        }

        $mimetype = $file->get_mimetype();
        if (!in_array($mimetype, ['image/png', 'image/jpeg'], true)) {
            return null;
        }

        return $file->get_content();
    }

    /**
     * Nom de l'établissement configuré, ou une valeur par défaut si non renseigné.
     *
     * @return string
     */
    public static function get_etablissement_nom(): string {
        return get_config('local_simhub', 'etablissementnom') ?: get_string('pluginname', 'local_simhub');
    }

    /**
     * Ajoute l'en-tête standard (logo si disponible + nom de l'établissement) en haut de la
     * page PDF courante, et positionne le curseur juste en dessous pour la suite du contenu.
     *
     * @param \pdf $pdf
     * @return void
     */
    public static function ajouter_entete(\pdf $pdf): void {
        $logo = self::get_logo_content();
        $top = $pdf->GetY();

        if ($logo !== null) {
            $pdf->Image('@' . $logo, 15, $top, 25, 0, '', '', '', true);
            $pdf->SetXY(45, $top);
        }

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, self::get_etablissement_nom(), 0, 1, 'R');
        $pdf->SetY(max($top + 22, $pdf->GetY() + 4));
    }

    /**
     * Construit l'attestation de certification ASV d'un niveau pour un étudiant (§9.4).
     * N'importe rien sur l'éligibilité : appelant responsable de n'appeler ceci qu'une fois
     * asv_certification_helper::get_actes_manquants() vérifiée vide, aussi bien pour la
     * génération individuelle (asv/attestation_pdf.php) que groupée
     * (manage/asv_attestations.php), qui partagent donc ce même document.
     *
     * @param \stdClass $user
     * @param string $niveau
     * @param \local_simhub\persistent\asv_acte[] $actes Actes du niveau, déjà tous validés.
     * @return \pdf
     */
    public static function construire_attestation_asv(\stdClass $user, string $niveau, array $actes): \pdf {
        $pdf = new \pdf();
        $pdf->SetCreator('SimHub');
        $pdf->SetTitle('Certification ' . $niveau . ' — ' . fullname($user));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        self::ajouter_entete($pdf);

        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 12, 'Attestation de certification', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 13);
        $pdf->Cell(0, 8, 'Actes vétérinaires délégables ASV — niveau ' . $niveau, 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML(
            '<p>' . self::get_etablissement_nom() . ' certifie que</p>'
            . '<p style="text-align:center;font-size:15pt;"><b>' . s(fullname($user)) . '</b></p>'
            . '<p>a validé, en simulation puis sur animal vivant, l\'ensemble des actes vétérinaires '
            . 'délégables du niveau ' . s($niveau) . ' listés ci-dessous :</p>',
            true,
            false,
            true,
            false,
            ''
        );

        $html = '<table border="1" cellpadding="4"><tr style="font-weight:bold;">'
            . '<th width="70%">Acte</th><th width="30%">Espèce</th></tr>';
        foreach ($actes as $acte) {
            $html .= '<tr><td>' . s($acte->get('nom')) . '</td><td>' . s($acte->get('espece')) . '</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(10);
        $pdf->Cell(0, 6, 'Délivrée le ' . userdate(time(), get_string('strftimedate', 'langconfig')), 0, 1);

        return $pdf;
    }
}
