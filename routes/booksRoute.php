<?php
require_once '../database/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAllBooks':

        try {
            $condition = "";

            // $sql = "SELECT b.Book_ID, b.Book_Title, b.Publication_Year,
            //                a.Author_Name, c.Category_Name
            //         FROM Books b
            //         JOIN Author a ON b.Author_ID = a.Author_ID
            //         JOIN Category c ON b.Category_ID = c.Category_ID
            //         ORDER BY b.Book_ID DESC";
            $sql = "SELECT 
                      b.Book_ID, 
                      b.Book_Title, 
                      b.Publication_Year,
                      b.Isbn,
                      a.Author_Name, 
                      c.Category_Name,
                      COUNT(bc.BookCopy_ID) AS Total_Copies,
                      SUM(CASE WHEN bc.Book_Status_ID = 1 THEN 1 ELSE 0 END) AS Available_Copies
                    FROM Books b
                    JOIN Author a ON b.Author_ID = a.Author_ID
                    JOIN Category c ON b.Category_ID = c.Category_ID
                    LEFT JOIN BookCopy bc ON b.Book_ID = bc.Book_ID
                    GROUP BY b.Book_ID
                    ORDER BY b.Book_ID DESC";
            $stmt = $pdo->query($sql);
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "status" => "success",
                "data" => $books
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }

    break;

   case 'getBorrowed':

    try {

        $stmt = $pdo->prepare("
            SELECT 
                br.Borrow_ID,
                bd.BorrowDetails_ID,
                bd.BookCopy_ID,

                b.Book_Title,
                a.Author_Name,

                br.Member_ID,
                m.Member_Name,

                br.Borrow_Date,
                br.Due_Date,

                bd.Borrow_Status_ID

            FROM borrowdetails bd
            INNER JOIN borrowrecord br ON bd.Borrow_ID = br.Borrow_ID
            INNER JOIN members m ON br.Member_ID = m.Member_ID
            INNER JOIN bookcopy bc ON bd.BookCopy_ID = bc.BookCopy_ID
            INNER JOIN books b ON bc.Book_ID = b.Book_ID
            INNER JOIN author a ON b.Author_ID = a.Author_ID

            WHERE bd.Borrow_Status_ID IN (1,3)
            AND bd.Return_Date IS NULL

            ORDER BY br.Borrow_Date DESC
        ");

        $stmt->execute();

        echo json_encode([
            "status" => "success",
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

break;

case 'returnBook':

    $data = json_decode(file_get_contents("php://input"), true);
    $borrowDetailsId = $data['BorrowDetails_ID'] ?? null;

    if (!$borrowDetailsId) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing BorrowDetails_ID"
        ]);
        exit;
    }

    try {

  
        $stmt = $pdo->prepare("
            SELECT 
                bd.BookCopy_ID,
                br.Due_Date
            FROM borrowdetails bd
            JOIN borrowrecord br ON bd.Borrow_ID = br.Borrow_ID
            WHERE bd.BorrowDetails_ID = ?
        ");
        $stmt->execute([$borrowDetailsId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode([
                "status" => "error",
                "message" => "Record not found"
            ]);
            exit;
        }

        $copyId = $row['BookCopy_ID'];
        $dueDate = $row['Due_Date'];

 
        $stmt = $pdo->prepare("
            UPDATE borrowdetails
            SET 
                Return_Date = CURDATE(),
                Borrow_Status_ID = 2
            WHERE BorrowDetails_ID = ?
        ");
        $stmt->execute([$borrowDetailsId]);

       
        $stmt = $pdo->prepare("
            UPDATE bookcopy
            SET Book_Status_ID = 1
            WHERE BookCopy_ID = ?
        ");
        $stmt->execute([$copyId]);

   
        // $today = date('Y-m-d');
        // $fineAmount = 0;

        // if ($dueDate < $today) {
        //     $days = (strtotime($today) - strtotime($dueDate)) / (60 * 60 * 24);
        //     $fineAmount = $days * 5;


        //     $stmt = $pdo->prepare("
        //         INSERT INTO fines 
        //         (BorrowDetails_ID, Fine_Amount, Fine_Status_ID, Issued_Date, Paid_Date)
        //         VALUES (?, ?, 1, CURDATE(), NULL)
        //     ");
        //     $stmt->execute([$borrowDetailsId, $fineAmount]);
        // }

        // echo json_encode([
        //     "status" => "success",
        //     "message" => "Book returned successfully",
        //     "fine_amount" => $fineAmount
        // ]);
        // exit;
        echo json_encode([
            "status" => "success",
            "message" => "Book returned successfully"
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit;
    }

break;

    case 'borrowBook':
        $data = json_decode(file_get_contents("php://input"), true);

    $bookId     = $data['Book_ID'] ?? null;
    $memberId   = $data['Member_ID'] ?? null;
    $borrowDate = $data['Borrow_Date'] ?? null;
    $dueDate    = $data['Due_Date'] ?? null;

    if (!$bookId || !$memberId || !$borrowDate || !$dueDate) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing fields"
        ]);
        exit;
    }

    try {


        $stmt = $pdo->prepare("
            SELECT bc.BookCopy_ID 
            FROM bookcopy bc
            WHERE bc.Book_ID = ? AND bc.Book_Status_ID = 1
            LIMIT 1
        ");
        $stmt->execute([$bookId]);
        $copy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$copy) {
            echo json_encode([
                "status" => "error",
                "message" => "No available copies"
            ]);
            exit;
        }


        $stmt = $pdo->prepare("
            SELECT Member_Status_ID 
            FROM members 
            WHERE Member_ID = ?
        ");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            echo json_encode([
                "status" => "error",
                "message" => "Member not found"
            ]);
            exit;
        }

        if ($member['Member_Status_ID'] != 1) {
            echo json_encode([
                "status" => "error",
                "message" => "Only active members can borrow books"
            ]);
            exit;
        }

        $copyId = $copy['BookCopy_ID'];


        $stmt = $pdo->prepare("
            INSERT INTO borrowrecord (Member_ID, Borrow_Date, Due_Date)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$memberId, $borrowDate, $dueDate]);

        $borrowId = $pdo->lastInsertId();


        $stmt = $pdo->prepare("
            INSERT INTO borrowdetails (Borrow_ID, BookCopy_ID, Return_Date, Borrow_Status_ID)
            VALUES (?, ?, NULL, 1)
        ");
        $stmt->execute([$borrowId, $copyId]);


        $stmt = $pdo->prepare("
            UPDATE bookcopy 
            SET Book_Status_ID = 2 
            WHERE BookCopy_ID = ?
        ");
        $stmt->execute([$copyId]);

        echo json_encode([
            "status" => "success",
            "message" => "Book borrowed successfully"
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
    break;

    case 'addBook':

    $title   = $data['Title'] ?? '';
    $author  = $data['Author'] ?? '';
    $isbn    = $data['ISBN'] ?? '';
    $genre   = $data['Genre'] ?? '';
    $year    = $data['Year'] ?? null;
    $copies  = $data['CopyCount'] ?? 0;


    if (!$title || !$author || !$isbn || !$genre || !$copies) {
        echo json_encode([
            "status" => "error",
            "message" => "All fields are required"
        ]);
        exit;
    }

    try {


        $stmt = $pdo->prepare("SELECT Book_ID FROM books WHERE ISBN = ?");
        $stmt->execute([$isbn]);

        if ($stmt->fetch()) {
            echo json_encode([
                "status" => "error",
                "message" => "ISBN already exists"
            ]);
            exit;
        }


        $stmt = $pdo->prepare("SELECT Author_ID FROM author WHERE Author_Name = ?");
        $stmt->execute([$author]);
        $authorId = $stmt->fetchColumn();

        if (!$authorId) {
            $stmt = $pdo->prepare("INSERT INTO author (Author_Name) VALUES (?)");
            $stmt->execute([$author]);
            $authorId = $pdo->lastInsertId();
        }


        $stmt = $pdo->prepare("SELECT Category_ID FROM category WHERE Category_Name = ?");
        $stmt->execute([$genre]);
        $categoryId = $stmt->fetchColumn();

        if (!$categoryId) {
            $stmt = $pdo->prepare("INSERT INTO category (Category_Name) VALUES (?)");
            $stmt->execute([$genre]);
            $categoryId = $pdo->lastInsertId();
        }


        $stmt = $pdo->prepare("
            INSERT INTO books (Book_Title, Author_ID, Category_ID, Publication_Year, ISBN)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $authorId, $categoryId, $year, $isbn]);

        $bookId = $pdo->lastInsertId();


        $stmt = $pdo->prepare("
            INSERT INTO bookcopy (Book_ID, Book_Status_ID)
            VALUES (?, 1)
        ");

        for ($i = 0; $i < $copies; $i++) {
            $stmt->execute([$bookId]);
        }

        echo json_encode([
            "status" => "success",
            "message" => "Book added successfully with $copies copies"
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

break;

    case 'updateBook':

        $bookId = $data['Book_ID'] ?? null;
        $title  = $data['Title'] ?? '';
        $author = $data['Author'] ?? '';
        $isbn   = $data['ISBN'] ?? '';
        $genre  = $data['Genre'] ?? '';
        $year   = $data['Year'] ?? null;

        if (!$bookId || !$title || !$author || !$genre) {
            echo json_encode([
                "status" => "error",
                "message" => "Missing required fields"
            ]);
            exit;
        }

        try {


            $stmt = $pdo->prepare("SELECT Author_ID FROM Author WHERE Author_Name = ?");
            $stmt->execute([$author]);
            $authorRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($authorRow) {
                $authorId = $authorRow['Author_ID'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO Author (Author_Name) VALUES (?)");
                $stmt->execute([$author]);
                $authorId = $pdo->lastInsertId();
            }

   
            $stmt = $pdo->prepare("SELECT Category_ID FROM Category WHERE Category_Name = ?");
            $stmt->execute([$genre]);
            $catRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($catRow) {
                $categoryId = $catRow['Category_ID'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO Category (Category_Name) VALUES (?)");
                $stmt->execute([$genre]);
                $categoryId = $pdo->lastInsertId();
            }

            if (!empty($isbn)) {
                $stmt = $pdo->prepare("
                    SELECT Book_ID 
                    FROM books 
                    WHERE ISBN = ? AND Book_ID != ?
                    LIMIT 1
                ");
                $stmt->execute([$isbn, $bookId]);   
                if ($stmt->fetch()) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "ISBN already exists"
                    ]);
                    exit;
                }
            }


            $stmt = $pdo->prepare("
                UPDATE books
                SET 
                    Book_Title = ?, 
                    Author_ID = ?, 
                    Category_ID = ?, 
                    Publication_Year = ?, 
                    ISBN = ?
                WHERE Book_ID = ?
            ");

            $stmt->execute([
                $title,
                $authorId,
                $categoryId,
                $year,
                $isbn,
                $bookId
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Book updated successfully"
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }

    break;

    case 'getBookCopies':

    $bookId = $_GET['Book_ID'] ?? null;

    if (!$bookId) {
        echo json_encode(["status" => "error", "message" => "Missing Book_ID"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT BookCopy_ID, Book_Status_ID
            FROM bookcopy
            WHERE Book_ID = ?
        ");
        $stmt->execute([$bookId]);

        $copies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "data" => $copies
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

break;
case 'deleteCopy':

    $data = json_decode(file_get_contents("php://input"), true);
    $copyId = $data['BookCopy_ID'] ?? null;

    if (!$copyId) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing copy ID"
        ]);
        exit;
    }

    try {


        $stmt = $pdo->prepare("
            SELECT Book_ID, Book_Status_ID 
            FROM bookcopy 
            WHERE BookCopy_ID = ?
        ");
        $stmt->execute([$copyId]);
        $copy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$copy) {
            echo json_encode([
                "status" => "error",
                "message" => "Copy not found"
            ]);
            exit;
        }

        $bookId = $copy['Book_ID'];


        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM borrowdetails 
            WHERE BookCopy_ID = ?
            AND Return_Date IS NULL
        ");
        $stmt->execute([$copyId]);
        $activeBorrow = $stmt->fetchColumn();

        if ($activeBorrow > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Cannot delete copy. It is still borrowed."
            ]);
            exit;
        }


        $stmt = $pdo->prepare("
            DELETE FROM borrowdetails
            WHERE BookCopy_ID = ?
            AND Return_Date IS NOT NULL
        ");
        $stmt->execute([$copyId]);


        $stmt = $pdo->prepare("
            DELETE FROM bookcopy 
            WHERE BookCopy_ID = ?
        ");
        $stmt->execute([$copyId]);


        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bookcopy 
            WHERE Book_ID = ?
        ");
        $stmt->execute([$bookId]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $pdo->prepare("
                DELETE FROM books 
                WHERE Book_ID = ?
            ");
            $stmt->execute([$bookId]);

            echo json_encode([
                "status" => "success",
                "message" => "Last copy deleted. Book removed."
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => "Copy deleted successfully"
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

break;

case 'addCopies':

    $data = json_decode(file_get_contents("php://input"), true);

    $bookId = $data['Book_ID'] ?? null;
    $copies = $data['Copies'] ?? 0;

    if (!$bookId || $copies <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid input"
        ]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO bookcopy (Book_ID, Book_Status_ID)
            VALUES (?, 1)
        ");

        for ($i = 0; $i < $copies; $i++) {
            $stmt->execute([$bookId]);
        }

        echo json_encode([
            "status" => "success",
            "message" => "$copies copies added successfully"
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

break;

case 'getTransactions':

    try {

        $stmt = $pdo->prepare("
            SELECT 
                bd.BorrowDetails_ID,
                br.Borrow_ID,
                bd.BookCopy_ID,

                b.Book_Title,

                br.Member_ID,
                m.Member_Name,

                br.Borrow_Date,
                bd.Return_Date,

                bd.Borrow_Status_ID,
                bs.Borrow_Status_Name

            FROM borrowdetails bd
            INNER JOIN borrowrecord br ON bd.Borrow_ID = br.Borrow_ID
            INNER JOIN members m ON br.Member_ID = m.Member_ID
            INNER JOIN bookcopy bc ON bd.BookCopy_ID = bc.BookCopy_ID
            INNER JOIN books b ON bc.Book_ID = b.Book_ID
            INNER JOIN borrow_status bs ON bd.Borrow_Status_ID = bs.Borrow_Status_ID

            ORDER BY br.Borrow_Date DESC
        ");

        $stmt->execute();

        echo json_encode([
            "status" => "success",
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit;
    }

break;
case 'deleteHistory':

    $data = json_decode(file_get_contents("php://input"), true);
    $borrowDetailsId = $data['BorrowDetails_ID'] ?? null;

    if (!$borrowDetailsId) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing BorrowDetails_ID"
        ]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            SELECT Borrow_ID
            FROM borrowdetails
            WHERE BorrowDetails_ID = ?
        ");
        $stmt->execute([$borrowDetailsId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode([
                "status" => "error",
                "message" => "Record not found"
            ]);
            exit;
        }

        $borrowId = $row['Borrow_ID'];

        $stmt = $pdo->prepare("
            SELECT Fines_ID, Fine_Status_ID
            FROM fines
            WHERE BorrowDetails_ID = ?
        ");
        $stmt->execute([$borrowDetailsId]);
        $fine = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fine) {

            if ((int)$fine['Fine_Status_ID'] !== 2) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Cannot delete. Fine is not paid."
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
                DELETE FROM fines
                WHERE BorrowDetails_ID = ?
            ");
            $stmt->execute([$borrowDetailsId]);
        }

        $stmt = $pdo->prepare("
            DELETE FROM borrowdetails
            WHERE BorrowDetails_ID = ?
        ");
        $stmt->execute([$borrowDetailsId]);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM borrowdetails
            WHERE Borrow_ID = ?
        ");
        $stmt->execute([$borrowId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($count['total'] == 0) {
            $stmt = $pdo->prepare("
                DELETE FROM borrowrecord
                WHERE Borrow_ID = ?
            ");
            $stmt->execute([$borrowId]);
        }

        echo json_encode([
            "status" => "success",
            "message" => "History deleted successfully"
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit;
    }

break;
}

?>