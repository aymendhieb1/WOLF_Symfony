<?php
namespace App\Service;

use TCPDF;

class PdfService
{
    private $tcpdf;

    public function __construct(string $projectDir)
    {
        require_once $projectDir . '/lib/tcpdf/tcpdf.php';
        $this->tcpdf = new \TCPDF();
        $this->tcpdf->SetCreator(PDF_CREATOR);
        $this->tcpdf->SetAuthor('TripToGo');
    }

    public function createPdf(string $html): string
    {
        $this->tcpdf->AddPage();
        $this->tcpdf->writeHTML($html, true, false, true, false, '');
        return $this->tcpdf->Output('', 'S');
    }
}
