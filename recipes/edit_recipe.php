<?php
session_start();
include "../db/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$recipe_id = intval($_GET['id'] ?? 0);

// Fetch recipe
$stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    die("Recipe not found");
}

// Ingredients
$ingQ = $conn->prepare("SELECT i.name, ri.quantity 
                        FROM recipe_ingredients ri 
                        JOIN ingredients i ON ri.ingredient_id = i.id 
                        WHERE ri.recipe_id = ?");
$ingQ->bind_param("i", $recipe_id);
$ingQ->execute();
$ingredients = $ingQ->get_result()->fetch_all(MYSQLI_ASSOC);

// Steps
$stepQ = $conn->prepare("SELECT * FROM steps WHERE recipe_id = ? ORDER BY step_no ASC");
$stepQ->bind_param("i", $recipe_id);
$stepQ->execute();
$steps = $stepQ->get_result()->fetch_all(MYSQLI_ASSOC);

// Convert CSV values to arrays
$mealTypes = explode(",", $recipe['meal'] ?? "");
$diets     = explode(",", $recipe['diet'] ?? "");
$dishes    = explode(",", $recipe['dish'] ?? "");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Recipe</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

<h2>Edit Recipe</h2>
<form method="POST" action="update_recipe.php" enctype="multipart/form-data">
  <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">

  <!-- ✅ Title -->
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" value="<?= htmlspecialchars($recipe['title']) ?>" class="form-control" required>
  </div>

  <!-- ✅ Description -->
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($recipe['description']) ?></textarea>
  </div>

  <!-- ✅ Meal Type -->
  <div class="mb-3">
    <label class="form-label">Meal Type</label><br>
    <?php foreach (["Breakfast", "Lunch", "Dinner"] as $m): ?>
      <label class="me-3">
        <input type="checkbox" name="meal[]" value="<?= $m ?>" <?= in_array($m, $mealTypes) ? "checked" : "" ?>> <?= $m ?>
      </label>
    <?php endforeach; ?>
  </div>

  <!-- ✅ Dish Type -->
  <div class="mb-3">
    <label class="form-label">Dish Type</label><br>
    <?php foreach (["Main-Dish", "Side-Dish", "Dessert", "Drink","Snack"] as $d): ?>
      <label class="me-3">
        <input type="checkbox" name="dish[]" value="<?= $d ?>" <?= in_array($d, $dishes) ? "checked" : "" ?>> <?= $d ?>
      </label>
    <?php endforeach; ?>
  </div>

  <!-- ✅ Diet -->
  <div class="mb-3">
    <label class="form-label">Diet</label><br>
    <?php foreach (["Vegetarian", "Non-Vegetarian", "Egg-free", "Nut-Free"] as $di): ?>
      <label class="me-3">
        <input type="checkbox" name="diet[]" value="<?= $di ?>" <?= in_array($di, $diets) ? "checked" : "" ?>> <?= $di ?>
      </label>
    <?php endforeach; ?>
  </div>

  <!-- ✅ Time -->
  <div class="mb-3">
    <label class="form-label">Cooking Time (minutes)</label>
    <input type="number" name="time" class="form-control" value="<?= htmlspecialchars($recipe['time']) ?>" required>
  </div>

  <!-- ✅ YouTube Video -->
  <div class="mb-3">
    <label class="form-label">YouTube Video URL (optional)</label>
    <input type="text" name="video_url" class="form-control" value="<?= htmlspecialchars($recipe['video_url']) ?>">
  </div>

  <!-- ✅ Recipe Image -->
  <div class="mb-3">
    <label class="form-label">Recipe Image</label>
    <div id="imagePreviewWrapper" style="<?= empty($recipe['image_url']) ? 'display:none;' : '' ?>">
      <img id="imagePreview" 
           src="../<?= htmlspecialchars($recipe['image_url']) ?>" 
           alt="Recipe Image"
           class="rounded border" 
           style="width:150px; height:100px; object-fit:cover;">
    </div>
    <input type="file" name="image_file" id="imageInput" accept="image/*" class="form-control mt-2">
  </div>

  <!-- ✅ Ingredients -->
  <label class="form-label">Ingredients</label>
  <div id="ingredients">
    <?php foreach ($ingredients as $ing): ?>
      <div class="ingredient-row d-flex mb-2">
        <input type="text" name="ingredients[]" class="form-control"
          value="<?= htmlspecialchars(trim($ing['quantity'].' '.$ing['name'])) ?>" />
        <button type="button" class="btn btn-danger ms-2" onclick="removeRow(this)">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-sm btn-success mt-2" onclick="addIngredient()">
    <i class="fas fa-plus"></i> Add Ingredient
  </button>

  <hr>

  <!-- ✅ Steps -->
  <label class="form-label">Steps</label>
  <div id="steps">
    <?php foreach ($steps as $step): ?>
      <div class="step-row d-flex mb-2">
        <input type="text" name="steps[]" class="form-control"
          value="<?= htmlspecialchars($step['instruction']) ?>" />
        <button type="button" class="btn btn-danger ms-2" onclick="removeRow(this)">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-sm btn-success mt-2" onclick="addStep()">
    <i class="fas fa-plus"></i> Add Step
  </button>

  <hr>
  <button type="submit" class="btn btn-primary">Save Changes</button>
</form>

<script>
function removeRow(btn) {
  btn.closest(".d-flex").remove();
}
function addIngredient() {
  const div = document.createElement("div");
  div.className = "ingredient-row d-flex mb-2";
  div.innerHTML = `
    <input type="text" name="ingredients[]" class="form-control" placeholder="e.g. 1 cup Sugar" />
    <button type="button" class="btn btn-danger ms-2" onclick="removeRow(this)">
      <i class="fas fa-trash"></i>
    </button>`;
  document.getElementById("ingredients").appendChild(div);
}
function addStep() {
  const div = document.createElement("div");
  div.className = "step-row d-flex mb-2";
  div.innerHTML = `
    <input type="text" name="steps[]" class="form-control" placeholder="Step instruction" />
    <button type="button" class="btn btn-danger ms-2" onclick="removeRow(this)">
      <i class="fas fa-trash"></i>
    </button>`;
  document.getElementById("steps").appendChild(div);
}

// ✅ Image preview replace
const imageInput = document.getElementById("imageInput");
const imagePreviewWrapper = document.getElementById("imagePreviewWrapper");
const imagePreview = document.getElementById("imagePreview");

imageInput.addEventListener("change", function () {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      imagePreview.src = e.target.result;
      imagePreviewWrapper.style.display = "block";
    };
    reader.readAsDataURL(file);
  } else {
    imagePreviewWrapper.style.display = "none";
    imagePreview.src = "";
  }
});
</script>
</body>
</html>
