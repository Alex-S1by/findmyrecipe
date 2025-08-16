<?php
include '../db/config.php';
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

$ingredientInput = $_GET['ingredients'] ?? '';
$ingredientWords = array_filter(array_map('trim', explode(',', strtolower($ingredientInput))));

$mealTypeFilter    = isset($_GET['meal'])  ? (array)$_GET['meal']       : [];
$specialDietFilter = isset($_GET['diet'])  ? (array)$_GET['diet']       : [];
$cookingTimeFilter = isset($_GET['time'])  ? (array)$_GET['time']       : [];




if (empty($ingredientWords)) {
    echo "❌ Please enter at least one ingredient.";
    exit;
}

// Step 1: Get all recipes matching ingredients
$recipeMatches = [];

foreach ($ingredientWords as $word) {
    $escaped = mysqli_real_escape_string($conn, $word);

    $sql = "
        SELECT
            r.id AS recipe_id,
            r.title,
            r.meal,
            r.diet,
            r.time,
            r.image_url,
            r.description,

            i.name AS ingredient_name
        FROM recipes r
        JOIN recipe_ingredients ri ON r.id = ri.recipe_id
        JOIN ingredients i ON ri.ingredient_id = i.id
       WHERE r.status = 'active'
      AND
         LOWER(i.name) LIKE '%$escaped%'
    ";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("❌ SQL Error: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rid = $row['recipe_id'];

        if (!isset($recipeMatches[$rid])) {
            $recipeMatches[$rid] = [
                'id' => $rid,
                'title' => $row['title'],
                'meal' => $row['meal'],
                'diet' => $row['diet'],
                'time' => $row['time'],
                'image_url' => $row['image_url'],
                'description' => $row['description'],

                'ingredients' => [],
                'matched_words' => [],
                'unMatched_words' => [],
            ];
        }

        if (!in_array($word, $recipeMatches[$rid]['matched_words'])) {
            $recipeMatches[$rid]['matched_words'][] = $word;
            $recipeMatches[$rid]['ingredients'][] = $row['ingredient_name'];
        }
    }
}

// Step 2: Filter recipes
$filteredRecipes = [];
$suggestedRecipes = [];
$recommendedRecipes=[];

foreach ($recipeMatches as $recipe) {

      $matchCount = count($recipe['matched_words']);
    $ingredientCount = count($ingredientWords);


    $matchFilter = true;
    
if (!empty($mealTypeFilter)) {
    $recipeMeals = array_map('trim', explode(',', $recipe['meal']));

    // if no overlap between recipe meals and selected filters, skip it
    if (empty(array_intersect($recipeMeals, $mealTypeFilter))) {
        $matchFilter = false;
    }
}



    if (!empty($specialDietFilter)) {
    $recipeDiets = array_map('trim', explode(',', $recipe['diet']));
    if (empty(array_intersect($recipeDiets, $specialDietFilter))) {
        $matchFilter = false;
    }
}
    // Cooking time filter (user can pick only 1 value)
if (!empty($cookingTimeFilter)) {
    $recipeTime = (int) $recipe['time'];
    $timeLimit = (int) $cookingTimeFilter[0]; // first value only




   
    if ($recipeTime > $timeLimit) {
        $matchFilter = false;
    }
}


   if ($matchCount === $ingredientCount && $matchFilter) {
        $filteredRecipes[] = $recipe;
    }
    elseif($matchCount==1)
    {
        $recommendedRecipes[]=$recipe;
    } else {
        $suggestedRecipes[] = $recipe;
    }
}

