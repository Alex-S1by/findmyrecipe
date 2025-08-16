<?php
session_start();
include "../db/config.php";

header('Content-Type: application/json');

// Validate session and POST data
if (!isset($_SESSION['user_id']) || !isset($_POST['recipe_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$recipe_id = (int) $_POST['recipe_id'];

// Check if already favorited
$sql = "SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $recipe_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Remove from favorites
    $delete_sql = "DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "ii", $user_id, $recipe_id);
    mysqli_stmt_execute($delete_stmt);
   

    echo json_encode(['status' => 'removed']);
} else {
    // Add to favorites
    $insert_sql = "INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $recipe_id);
    mysqli_stmt_execute($insert_stmt);
 
    echo json_encode(['status' => 'added']);
}
?>
