<?php
session_start();

require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];

            if ($_SESSION['role'] === 'Academic') {
                header("Location: catalogue.php");
            } elseif ($_SESSION['role'] === 'Planner') {
                header("Location: planner_dashboard.php");
            } else {
                echo "Role dashboard not built yet!";
            }
            
            exit();
        } else {
            echo "<h1>Error: Incorect password.</h1> <a href='index.html'>Try again</a>";
        }
    } else {
        echo "<h1>Error: No account found with that email.</h1> <a href='index.html'>Try again</a>";
    }
    $stmt->close();
}
$conn->close();
?>