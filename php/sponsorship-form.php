<?php

require '../vendor/setasign/fpdf/fpdf.php';
require 'statics.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Capture form data
    $typeOfSponsorship = $_POST['Type_of_sponsorship'];
    $companyName = $_POST['Company_name'];
    $companyAddress = $_POST['Company_address'];
    $city = $_POST['City'];
    $contactName = $_POST['Name'];
    $email = $_POST['Email'];
    $phone = $_POST['Mobile'];

    // Generate PDF using FPDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    $pdf->Cell(0, 10, '#ICAIMT2025 Registration Details of Sposorship Form', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 10, 'Type of Sponsorship:', 0, 0);
    $pdf->Cell(0, 10, $typeOfSponsorship, 0, 1);

    $pdf->Cell(50, 10, 'Company Name:', 0, 0);
    $pdf->Cell(0, 10, $companyName, 0, 1);

    $pdf->Cell(50, 10, 'Company Address:', 0, 0);
    $pdf->Cell(0, 10, $companyAddress, 0, 1);

    $pdf->Cell(50, 10, 'City:', 0, 0);
    $pdf->Cell(0, 10, $city, 0, 1);

    $pdf->Cell(50, 10, 'Contact Name:', 0, 0);
    $pdf->Cell(0, 10, $contactName, 0, 1);

    $pdf->Cell(50, 10, 'Email:', 0, 0);
    $pdf->Cell(0, 10, $email, 0, 1);

    $pdf->Cell(50, 10, 'Phone:', 0, 0);
    $pdf->Cell(0, 10, $phone, 0, 1);

    // Save PDF to a temporary file
    $pdfFilePath = 'registration_sposorship_details_' . date('Y_m_d_h_i_s') . '.pdf';
    $pdf->Output('F', $pdfFilePath);

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = $host; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = $username; // Replace with your email
        $mail->Password = $password; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom($mail->Username, 'Sposorship Registration');
        $mail->addAddress($to); // Replace with recipient's email

        // Attachments
        $mail->addAttachment($pdfFilePath);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Sposorship Registration Details for ICAIMT2025';
        $mail->Body = 'Please find the attached sposorship registration details.';

        $mail->send();
        echo 1;
    } catch (Exception $e) {
        echo 0;
    }

    // Delete temporary PDF file
    unlink($pdfFilePath);
}
?>