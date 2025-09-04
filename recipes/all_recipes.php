<?php
include '../db/config.php';
session_start();

// Redirect if not logged in
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

// Pagination
$recipesPerPage = 9;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $recipesPerPage;

// Check if trending
$isTrending = isset($_GET['trending']) && $_GET['trending'] == 1;

// Count total recipes
$totalQuery = $isTrending
    ? mysqli_query($conn, "SELECT COUNT(*) AS total FROM recipes WHERE status='active' AND views > 0")
    : mysqli_query($conn, "SELECT COUNT(*) AS total FROM recipes WHERE status='active'");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalRecipes = $totalRow['total'];
$totalPages = ceil($totalRecipes / $recipesPerPage);

// Fetch recipes
$sql = $isTrending
    ? "SELECT id, title, image_url, meal, time, created_at, views 
       FROM recipes 
       WHERE status = 'active' AND views > 0
       ORDER BY views DESC, created_at DESC
       LIMIT $offset, $recipesPerPage"
    : "SELECT id, title, image_url, meal, time, created_at, views
       FROM recipes 
       WHERE status='active'
       ORDER BY created_at DESC
       LIMIT $offset, $recipesPerPage";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Recipes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }

        .section-header { margin-bottom: 30px; }
        .section-header h2 { font-size: 2rem; font-weight: 700; color: #d10031; margin: 0; }

        .recipe-card { border: none; border-radius: 20px; overflow: hidden; transition: all 0.4s; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.1); height: 100%; }
        .recipe-card:hover { transform: translateY(-8px); box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .card-img-container { position: relative; overflow: hidden; height: 220px; }
        .card-img-top { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
        .recipe-card:hover .card-img-top { transform: scale(1.1); }
        .time-badge { background: rgba(0,0,0,0.75); border-radius: 20px; padding: 6px 12px; font-size: 0.85rem; font-weight: 600; color: #fff; }

        .card-body { padding: 22px; display: flex; flex-direction: column; }
        .meal-category { font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; color: #6c757d; margin-bottom: 8px; text-transform: uppercase; }
        .card-title { font-size: 1.2rem; font-weight: 700; color: #343a40; margin-bottom: 12px; }
        .recipe-meta { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 10px; border-top: 1px solid #dee2e6; font-size: 0.85rem; color: #6c757d; }
        .meta-item { display: flex; align-items: center; gap: 6px; }
        .meta-item i { color: black; }

        /* Filter Sidebar */
        .filter-sidebar { height: 100%; width: 0; position: fixed; z-index: 999; top: 0; left: 0; background: linear-gradient(180deg,#2c3e50 0%,#34495e 100%); overflow-x: hidden; transition: all 0.4s; padding-top: 60px; color: white; box-shadow: 5px 0 20px rgba(0,0,0,0.3); }
        .filter-sidebar.open { width: 280px; }
        .filter-sidebar h5 { font-size: 1.5rem; font-weight: 500; padding: 0 30px 20px; }
        .filter-sidebar label { font-weight: 500; }
        .filter-sidebar input[type="text"] { background: #fff; border-radius: 10px; border: none; padding: 8px 12px; width: 80%; margin: 0 30px 15px; }
        .filter-sidebar .filter-section { padding: 0 30px 20px; }
        .filter-sidebar button { margin: 0 ; border-radius: 25px; padding: 10px; background: #e60033; border: none; font-weight: 400; transition: all 0.3s; }
        .filter-sidebar button:hover { background: #c70029; }

        .filter-closebtn { position: absolute; top: 15px; right: 25px; font-size: 2rem; color: #ecf0f1; cursor: pointer; transition: all 0.3s ease; }
        .filter-closebtn:hover { color: #e74c3c; }

        .filter-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; transition: opacity 0.3s ease; }
        .filter-overlay.show { display: block; }

        .filter-btn { border: 2px solid #e60033; color: #e60033; font-weight: 600; border-radius: 25px; padding: 8px 16px; transition: 0.3s; }
        .filter-btn:hover { background: #e60033; color: #fff; }

        @media (max-width:576px){.card-title{font-size:1rem;}.card-body{padding:18px;}.card-img-container{height:180px;}}
    </style>
</head>
<body>

<?php include "../includes/header.php"; ?>

<!-- Overlay -->
<div id="filterOverlay" class="filter-overlay" onclick="toggleFilterSidebar()"></div>

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar">
    <a href="javascript:void(0)" class="filter-closebtn" onclick="toggleFilterSidebar()">&times;</a>
    <h5>Filters</h5>
    <div class="filter-section">
        <label class="form-label">Search</label>
        <input type="text" placeholder="Search recipes...">
    </div>
    <div class="filter-section">
        <label class="form-label">Meal Type</label><br>
        <input type="checkbox" id="breakfast"> <label for="breakfast">Breakfast</label><br>
        <input type="checkbox" id="lunch"> <label for="lunch">Lunch</label><br>
        <input type="checkbox" id="dinner"> <label for="dinner">Dinner</label>
    </div>
    <div class="filter-section">
        <label class="form-label">Diet</label><br>
        <input type="checkbox" id="veg"> <label for="veg">Vegetarian</label><br>
        <input type="checkbox" id="nonveg"> <label for="nonveg">Non-Veg</label>
    </div>
    <div class="filter-section">
        <label class="form-label">Cooking Time</label><br>
        <input type="radio" name="time" id="15"> <label for="15">Under 15 mins</label><br>
        <input type="radio" name="time" id="30"> <label for="30">Under 30 mins</label><br>
        <input type="radio" name="time" id="60"> <label for="60">Under 1 hour</label>
    </div>
    <button class="btn btn-danger w-100">Apply Filters</button>
</div>

<div class="container py-5 main-class">
    <div class="section-header d-flex justify-content-center align-items-center position-relative">
        <button class="btn filter-btn position-absolute start-0" onclick="toggleFilterSidebar()"><i class="fas fa-sliders-h"></i> Filters</button>
        <?php if($isTrending): ?>
            <h2 class="text-black text-lg"><i class="fas fa-fire"></i> Trending Recipes</h2>
        <?php else: ?>
            <h2 class="text-black text-lg"><i class="fas fa-list-ul"></i> All Recipes</h2>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card recipe-card h-100">
                        <a href="./recipe.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                            <div class="card-img-container">
                                <img src="../<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($row['title']) ?>">
                                <span class="badge time-badge position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-clock"></i> <?= htmlspecialchars($row['time']) ?> mins
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="meal-category">
                                    <i class="fas fa-utensils"></i> <?= htmlspecialchars($row['meal']) ?>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                <div class="recipe-meta">
                                    <div class="meta-item"><i class="fas fa-users"></i> 4 servings</div>
                                    <div class="meta-item"><i class="fas fa-calendar-alt"></i> <?= date("d M Y", strtotime($row['created_at'])) ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted"><p>No recipes found.</p></div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $isTrending ? '&trending=1' : '' ?>">Previous</a>
                </li>
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $isTrending ? '&trending=1' : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $isTrending ? '&trending=1' : '' ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
function toggleFilterSidebar(){
    const sidebar = document.getElementById("filterSidebar");
    const overlay = document.getElementById("filterOverlay");
    sidebar.classList.toggle("open");
    overlay.classList.toggle("show");
    document.body.style.overflow = sidebar.classList.contains("open") ? "hidden" : "auto";
}
document.addEventListener("keydown", function(e){
    if(e.key === "Escape" && document.getElementById("filterSidebar").classList.contains("open")){
        toggleFilterSidebar();
    }
});
</script>

</body>
</html>
