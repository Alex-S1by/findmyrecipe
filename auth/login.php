<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db/config.php"; 

$errorMsg = "";

 if(isset( $_SESSION["admin"])){
     header("Location: ../admin");
 }

if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $remember = isset($_POST['remember']);

    // Check admin table first
    $admin_sql = "SELECT * FROM admin WHERE email = '$email'";
    $admin_result = mysqli_query($conn, $admin_sql);

    if ($admin_result && mysqli_num_rows($admin_result) === 1) {
        $admin = mysqli_fetch_assoc($admin_result);
        if (password_verify($password, $admin["pwd"])) {
            $_SESSION["admin"] = $admin["name"];
            header("Location: ../admin/");
            exit();
        } else {
            $errorMsg = "Invalid password!";
        }
    } else {
        // Check user table
        $sql = "SELECT * FROM user WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
           if ($user['status'] !== 'active') {
        $errorMsg = "User account is disabled!";
    } elseif (password_verify($password, $user["pwd"])) {

                $_SESSION['user'] = $user['name'];
                $_SESSION['user_id'] = $user['id'];

                // Remember Me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), "/", "", true, true); // 30 days, secure, HTTP only

                    // Store token in DB
                    $userId = $user['id'];
                    $escaped_token = mysqli_real_escape_string($conn, $token);
                    mysqli_query($conn, "UPDATE user SET remember_token = '$escaped_token' WHERE id = $userId");
                }
             

                // Redirect
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                } else {
                    $_SESSION["login_success"] = true;
                    header("Location: ../index.php");
                }
                exit();
            } else {
                $errorMsg = "Invalid password!";
            }
        } else {
            $errorMsg = "User not found!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login | Find My Dish</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
    * {
        box-sizing: border-box;
    }

    body {
        background: white;
    }

    .body-div {
        font-family: 'Quicksand', sans-serif;
        background-color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .login-container {
        background-color: #f8f8f8;
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        border: 2px solid #ffd6a5;
    }

    .login-container h2 {
        text-align: center;
        color: #e60033;
        margin-bottom: 25px;
    }



    input[type="text"],
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
        border-color: #ffa94d;
        outline: none;
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
        background-color: #d9315bff;
    }

    .signup-link {
        text-align: center;
        margin-top: 15px;
        font-size: 0.9rem;
    }

    .signup-link a {
        color: #e60033;
        text-decoration: none;
        font-weight: 600;
    }

    .signup-link a:hover {
        text-decoration: underline;
    }

    .error-message {
        color: #e60033;
        text-align: center;
        margin-bottom: 15px;
        font-weight: bold;
    }
    </style>
</head>

<body>

    <div class="body-div">
        <div class="login-container">
            <h2>Welcome Back!</h2>



            <form action="" method="POST" id="loginForm">
               
                <input type="text" name="email" placeholder="E-mail" required />
                <div style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="Password" required />
                    <i class="fa-solid fa-eye" id="togglePassword"
                        style="position: absolute; top: 35%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
                </div>



                <!-- Display error if any -->
                <?php if (!empty($errorMsg)): ?>
                <div class="error-message"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
     <div style="margin-bottom: 15px;">
    <input type="checkbox" name="remember" id="remember">
    <label for="remember" style="font-size: 0.9rem;">Remember me</label>
</div>

                <button type="submit">Log In</button>
           

            </form>

            <div style="text-align: center; margin-top: 10px;">
                <a href="forgot_password.php?start=1"
                    style="font-size: 0.9rem; color: #e60033; text-decoration: none;">Forgot Password?</a>
            </div>

            <div class="signup-link">
                New to the kitchen? <a href="signup.php">Sign up here</a>
            </div>
        </div>
    </div>


    <script>
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");


    togglePassword.addEventListener("click", function() {
        const isPassword = passwordInput.type === "password";
        passwordInput.type = isPassword ? "text" : "password";

        // Toggle icon class
        this.classList.toggle("fa-eye");
        this.classList.toggle("fa-eye-slash");
    });
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>


</html>