<?php
session_start();
include "../db/config.php";
 unset(
        $_SESSION['otp_sent'],
       
        $_SESSION['otp_verified']
    );

// PHPMailer
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If user is already logged in
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$error = "";
$success = "";

// Send OTP function
function sendOTPEmail($username, $email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'findmyrecipeotp@gmail.com';
        $mail->Password = 'mxze nhjs ldmh qufa';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('findmyrecipeotp@gmail.com', 'Find My Dish');
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP - Find My Dish';
        $mail->Body= '


<div style="font-family:  Arial, sans-serif; max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); padding: 30px; box-sizing: border-box;">
    <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0; margin-bottom: 25px;">
        <h1 style="font-size: 24px; color: #333333; margin: 0; font-weight: 600;">Verify Your Email Address</h1>
    </div>
    <div style="font-size: 16px; line-height: 1.6; color: #555555; margin-bottom: 15px;">
        <p>Hi <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>Thank you for signing up. Please use the OTP below to verify your email address:</p>
        <div style="background-color: #f0f8ff; border: 1px solid #cceeff; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0;">
            <p style="margin-bottom: 10px;">Your OTP:</p>
            <span style="font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 4px; display: inline-block; padding: 5px 15px; background-color: #e6f7ff; border-radius: 6px;">' . $otp . '</span>
        </div>
        <p>⏳ This OTP is valid for <strong>5 minutes</strong>.</p>
        <p style="font-size: 14px; color: #777777; margin-top: 20px; padding-top: 15px; border-top: 1px dashed #e0e0e0;">If you didn’t request this, you can safely ignore this email.</p>
    </div>
    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e0e0e0; margin-top: 25px; font-size: 14px; color: #777777;">
        <p>— The Find My Dish TeamTeam</p>
        <p><a href="mailto:[findmydishotp@gmail.com]" style="color: #007bff; text-decoration: none;">[findmydishotp@gmail.com]</a></p>
    </div>
</div>

';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle form actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    // Step 1: Send OTP
    if (isset($_POST['send_otp'])) {
        $username = trim($_POST["username"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $confirm = $_POST["confirm_password"];

        if (empty($username) || strpos($username, ' ') !== false) {
            $error = "Username must not be empty or contain spaces.";
        } elseif (!preg_match("/^[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]{2,}$/", $email)) {
           $error .= "Invalid email format.";
        }elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        }
        elseif (preg_match('/\s/', $password)) {
           $error = "Password must not contain spaces.";
          }
         elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $password)) {
            $error = "Password must include at least one uppercase letter, one number, and one special character.";
        }  else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE name = ? OR email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $username, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $error = "Username or Email already exists.";
            } else {
                $otp = rand(100000, 999999);
                $_SESSION['signup'] = [
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'otp' => $otp,
                   
                ];

                if (sendOTPEmail($username, $email, $otp)) {
                    $_SESSION['otp_sent'] = true;
                      $_SESSION['otp_expires'] = time() + 300; 
                        $_SESSION['resend_otp'] = time() + 60; 
                    $success = "OTP sent to $email";
                } else {
                    $error = "Failed to send OTP. Try again.";
                }
            }
        }
    }
    if (isset($_POST['resend_otp']) && isset($_SESSION['signup'])) {
    $username = $_SESSION['signup']['username'];
    $email = $_SESSION['signup']['email'];
   

    $otp = rand(100000, 999999);
    $_SESSION['signup']['otp'] = $otp;
    $_SESSION['otp_expires'] = time() + 300;
    $_SESSION['resend_otp'] = time() + 60;

    if (sendOTPEmail($username, $email, $otp)) {
          $_SESSION['otp_sent'] = true;
        $success = "A new OTP has been sent to $email";
    } else {
        $error = "Failed to resend OTP.";
    }
}

    // Step 2: Verify OTP and register
    if (isset($_POST['verify_register'])) {
        $entered_otp = trim($_POST["otp"]);
        $data = $_SESSION['signup'] ?? null;

        if (!$data || time() > $_SESSION['otp_expires']) {
            $error = "OTP expired. Please try again.";
            session_unset();
        } elseif ($entered_otp == $data['otp']) {
            $stmt = mysqli_prepare($conn, "INSERT INTO user (name, email, pwd) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $data['username'], $data['email'], $data['password']);
            if ($res=mysqli_stmt_execute($stmt)) {
                $_SESSION['user'] = $data['username'];
                 $_SESSION['user_id'] =$res['id'];
                  $_SESSION["login_success"] = true;
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Registration failed.";
            }
        } else {
            $error = "Incorrect OTP.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <title>Sign Up | Recipe Finder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    body {
        font-family: 'Quicksand', sans-serif;
        background-color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .signup-container {
        background-color: #f8f8f8;
        padding: 40px 40px;
        padding-right: 60px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        border: 2px solid #ffd6a5;
    }

    .signup-container h2 {
        text-align: center;
        color: #e60033;
        margin-bottom: 25px;
    }



    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
        border: 1.5px solid #e60033;
        border-radius: 8px;
        font-size: 1rem;
        background-color: #fffef8;
        transition: border-color 0.3s;
    }

    input:focus {
        border-color: #e60033;
        outline: none;
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper i {
        position: absolute;
        right: 15px;
        top: 14px;
        cursor: pointer;
        color: #e60033;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #e60033;
        color: white;
        border: none;
        font-size: 1rem;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #e60044;
    }

    .login-link {
        text-align: center;
        margin-top: 15px;
        font-size: 0.9rem;
    }

    .login-link a {
        color: #e60033;
        text-decoration: none;
        font-weight: 600;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    .message {
        text-align: center;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }

    .message.error {
        color: #d10031;
    }

    .message.success {
        color: green;
    }

    .small-btn {
        padding: 6px 10px;
        font-size: 0.8rem;
        background-color: #ff6600;
        border-radius: 5px;
        width: auto;
        margin-top: 10px;
    }
    </style>
</head>

<body>


    <div class="signup-container">
        <h2>Join Find My Dish</h2>



        <form id="signupForm" method="POST" action="">


            <!-- ✅ STEP 1: Username + Email + Send OTP -->
            <?php if (!isset($_SESSION['otp_sent']) && !isset($_SESSION['otp_verified'])): ?>
            <input type="text" name="username" id="username" placeholder="Username" required
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
            <input type="email" name="email" id="email" placeholder="Email" required
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            <div style="position: relative;">
                <input type="password" name="password" id="password" placeholder="Password" required
                    value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" />
                <i class="fa-solid fa-eye" id="togglePassword1"
                    style="position: absolute; top: 35%; right: 1px; transform: translateY(-50%); cursor: pointer;"></i>
            </div>
            <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password"
                    required value="<?= htmlspecialchars($_POST['confirm_password'] ?? '') ?>" />
                <i class="fa-solid fa-eye" id="togglePassword2"
                    style="position: absolute; top: 35%; right: 1px; transform: translateY(-50%); cursor: pointer;"></i>
            </div>
            <!-- ✅ Show Errors or Success -->
            <?php if (!empty($error)): ?>
            <div class="message error"><?= $error ?></div>
            <?php elseif (!empty($success)): ?>
            <div class="message success"><?= $success ?></div>
            <?php endif; ?>
            <button type="submit" name="send_otp">Send OTP</button>

            <!-- ✅ STEP 2: OTP Sent -->
            <?php elseif (isset($_SESSION['otp_sent']) && !isset($_SESSION['otp_verified'])): ?>

            <!-- OTP input -->
            <input type="text" name="otp" placeholder="Enter OTP">

            <div class="message">
               Resend otp in <span id="countdown">01:00</span>
                <span id="resendContainer" style="display: none;">
                    <button type="submit" name="resend_otp" id="resendBtn" class="small-btn">Resend OTP</button>
                </span>
            </div>
            <!-- ✅ Show Errors or Success -->
            <?php if (!empty($error)): ?>
            <div class="message error"><?= $error ?></div>
            <?php elseif (!empty($success)): ?>
            <div class="message success"><?= $success ?></div>
            <?php endif; ?>
            <button type="submit" name="verify_register">Verify OTP</button>
            <script>
            const countdownEl = document.getElementById('countdown');
            const resendBtn = document.getElementById('resendBtn');
            const resendContainer = document.getElementById('resendContainer');

            const expiryTime = <?= $_SESSION['resend_otp'] ?> * 1000;

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


        </form>






        <div class="login-link">
            Already have a Account? ? <a href="login.php">Log in here</a>
        </div>
    </div>



    <script>
    window.addEventListener('DOMContentLoaded', () => {
        const togglePassword1 = document.getElementById('togglePassword1');
        const password = document.getElementById('password');



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