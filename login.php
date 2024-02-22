<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/png" href="img/2.png">
    <link href="css/login.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
</head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<body>
    <?php
    session_start();

    $con = mysqli_connect("localhost", "root", "", "donerkingsdb");
    // Check connection
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password']; // Assuming you're storing hashed passwords

        // Prepared statement to select user
        $stmt = $con->prepare("SELECT * FROM employee WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            // Verify password (assuming you are using hashed passwords)
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session variables
                $_SESSION['user_id'] = $user['employee_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Log the login event in Login_Audit table
                $loginTime = date('Y-m-d H:i:s'); // current time in MySQL DATETIME format
                $employeeId = $user['employee_id'];
                $auditStmt = $con->prepare("INSERT INTO Login_Audit (employee_id, login_time) VALUES (?, ?)");
                $auditStmt->bind_param("is", $employeeId, $loginTime);
                $auditStmt->execute();
                $auditStmt->close();

                // Redirect based on role
                switch ($_SESSION['role']) {
                    case 'Admin':
                        header("Location: employee.php");
                        break;
                    case 'Manager':
                        header("Location: inventory.php");
                        break;
                    case 'Staff':
                        header("Location: order.php");
                        break;
                }
                exit();
            } else {
                // Password is not correct
                $login_error = "Invalid username or password.";
            }
        } else {
            // No user found with that username
            $login_error = "Invalid username or password.";
            header("Location: login.php");
        }
        $stmt->close();
    }
    ?>
    <div class="container-fluid">
        <div class="d-flex flex-column justify-content-center align-items-center login-content" style="min-height: 100vh;">
            <div class="login-container">
                <div class="text-center">
                    <img src="img/2.png" alt="Logo">
                </div>
                <h1 class="text-center">Login</h1>

                <div class="form-container">
                    <form class="form" method="post" action="login.php">
                        <div class="mb-3 form-group">
                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                        </div>
                        <div class="mb-3 form-group">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required> <br>
                            <label style="color: white; font-size: 1.2rem; cursor: pointer;">
                                <input type="checkbox" onclick="myFunction()" style="margin-right: 15px;">
                                Show Password
                            </label>
                        </div>
                        <?php if (!empty($login_error)) echo "<div class='mb-3 alert alert-danger''>$login_error</div>"; ?>
                        <div class="d-grid form-group">
                            <input type="submit" class="btn-submit" name="login" value="Login">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    </div>
</body>
<script src="js/script.js"></script>
<script>
    function myFunction() {
        var password = document.getElementById("password");
        if (password.type === "password") {
            password.type = "text";
        } else {
            password.type = "password";
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

</html>