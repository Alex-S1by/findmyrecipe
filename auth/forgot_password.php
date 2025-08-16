<?php
session_start();
include "../db/config.php";
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}
if (isset($_GET['start']) && $_GET['start'] === '1') {
  session_unset();
  session_destroy();
  header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
  exit;
}



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";

function sendResetOTP($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'findmyrecipeotp@gmail.com';
        $mail->Password = 'mxze nhjs ldmh qufa'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('findmyrecipeotp@gmail.com', 'Find My Dish');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = 
        $mail->Body = "
<div style=\"font-family: Arial, sans-serif; max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); padding: 30px; box-sizing: border-box;\">
    <div style=\"text-align: center; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0; margin-bottom: 25px;\">
        <h1 style=\"font-size: 24px; color: #333333; margin: 0; font-weight: 600;\">Password Reset Request</h1>
    </div>
    <div style=\"font-size: 16px; line-height: 1.6; color: #555555; margin-bottom: 15px;\">
        <p>Dear User,</p>
        <p>We received a request to reset the password for your account on FindMyDish.</p>
        <p>To proceed with your password reset, please use the One-Time Password (OTP) below:</p>
        <div style=\"background-color: #f0f8ff; border: 1px solid #cceeff; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0;\">
            <p style=\"margin-bottom: 10px;\">Your OTP:</p>
            <span style=\"font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 4px; display: inline-block; padding: 5px 15px; background-color: #e6f7ff; border-radius: 6px;\">$otp</span>
        </div>
        <p>This OTP is valid for the next 5 minutes. Please do not share this code with anyone.</p>
        <p style=\"font-size: 14px; color: #777777; margin-top: 20px; padding-top: 15px; border-top: 1px dashed #e0e0e0;\">If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
    </div>
    <div style=\"text-align: center; padding-top: 20px; border-top: 1px solid #e0e0e0; margin-top: 25px; font-size: 14px; color: #777777;\">
        <p>Thank you,</p>
        <p>The Team at FindMyDish</p>
        <p><a href=\"mailto:findmydishsupport@gmail.com\" style=\"color: #007bff; text-decoration: none;\">findmydishsupport@gmail.com</a></p>
    </div>
</div>
";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['send_otp']) || isset($_POST['resend_otp'])) {
        $email = trim($_POST["email"]);
        $result = mysqli_query($conn, "SELECT * FROM user WHERE email = '$email'");
        if (mysqli_num_rows($result) == 1) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['otp_expires'] = time() + 300; // 5 mins

            if (sendResetOTP($email, $otp)) {
                $success = "OTP sent to your email.";
            } else {
                $error = "Failed to send OTP.";
            }
        } else {
            $error = "Email not registered.";
        }

    } elseif (isset($_POST['reset_password'])) {

        $entered_otp = $_POST['otp'] ?? '';
        $password = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // ✅ Step 1: Check OTP first
        if (!isset($_SESSION['reset_otp']) || time() > $_SESSION['otp_expires']) {
            $error = "OTP expired. Please request a new one.";
        } elseif ($entered_otp != $_SESSION['reset_otp']) {
            $error = "Incorrect OTP.";
        } 
        // ✅ Step 2: OTP is correct, now validate password
        else {
            if ($password !== $confirm) {
                $error = "Passwords do not match.";
            } elseif (preg_match('/\s/', $password)) {
                $error = "Password must not contain spaces.";
            } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
                $error = "Password must be at least 8 characters long and include an uppercase letter, a number, and a special character.";
            } else {
                // ✅ Step 3: All validations passed, reset password
                $email = $_SESSION['reset_email'];
                $new_pwd = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE user SET pwd='$new_pwd' WHERE email='$email'");
                $success = "Password reset successful.";
                session_unset();
                header("Location: login.php");
                exit;
            }
        }
    }
}




?>

