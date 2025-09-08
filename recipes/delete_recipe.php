<?php
session_start();
include '../db/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipe_id'])) {
    $user_id = $_SESSION['user_id'];
    $recipe_id = intval($_POST['recipe_id']);

    // Check if recipe belongs to user
    $check_sql = "SELECT id, image_url FROM recipes WHERE id = ? AND creator = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $recipe_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $recipe = mysqli_fetch_assoc($result);

    if ($recipe) {
        // Delete related data first (ingredients, steps, etc.)
        mysqli_begin_transaction($conn);

        try {
            $tables = ['recipe_ingredients', 'steps']; // tables with recipe_id
            foreach ($tables as $table) {
                $del_sql = "DELETE FROM $table WHERE recipe_id = ?";
                $stmt_del = mysqli_prepare($conn, $del_sql);
                mysqli_stmt_bind_param($stmt_del, "i", $recipe_id);
                mysqli_stmt_execute($stmt_del);
            }

            // Delete recipe
            $delete_sql = "DELETE FROM recipes WHERE id = ? AND creator = ?";
            $stmt2 = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($stmt2, "ii", $recipe_id, $user_id);
            mysqli_stmt_execute($stmt2);

            // Remove image file if exists
            if (!empty($recipe['image_url']) && file_exists("../" . $recipe['image_url'])) {
                unlink("../" . $recipe['image_url']);
            }

            mysqli_commit($conn);
            $_SESSION['success'] = "Recipe deleted successfully.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Error deleting recipe. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Recipe not found or you don't have permission to delete it.";
    }
}

header("Location: ../profile");
exit();
