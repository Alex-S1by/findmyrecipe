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

// Check if trending
$isTrending = isset($_GET['trending']) && $_GET['trending'] == 1;

// Fetch all recipes
$sql = $isTrending
    ? "SELECT id, title, image_url, meal, time, diet, created_at, views 
       FROM recipes 
       WHERE status = 'active' AND views > 0
       ORDER BY views DESC, created_at DESC"
    : "SELECT id, title, image_url, meal, time, diet, created_at, views
       FROM recipes 
       WHERE status='active'
       ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);

// Build array for PHP rendering and JS
$allRecipes = [];
while($row = mysqli_fetch_assoc($result)){
    $allRecipes[] = $row;
}
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
        <input type="text" id="searchInput" placeholder="Search recipes..." >
    </div>
    <div class="filter-section">
        <label class="form-label">Meal Type</label><br>
        <input type="checkbox" name="meal[]" value="Breakfast" id="breakfast"> <label for="breakfast">Breakfast</label><br>
        <input type="checkbox" name="meal[]" value="Lunch" id="lunch" > <label for="lunch">Lunch</label><br>
        <input type="checkbox" name="meal[]" value="Dinner" id="dinner"> <label for="dinner">Dinner</label>
    </div>
    <div class="filter-section">
        <label class="form-label">Diet</label><br>
        <input type="checkbox" name="diet[]" value="Vegetarian" id="veg" > <label for="veg">Vegetarian</label><br>
        <input type="checkbox" name="diet[]" value="Non-Vegetarian" id="nonveg"> <label for="nonveg">Non-Veg</label>
    </div>
    <div class="filter-section">
        <label class="form-label">Cooking Time</label><br>
        <input type="radio" name="time" value="15" id="time15" > <label for="time15">Under 15 mins</label><br>
        <input type="radio" name="time" value="30" id="time30" > <label for="time30">Under 30 mins</label><br>
        <input type="radio" name="time" value="60" id="time60" > <label for="time60">Under 1 hour</label><br>
        <input type="radio" name="time" value="0" id="timeAny" checked > <label for="timeAny">Any</label>
    </div>
    <button class="btn btn-danger w-100" onclick="applyFilters()">Apply Filters</button>
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

    <div id="recipeGrid" class="row g-4"></div>
    <div id="pagination" class="d-flex justify-content-center mt-4"></div>
</div>

<script>
    const searchinpt=document.getElementById('searchInput');
const recipes = <?= json_encode($allRecipes) ?>;
let currentPage = 1;
const recipesPerPage = 9;

function toggleFilterSidebar(){
    const sidebar = document.getElementById("filterSidebar");
    const overlay = document.getElementById("filterOverlay");
    sidebar.classList.toggle("open");
    overlay.classList.toggle("show");
    document.body.style.overflow = sidebar.classList.contains("open") ? "hidden" : "auto";
}
searchinpt.addEventListener("keydown", function(e){
      
 if(e.key === "Enter" ){
    

       applyFilters();
    }

});
document.addEventListener("keydown", function(e){
    if(e.key === "Escape" && document.getElementById("filterSidebar").classList.contains("open")){
        toggleFilterSidebar();
    }
});

function renderRecipes(filtered) {
    const container = document.getElementById('recipeGrid');
    container.innerHTML = '';

    if(filtered.length === 0){
        container.innerHTML = `<div class="col-12 text-center text-muted"><p>No recipes found.</p></div>`;
        return;
    }

    filtered.forEach(row => {
        container.innerHTML += `
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card recipe-card h-100">
                <a href="./recipe.php?id=${row.id}" class="text-decoration-none text-dark">
                    <div class="card-img-container">
                        <img src="../${row.image_url}" class="card-img-top" alt="${row.title}">
                        <span class="badge time-badge position-absolute top-0 end-0 m-2">
                            <i class="fas fa-clock"></i> ${row.time} mins
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="meal-category">
                            <i class="fas fa-utensils"></i> ${row.meal}
                        </div>
                        <h5 class="card-title">${row.title}</h5>
                        <div class="recipe-meta">
                            <div class="meta-item"><i class="fas fa-users"></i> 4 servings</div>
                            <div class="meta-item"><i class="fas fa-calendar-alt"></i> ${new Date(row.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>`;
    });
}

// Pagination rendering
function renderRecipesWithPagination(filtered) {
    const totalPages = Math.ceil(filtered.length / recipesPerPage);
    if(currentPage > totalPages) currentPage = totalPages || 1;

    const start = (currentPage - 1) * recipesPerPage;
    const end = start + recipesPerPage;
    const paginatedRecipes = filtered.slice(start, end);

    renderRecipes(paginatedRecipes);
    renderPagination(totalPages, filtered);
}

function renderPagination(totalPages, filtered) {
    const container = document.getElementById('pagination');
    container.innerHTML = '';
    if(totalPages <= 1) return;

    const prevBtn = document.createElement('button');
    prevBtn.className = 'btn btn-outline-danger me-2';
    prevBtn.textContent = 'Prev';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => { currentPage--; renderRecipesWithPagination(filtered); };
    container.appendChild(prevBtn);

    for(let i=1; i<=totalPages; i++){
        const btn = document.createElement('button');
        btn.className = `btn me-2 ${i === currentPage ? 'btn-danger' : 'btn-outline-danger'}`;
        btn.textContent = i;
        btn.onclick = () => { currentPage = i; renderRecipesWithPagination(filtered); };
        container.appendChild(btn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'btn btn-outline-danger';
    nextBtn.textContent = 'Next';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = () => { currentPage++; renderRecipesWithPagination(filtered); };
    container.appendChild(nextBtn);
}

// Filtering
function applyFilters() {
     toggleFilterSidebar();
   
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const selectedMeals = Array.from(document.querySelectorAll('input[name="meal[]"]:checked')).map(el => el.value);
    const selectedDiets = Array.from(document.querySelectorAll('input[name="diet[]"]:checked')).map(el => el.value);
    const selectedTime = parseInt(document.querySelector('input[name="time"]:checked')?.value || 0);

    const filtered = recipes.filter(row => {
        if(selectedMeals.length) {
            const rowMeals = row.meal.split(',').map(m => m.trim());
            if(!selectedMeals.some(meal => rowMeals.includes(meal))) return false;
        }
        if(selectedDiets.length && !selectedDiets.includes(row.diet)) return false;
        if(selectedTime && row.time > selectedTime) return false;
        if(searchText && !row.title.toLowerCase().includes(searchText)) return false;
        return true;
    });

    currentPage = 1; // reset page
    renderRecipesWithPagination(filtered);
}

// Initial render
renderRecipesWithPagination(recipes);
</script>

</body>
</html>
