<?php
/**
 * Simple PDF Generator Helper
 * Generates PDFs from HTML templates
 */

class PDFHelper {

    /**
     * Generate PDF from HTML
     * @param string $html HTML content
     * @param string $filename Output filename
     * @param bool $download Force download (true) or display (false)
     * @return mixed PDF content or output
     */
    public static function generateFromHTML($html, $filename = 'document.pdf', $download = true) {
        // For basic implementation, we'll convert HTML to PDF using FPDF-like approach
        // In production, you should use TCPDF or mPDF library

        // For now, let's generate a simple text-based PDF
        // This is a placeholder - implement proper PDF generation with TCPDF/mPDF

        $pdf_content = self::simpleHTMLtoPDF($html);

        if ($download) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf_content));
            echo $pdf_content;
            exit;
        }

        return $pdf_content;
    }

    /**
     * Generate Fee Receipt PDF
     * @param array $data Receipt data
     * @param bool $download Force download (true) or display (false)
     */
    public static function generateFeeReceipt($data, $download = true) {
        $html = self::loadTemplate('fee_receipt_pdf', $data);
        $filename = 'Fee_Receipt_' . $data['receipt_no'] . '.pdf';
        return self::generateFromHTML($html, $filename, $download);
    }

    /**
     * Load PDF template
     * @param string $template Template name
     * @param array $data Data to pass to template
     * @return string HTML content
     */
    private static function loadTemplate($template, $data) {
        $templatePath = __DIR__ . '/../pdf_templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            error_log("PDF template not found: $templatePath");
            return '<html><body><h1>Template not found</h1></body></html>';
        }

        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Simple HTML to PDF converter (basic implementation)
     * Note: This is a very basic implementation. For production use TCPDF or mPDF
     * @param string $html HTML content
     * @return string PDF content
     */
    private static function simpleHTMLtoPDF($html) {
        // This is a placeholder
        // In production, use:
        // require_once('tcpdf/tcpdf.php');
        // $pdf = new TCPDF();
        // $pdf->AddPage();
        // $pdf->writeHTML($html);
        // return $pdf->Output('', 'S');

        // For now, return HTML with PDF mime type (will display as HTML in browser)
        // To use real PDF generation, install TCPDF or mPDF library
        return $html;
    }

    /**
     * Generate multiple receipts as single PDF
     * @param array $receipts Array of receipt data
     * @param string $filename Output filename
     */
    public static function generateBulkReceipts($receipts, $filename = 'bulk_receipts.pdf') {
        $html = '<html><body>';

        foreach ($receipts as $index => $receipt) {
            $html .= self::loadTemplate('fee_receipt_pdf', $receipt);
            if ($index < count($receipts) - 1) {
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }

        $html .= '</body></html>';
        return self::generateFromHTML($html, $filename, true);
    }
}

/**
 * INSTALLATION INSTRUCTIONS FOR TCPDF:
 *
 * Option 1: Download TCPDF manually
 * 1. Download from: https://github.com/tecnickcom/TCPDF/releases
 * 2. Extract to: /includes/tcpdf/
 * 3. Replace simpleHTMLtoPDF() with:
 *    require_once(__DIR__ . '/tcpdf/tcpdf.php');
 *    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
 *    $pdf->SetCreator('Fee Management System');
 *    $pdf->SetAuthor('School');
 *    $pdf->SetTitle('Fee Receipt');
 *    $pdf->SetMargins(15, 15, 15);
 *    $pdf->AddPage();
 *    $pdf->writeHTML($html, true, false, true, false, '');
 *    return $pdf->Output('', 'S');
 *
 * Option 2: Use Composer (if available)
 * composer require tecnickcom/tcpdf
 * require_once 'vendor/autoload.php';
 */
?>
