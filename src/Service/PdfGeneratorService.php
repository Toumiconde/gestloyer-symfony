<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PdfGeneratorService
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    /**
     * Génère un PDF à partir d'une chaîne HTML et retourne le contenu binaire
     */
    public function generateBinaryPdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Pour charger les images depuis une URL ou absolute path
        $options->set('chroot', $this->projectDir . '/public'); // Autoriser l'accès aux assets locaux

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Génère un PDF et le sauvegarde dans un fichier
     */
    public function generateAndSavePdf(string $html, string $filename, string $directory): string
    {
        $output = $this->generateBinaryPdf($html);
        
        $path = $this->projectDir . '/public/' . $directory;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $fullPath = $path . '/' . $filename;
        file_put_contents($fullPath, $output);

        return $fullPath;
    }
}
