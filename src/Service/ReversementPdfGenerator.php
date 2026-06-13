<?php

namespace App\Service;

use App\Entity\Reversement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment as TwigEnv;

class ReversementPdfGenerator
{
    private string $projectDir;
    private TwigEnv $twig;
    private Filesystem $fs;

    public function __construct(string $projectDir, TwigEnv $twig, Filesystem $fs)
    {
        $this->projectDir = $projectDir;
        $this->twig       = $twig;
        $this->fs         = $fs;
    }

    /**
     * Génère le bordereau PDF d'un reversement.
     * Retourne le chemin relatif (exploitable avec asset()) ou null en cas d'échec.
     */
    public function generate(Reversement $reversement): string
    {
        // 1. Rendu du template HTML du bordereau
        $html = $this->twig->render('reversement/pdf.html.twig', [
            'reversement' => $reversement,
        ]);

        // 2. Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 3. Création du répertoire de stockage si inexistant
        $dir = $this->projectDir . '/public/uploads/reversements';
        $this->fs->mkdir($dir, 0755);

        // 4. Chemin du fichier PDF
        $filename = 'bordereau_' . $reversement->getId() . '_' . date('Ymd') . '.pdf';
        $relativePath = '/uploads/reversements/' . $filename;
        $fullPath     = $this->projectDir . '/public' . $relativePath;

        // 5. Écriture du fichier sur le disque
        file_put_contents($fullPath, $dompdf->output());

        return $relativePath; // chemin utilisé par asset()
    }
}
