<?php
include "db/config.php";

$sql = "SELECT * FROM recipes WHERE status = 'active'  ORDER BY created_at DESC   LIMIT 4";

$result = mysqli_query($conn, $sql);



$sql2 = "SELECT * 
         FROM recipes 
         WHERE status = 'active' 
         ORDER BY views DESC  
         LIMIT 4";
$result2 = mysqli_query($conn, $sql2);
?>
<style>
/* Section Header */
.section-header {
    text-align: center;
    margin-bottom: 10px;
    padding: 40px 0;
}

.section-header h2 {
    font-size: 3rem;
    font-weight: 700;
    color: black;
    ;

    margin-bottom: 15px;
}

.section-header p {
    font-size: 1.2rem;
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
    height: 100%;
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
    display: flex;
    flex-direction: column;
    height: calc(100% - 250px);
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
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: #4a5568;
}
</style>




<div class="container my-5">
    <!-- Section Header -->
    <div class="section-header">
        <h2><i class="fa-solid fa-arrow-trend-up"></i> Trending Recipes</h2>
        <p>Discover our most popular and delicious recipes</p>
    </div>

    <!-- Recipe Cards -->
    <div class="row g-4">
        <?php if (mysqli_num_rows($result2) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result2)): ?>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
            <div class="card recipe-card h-100">
                <a href="recipes/recipe.php?id=<?= $row['id'] ?>" class="card-link">
                    <div class="card-img-container">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top"
                            alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">


                        <!-- Time Badge -->
                        <span class="badge time-badge position-absolute top-0 end-0 m-3">
                            <i class="fas fa-clock"></i>
                            <?= htmlspecialchars($row['time']) ?> mins
                        </span>


                    </div>

                    <div class="card-body">
                        <div class="meal-category">
                            <i class="fas fa-utensils"></i>
                            <?= htmlspecialchars($row['dish']) ?>
                        </div>

                        <h5 class="card-title">
                            <?= htmlspecialchars($row['title']) ?>
                        </h5>

                        <?php if (isset($row['description'])): ?>
                        <p class="card-text text-muted" style="font-size: 0.9rem; line-height: 1.4;">
                            <?= htmlspecialchars(substr($row['description'], 0, 80)) ?>...
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
        <?php endwhile; ?>
        <?php else: ?>
        <!-- Empty State -->
        <div class="col-12">
            <div class="empty-state">
                <i class="fas fa-utensils"></i>
                <h3>No Recipes Found</h3>
                <p>We couldn't find any recipes at the moment. Please check back later!</p>
            </div>
        </div>
        <?php endif; ?>
    <div class="text-center mt-5">
    <a href="recipes/all_recipes.php?trending=1"
        class="btn btn-lg px-4 py-2 rounded-pill shadow d-inline-flex align-items-center gap-2"
        style="background: linear-gradient(135deg, #000000, #434343); color: #fff; border: none;">
        Show Trending Recipes 
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>


    </div>
</div>




<div class="container my-5">
    <!-- Section Header -->
    <div class="section-header">
       <h2><i class="fas fa-clock"></i> Latest Recipes</h2>
       <p>Discover our latest popular and delicious recipes</p>
        
    </div>

    <!-- Recipe Cards -->
    <div class="row g-4">
        <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
            <div class="card recipe-card h-100">
                <a href="recipes/recipe.php?id=<?= $row['id'] ?>" class="card-link">
                    <div class="card-img-container">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top"
                            alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">


                        <!-- Time Badge -->
                        <span class="badge time-badge position-absolute top-0 end-0 m-3">
                            <i class="fas fa-clock"></i>
                            <?= htmlspecialchars($row['time']) ?> mins
                        </span>


                    </div>

                    <div class="card-body">
                        <div class="meal-category">
                            <i class="fas fa-utensils"></i>
                            <?= htmlspecialchars($row['dish']) ?>
                        </div>

                        <h5 class="card-title">
                            <?= htmlspecialchars($row['title']) ?>
                        </h5>

                        <?php if (isset($row['description'])): ?>
                        <p class="card-text text-muted" style="font-size: 0.9rem; line-height: 1.4;">
                            <?= htmlspecialchars(substr($row['description'], 0, 80)) ?>...
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
        <?php endwhile; ?>
        <?php else: ?>
        <!-- Empty State -->
        <div class="col-12">
            <div class="empty-state">
                <i class="fas fa-utensils"></i>
                <h3>No Recipes Found</h3>
                <p>We couldn't find any recipes at the moment. Please check back later!</p>
            </div>
        </div>
        <?php endif; ?>
        <div class="text-center mt-5">
       
            <a href="recipes/all_recipes.php"
        class="btn btn-lg px-4 py-2 rounded-pill shadow d-inline-flex align-items-center gap-2"
        style="background: linear-gradient(135deg, #000000, #434343); color: #fff; border: none;">
        Show All Recipes
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
    </div>
</div>