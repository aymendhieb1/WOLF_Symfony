<?php

namespace App\Service;

use App\Entity\ReservationActivite;
use TCPDF;

class PdfGenerator
{
    public function generateReservationPdf(ReservationActivite $reservation): string
    {
        // Create new PDF document with UTF-8 encoding
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('TripToGo');
        $pdf->SetTitle('Reservation Confirmation');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set font subsetting
        $pdf->setFontSubsetting(true);
        
        // Set margins
        $pdf->SetMargins(20, 20, 20);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 20);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font with UTF-8 support
        $pdf->SetFont('dejavusans', '', 12);
        
        try {
            // Logo (check if exists first)
            if (file_exists('public/assets/img/logo.png')) {
                $pdf->Image('public/assets/img/logo.png', 15, 10, 50, '', 'PNG');
            }
            
            // Title
            $pdf->SetFont('dejavusans', 'B', 24);
            $pdf->Cell(0, 30, 'Reservation Confirmation', 0, 1, 'C');
            
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Ln(10);
            
            // Reservation Details
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 10, 'Reservation Details', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Ln(5);
            
            // User Information
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(50, 8, 'Client Email:', 0, 0);
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 8, $this->cleanString($reservation->getUser()->getMail()), 0, 1);
            
            // Activity Information
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(50, 8, 'Activity:', 0, 0);
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 8, $this->cleanString($reservation->getSession()->getActivite()->getNom()), 0, 1);
            
            // Session Information
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(50, 8, 'Date:', 0, 0);
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 8, $reservation->getSession()->getDate()->format('d/m/Y'), 0, 1);
            
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(50, 8, 'Time:', 0, 0);
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 8, $reservation->getSession()->getHeure()->format('H:i'), 0, 1);
            
            // Reservation ID and Date
            $pdf->Ln(10);
            $pdf->SetFont('dejavusans', 'I', 10);
            $pdf->Cell(0, 8, 'Reservation ID: ' . $reservation->getId(), 0, 1);
            $pdf->Cell(0, 8, 'Reservation Date: ' . $reservation->getDateRes()->format('d/m/Y H:i'), 0, 1);
            
            // Terms and Conditions
            $pdf->Ln(15);
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Important Information:', 0, 1);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->MultiCell(0, 6, $this->cleanString('Please arrive 15 minutes before the scheduled time. Don\'t forget to bring valid identification. This reservation is non-transferable.'), 0, 'L');
            
            // Footer with contact information
            $pdf->Ln(15);
            $pdf->SetFont('dejavusans', 'I', 10);
            $pdf->Cell(0, 6, 'For any inquiries, please contact us:', 0, 1, 'C');
            $pdf->Cell(0, 6, 'Email: contact@triptogo.com | Phone: +216 XX XXX XXX', 0, 1, 'C');
            
            // Generate PDF content
            return $pdf->Output('reservation.pdf', 'S');
        } catch (\Exception $e) {
            // Log the error
            error_log('PDF Generation Error: ' . $e->getMessage());
            throw new \RuntimeException('Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Clean string for PDF output
     */
    private function cleanString(string $string): string
    {
        // Remove any non-UTF8 characters and normalize
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        // Convert special characters to HTML entities
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return $string;
    }
} 