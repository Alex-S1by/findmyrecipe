<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recipe Finder</title>
  <link rel="stylesheet" href="assets/styles.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap + Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

   <script src="https://cdn.tailwindcss.com"></script>

</head>
<body class="bg-gradient-to-br from-green-100 via-white to-green-200">
  

      <?php  session_start();   ?>
  <!-- Navigation -->
  <?php include "includes/header.php"; ?>

  <!-- Hero Section -->
  <?php include "includes/hero.html"; ?>

  <!-- Search Form (you can separate this too) -->
  <?php include "includes/search_form.html"; ?> 
  <!-- Recipe Cards -->
  <?php include "includes/recipe_cards.php"; ?>

  <!-- Footer -->
  <?php include "includes/footer.html"; ?>
<?php if (isset($_SESSION['login_success']) && $_SESSION['login_success']) : ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            Swal.fire({
                icon: 'success',
                title: 'Welcome back!',
                text: 'You have logged in successfully!',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>
    <?php unset($_SESSION['login_success']);

 endif;
 
 
 
    if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $token = mysqli_real_escape_string($conn, $_COOKIE['remember_token']);
    $result = mysqli_query($conn, "SELECT * FROM user WHERE remember_token = '$token' LIMIT 1");

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user'] = $user['name'];
        $_SESSION['user_id'] = $user['id'];
    }
}
?>

<script>
    // Force reload from server when page is loaded from cache (Back button)
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>


</body>

</html>

