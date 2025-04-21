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
    $paper_title = $_POST['paper_title'] ?? '';
    $authors_names = $_POST['authors_names'] ?? '';
    $corresponding_author_email = $_POST['corresponding_author_email'] ?? '';
    $corresponding_phone_number = $_POST['corresponding_phone_number'] ?? '';
    $corresponding_affiliation = $_POST['corresponding_affiliation'] ?? '';
    $country = $_POST['country'] ?? '';
    $to = 'n.abdelhamid@adsm.ac.ae';
    // $to = 'nawwar.mkh@gmail.com';

    // Load the current paperID from a JSON file
    $jsonFile = 'paperID.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $data = json_decode($jsonData, true);
        $paperID = $data['paperID'] ?? 10; // Default to 10 if the file is empty or invalid
    } else {
        $paperID = 10; // Default starting paperID
    }

    // File upload handling
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Check for file upload errors
    if ($fileError === 0) {
        // Define upload directory
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileDestination = $uploadDir . $fileName;

        // Move uploaded file to destination
        move_uploaded_file($fileTmpName, $fileDestination);

        // Generate PDF using FPDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        $pdf->Cell(0, 10, 'ICAIMT 2025 Short Paper Submission Details', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(50, 10, 'Paper ID:', 0, 0);
        $pdf->Cell(0, 10, $paperID, 0, 1);

        $pdf->Cell(50, 10, 'Paper Title:', 0, 0);
        $pdf->Cell(0, 10, $paper_title, 0, 1);

        $pdf->Cell(50, 10, 'Authors:', 0, 0);
        $pdf->MultiCell(0, 10, $authors_names, 0, 'L');

        $pdf->Cell(50, 10, 'Corresponding Email:', 0, 0);
        $pdf->Cell(0, 10, $corresponding_author_email, 0, 1);

        $pdf->Cell(50, 10, 'Corresponding Phone:', 0, 0);
        $pdf->Cell(0, 10, $corresponding_phone_number, 0, 1);

        $pdf->Cell(50, 10, 'Corresponding Affiliation:', 0, 0);
        $pdf->Cell(0, 10, $corresponding_affiliation, 0, 1);

        $pdf->Cell(50, 10, 'Country:', 0, 0);
        $pdf->Cell(0, 10, $country, 0, 1);

        $pdf->Cell(50, 10, 'Uploaded File:', 0, 0);
        $pdf->Cell(0, 10, $fileName, 0, 1);

        // Save PDF to a temporary file
        $pdfFilePath = 'short_paper_submission_' . date('Y_m_d_h_i_s') . '.pdf';
        $pdf->Output('F', $pdfFilePath);

        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $host; // Replace with your SMTP server from statics.php
            $mail->SMTPAuth = true;
            $mail->Username = $username; // Replace with your email from statics.php
            $mail->Password = $password; // Replace with your email password from statics.php
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom($username, 'ICAIMT 2025');
            $mail->addAddress($corresponding_author_email); // Send to the submitter
            $mail->addAddress($to); // Send to the conference chair

            // Attachments
            $mail->addAttachment($pdfFilePath); // Attach the PDF
            $mail->addAttachment($fileDestination); // Attach the uploaded file

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Short Paper Submission Confirmation for ICAIMT2025';
            $mail->Body = "
                Dear Author,<br><br>
                Thank you for submitting your short paper to ICAIMT 2025. We have received your submission and will begin the review process shortly.<br><br>
                <strong>Paper ID:</strong> $paperID<br>
                <strong>Paper Title:</strong> $paper_title<br><br>
                If any additional information is required, we will reach out to you.<br><br>
                Best Regards,<br>
                Dr Neda<br>
                Local Conference Chair<br>
                ICAIMT2025<br>
                Organized by Abu Dhabi School of Management, Abu Dhabi UAE
            ";

            $mail->send();

            // Increment the paperID by 1
            $paperID++;

            $data = ['paperID' => $paperID];
            file_put_contents($jsonFile, json_encode($data));
            echo 1;
        } catch (Exception $e) {
            echo 0;
        }

        // Delete temporary PDF file
        unlink($pdfFilePath);
    } else {
        echo 0;
    }
}
?>