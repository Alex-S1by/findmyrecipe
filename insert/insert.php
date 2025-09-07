<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}
include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        // ✅ Sanitize inputs
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $meal        = isset($_POST['meal']) ? implode(',', $_POST['meal']) : '';
        $dish        = isset($_POST['dish']) ? implode(',', $_POST['dish']) : '';
        $time        = trim($_POST['time'] ?? '');
        $diets       = isset($_POST['diet']) ? implode(',', $_POST['diet']) : '';
        $video_url   = trim($_POST['video_url'] ?? '');
        $ingredients = trim($_POST['ingredients'] ?? '');
        $steps       = trim($_POST['steps'] ?? '');
        $name        = $_SESSION['user'];

        // ✅ Backend validation
        if ($title === '' || strlen($title) < 3) throw new Exception("Recipe title is required (min 3 chars).");
        if ($description === '' || strlen($description) < 10) throw new Exception("Description is required (min 10 chars).");
        if ($meal === '') throw new Exception("Please select at least one meal type.");
        if ($time === '' || !ctype_digit($time) || intval($time) <= 0) throw new Exception("Enter a valid cooking time.");
        if ($ingredients === '') throw new Exception("Ingredients cannot be empty.");
        if ($steps === '') throw new Exception("Steps cannot be empty.");

        // ✅ Handle Image Upload
        $image_url = '';
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Image upload is required.");
        }

        $uploadDir = "../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmp = $_FILES['image_file']['tmp_name'];
        $fileName = basename($_FILES['image_file']['name']);
        $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName); // Sanitize filename
        $uniqueName = uniqid() . "_" . $fileName;
        $targetPath = $uploadDir . $uniqueName;

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $image_url = "uploads/" . $uniqueName;
        } else {
            throw new Exception("Image upload failed.");
        }

        // ✅ YouTube URL check
        function getYoutubeEmbedUrl($url) {
            if (preg_match('/(?:youtu\.be\/|v=|\/shorts\/)([A-Za-z0-9_-]{11})/', $url, $matches)) {
                return "https://www.youtube.com/embed/" . $matches[1];
            }
            return null;
        }

        $embed_url = null;
        if (!empty($video_url)) {
            $embed_url = getYoutubeEmbedUrl($video_url);
            if (!$embed_url) throw new Exception("Invalid YouTube URL.");
        }

        // ✅ Find user id
        $creator = $conn->prepare("SELECT * FROM user WHERE name = ?");
        $creator->bind_param("s", $name);
        $creator->execute();
        $result = $creator->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("User not found.");
        }

        $user = $result->fetch_assoc();

        // ✅ Insert recipe
        $stmt = $conn->prepare("INSERT INTO recipes 
            (title, description, image_url, creator, meal, time, diet, dish, video_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssssss",
            $title,
            $description,
            $image_url,
            $user['id'],
            $meal,
            $time,
            $diets,
            $dish,
            $embed_url
        );
        $stmt->execute();
        $recipe_id = $stmt->insert_id;

        // ✅ Ingredients
        $ingredientLines = explode("\n", $ingredients);
        foreach ($ingredientLines as $line) {
            $line = trim($line);
            if ($line == "") continue;

            if (preg_match('/^(\d+(?:\/\d+)?(?:\.\d+)?)(.*)$/', $line, $matches)) {
                $quantity = trim($matches[1]);
                $ingName = trim($matches[2]);
            } else {
                $quantity = '';
                $ingName = $line;
            }

            $check = $conn->prepare("SELECT id FROM ingredients WHERE name = ?");
            $check->bind_param("s", $ingName);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $ingredient_id = $row['id'];
            } else {
                $insertIng = $conn->prepare("INSERT INTO ingredients (name) VALUES (?)");
                $insertIng->bind_param("s", $ingName);
                $insertIng->execute();
                $ingredient_id = $insertIng->insert_id;
            }

            $link = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)");
            $link->bind_param("iis", $recipe_id, $ingredient_id, $quantity);
            $link->execute();
        }

        // ✅ Steps
        $stepLines = explode("\n", $steps);
        $step_no = 1;
        foreach ($stepLines as $step) {
            $step = trim($step);
            if ($step == "") continue;

            $insertStep = $conn->prepare("INSERT INTO steps (recipe_id, step_no, instruction) VALUES (?, ?, ?)");
            $insertStep->bind_param("iis", $recipe_id, $step_no, $step);
            $insertStep->execute();
            $step_no++;
        }

        $conn->commit();

        echo "<div style='background:#e7fbe7;padding:20px;border-radius:10px;color:#3c763d;text-align:center;'>
            ✅ Recipe added successfully! Redirecting...
        </div>
        <script>setTimeout(()=>{window.location.href='../profile';},3000);</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div style='background:#ffe5e5;padding:20px;border-radius:10px;color:#a94442;text-align:center;'>
            ❌ Error: " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
}
?>