// Step 3: Sort by number of matched ingredients
usort($filteredRecipes, fn($a, $b) => count($b['matched_words']) <=> count($a['matched_words']));
usort($suggestedRecipes, fn($a, $b) => count($b['matched_words']) <=> count($a['matched_words']));
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recipe Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>

    <style>
    /* Section Header */
    .section-header {
        text-align: center;


    }

    .section-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #181516ff;

        margin-bottom: 45px;
    }

    .section-header p {
        font-size: 1rem;
        color: #6c757d;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Recipe Cards */
    .recipe-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: relative;
        display: flex;
        flex-direction: column;
        max-width: 700px;
        margin: 0 auto;

    }


    .recipe-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    .card-link {
        text-decoration: none !important;
        color: inherit !important;
        display: block;
        height: 100%;
    }

    .card-img-container {
        position: relative;
        overflow: hidden;
        height: 250px;
    }

    .card-img-top {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .recipe-card:hover .card-img-top {
        transform: scale(1.1);
    }

    /* Image Overlay */
    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(180deg,
                rgba(0, 0, 0, 0.1) 0%,
                rgba(0, 0, 0, 0.3) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .recipe-card:hover .image-overlay {
        opacity: 1;
    }

    /* Badges */
    .time-badge {
        background: rgba(0, 0, 0, 0.8) !important;
        backdrop-filter: blur(10px);
        border-radius: 20px !important;
        padding: 8px 15px !important;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }



    /* Card Body */
    .card-body {
        padding: 25px;
        /* Increased padding */
        display: flex;
        flex-direction: column;

    }

    .meal-category {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: #2d3748;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .card-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 15px;
        line-height: 1.3;
        word-break: break-word;
        white-space: normal;
    }


    .recipe-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.85rem;
        color: #718096;
    }

    .meta-item i {
        color: #667eea;
    }




    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }

    .empty-state i {
        font-size: 1rem;
        color: #cbd5e0;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: .8rem;
        margin-bottom: 10px;
        color: #4a5568;
    }

    /* Mobile-first card spacing & adjustments */
    .card-spacing {
        margin-bottom: 30px;
    }

    @media (max-width: 576px) {
        .card-spacing {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .recipe-card {
            border-radius: 12px;
            margin: 0 auto;
        }

        .card-title {
            font-size: 1.1rem;
        }

        .section-header h2 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .section-header p {
            font-size: 0.85rem;
        }

        .card-body {
            padding: 18px;
        }

        .card-img-container {
            height: 200px;
        }
    }
    </style>


    <?php include "../includes/header.php"; ?>

    <div class="container my-5 ">
        <div class="section-header">
            <h2><i class="fas fa-search"></i> Search Results</h2>


        </div>

        <div class="row g-6  mt-md-5 mt-0">
            <?php if (!empty($filteredRecipes)): ?>
            <?php foreach ($filteredRecipes as $row): ?>
            <div class="col-12 card-spacing">


                <div class="card recipe-card h-100">
                    <a href="../recipes/recipe.php?id=<?= $row['id'] ?>" class="card-link">
                        <div class="card-img-container">
                            <img src="../<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">

                            <span class="badge time-badge position-absolute top-0 end-0 m-3">
                                <i class="fas fa-clock"></i>
                                <?= htmlspecialchars($row['time']) ?> mins
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="meal-category">
                                <i class="fas fa-utensils"></i>
                                <?= htmlspecialchars($row['meal'] ?? 'Meal') ?>
                            </div>

                            <h5 class="card-title">
                                <?= htmlspecialchars($row['title']) ?>
                            </h5>

                            <?php if (!empty($row['matched_words'])): ?>
                            <p class="card-text text-muted" style="font-size: 0.9rem; line-height: 1.4;">
                                <strong>Matched Ingredients:</strong>
                                <?= htmlspecialchars(implode(', ', $row['matched_words'])) ?>
                            </p>
                            <?php endif; ?>

                            <div class="recipe-meta">
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <?= isset($row['servings']) ? htmlspecialchars($row['servings']) : '4' ?> servings
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <h3>No Recipes Found</h3>
                    <p>We couldn't find any recipes that satisfies provided data !</p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="container my-5 ">
        <div class="section-header">


            <h2><i class="fas fa-search"></i> Suggested Recipes..</h2>
            <div class="row g-4 ">
                <?php if (!empty($suggestedRecipes)): ?>
                <?php foreach ($suggestedRecipes as $row): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">


                    <div class="card recipe-card h-100">
                        <a href="../recipes/recipe.php?id=<?= $row['id'] ?>" class="card-link">
                            <div class="card-img-container">
                                <img src="../<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">

                                <span class="badge time-badge position-absolute top-0 end-0 m-3">
                                    <i class="fas fa-clock"></i>
                                    <?= htmlspecialchars($row['time']) ?> mins
                                </span>
                            </div>

                            <div class="card-body">
                                <div class="meal-category">
                                    <i class="fas fa-utensils"></i>
                                    <?= htmlspecialchars($row['meal'] ?? 'Meal') ?>
                                </div>

                                <h5 class="card-title">
                                    <?= htmlspecialchars($row['title']) ?>
                                </h5>
                                <?php if (!empty($row['matched_words'])): ?>
                                <p class="card-text text-muted" style="font-size: 0.9rem; line-height: 1.4;">
                                    <strong>Matched Ingredients:</strong>
                                    <?= htmlspecialchars(implode(', ', $row['matched_words'])) ?>
                                </p>
                                <?php endif; ?>

                                <div class="recipe-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-users"></i>
                                        <?= isset($row['servings']) ? htmlspecialchars($row['servings']) : '4' ?>
                                        servings
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>No Recipes Found</h3>
                        <p>We couldn't find any recipes that satisfies provided data !</p>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>


        <div class="container my-5">
            <div class="section-header">
                <h2 >Recommended Recipes</h2>
           
            <div class="row g-4 ">
                <?php if (!empty($recommendedRecipes)): ?>
                <?php foreach ($recommendedRecipes as $row): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="card recipe-card h-100">
                        <a href="../recipes/recipe.php?id=<?= $row['id'] ?>" class="card-link">

                            <!-- Image -->
                            <div class="card-img-container">
                                <img src="../<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">
                                <span class="badge time-badge position-absolute top-0 end-0 m-3">
                                    <i class="fas fa-clock"></i>
                                    <?= htmlspecialchars($row['time']) ?> mins
                                </span>
                            </div>

                            <!-- Body -->
                            <div class="card-body d-flex flex-column">
                                <div>
                                    <div class="meal-category mb-2">
                                        <i class="fas fa-utensils"></i>
                                        <?= htmlspecialchars($row['meal'] ?? 'Meal') ?>
                                    </div>

                                    <h5 class="card-title mb-2">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </h5>

                                    <?php if (!empty($row['matched_words'])): ?>
                                    <p class="card-text text-muted small">
                                        <strong>Matched Ingredients:</strong>
                                        <?= htmlspecialchars(implode(', ', $row['matched_words'])) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Footer -->
                                <div class="mt-auto recipe-meta pt-2 border-top">
                                    <div class="meta-item">
                                        <i class="fas fa-users"></i>
                                        <?= isset($row['servings']) ? htmlspecialchars($row['servings']) : '4' ?>
                                        servings
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>No Recipes Found</h3>
                        <p>We couldn't find any recipes that match the provided data!</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>





</body>

</html>