<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    * {
        box-sizing: border-box;
    }

    body {
        background: white;
    }

    body {
        font-family: 'Quicksand', sans-serif;
        background-color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .box {
        background: #fff;
        border: 2px solid #ffd6a5;
        padding: 40px 30px;
        width: 100%;
        max-width: 420px;
        border-radius: 10px;
        box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        color: #e60033;
    }

    input {

        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
        border: 1.5px solid #e60033;
        border-radius: 8px;
        font-size: 1rem;
        background-color: #fffef8;
        transition: border-color 0.3s;
    }


    button {
        width: 100%;
        padding: 12px;
        background: #e60033;
        color: #fff;
        border: none;
        font-weight: bold;
        border-radius: 6px;
        cursor: pointer;
    }

    .message {
        text-align: center;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .message.error {
        color: #d10031;
    }

    .message.success {
        color: green;
    }
    </style>
</head>

<body>
    <div class="box">
        <h2>Forgot Password</h2>
        <form method="POST">
            <?php if (!isset($_SESSION['reset_otp'])): ?>
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" name="send_otp">Send OTP</button>
            <?php else: ?>
            <input type="text" name="otp" placeholder="Enter OTP"
                value="<?= htmlspecialchars( $_POST['otp'] ?? '') ?>" />
            <div style="position: relative;">
                <input type="password" name="new_password" id="new_password" placeholder="New Password" required
                    value="<?= htmlspecialchars($_POST['new_password'] ?? '') ?>" />
                <i class="fa-solid fa-eye" id="togglePassword1"
                    style="position: absolute; top: 35%; right: 15px; transform: translateY(-50%); cursor: pointer;"></i>
            </div>
            <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password"
                    required value="<?= htmlspecialchars($_POST['confirm_password'] ?? '') ?>" />
                <i class="fa-solid fa-eye" id="togglePassword2"
                    style="position: absolute; top: 35%; right: 15px; transform: translateY(-50%); cursor: pointer;"></i>
            </div>



            <?php if (!empty($error)): ?>
            <div class="message error"><?= $error ?></div>
            <?php elseif (!empty($success)): ?>
            <div class="message success"><?= $success ?></div>
            <?php endif; ?>
            <div class="message">
                OTP valid for <span id="countdown">05:00</span>
                <span id="resendContainer" style="display: none;">
                    <button type="submit" name="resend_otp" id="resendBtn" class="small-btn">Resend OTP</button>
                </span>


            </div>
            <button type="submit" name="reset_password">Reset Password</button>
            <?php endif; ?>

        </form>
        <div style="text-align: center; margin-top: 10px;">
            <a href="login.php" style="font-size: 0.9rem; color: #e60033; text-decoration: none;">Back to Login</a>
        </div>

        <?php if (isset($_SESSION['reset_otp']) && isset($_SESSION['otp_expires'])): ?>

        <script>
        const countdownEl = document.getElementById('countdown');
        const resendBtn = document.getElementById('resendBtn');
        const resendContainer = document.getElementById('resendContainer');

        const expiryTime = <?= $_SESSION['otp_expires'] ?> * 1000;

        function updateCountdown() {
            const now = new Date().getTime();
            const remaining = expiryTime - now;

            if (remaining <= 0) {
                countdownEl.textContent = "00:00";
                resendBtn.disabled = false;
                resendContainer.style.display = "inline-block"; // <- make it visible
                clearInterval(timer);
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            countdownEl.textContent =
                (minutes < 10 ? "0" : "") + minutes + ":" +
                (seconds < 10 ? "0" : "") + seconds;
        }

        updateCountdown();
        const timer = setInterval(updateCountdown, 1000);
        </script>
        <?php endif; ?>


    </div>


    <script>
    window.addEventListener('DOMContentLoaded', () => {

        const togglePassword1 = document.getElementById('togglePassword1');
        const password = document.getElementById('new_password');



        togglePassword1.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });

        const togglePassword2 = document.getElementById('togglePassword2');
        const confirmPassword = document.getElementById('confirm_password');
        togglePassword2.addEventListener('click', function() {
            const type = confirmPassword.type === 'password' ? 'text' : 'password';
            confirmPassword.type = type;
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });

    });
    </script>

</body>

</html>