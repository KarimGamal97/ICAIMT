<?php



header('Content-Type: application/json');

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

// Function to sanitize input data
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


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
$isAuthor = isset($_POST['IsAuthor']) ? (int) $_POST['IsAuthor'] : 0;
$paperID = isset($_POST['EDASPaperReferences']) ? sanitizeInput($_POST['EDASPaperReferences']) : '';
$title = isset($_POST['Title']) ? sanitizeInput($_POST['Title']) : '';
$firstName = isset($_POST['FirstName']) ? sanitizeInput($_POST['FirstName']) : '';
$lastName = isset($_POST['LastName']) ? sanitizeInput($_POST['LastName']) : '';
$affiliation = isset($_POST['Affiliation']) ? sanitizeInput($_POST['Affiliation']) : '';
$email = isset($_POST['Email']) ? sanitizeInput($_POST['Email']) : '';
$mobile = isset($_POST['Mobile']) ? sanitizeInput($_POST['Mobile']) : '';
$country = isset($_POST['Country']) ? sanitizeInput($_POST['Country']) : '';
$typeOfRegistration = isset($_POST['TypeOfRegistration']) ? sanitizeInput($_POST['TypeOfRegistration']) : '';
$ieeeMembershipNumber = isset($_POST['IEEEMembershipNumber']) ? sanitizeInput($_POST['IEEEMembershipNumber']) : '';
$paymentReference = isset($_POST['paymentReference']) ? sanitizeInput($_POST['paymentReference']) : 'N/A';

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

    $link = $responseData['result']['link'];
    $reference = $responseData['result']['reference'];

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO registrations (is_author, paper_id, title, first_name, last_name, 
                           affiliation, email, mobile, country, type_of_registration, 
                           ieee_membership_number, payment_reference, payment_link, payment_for_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "isssssssssssss",
        $isAuthor,
        $paperID,
        $title,
        $firstName,
        $lastName,
        $affiliation,
        $email,
        $mobile,
        $country,
        $typeOfRegistration,
        $ieeeMembershipNumber,
        $reference,
        $link,
        $paymentForID
    );

    // Execute the statement
    if ($stmt->execute()) {
        $registrationId = $stmt->insert_id;
    } else {
    }

    // Close connection
    $conn->close();

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