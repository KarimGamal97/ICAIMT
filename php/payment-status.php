<?php

header('Content-Type: application/json');

function checkHeaderValue($headerName, $s_value)
{
    // Convert header name to the format expected by $_SERVER
    // HTTP_X_CUSTOM_HEADER becomes HTTP_X_CUSTOM_HEADER
    $serverHeaderName = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));

    // Check if the header exists in $_SERVER
    if (isset($_SERVER[$serverHeaderName]) && !empty($_SERVER[$serverHeaderName])) {
        if ($_SERVER[$serverHeaderName] == $s_value) {
            return true;
        }
    }

    // Alternative method using getallheaders() if available
    if (function_exists('getallheaders')) {
        $headers = getallheaders();

        // getallheaders() returns an associative array with original header names
        foreach ($headers as $name => $value) {
            if (strtolower($name) === strtolower($headerName) && !empty($value)) {
                if ($value == $s_value) {
                    return true;
                }
            }
        }
    }

    // Alternative method using apache_request_headers() if available
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();

        foreach ($headers as $name => $value) {
            if (strtolower($name) === strtolower($headerName) && !empty($value)) {
                if ($value == $s_value) {
                    return true;
                }
            }
        }
    }

    // Header doesn't exist or has no value
    return false;
}

$headerValue = checkHeaderValue('X-API-KEY', "afc7837a904c72052b3f2f3588a9adf9131c6bb5459984a159ad4f874d1280b2");

if ($headerValue === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Request denied',
    ]);
    die();
}

require '../vendor/setasign/fpdf/fpdf.php';
require 'statics.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


function getRegistrationByPaymentReference($conn, $payment_reference)
{
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE payment_reference = ?");

    // Bind parameter
    $stmt->bind_param("s", $payment_reference);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if any rows were returned
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return [];
    }
}

function updatePaymentStatus($conn, $payment_reference, $payment_status)
{
    // First, let's add the payment_status column if it doesn't exist
    $checkColumnSql = "SHOW COLUMNS FROM registrations LIKE 'payment_status'";
    $result = $conn->query($checkColumnSql);

    if ($result->num_rows == 0) {
        // Column doesn't exist, so add it
        $alterTableSql = "ALTER TABLE registrations ADD COLUMN payment_status VARCHAR(50) DEFAULT 'pending'";
        $conn->query($alterTableSql);
    }

    // Prepare SQL statement to update the payment status
    $stmt = $conn->prepare("UPDATE registrations SET payment_status = ? WHERE payment_reference = ?");

    // Bind parameters
    $stmt->bind_param("ss", $payment_status, $payment_reference);

    // Execute the query
    $result = $stmt->execute();

    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        return true;
    } else {
        return false;
    }
}

$payment_ref = isset($_POST['payment_ref']) ? $_POST['payment_ref'] : '';
$payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
if (empty($payment_ref) || empty($payment_ref)) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment ref and status must be valid!',
    ]);
    die();
}

updatePaymentStatus($conn, $payment_ref, $payment_status);
$registration = getRegistrationByPaymentReference($conn, $payment_ref);

if (sizeof($registration) <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment registration not found!',
    ]);
    die();
}

// Process the data
$name = $registration['first_name'] . ' ' . $registration['last_name'];
$email = $registration['email'];
$phone = $registration['mobile'];
$paymentForID = $registration['payment_for_id'];
// Capture form data
$isAuthor = $registration['is_author'];
$paperID = $registration['paper_id'];
$title = $registration['title'];
$firstName = $registration['first_name'];
$lastName = $registration['last_name'];
$affiliation = $registration['affiliation'];
$email = $registration['email'];
$confirmEmail = $registration['email'];
$mobile = $registration['mobile'];
$country = $registration['country'];
$typeOfRegistration = $registration['type_of_registration'];
$ieeeMembershipNumber = $registration['ieee_membership_number'];
$paymentReference = $registration['payment_reference'];


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
    $mail->addAddress($to2);    // Send to admin

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
    echo json_encode([
        'success' => false,
        'message' => 'Error while sending the email'
    ]);
    die();
}

// Delete temporary PDF file
unlink($pdfFilePath);

echo json_encode([
    'success' => true,
    'message' => 'Data saved and email sent!'
]);

?>