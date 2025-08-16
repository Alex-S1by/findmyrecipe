<?php
session_start();
include '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
  unset($_SESSION['otp_sent']);

$user_id = $_SESSION['user_id'];

$user_sql = "SELECT name,email,created_at,profile_pic FROM user WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);


$recipes_sql = "SELECT * FROM recipes WHERE creator = ? ORDER BY id DESC";
$stmt2 = mysqli_prepare($conn, $recipes_sql);
mysqli_stmt_bind_param($stmt2, "i", $user_id);
mysqli_stmt_execute($stmt2);
$recipes_result = mysqli_stmt_get_result($stmt2);

$recipes = [];
while ($row = mysqli_fetch_assoc($recipes_result)) {
    $recipes[] = $row;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($user['name']) ?> | Profile</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #f0fdf4, #f9fafb);
      color: #1f2937;
      margin: 0;
      padding: 0;
    }

    a {
      text-decoration: none;
    }

    .container {
      max-width: 1100px;
      margin: 60px auto;
      padding: 0 20px;
    }

    .card {
      background-color: #ffffff;
      border-radius: 20px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      padding: 40px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 30px;
    }

    .profile-pic {
      width: 160px;
      height: 160px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid white;
      box-shadow: 0 0 0 5px #34d399;
    }

    .info {
      flex: 1;
    }

    .info h2 {
      font-size: 28px;
      margin-bottom: 8px;
    }

    .info p {
      font-size: 15px;
      color: #6b7280;
      margin: 6px 0;
    }

    .info i {
      margin-right: 6px;
      color: #9ca3af;
    }

    .buttons {
      margin-top: 24px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      padding: 10px 18px;
      border-radius: 8px;
      border:none;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn i {
      margin-right: 6px;
      color: white;
    }

    .btn-green {
      background-color: #10b981;
      color: white;
    }

    .btn-green:hover {
      background-color: #059669;
    }

    .btn-gray {
      background-color: #f3f4f6;
      color: #1f2937;
    }

    .btn-gray:hover {
      background-color: #e5e7eb;
    }

    .dropdown {
      position: relative;
    }

    .dropdown-menu {
      position: absolute;
      top: 110%;
      right: 0;
      width: 200px;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.08);
      display: none;
      flex-direction: column;
      z-index: 100;
      overflow: hidden;
    }

    .dropdown-menu a {
      padding: 12px 18px;
      color: #374151;
      font-size: 14px;
      border-bottom: 1px solid #f3f4f6;
    }

    .dropdown-menu a:last-child {
      border-bottom: none;
    }

    .dropdown-menu a:hover {
      background-color: #f9fafb;
    }

    .section-title {
      text-align: center;
      font-size: 22px;
      font-weight: bold;
      margin: 60px 0 30px;
    }

    .recipe-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 25px;
    }

    .recipe-card {
      background: white;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
    }

    .recipe-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .recipe-content {
      padding: 16px;
      display: flex;
      flex-direction: column;
    }

    .recipe-content h4 {
      margin: 0 0 8px;
      font-size: 18px;
      color: #111827;
    }

    .recipe-content p {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 14px;
    }

    .recipe-actions {
      margin-top: auto;
      border-top: 1px solid #f3f4f6;
      padding-top: 12px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
        .recipe-actions i {
 color:black;
        }

    .btn-blue {
      background-color: #dbeafe;
      color: #1d4ed8;
    }

    .btn-blue:hover {
      background-color: #bfdbfe;
    }

    .btn-yellow {
      background-color: #fef9c3;
      color: #92400e;
    }

    .btn-yellow:hover {
      background-color: #fde68a;
    }

    .btn-red {
      background-color: #fee2e2;
      color: #b91c1c;
    }

    .btn-red:hover {
      background-color: #fecaca;
    }

    @media screen and (max-width: 768px) {
      .card {
        flex-direction: column;
        text-align: center;
      }
      .info {
        text-align: center;
      }
      .dropdown-menu {
        left: 0;
        right: auto;
      }
    }
  </style>
</head>
<body>
     <?php include "../includes/header.php"; ?>

<div class="container">
  <div class="card">
      <img src="../<?= htmlspecialchars($user['profile_pic'] ) ?>"
         alt="Profile Picture" class="profile-pic" />
    <div class="info">
      <h2>Name: <?= htmlspecialchars($user['name']) ?></h2>
      <p>Email: <?= htmlspecialchars($user['email']) ?></p>
     
      <p><i class="fas fa-calendar-alt"></i>Joined: <?= date("F j, Y", strtotime($user['created_at'] ?? '2023-01-01')) ?></p>
      <p><i class="fas fa-utensils"></i>Total Recipes: <?= count($recipes) ?></p>

      <div class="buttons">
        <div class="dropdown">
          <button class="btn btn-green" onclick="toggleDropdown()">
            <i class="fas fa-user-edit"></i> Edit Profile <i class="fas fa-chevron-down" style="margin-left: 6px;"></i>
          </button>
          <div class="dropdown-menu" id="dropdownMenu">
            <a href="edit-profile.php?section=username">Edit Username</a>
            <a href="edit-profile.php?section=image">Change Image</a>
            <a href="edit-profile.php?section=email">Update Email</a>
            <a href="edit-profile.php?section=password">Change Password</a>
          </div>
        </div>

        <a href="../favourite" class="btn btn-gray">
          <i class="fas fa-heart" style="color: #ec4899;"></i> View Favorites
        </a>
      </div>
    </div>
  </div>

  <h3 class="section-title">Your Recipes</h3>

  <?php if (!empty($recipes)): ?>
    <div class="recipe-grid">
      <?php foreach ($recipes as $recipe): ?>
        <div class="recipe-card">
          <img src="../<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
          <div class="recipe-content">
            <h4><?= htmlspecialchars($recipe['title']) ?></h4>
            <p><?= htmlspecialchars($recipe['meal']) ?> | <?= htmlspecialchars($recipe['time']) ?> mins</p>
            <div class="recipe-actions">
              <a href="../recipes/recipe.php?id=<?= $recipe['id'] ?>" class="btn btn-blue"><i class="fas fa-eye"></i> View</a>
              <a href="../recipes/edit_recipe.php?id=<?= $recipe['id'] ?>" class="btn btn-yellow"><i class="fas fa-edit"></i> Edit</a>
              <form method="POST" action="../recipes/delete_recipe.php" onsubmit="return confirm('Are you sure you want to delete this recipe?');">
                <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                <button type="submit" class="btn btn-red"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center; color: #6b7280;">You haven't added any recipes yet.</p>
  <?php endif; ?>
</div>

<script>
  function toggleDropdown() {
    const menu = document.getElementById("dropdownMenu");
    menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
  }

  document.addEventListener('click', function (event) {
    const dropdown = document.getElementById('dropdownMenu');
    const button = event.target.closest('button');
    if (!dropdown.contains(event.target) && !button) {
      dropdown.style.display = 'none';
    }
  });
</script>

</body>
</html>

