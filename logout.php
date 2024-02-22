<?php
session_start();

$con = mysqli_connect("localhost", "root", "", "donerkingsdb");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (isset($_SESSION['user_id'])) {
    $logoutTime = date('Y-m-d H:i:s');
    $employeeId = $_SESSION['user_id'];

    // Update the latest login record with logout time
    $auditStmt = $con->prepare("UPDATE Login_Audit SET logout_time = ? WHERE employee_id = ? ORDER BY login_time DESC LIMIT 1");
    $auditStmt->bind_param("si", $logoutTime, $employeeId);
    $auditStmt->execute();
    $auditStmt->close();
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
header("location: login.php");
exit;
