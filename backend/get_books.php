<?php
require_once "db.php";

header("Content-Type: application/json");

try {
   $stmt = $conn->query(
    "SELECT id, title, author, category, price, description
     FROM books
     WHERE status = 'Available'"
);

    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($books);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "error" => "Could not retrieve books"
    ]);
}
?>