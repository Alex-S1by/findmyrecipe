<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../db/config.php";
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<script>
        setTimeout(function() {
            Swal.fire({
                icon: "warning",
                title: "Login Required",
                text: "Please login to continue",
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "../auth/login.php";
            });
        }, 100);
    </script>';
    exit();
}


// Insert comment if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
    $comment = trim($_POST['comment']);
    $stmt = $conn->prepare("INSERT INTO comments (recipe_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $recipe_id, $user_id, $comment);
    $stmt->execute();
    // Optional: redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Fetch comments
$comments_stmt = $conn->prepare("
    SELECT c.comment, c.created_at, u.name 
    FROM comments c
    JOIN user u ON c.user_id = u.id
    WHERE c.recipe_id = ?
    ORDER BY c.created_at DESC
");
$comments_stmt->bind_param("i", $recipe_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();



// Validate recipe ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid recipe ID.";
    exit();
}

$recipe_id = (int) $_GET['id'];

// Fetch recipe
$recipe_stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ?");
$recipe_stmt->bind_param("i", $recipe_id);
$recipe_stmt->execute();
$recipe_result = $recipe_stmt->get_result();

if ($recipe_result->num_rows === 0) {
    echo "Recipe not found.";
    exit();
}
$recipe = $recipe_result->fetch_assoc();

// Fetch creator




        
$creator = $conn->prepare("SELECT * FROM user WHERE id = ?");
$creator->bind_param("i", $recipe['creator']); 
$creator->execute();

$result = $creator->get_result();
if ($result->num_rows > 0) {
    $creator = $result->fetch_assoc();

}

// Fetch ingredients
$ingredients_sql = "
  SELECT iq.quantity, ing.name 
  FROM recipe_ingredients iq
  JOIN ingredients ing ON iq.ingredient_id = ing.id
  WHERE iq.recipe_id = $recipe_id
  ORDER BY iq.id ASC
";
$ingredients_result = mysqli_query($conn, $ingredients_sql);

// Fetch steps
$steps_sql = "
  SELECT step_no, instruction 
  FROM steps 
  WHERE recipe_id = $recipe_id
  ORDER BY step_no ASC
";
$steps_result = mysqli_query($conn, $steps_sql);







$user_id = $_SESSION['user_id'];
$recipe_id = $recipe['id'];



$fav_sql = "SELECT id FROM favorites WHERE user_id = $user_id AND recipe_id = $recipe_id";
$fav_result = mysqli_query($conn, $fav_sql);

$is_favorite = false; // Set default first

if (mysqli_num_rows($fav_result) > 0) {
    $is_favorite = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($recipe['title']) ?> - Recipe</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f2f2f2;
      font-family: 'Noto Sans', 'Segoe UI', 'Helvetica Neue', sans-serif;
    }

    .paper-container {
      background: #fffdf5;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 90%;
      margin: 50px auto;
      position: relative;
    }
    .top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.back-link {
  position: static; /* disable absolute positioning */
  top: auto;
  left: auto;
}


    .recipe-img {
      max-height: 400px;
      width: 100%;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .badge-tag {

      color: #8a4b00;
      
      font-size: 0.85rem;
    }

    .section {
      margin-top: 30px;
    }

    .btn-fav, .btn-comment {
      margin-top: 15px;
      margin-right: 10px;
    }

    .comment-box {
      background: #fff;
      border: 1px solid #ddd;
      padding: 15px;
      border-radius: 6px;
      margin-top: 20px;
    }

    .comment-box textarea {
      resize: vertical;
    }

    /* .back-link {
      position: absolute;
      top: 40px;
      left: 30px;
    } */

    @media (max-width: 768px) {
      .paper-container {
        padding: 25px 20px;
      }

      
    }
  </style>
</head>
<body>

<?php include "../includes/header.php"; ?>
<div class="paper-container">
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
  <a href="javascript:history.back()" class="btn btn-outline-secondary">
    <i class="fa-solid fa-arrow-left"></i> Back
  </a>

  <div class="d-flex flex-column text-end">
    <span class="badge bg-light text-muted mb-1" style="font-size: 0.9rem;">
      <i class="fa-regular fa-calendar-days"></i>
      Updated on <?= date("F j, Y", strtotime($recipe['created_at'])) ?>
    </span>
    <span class="badge bg-light text-muted" style="font-size: 0.9rem;">
      By <?= empty($creator['name']) ? "":  htmlspecialchars($creator['name']) ?>
    </span>
  </div>
</div>


  <div class="text-center mb-4">
    <h1><?= htmlspecialchars($recipe['title']) ?> Recipe</h1>
    <p class="text-muted"><?= htmlspecialchars($recipe['meal']) ?></p>
    <p class="text-muted"><?= htmlspecialchars($recipe['dish']) ?></p>
    <span class="badge bg-dark"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($recipe['time']) ?> mins</span>
    <?php
    if (!empty($recipe['diet'])) {
        $diet_tags = explode(',', $recipe['diet']);
        foreach ($diet_tags as $tag) {
            echo '<span class="badge badge-tag">' . htmlspecialchars(trim($tag)) . '</span>';
        }
    }
    ?>
  </div>

  <img src="../<?= htmlspecialchars($recipe['image_url']) ?>" class="recipe-img" alt="Recipe Image" />

  <div class="text-end">
 <button id="favBtn" class="btn <?= $is_favorite ? 'btn-warning' : 'btn-outline-secondary' ?> btn-fav">
  <i id="favIcon" class="<?= $is_favorite ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
  <span id="favText"><?= $is_favorite ? '' : 'Add to Favourite' ?></span>
</button>

    <a href="#comment-form" class="btn btn-outline-primary btn-comment">
      <i class="fa-solid fa-comment"></i> Comment
    </a>
  </div>

  <?php if (!empty($recipe['description'])): ?>
  <div class="section">
    <h4>Description</h4>
    <p><?= nl2br(htmlspecialchars($recipe['description'])) ?></p>
  </div>
  <?php endif; ?>

  <div class="section">
    <h4>Ingredients</h4>
    <ul>
      <?php while ($row = mysqli_fetch_assoc($ingredients_result)): ?>
        <li><?= htmlspecialchars($row['quantity']) ?> <?= htmlspecialchars($row['name']) ?></li>
      <?php endwhile; ?>
    </ul>
  </div>

  <div class="section">
    <h4>Steps</h4>
    <ol>
      <?php while ($step = mysqli_fetch_assoc($steps_result)): ?>
        <li><?= htmlspecialchars(preg_replace('/^\d+\.?\s*/', '', $step['instruction'])) ?></li>
      <?php endwhile; ?>
    </ol>
  </div>

<div class="section">
  <h4 id="comment-form">Add a Comment</h4>
  <div class="comment-box">
    <form action="" method="POST">
      <textarea name="comment" rows="4" class="form-control mb-2" placeholder="Write your thoughts..." required></textarea>
      <button class="btn btn-sm btn-primary" type="submit">Submit Comment</button>
    </form>
  </div>

  <?php if ($comments_result->num_rows > 0): ?>
    <div class="mt-4">
      <h5>Recent Comments</h5>
      <?php while ($c = $comments_result->fetch_assoc()): ?>
        <div class="border rounded p-3 mb-3 bg-white shadow-sm">
          <div class="fw-bold"><?= htmlspecialchars($c['name']) ?></div>
          <div class="text-muted" style="font-size: 0.85rem;">
            <?= date("F j, Y, g:i a", strtotime($c['created_at'])) ?>
          </div>
          <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-muted mt-3">No comments yet. Be the first to comment!</p>
  <?php endif; ?>
</div>

</div>




<script>
document.getElementById("favBtn").addEventListener("click", function(e) {
  e.preventDefault();

  const recipeId = <?= json_encode($recipe['id']) ?>;

  fetch("../favourite/toggle_fav.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "recipe_id=" + encodeURIComponent(recipeId)
  })
  .then(res => res.json())
  .then(data => {
    console.log(data);
    if (data.status === "added") {
      <?php $is_favorite=True;?>
      favIcon.classList.remove("fa-regular");
      favIcon.classList.add("fa-solid");
      favBtn.classList.remove("btn-outline-secondary");
      favBtn.classList.add("btn-warning");
      favText.innerText = "";
    } else if (data.status === "removed") {
       <?php $is_favorite=FALSE;?>
      favIcon.classList.remove("fa-solid");
      favIcon.classList.add("fa-regular");
      favBtn.classList.remove("btn-warning");
      favBtn.classList.add("btn-outline-secondary");
      favText.innerText = "Add to Favourite";
    }
  })
  .catch(err => console.error("Failed:", err));
});


  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
</script>

</body>
</html>
