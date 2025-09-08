<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn->begin_transaction();

    try {
        // ✅ Inputs
        $recipe_id   = intval($_POST['recipe_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $meal        = $_POST['meal'] ?? [];
        $dish        = $_POST['dish'] ?? [];
        $time        = trim($_POST['time'] ?? '');
        $diets       = $_POST['diet'] ?? [];
        $video_url   = trim($_POST['video_url'] ?? '');
        $ingredients = $_POST['ingredients'] ?? [];
        $steps       = $_POST['steps'] ?? [];

        // ✅ Validate
        if ($recipe_id <= 0) throw new Exception("Invalid recipe ID.");
        if ($title === '' || strlen($title) < 3) throw new Exception("Title required (min 3 chars).");
        if ($description === '' || strlen($description) < 10) throw new Exception("Description too short.");
        if (empty($meal)) throw new Exception("Select at least one meal type.");
        if ($time === '' || !ctype_digit($time) || intval($time) <= 0) throw new Exception("Invalid cooking time.");
        if (empty($ingredients)) throw new Exception("Ingredients required.");
        if (empty($steps)) throw new Exception("Steps required.");

        $meal_str  = implode(',', $meal);
        $dish_str  = implode(',', $dish);
        $diet_str  = implode(',', $diets);

        // ✅ YouTube validation
        function getYoutubeEmbedUrl($url) {
            if (preg_match('/(?:youtu\.be\/|v=|\/shorts\/|v=)([A-Za-z0-9_-]{11})/', $url, $m)) {
                return "https://www.youtube.com/embed/" . $m[1];
            }
            return null;
        }
        $embed_url = $video_url ? getYoutubeEmbedUrl($video_url) : null;
        if ($video_url && !$embed_url) throw new Exception("Invalid YouTube URL.");

        // ✅ Get current recipe
        $check = $conn->prepare("SELECT * FROM recipes WHERE id = ?");
        $check->bind_param("i", $recipe_id);
        $check->execute();
        $recipe = $check->get_result()->fetch_assoc();
        if (!$recipe) throw new Exception("Recipe not found.");

        // ✅ Handle image
        $image_url = $recipe['image_url']; // keep old by default
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            // remove old
            if (!empty($image_url) && file_exists("../" . $image_url)) {
                unlink("../" . $image_url);
            }

            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "_", basename($_FILES['image_file']['name']));
            $uniqueName = uniqid() . "_" . $fileName;
            $targetPath = $uploadDir . $uniqueName;

            if (!move_uploaded_file($fileTmp, $targetPath)) {
                throw new Exception("Image upload failed.");
            }
            $image_url = "uploads/" . $uniqueName;
        }

        // ✅ Update recipe
        $stmt = $conn->prepare("UPDATE recipes 
            SET title=?, description=?, image_url=?, meal=?, time=?, diet=?, dish=?, video_url=? 
            WHERE id=?");
        $stmt->bind_param(
            "ssssssssi",
            $title, $description, $image_url,
            $meal_str, $time, $diet_str, $dish_str, $embed_url, $recipe_id
        );
        $stmt->execute();

        // ✅ Clear old ingredients & steps
        $conn->query("DELETE FROM recipe_ingredients WHERE recipe_id=$recipe_id");
        $conn->query("DELETE FROM steps WHERE recipe_id=$recipe_id");

        // ✅ Save ingredients
        foreach ($ingredients as $line) {
            $line = trim(preg_replace('/[[:^print:]]/', '', $line));
            if ($line === "") continue;

            // try to split qty + name
            if (preg_match('/^([0-9\/.\-a-zA-Z ]+)\s+(.+)$/', $line, $m)) {
                $quantity = trim($m[1]);
                $ingName  = trim($m[2]);
            } else {
                $quantity = "";
                $ingName = $line;
            }

            // insert/find ingredient
            $ins = $conn->prepare("INSERT IGNORE INTO ingredients (name) VALUES (?)");
            $ins->bind_param("s", $ingName);
            $ins->execute();

            $ing_id = $conn->insert_id;
            if (!$ing_id) {
                $get = $conn->prepare("SELECT id FROM ingredients WHERE name=?");
                $get->bind_param("s", $ingName);
                $get->execute();
                $ing_id = $get->get_result()->fetch_assoc()['id'];
            }

            $link = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)");
            $link->bind_param("iis", $recipe_id, $ing_id, $quantity);
            $link->execute();
        }

        // ✅ Save steps
        $step_no = 1;
        foreach ($steps as $s) {
            $s = trim($s);
            if ($s === "") continue;
            $ins = $conn->prepare("INSERT INTO steps (recipe_id, step_no, instruction) VALUES (?, ?, ?)");
            $ins->bind_param("iis", $recipe_id, $step_no, $s);
            $ins->execute();
            $step_no++;
        }

        $conn->commit();

        echo "<div style='background:#e7fbe7;padding:20px;border-radius:10px;color:#2e7d32;text-align:center;'>
            ✅ Recipe updated successfully! Redirecting...
        </div>
        <script>setTimeout(()=>{window.location.href='recipe.php?id=$recipe_id';},2000);</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div style='background:#ffe5e5;padding:20px;border-radius:10px;color:#c62828;text-align:center;'>
            ❌ Error: " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
}
?>
