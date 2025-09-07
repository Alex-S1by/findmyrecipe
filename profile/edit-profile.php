<?php
session_start();
include '../db/config.php';

$section = $_GET['section'] ?? '';

$valid_sections = ['image', 'username', 'email', 'password'];
if (!in_array($section, $valid_sections)) {
    header("Location: profile"); // default section
    exit();
}


// PHPMailer
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
;


// // Fetch user data including profile_pic
 $sql = "SELECT profile_pic FROM user WHERE id = ?";
 $stmt = mysqli_prepare($conn, $sql);
 mysqli_stmt_bind_param($stmt, "i", $user_id);
 mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);
 $user = mysqli_fetch_assoc($result);

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
        <h1 style="font-size: 24px; color: #333333; margin: 0; font-weight: 600;">Verify Email change</h1>
    </div>
    <div style="font-size: 16px; line-height: 1.6; color: #555555; margin-bottom: 15px;">
        <p>Hi <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p> Please use the OTP below to verify your email address:</p>
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
  

    $new_username = trim($_POST["new_name"] ?? '');
    
    
    $new_email = trim($_POST["new_email"] ?? '');
    
    $current_password = $_POST["current_password"] ?? '';
    $new_password = $_POST["new_password"] ?? '';
    $new_confirm = $_POST["confirm_password"] ?? '';

if (isset($_POST['pic-change'])) {
    if (!isset($_FILES['new_profile_pic']) || $_FILES['new_profile_pic']['error'] !== UPLOAD_ERR_OK) {
        die("❌ Upload error: " . ($_FILES['new_profile_pic']['error'] ?? 'No file sent.'));
    }

    $file = $_FILES['new_profile_pic'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        die("❌ Invalid file type: $ext");
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        die("❌ File too large. Max 2MB allowed.");
    }

    $uploadDir   = __DIR__ . "/../uploads/users/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Use unique name to avoid caching issues
    $newFilename = "user_" . $user_id . "_" . time() . "." . $ext;
    $uploadPath  = $uploadDir . $newFilename;
    $relativePath = "uploads/users/" . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Delete old image if exists
        $stmt_old = mysqli_prepare($conn, "SELECT profile_pic FROM user WHERE id = ?");
        mysqli_stmt_bind_param($stmt_old, "i", $user_id);
        mysqli_stmt_execute($stmt_old);
        mysqli_stmt_bind_result($stmt_old, $old_profile_pic);
        mysqli_stmt_fetch($stmt_old);
        mysqli_stmt_close($stmt_old);

        if (!empty($old_profile_pic) && $old_profile_pic !== "assets/image.png") {
            $oldPath = __DIR__ . "/../" . $old_profile_pic;
            if (file_exists($oldPath)) unlink($oldPath);
        }

        // Update DB
        $stmt = mysqli_prepare($conn, "UPDATE user SET profile_pic = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $relativePath, $user_id);
        mysqli_stmt_execute($stmt);

        header("Location: ./?section=image&v=" . time()); // cache bust
        exit;
    } else {
        die("❌ Failed to move file.");
    }
}



 if (isset($_POST['name-change'])) {
    // Username update
    if (empty($new_username) || strlen($new_username) < 3 ||  strpos($new_username, ' ') !== false) {
            $error = "Username must be at least 3 characters long and must not contain spaces.";
        } 
        elseif (empty($current_password) || strpos($current_password, ' ') !== false) {
            $error = "Password must not be empty or contain spaces.";
        } 
        else{
         $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE name = ? ");
            mysqli_stmt_bind_param($stmt, "s", $new_username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $error = "Username  already exists.";
            }else {
         $sql = "SELECT * FROM user WHERE id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($result);
           if( password_verify($current_password, $user["pwd"])){
               $stmt = mysqli_prepare($conn, "UPDATE user SET name = ? WHERE id = ?");
               mysqli_stmt_bind_param($stmt, "si", $new_username, $user_id);

            mysqli_stmt_execute($stmt);
               $error = "Username updated. ";
                header("Location: ./");
           }
           else{
                 $error = "password wrong. ";
           }
            
        }
    }
   
}




