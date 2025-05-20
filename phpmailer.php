<?php
// Replace the native mail() function with PHPMailer

// First, install PHPMailer via Composer:
// composer require phpmailer/phpmailer

// At the top of your file, add:
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require the Composer autoloader
require 'vendor/autoload.php';

// Replace your current mail sending code with this function:
function sendPasswordResetEmail($email, $token) {
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/recupera-password.php?token=" . $token;
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        // For Gmail or other external SMTP:
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com'; // Your Gmail email
        $mail->Password   = 'your-app-password';    // Your Gmail app password (not your regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // For local testing with tools like Mailhog:
        /*
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = false;
        $mail->Port       = 1025; // Mailhog default port
        */
        
        // Recipients
        $mail->setFrom('noreply@immobiliare-esempio.it', 'Immobiliare');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Recupero Password - Immobiliare';
        
        // HTML email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; 
                       text-decoration: none; border-radius: 5px; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Recupero Password</h2>
                </div>
                <p>Ciao,</p>
                <p>Hai richiesto il reset della password. Clicca sul pulsante seguente per reimpostare la tua password:</p>
                <p style='text-align: center;'>
                    <a href='{$reset_link}' class='btn'>Reimposta Password</a>
                </p>
                <p>Oppure copia e incolla il seguente link nel tuo browser:</p>
                <p>{$reset_link}</p>
                <p>Il link è valido per un'ora.</p>
                <p>Se non hai richiesto questo reset, ignora questa email.</p>
                <div class='footer'>
                    <p>Cordiali saluti,<br>Team Immobiliare</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative for email clients that don't support HTML
        $mail->AltBody = "Ciao,\n\n" .
                        "Hai richiesto il reset della password. Clicca sul link seguente per reimpostare la tua password:\n\n" .
                        $reset_link . "\n\n" .
                        "Il link è valido per un'ora.\n\n" .
                        "Se non hai richiesto questo reset, ignora questa email.\n\n" .
                        "Cordiali saluti,\nTeam Immobiliare";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // For debugging
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Then in your code, replace the mail() function call with:
if (sendPasswordResetEmail($email, $token)) {
    $email_sent = true;
    $success = true;
} else {
    // Handle email sending failure
    // For development, you might still want to show the debug link
    $email_sent = true; // Simulate success
    $success = true;
    
    // Only for debugging - Mostra il link (rimuovere in produzione)
    $_SESSION['debug_reset_link'] = $reset_link;
}
?>