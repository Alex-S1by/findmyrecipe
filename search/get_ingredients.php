<?php
include '../db/config.php';

header("Content-Type: application/json; charset=UTF-8");

/**
 * Fix fractions and encoding
 */
function fixFractions($str) {
    $map = ["Â½"=>"½","Â¼"=>"¼","Â¾"=>"¾","ï»¿"=>""];
    return strtr($str, $map);
}

/**
 * Clean ingredient names for suggestions
 */
function cleanIngredientName($line) {
    $line = fixFractions($line);
  // Remove leading slash
    $line = ltrim($line, '/');

    // Remove BOM, fractions, numbers
    $line = preg_replace('/[0-9]+[^\s]*/u', '', $line); 
    $line = preg_replace('/[¼½¾]/u', '', $line);

    // Remove common units
    $line = preg_replace('/\b(kg|g|Gm|grams?|ml|l|litres?|cup?s?|tbsp|tsp|lbs?|pcs?|nos?|inch|pinch|handful|glass|slice|piece|pieces|Teaspoons|Teaspoon|Tablespoon|Tablespoons?)\b/i', '', $line);

    // Remove brackets and their contents
    $line = preg_replace('/\(.*?\)/', '', $line);

    // Remove preparation words
    $line = preg_replace('/\b(chopped|sliced|cubed|finely|cut|pieces?|skinned|boneless|into|small|large|medium|thick|thin|No|Nos|lengthwise|big|diced|crushed|roughly|optional|peeled|soaked|boiled|hot|deep|for|As|reqd|As reqd|required|Required|req|rqrd|for|taste|to|frying)\b/i', '', $line);

    // Remove stray punctuation
    $line = preg_replace('/[.,+)(:-]+/', ' ', $line);

    // Normalize spacing
    $line = preg_replace('/\s+/', ' ', $line);


    return ucfirst(trim($line));
}


if (isset($_GET['q'])) {
    $q = trim($_GET['q']);

    if ($q !== "") {
        $likeAny = "%" . $q . "%";

        $stmt = $conn->prepare("SELECT name FROM ingredients WHERE name LIKE ? LIMIT 50");
        $stmt->bind_param("s", $likeAny);
        $stmt->execute();
        $result = $stmt->get_result();

        $suggestions = [];
        while ($row = $result->fetch_assoc()) {
            $clean = cleanIngredientName($row['name']);
            if ($clean !== "" && stripos($clean, $q) !== false) {
                $suggestions[] = ucfirst($clean);
            }
        }

        // Remove duplicates
        $suggestions = array_values(array_unique($suggestions));

        echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode([]);