if (isset($_POST['send_otp'])) {

     
    if (!preg_match("/^[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]{2,}$/", $new_email)) {
        $error = "Invalid email format.";
    } elseif (empty($current_password) || strpos($current_password, ' ') !== false) {
        $error = "Password must not be empty or contain spaces.";
    } else {
        $sql = "SELECT * FROM user WHERE id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($result);

        if($new_email== $user['email'])
        {
              $error = " Email should be different from current.";
        }
        else{
        
        $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE email = ? ");
            mysqli_stmt_bind_param($stmt, "s", $new_email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $error = "Email already exists.";
            }
            else{

        if(password_verify($current_password, $user["pwd"])) {
            $otp = rand(100000, 999999);

            if (sendOTPEmail($user['name'], $new_email, $otp)) {
                $_SESSION['otp_sent'] = true;
                $_SESSION['otp_expires'] = time() + 300; // 5 mins
                $_SESSION['resend_otp'] = time() + 60; // 1 min cooldown
                $_SESSION['email_change'] = [
                    'username'=>$user['name'],
                    'otp' => $otp,
                    'new_email' => $new_email
                ];
                $success = "OTP sent to $new_email";
            } else {
                $error = "Failed to send OTP. Try again.";
            }
        } else {
            $error = "Password incorrect.";
          
             
        }
    }
}
    }
}

    if (isset($_POST['resend_otp']) && isset($_SESSION['email_change'])) {
    $username = $_SESSION['email_change']['username'];
    $email = $_SESSION['email_change']['new_email'];
   
 
    $otp = rand(100000, 999999);
  $_SESSION['email_change']['otp'] = $otp;
    $_SESSION['otp_expires'] = time() + 300;
    $_SESSION['resend_otp'] = time() + 60;

    if (sendOTPEmail($username, $email, $otp)) {
          $_SESSION['otp_sent'] = true;
        $success = "A new OTP has been sent to $email";
    } else {
        $error = "Failed to resend OTP.";
    }
}

   
    if (isset($_POST['sub-email'])) {
        $entered_otp = trim($_POST["email_otp"]);
        $data = $_SESSION['email_change'] ?? null;
   
        if (!$data || time() > $_SESSION['otp_expires']) {
            $error = "OTP expired. Please try again.";
           unset($_SESSION['otp_sent']);
        } elseif ($entered_otp == $data['otp']) {
             $stmt = mysqli_prepare($conn, "UPDATE user SET email = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $data['new_email'], $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Clean up session
              unset($_SESSION['otp_sent']);
            unset($_SESSION['email_change']);
            unset($_SESSION['otp_expires']);
              unset( $_SESSION['resend_otp']);
           

            // Redirect
            header("Location: ./");
            exit();
        } else {
            $error = "Email update failed. Please try again.";
        }
        } else {
            $error = "Incorrect OTP.";
        }
    }

   


   

    // Password update

    
    if (isset($_POST['password-change'])) {
    if (!empty($current_password) && !empty($new_password) && !empty($new_confirm)) {
          $sql = "SELECT * FROM user WHERE id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($result);
        if (!password_verify($current_password, $user['pwd'])) {
            $error .= "Current password incorrect. ";
        } elseif ($new_password !== $new_confirm) {
            $error .= "New passwords do not match. ";
        } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $new_password)) {
            $error = "Password must include at least one uppercase letter, one number, and one special character.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
           mysqli_query($conn, "UPDATE user SET pwd='$hashed' WHERE id=$user_id");
            $success .= "Password updated successfully. ";
              // Redirect
            header("Location: ./");
            exit();
        }
    }
 }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
    body {
        background-color: #f3f4f6;
        font-family: Arial, sans-serif;
        color: #333;
        margin: 0;
        padding: 0;
    }


    .container {
        max-width: 600px;
        margin: 40px auto;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    .header {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-bottom: 30px;
    }

    .back-btn {
        position: absolute;
        left: 0;
        margin-bottom: 20px;
    }

    .back-btn button {
        background: none;
        border: none;
        color: #15803d;
        font-size: 14px;
        cursor: pointer;
    }

    h2 {

        text-align: center;

        font-size: 24px;
        margin-bottom: 30px;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    label {
        font-size: 14px;
        margin-bottom: 5px;
        display: block;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .image-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .image-section img {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #22c55e;
    }

    .image-section label {
        width: 32px;
        height: 32px;
        background-color: #22c55e;
        color: white;
        font-weight: bold;
        text-align: center;
        line-height: 32px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
    }

    .cancel-btn {
        color: red;
        font-size: 13px;
        cursor: pointer;
        display: none;
    }

    .error-message {
        color: red;
        padding: 2px;

        font-size: 14px;
        margin-top: -10px;
    }

    .sub-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .submit-btn {
        background-color: #16a34a;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
    }

    button:disabled,
    .submit-btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
        opacity: 0.6;
        pointer-events: none;
    }

    .submit-btn:hover {
        background-color: #15803d;
    }
    </style>
</head>

<body>
    <?php include "../includes/header.php"; ?>

    <div class="container">
        <div class="header">
            <div class="back-btn">
                <a href="./">← Back</a>
            </div>

            <h2>Edit Profile</h2>
        </div>
        <form method="POST"  enctype="multipart/form-data" action=""  >

            <!-- Image Upload -->
            <?php if ($section === 'image'): ?>
            <div class="image-section">
                <img id="preview" src="../<?= htmlspecialchars($user['profile_pic'] ?? 'assets/image.png') ?>"
                    data-original-src="../<?= htmlspecialchars($user['profile_pic'] ?? 'assets/image.png') ?>"
                    alt="Profile Picture">

                <input type="file" id="fileInput" name="new_profile_pic" accept="image/*" style="display: none;"
                    onchange="previewImage(event)">

                <label for="fileInput">+</label>
                <span id="cancelBtn" class="cancel-btn" onclick="cancelImage()">Cancel</span>
            </div>
              <p class="error-message"> <?= htmlspecialchars($error) ?></p>
            <div class="sub-btn">
                <button type="submit"  name="pic-change" class="submit-btn">
                    Save Changes
                </button>
            </div>
            <?php endif; ?>

            <!-- Username Section -->
            <?php if ($section === 'username'): ?>
            <div>
                <label>New Username</label>
                <input type="text" name="new_name" value="<?= htmlspecialchars($_POST['new_name'] ?? '') ?>" >
            </div>
            <div>
                <label>Password Confirmation</label>
               <div style="position: relative;">
    <input type="password" name="current_password" id="current_password"
        value="<?= htmlspecialchars($_POST['current_password'] ?? '') ?>" />
    <i class="fa-solid fa-eye" id="togglePassword"
        style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
</div>
            </div>

              <p class="error-message"> <?= htmlspecialchars($error) ?></p>
            <div class="sub-btn">
                <button type="submit"  name="name-change" class="submit-btn">
                    Save Changes
                </button>
            </div>
            <?php endif; ?>

            <!-- Email Section -->
            <?php if ($section === 'email'): ?>
                  <?php if (!isset($_SESSION['otp_sent'])): ?>
            <div>
                <label>New Email</label>
                <input type="email" name="new_email"  value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>">
            </div>
            <div>
                <label>Password Confirmation</label>
              
                        <div style="position: relative;">
    <input type="password" name="current_password" id="current_password"
        value="<?= htmlspecialchars($_POST['current_password'] ?? '') ?>" />
    <i class="fa-solid fa-eye" id="togglePassword"
        style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
</div>
              
                </div>
            <div class="sub-btn">
                <button type="submit" name="send_otp" class="submit-btn">Send otp</button>
            </div>
            <p class="error-message"> <?= htmlspecialchars($error) ?></p>
             <?php elseif (isset($_SESSION['otp_sent']) && !isset($_SESSION['otp_verified'])): ?>

            <!-- OTP input -->
              <label>Email OTP</label>
            <input type="text" name="email_otp">

            <div class="message">
               Resend otp in <span id="countdown">01:00</span>
                <span id="resendContainer" style="display: none;">
                    <button type="submit" name="resend_otp" id="resendBtn" class="small-btn">Resend OTP</button>
                </span>
            </div>
          
            <div class="sub-btn">
                <button type="submit" class="submit-btn"  name="sub-email" id="sharedSubmitBtn"
                  >
                    Save Changes
                </button>
            </div>
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
            <?php endif; ?>

            <!-- Password Section -->
            <?php if ($section === 'password'): ?>
            <div>
                <label>Current Password</label>
                <div style="position: relative;">
                <input type="password" name="current_password"  id="current_password">
                  <i class="fa-solid fa-eye" id="togglePassword"
        style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
</div>
            </div>
            <div>
                <label>New Password</label>
                 <div style="position: relative;">
                <input type="password" name="new_password"  id="new_password1">
                <i class="fa-solid fa-eye" id="togglePassword1"
        style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
</div>
            </div>
            <div>
                <label>Confirm New Password</label>
                <div style="position: relative;">
                <input type="password" name="confirm_password" id="new_password2">
                <i class="fa-solid fa-eye" id="togglePassword2"
        style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
</div>
            </div>
             <p class="error-message"> <?= htmlspecialchars($error) ?></p>
            <div class="sub-btn">
                <button type="submit" name="password-change" class="submit-btn">
                    Save Changes
                </button>
            </div>
            <?php endif; ?>

           



        </form>
    </div>

    <script>
    function previewImage(event) {
        const file = event.target.files[0];
        if (!file) return;

        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        const maxSize = 2 * 1024 * 1024;

        if (!validTypes.includes(file.type)) {
            alert("Only JPG, PNG, or WEBP files are allowed.");
            event.target.value = "";
            return;
        }

        if (file.size > maxSize) {
            alert("Image must be less than 2MB.");
            event.target.value = "";
            return;
        }

        const preview = document.getElementById("preview");
        preview.src = URL.createObjectURL(file);
        preview.dataset.changed = "true";
        document.getElementById("cancelBtn").style.display = "inline";
    };

    function cancelImage() {
        const preview = document.getElementById("preview");
        const fileInput = document.getElementById("fileInput");

        preview.src = preview.dataset.originalSrc;
        fileInput.value = "";
        document.getElementById("cancelBtn").style.display = "none";
        alert("Image selection canceled.");
    };



   
document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.getElementById("togglePassword");
     const toggle1 = document.getElementById("togglePassword1");
      const toggle2 = document.getElementById("togglePassword2");
    const passwordInput = document.getElementById("current_password");
    const passwordInput1 = document.getElementById("new_password1");
     const passwordInput2 = document.getElementById("new_password2");

    toggle.addEventListener("click", () => {
        const type = passwordInput.type === "password" ? "text" : "password";
        passwordInput.type = type;
        toggle.classList.toggle("fa-eye");
        toggle.classList.toggle("fa-eye-slash");
    });

     toggle1.addEventListener("click", () => {
        const type = passwordInput1.type === "password" ? "text" : "password";
        passwordInput1.type = type;
        toggle1.classList.toggle("fa-eye");
        toggle1.classList.toggle("fa-eye-slash");
    });
      toggle2.addEventListener("click", () => {
        const type = passwordInput2.type === "password" ? "text" : "password";
        passwordInput2.type = type;
        toggle2.classList.toggle("fa-eye");
        toggle2.classList.toggle("fa-eye-slash");
    });
});




       </script>
</body>

</html>