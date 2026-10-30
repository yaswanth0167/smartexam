<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function sendOTP($toEmail, $otp)
{
    $mail = new PHPMailer(true);

    try {

        // SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Your Gmail
        $mail->Username = 'smartexam0167@gmail.com';

        // Your Google App Password
        $mail->Password = 'aetmgthewftvkqrh';

        // SSL certificate verification workaround
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // IMPORTANT: Use your actual Gmail here
        $mail->setFrom(
            'smartexam0167@gmail.com',
            'SmartExam'
        );

        $mail->addAddress($toEmail);

        $mail->isHTML(true);

        $mail->Subject = 'SmartExam - Email Verification OTP';

        $mail->Body = "
            <div style='font-family: Arial; padding: 20px;'>
                <h2 style='color: #4f46e5;'>SmartExam</h2>

                <p>Your email verification OTP is:</p>

                <h1 style='
                    letter-spacing: 8px;
                    color: #4f46e5;
                '>
                    $otp
                </h1>

                <p>This OTP is valid for <b>10 minutes</b>.</p>

                <p>Please do not share this OTP with anyone.</p>
            </div>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}