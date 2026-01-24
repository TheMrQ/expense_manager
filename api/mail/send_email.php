<?php
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';
require_once '../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPasswordResetEmail($toEmail, $token)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'quynhb2206010@student.ctu.edu.vn';  // Replace
        $mail->Password = 'vboymyqmnpywceie';           // Replace
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('quynhb2206010@student.ctu.edu.vn', 'ExpenseManager');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Reset your password';

        $link = "http://localhost/expense_manager/frontend/reset_password.html?token=$token";
        $mail->Body = "Click <a href='$link'>here</a> to reset your password. This link expires in 10 minutes.";

        $mail->send();
    } catch (Exception $e) {
        echo "Mail error: " . $mail->ErrorInfo;
    }
}
