<?php
require '../vendor/setasign/fpdf/fpdf.php';
require 'statics.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';


header('Content-Type: application/json');

// Get the data (for jQuery AJAX)
$data = $_POST;

// Or for vanilla JS AJAX with JSON:
// $json = file_get_contents('php://input');
// $data = json_decode($json, true);

// Process the data
$name = isset($data['name']) ? $data['name'] : '';
$email = isset($data['email']) ? $data['email'] : '';
$phone = isset($data['phone']) ? $data['phone'] : '';
$paymentForID = isset($data['paymentForID']) ? $data['paymentForID'] : '';
// Capture form data
$isAuthor = $_POST['IsAuthor'] ?? '';
$paperID = $_POST['EDASPaperReferences'] ?? '';
$title = $_POST['Title'] ?? '';
$firstName = $_POST['FirstName'] ?? '';
$lastName = $_POST['LastName'] ?? '';
$affiliation = $_POST['Affiliation'] ?? '';
$email = $_POST['Email'] ?? '';
$confirmEmail = $_POST['ConfirmEmail'] ?? '';
$mobile = $_POST['Mobile'] ?? '';
$country = $_POST['Country'] ?? '';
$typeOfRegistration = $_POST['TypeOfRegistration'] ?? '';
$ieeeMembershipNumber = $_POST['IEEEMembershipNumber'] ?? '';
$paymentReference = $_POST['paymentReference'] ?? 'N/A';

$curl = curl_init();

$data = '{
            "name": "' . $name . '",
            "email":"' . $email . '",
            "phone": "' . $phone . '"
          }';

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://erp.adsm.ac.ae/event/registration/136/' . $paymentForID,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false, // Disables SSL certificate verification
    CURLOPT_SSL_VERIFYHOST => false, // Disables hostname verification
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => array(
        'X-API-KEY: afc7837a904c72052b3f2f3588a9adf9131c6bb5459984a159ad4f874d1280b2',
        'Accept: application/json',
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);

// Check for cURL errors first
if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
    echo json_encode([
        'success' => false,
        'message' => 'cURL error: ' . $error_msg,
        'http_code' => curl_errno($curl),
        'sent_data' => $data
    ]);
    curl_close($curl);
    exit;
}

// Get HTTP status code
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Check for HTTP errors
if ($http_code >= 400) {
    // Decode response if it's JSON
    $response_data = json_decode($response, true);

    $error_msg = $response_data['message'] ?? 'HTTP Error ' . $http_code;

    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'http_code' => $http_code,
        'sent_data' => $data,
        'response' => $response_data
    ]);
    exit;
}


curl_close($curl);

// Decode the JSON response
$responseData = json_decode($response, true);

// Check if 'result' and 'link' exist
if (isset($responseData['result']['link'])) {

    // Map registration type code to descriptive text
    $registrationTypeMap = [
        '51' => 'Early Regular paper ($395)',
        '52' => 'Early Student paper ($325)',
        '53' => 'Late Registration for regular paper ($495)',
        '54' => 'Late Registration for student paper ($375)',
        '56' => 'Short Paper ($200)',
        '55' => 'Attendee for ($150)',
        '7' => 'Late Attendee for ($175)'
    ];

    $registrationTypeText = $registrationTypeMap[$typeOfRegistration] ?? $typeOfRegistration;

    // Generate PDF using FPDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    $pdf->Cell(0, 10, '#ICAIMT2025 Details of Registration Form', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 10, 'Type of Attendee:', 0, 0);
    $pdf->Cell(0, 10, $isAuthor === 'Yes' ? 'Author with accepted Paper' : 'Non-Author Attendee', 0, 1);

    if ($isAuthor === 'Yes') {
        $pdf->Cell(50, 10, 'Paper ID:', 0, 0);
        $pdf->Cell(0, 10, $paperID, 0, 1);
    }

    $pdf->Cell(50, 10, 'Name:', 0, 0);
    $pdf->Cell(0, 10, $title . ' ' . $firstName . ' ' . $lastName, 0, 1);

    $pdf->Cell(50, 10, 'Affiliation:', 0, 0);
    $pdf->Cell(0, 10, $affiliation, 0, 1);

    $pdf->Cell(50, 10, 'Email:', 0, 0);
    $pdf->Cell(0, 10, $email, 0, 1);

    $pdf->Cell(50, 10, 'Mobile:', 0, 0);
    $pdf->Cell(0, 10, $mobile ?: 'Not provided', 0, 1);

    $pdf->Cell(50, 10, 'Country:', 0, 0);
    $pdf->Cell(0, 10, $country, 0, 1);

    $pdf->Cell(50, 10, 'Type of Registration:', 0, 0);
    $pdf->Cell(0, 10, $registrationTypeText, 0, 1);

    if (!empty($ieeeMembershipNumber)) {
        $pdf->Cell(50, 10, 'IEEE Membership Number:', 0, 0);
        $pdf->Cell(0, 10, $ieeeMembershipNumber, 0, 1);
    }

    $pdf->Cell(50, 10, 'Payment Reference:', 0, 0);
    $pdf->Cell(0, 10, $paymentReference, 0, 1);

    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'ICAIMT2025 Conference Information', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Conference Date: May 21, 2025', 0, 1);
    $pdf->Cell(0, 10, 'Location: ADSM, Abu Dhabi', 0, 1);

    // Save PDF to a temporary file
    $pdfFilePath = 'registration_form_details_' . date('Y_m_d_h_i_s') . '.pdf';
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
        $mail->setFrom($mail->Username, 'ICAIMT2025 Registration');
        $mail->addAddress($email); // Send to registrant
        $mail->addAddress($to);    // Send to admin

        // Attachments
        $mail->addAttachment($pdfFilePath);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'ICAIMT2025 Registration Confirmation';

        // Build email body
        $emailBody = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background-color: #f9f9f9; padding: 20px; text-align: center; border-bottom: 3px solid #2ccfbb; }
            .content { padding: 20px; }
            .footer { font-size: 12px; text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>ICAIMT2025 Registration Confirmation</h2>
            </div>
            <div class="content">
                <p>Dear ' . $title . ' ' . $firstName . ' ' . $lastName . ',</p>
                <p>Thank you for registering for the International Conference on Artificial Intelligence Management and Trends (ICAIMT2025).</p>
                <p>Your registration has been successfully processed with payment reference: <strong>' . $paymentReference . '</strong></p>
                <p>Please find your registration details in the attached PDF.</p>
                <p><strong>Conference Information:</strong></p>
                <ul>
                    <li>Date: May 21, 2025</li>
                    <li>Location: ADSM, Abu Dhabi</li>
                </ul>
                <p>We look forward to seeing you at the conference!</p>
                <p>Best regards,<br>ICAIMT2025 Organizing Committee</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $emailBody));

        $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
    }

    // Delete temporary PDF file
    unlink($pdfFilePath);

    echo json_encode([
        'success' => true,
        'message' => 'Data received',
        'sent_data' => $data,
        'received_data' => $responseData['result']['link']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error while getting the link',
        'sent_data' => $data,
        'received_data' => null
    ]);
}

?>