
<?php
session_start();
include '../db/config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please login to view favorites.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all favorite recipe IDs
$fav_sql = "SELECT recipe_id FROM favorites WHERE user_id = $user_id ORDER BY favorited_at DESC";
$fav_result = mysqli_query($conn, $fav_sql);

// Collect recipe IDs into an array
$recipe_ids = [];
while ($row = mysqli_fetch_assoc($fav_result)) {
    $recipe_ids[] = $row['recipe_id'];
}
$Recipes = [];

 foreach ($recipe_ids as $id){

     

       $recipes_sql = "SELECT * FROM recipes WHERE id IN ($id)";
    $recipes_result = mysqli_query($conn, $recipes_sql);


     while ($recipe = mysqli_fetch_assoc($recipes_result)) {
        $Recipes[] = $recipe;
    }
 }


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
        padding: 5px;

    }

    .section-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #d10031;

        margin-bottom: 15px;
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
            <h2><i class="fa fa-heart"></i>  Favourite Recipes</h2>
           

        </div>

        <div class="row g-6  mt-md-5 mt-0">
            <?php if (!empty($Recipes)): ?>
            <?php foreach ($Recipes as $row): ?>
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
                    <p>You can add by clicking add to favourite button !</p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script>

          window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
        </script>

               </body>
               </html>