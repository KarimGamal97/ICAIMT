<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $firstName = $_POST['FirstName'];
    $lastName = $_POST['LastName'];
    $email = $_POST['Email'];
    $country = $_POST['Country'];
    $typeOfRegistration = $_POST['TypeOfRegistration'];

    // Prepare the email content
    $to = "kemokamasha1234@gmail.com";
    $subject = "Registration Details";
    $message = "
        <html>
        <head>
            <title>Registration Details</title>
        </head>
&lt;!-- Google tag (gtag.js) --&gt; &lt;script async
src=&quot;https://www.googletagmanager.com/gtag/js?id=G-TQ7HQF6HGE&quot;&gt;&lt;/script&gt; &lt;script&gt;
window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);}
gtag(&#39;js&#39;, new Date()); gtag(&#39;config&#39;, &#39;G-TQ7HQF6HGE&#39;); &lt;/script&gt;
        <body>
            <h2>Registration Details</h2>
            <p><strong>First Name:</strong> $firstName</p>
            <p><strong>Last Name:</strong> $lastName</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Country:</strong> $country</p>
            <p><strong>Type of Registration:</strong> $typeOfRegistration</p>
        </body>
        </html>
    ";

    // Email headers for HTML content
    $headers = "From: no-reply@example.com\r\n";
    $headers .= "Reply-To: no-reply@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Send the email
    if (mail($to, $subject, $message, $headers)) {
        // Redirect to the "coming soon" page
        header("Location: coming-soon.html");
        exit();
    } else {
        echo "Failed to send email.";
    }
}
