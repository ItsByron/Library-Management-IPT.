<?php
session_start();
header('Content-Type: application/json');

require_once '../database/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

switch ($action) {
case 'signup':

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required!'
        ]);
        exit;
    }

    try {

        // 🔍 Check if username already exists
        $check = $pdo->prepare("SELECT Admin_ID FROM admin WHERE Admin_Username = ?");
        $check->execute([$username]);

        if ($check->fetch()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username already exists!'
            ]);
            exit;
        }

        //Hash password (IMPORTANT)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // ➕ Insert new admin
        $stmt = $pdo->prepare("INSERT INTO admin (Admin_Username, Password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Account created successfully!'
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Signup failed: ' . $e->getMessage()
        ]);
    }

break;
case 'login':

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username and password are required!'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE Admin_Username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    //Verify hashed password
    if ($admin && password_verify($password, $admin['Password'])) {

        $_SESSION['user'] = [
            "id" => $admin['Admin_ID'],
            "username" => $admin['Admin_Username']
        ];

        echo json_encode([
            'status'  => 'success',
            'message' => 'Login successful!'
        ]);

    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Incorrect username or password!'
        ]);
    }

break;

    case 'logout':

        //destroy session
        session_unset();
        session_destroy();

        echo json_encode([
            'status'  => 'success',
            'message' => 'Logged out successfully'
        ]);

    break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
    break;
}
?>