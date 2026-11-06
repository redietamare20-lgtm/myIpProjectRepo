<?php
require_once "db.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "POST method required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$title = trim($data["title"] ?? "");
$author = trim($data["author"] ?? "");
$category = trim($data["category"] ?? "");
$price = $data["price"] ?? null;
$description = trim($data["description"] ?? "");

if (
    $title === "" ||
    $author === "" ||
    !is_numeric($price) ||
    $price < 0
) {
    http_response_code(400);
    echo json_encode([
        "message" => "Please enter valid book information"
    ]);
    exit;
}
try {
    $stmt = $conn->prepare(
        "INSERT INTO books (title, author, category, price, description)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $title,
        $author,
        $category,
        $price,
        $description
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Book added successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Could not add book"]);
}
?>