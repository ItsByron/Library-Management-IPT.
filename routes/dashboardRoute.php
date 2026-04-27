<?php
require_once '../database/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

switch ($action) {

    // case 'addBook':

    //     $data = json_decode(file_get_contents("php://input"), true);

    //     $title  = $data['Title'] ?? '';
    //     $author = $data['Author'] ?? '';
    //     $genre  = $data['Genre'] ?? '';
    //     $year   = $data['Year'] ?? '';
    //     $copies = $data['Copies'] ?? 1;

    //     if (!$title || !$author || !$genre || !$year) {
    //         echo json_encode(["status" => "error", "message" => "Missing fields"]);
    //         exit;
    //     }

    //     try {

    //         $stmt = $pdo->prepare("SELECT Author_ID FROM Author WHERE Author_Name = ?");
    //         $stmt->execute([$author]);
    //         $authorRow = $stmt->fetch();

    //         if ($authorRow) {
    //             $author_id = $authorRow['Author_ID'];
    //         } else {
    //             $stmt = $pdo->prepare("INSERT INTO Author (Author_Name) VALUES (?)");
    //             $stmt->execute([$author]);
    //             $author_id = $pdo->lastInsertId();
    //         }

    //         $stmt = $pdo->prepare("SELECT Category_ID FROM Category WHERE Category_Name = ?");
    //         $stmt->execute([$genre]);
    //         $catRow = $stmt->fetch();

    //         if ($catRow) {
    //             $category_id = $catRow['Category_ID'];
    //         } else {
    //             $stmt = $pdo->prepare("INSERT INTO Category (Category_Name) VALUES (?)");
    //             $stmt->execute([$genre]);
    //             $category_id = $pdo->lastInsertId();
    //         }

    //         $stmt = $pdo->prepare("
    //             INSERT INTO Books (Book_Title, Publication_Year, Author_ID, Category_ID)
    //             VALUES (?, ?, ?, ?)
    //         ");
    //         $stmt->execute([$title, $year, $author_id, $category_id]);

    //         $book_id = $pdo->lastInsertId();

    //         for ($i = 0; $i < $copies; $i++) {
    //             $stmt = $pdo->prepare("
    //                 INSERT INTO BookCopy (Book_ID, Book_Status_ID)
    //                 VALUES (?, 1)
    //             ");
    //             $stmt->execute([$book_id]);
    //         }

    //         echo json_encode([
    //             "status" => "success",
    //             "message" => "Book added successfully"
    //         ]);

    //     } catch (PDOException $e) {
    //         echo json_encode([
    //             "status" => "error",
    //             "message" => $e->getMessage()
    //         ]);
    //     }

    // break;


    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid action"
        ]);
    break;
}
?>