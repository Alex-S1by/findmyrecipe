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
$recipesPerPage = 9;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $recipesPerPage;

// Total number of recipesa
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM recipes");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalRecipes = $totalRow['total'];
$totalPages = ceil($totalRecipes / $recipesPerPage);

// Fetch latest to oldest
$sql = "SELECT id, title, image_url, meal, time, created_at FROM recipes  WHERE status = 'active' ORDER BY created_at DESC LIMIT $offset, $recipesPerPage";
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
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', sans-serif;
    }

    .section-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .section-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #d10031;
    }

    .recipe-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s;
        background: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        height: 100%;
    }

    .recipe-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    .card-img-container {
        position: relative;
        overflow: hidden;
        height: 220px;
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

    .time-badge {
        background: rgba(0, 0, 0, 0.75);
        border-radius: 20px;
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #fff;
    }

    .card-body {
        padding: 22px;
        display: flex;
        flex-direction: column;
    }

    .meal-category {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: #6c757d;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #343a40;
        margin-bottom: 12px;
    }

    .recipe-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #dee2e6;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .meta-item i {
        color: black;
    }

    .pagination {
        margin-top: 40px;
    }

    @media (max-width: 576px) {
        .card-title {
            font-size: 1rem;
        }

        .card-body {
            padding: 18px;
        }

        .card-img-container {
            height: 180px;
        }
    }
    </style>
</head>

<body>
    <?php include "../includes/header.php";  ?>
    <div class="container py-5">

        <div class="section-header">
            <h2 class="  text-black text-lg"><i class="fas fa-list-ul"></i> All Recipes</h2>

  

        </div>

        <div class="row g-4">
            <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card recipe-card h-100">
                    <a href="./recipe.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                        <div class="card-img-container">
                            <img src="../<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($row['title']) ?>">
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
                                <div class="meta-item">
                                    <i class="fas fa-users"></i> 4 servings
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?= date("d M Y", strtotime($row['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="col-12 text-center text-muted">
                <p>No recipes found.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

</body>

</html>