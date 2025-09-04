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
        // ✅ Handle Image Upload
        $image_url = '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
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
                $image_url = "uploads/" . $uniqueName; // Relative path to save in DB
            } else {
                throw new Exception("Image upload failed");
            }
        }

        // 1. Insert into `recipes`
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $meal = isset($_POST['meal']) ? implode(',', $_POST['meal']) : '';
         $dish = isset($_POST['dish']) ? implode(',', $_POST['dish']) : '';
       
        $time = trim($_POST['time']);
        $diets = isset($_POST['diet']) ? implode(',', $_POST['diet']) : '';
        $video_url = trim($_POST['video_url']); // ✅ New field
         $name=$_SESSION['user'];



function getYoutubeEmbedUrl($url) {
    // Match both youtube.com and youtu.be links
    if (preg_match('/(?:youtu\.be\/|v=|\/shorts\/)([A-Za-z0-9_-]{11})/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return null;
}

$video_url = trim($_POST['video_url']);
$embed_url = null;

if (!empty($video_url)) {
    $embed_url = getYoutubeEmbedUrl($video_url);
    if (!$embed_url) {
        throw new Exception("Invalid YouTube URL");
    }
}

         
$creator = $conn->prepare("SELECT * FROM user WHERE name = ?");
$creator->bind_param("s", $name); // "s" for string
$creator->execute();

$result = $creator->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $stmt = $conn->prepare("INSERT INTO recipes (title, description, image_url,creator, meal, time, diet,dish,video_url) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");
        $stmt->bind_param("sssssssss", $title, $description, $image_url, $user['id'], $meal, $time, $diets,$dish,$embed_url);
        $stmt->execute();
        $recipe_id = $stmt->insert_id;
    }

        // 2. Ingredients
        $ingredientLines = explode("\n", trim($_POST['ingredients']));
        foreach ($ingredientLines as $line) {
            $line = trim($line);
            if ($line == "") continue;

            // Extract quantity and name
            if (preg_match('/^(\d+(?:\/\d+)?(?:\.\d+)?)(.*)$/', $line, $matches)) {
                $quantity = trim($matches[1]);
                $name = trim($matches[2]);
            } else {
                $quantity = '';
                $name = trim($line);
            }

            // Check if ingredient exists
            $check = $conn->prepare("SELECT id FROM ingredients WHERE name = ?");
            $check->bind_param("s", $name);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $ingredient_id = $row['id'];
            } else {
                $insertIng = $conn->prepare("INSERT INTO ingredients (name) VALUES (?)");
                $insertIng->bind_param("s", $name);
                $insertIng->execute();
                $ingredient_id = $insertIng->insert_id;
            }

            // Insert into recipe_ingredients
            $link = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) 
                                    VALUES (?, ?, ?)");
            if (!$link) throw new Exception("Prepare failed: " . $conn->error);
            $link->bind_param("iis", $recipe_id, $ingredient_id, $quantity);
            $link->execute();
        }

        // 3. Steps
        $stepLines = explode("\n", trim($_POST['steps']));
        $step_no = 1;
        foreach ($stepLines as $step) {
            $step = trim($step);
            if ($step == "") continue;

            $insertStep = $conn->prepare("INSERT INTO steps (recipe_id, step_no, instruction) VALUES (?, ?, ?)");
            if (!$insertStep) throw new Exception("Prepare failed (steps): " . $conn->error);
            $insertStep->bind_param("iis", $recipe_id, $step_no, $step);
            $insertStep->execute();
            $step_no++;
        }

        $conn->commit();

        echo "
        <div style='
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 12px;
            background: #e7fbe7;
            text-align: center;
            font-family: Arial, sans-serif;
            border: 2px solid #3c763d;
            color: #3c763d;
        '>
            ✅ Recipe added successfully! Redirecting to form...
        </div>
        <script>
            setTimeout(() => {
                window.location.href = '../profile';
            }, 3000);
        </script>
        ";

    } catch (Exception $e) {
        $conn->rollback();
        echo "
        <div style='
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 12px;
            background: #ffe5e5;
            text-align: center;
            font-family: Arial, sans-serif;
            border: 2px solid #a94442;
            color: #a94442;
        '>
            ❌ Error occurred: " . $e->getMessage() . "
        </div>
        ";
    }
}
?>