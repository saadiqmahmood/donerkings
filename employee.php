<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link rel="icon" type="image/png" href="img/2.png">
    <link href="css/employee.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php';

    $con = mysqli_connect("localhost", "root", "", "donerkingsdb");
    if (mysqli_connect_error()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    session_start();

    // Define accessible pages for each role
    $accessControl = [
        'Admin' => ['employee.php', 'inventory.php', 'order.php', 'menu.php', 'supplier.php'],
        'Manager' => ['inventory.php', 'order.php', 'menu.php'],
        'Staff' => ['order.php'],
    ];

    // Check if the user is logged in and has a role
    if (isset($_SESSION['role'])) {
        $userRole = $_SESSION['role'];
        $currentPage = basename($_SERVER['PHP_SELF']);

        // Check if the user's role has access to the current page
        if (!in_array($currentPage, $accessControl[$userRole])) {
            // If access is not allowed, redirect to a default page based on the role
            if ($userRole == 'Admin') {
                header("Location: employee.php");
            } elseif ($userRole == 'Manager') {
                header("Location: inventory.php");
            } elseif ($userRole == 'Staff') {
                header("Location: order.php");
            }
            exit();
        }
    } else {
        // If not logged in, redirect to the login page
        if ($currentPage != 'login.php') {
            header("Location: login.php");
            exit();
        }
    }

    // Initialize an empty message
    $message = '';

    // Handle deletion
    if (isset($_POST['employee_id']) && is_array($_POST['employee_id'])) {
        $employee_id = $_POST['employee_id'];
        $idsString = implode(',', array_map('intval', $employee_id)); // Ensuring IDs are integers

        $query = "DELETE FROM employee WHERE employee_id IN ($idsString)";
        if (mysqli_query($con, $query)) {
            $message = "Selected employees deleted successfully.";
        } else {
            $message = "Error deleting employees: " . mysqli_error($con);
        }
    }

    // Handle update
    if (isset($_POST['id']) && isset($_POST['data'])) {
        $id = $_POST['id'];
        $data = $_POST['data']; // This is an associative array of field => value

        foreach ($data as $field => $value) {
            $value = mysqli_real_escape_string($con, $value);
            $query = "UPDATE employee SET $field = '$value' WHERE employee_id = $id";
            if (mysqli_query($con, $query)) {
                $message = "Update successful";
            } else {
                $message = "Error updating employee: " . mysqli_error($con);
            }
        }
    }

    // Handle addition
    if (isset($_POST['submit']) && !isset($_POST['id'])) {
        $username = mysqli_real_escape_string($con, $_POST['username']);
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($con, $_POST['last_name']);

        // Concatenate the first name and last name with a space in between
        $name = trim($first_name) . ' ' . trim($last_name);
        $password = $_POST['password'];
        $role = mysqli_real_escape_string($con, $_POST['role']);
        $contact_info = mysqli_real_escape_string($con, $_POST['contact_info']);

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO employee (username, email, name, password, role, contact_info) VALUES (?, ?, ?, ?, ?, ?)";

        // Prepare the statement to prevent SQL injection
        $stmt = mysqli_prepare($con, $query);

        // Check if the statement was prepared successfully
        if ($stmt === false) {
            $message = "Error preparing statement: " . mysqli_error($con);
        } else {
            // Bind the parameters to the statement
            mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $name, $hashed_password, $role, $contact_info);

            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                $mail = new PHPMailer(true); // Passing `true` enables exceptions

                try {
                    //Server settings
                    $mail->isSMTP();                                      // Set mailer to use SMTP
                    $mail->Host = 'sandbox.smtp.mailtrap.io';                     // Specify main and backup SMTP servers
                    $mail->SMTPAuth = true;                               // Enable SMTP authentication
                    $mail->Username = 'fbd87d564f788b';                // SMTP username
                    $mail->Password = 'db34ae48d835bc';                         // SMTP password
                    $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
                    $mail->Port = 587;                                    // TCP port to connect to

                    //Recipients
                    $mail->setFrom('mahmoodsaadiq@gmail.com', 'Admin');
                    $mail->addAddress($email, $name);

                    //Content
                    $mail->isHTML(true);                                  // Set email format to HTML
                    $mail->Subject = 'Your New Account';
                    $mail->Body    = "Hello " . $name . ",<br><br>Your account has been successfully created. Your username is: " . $username . "<br>Your password is: " . $password . "<br>Please visit our site to log in and change your password.<br><br>Best regards,<br>Admin";

                    $mail->send();
                    echo "Message has been sent successfully";
                } catch (Exception $e) {
                    echo "Mailer Error: " . $mail->ErrorInfo;
                }

                // Redirect to prevent form resubmission
                header('Location: employee.php?addSuccess=true=');
                exit;
            } else {
                $message = "Error adding new employee: " . mysqli_stmt_error($stmt);
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        }
    }

    $searchTerm = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
    $employees = []; // Initialize the array to hold employee records

    if (!empty($searchTerm)) {
        // If there is a search term, fetch matching records
        $likeSearchTerm = "%" . $searchTerm . "%";
        $stmt = $con->prepare("SELECT * FROM employee WHERE name LIKE ?");
        $stmt->bind_param("s", $likeSearchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        $stmt->close();
    } else {
        // If there is no search term, fetch all records
        $result = mysqli_query($con, "SELECT * FROM employee");
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
    }


    // Fetch login audit records from the database
    $loginAudits = [];
    $query = "SELECT la.*, e.username FROM Login_Audit la INNER JOIN Employee e ON la.employee_id = e.employee_id ORDER BY la.login_time DESC";
    $result = mysqli_query($con, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $loginAudits[] = $row;
        }
    }

    // Close the connection
    mysqli_close($con);
    ?>

    <div class="container-fluid">
        <nav class="row navbar navbar-expand-lg navbar-light bg-light top-navbar">
            <div class="d-flex justify-content-between align-items-center col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <a class="navbar-brand" href="#">
                    <img src="./img/1.png" alt="Logo" style="width: 40px; height: auto;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager') : ?>
                        <li class="nav-item">
                            <a class="nav-link active" id="navInventory" href="inventory.php"><i class="bi bi-box nav-icon"></i><span data-translate="inventory">Inventory</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link active" id="navMenu" href="menu.php"><i class="bi bi-menu-button-wide nav-icon"></i><span data-translate="menuItems">Menu Items</span></a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link active" id="navOrder" href="order.php"><i class="bi bi-cart nav-icon"></i><span data-translate="order">Order</span></a>
                    </li>

                    <?php if ($_SESSION['role'] == 'Admin') : ?>
                        <li class="nav-item">
                            <a class="nav-link active" id="navEmployee" href="employee.php"><i class="bi bi-people nav-icon"></i><span data-translate="employee"></span>Employee</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" id="navSupplier" href="supplier.php"><i class="bi bi-truck nav-icon"></i><span data-translate="supplier">Supplier</span></a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link" id="navSettings" tabindex="0" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear nav-icon"></i><span data-translate="settings">Settings</span></a>
                    </li>
                </ul>
            </div>
        </nav>
        <div id="sidebarRow" class="row">
            <nav id="sidebar" class="col-md-2 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3 d-flex flex-column" style="height: 100%;">
                    <div class="sidebar-logo text-center">
                        <img src="./img/1.png" alt="Logo" style="width: 40%; height: auto;">
                    </div>
                    <ul class="nav flex-column flex-grow-1 mb-3">
                        <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager') : ?>
                            <li class="nav-item">
                                <a class="nav-link active" id="navInventory" href="inventory.php"><i class="bi bi-box nav-icon"></i><span data-translate="inventory">Inventory</span></a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link active" id="navMenu" href="menu.php"><i class="bi bi-menu-button-wide nav-icon"></i><span data-translate="menuItems">Menu Items</span></a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link active" id="navOrder" href="order.php"><i class="bi bi-cart nav-icon"></i><span data-translate="order">Order</span></a>
                        </li>

                        <?php if ($_SESSION['role'] == 'Admin') : ?>
                            <li class="nav-item">
                                <a class="nav-link active" id="navEmployee" href="employee.php"><i class="bi bi-people nav-icon"></i><span data-translate="employee">Employee</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" id="navSupplier" href="supplier.php"><i class="bi bi-truck nav-icon"></i><span data-translate="supplier">Supplier</span></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="nav flex-column" style="padding-bottom: 5rem;">
                        <li class="nav-item mt-auto">
                            <a class="nav-link" id="navSettings" tabindex="0" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear nav-icon"></i><span data-translate="settings">Settings</span></a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <div id="notification-container" class="col-md-10 ms-sm-auto col-lg-10 px-md-4"></div>
        <!-- Settings Modal -->
        <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settingsModalLabel" data-translate="settings">Settings</h5>
                        <button tabindex="" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="display: flex; flex-direction: column;align-items: center;">
                        <div class="mb-3">
                            <label for="themeSelection" class="form-label" data-translate="theme">Theme</label>
                            <select class="form-control" id="themeSelection">
                                <option value="default" data-translate="default">Default</option>
                                <option value="dark" data-translate="darkMode">Dark Mode</option>
                                <option value="light" data-translate="lightMode">Light Mode</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="languageSelection" class="form-label" data-translate="languagePreference">Language Preference</label>
                            <select class="form-control" id="languageSelection">
                                <option value="en" data-translate="anglaise">English</option>
                                <option value="fr" data-translate="french">Français</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end" style="padding-right: 2rem; padding-bottom: 2rem;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-translate="close">Close</button>
                        <button id="logoutBtn" class="logout-btn" data-translate="logOut">Log Out</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <header id="title" class="col-md-10 ms-sm-auto col-lg-10 px-md-4 ">
                <h3 data-translate="employeeManagement">Employee Management</h3>
            </header>
        </div>
        <main class="row">
            <div class="d-flex justify-content-between align-items-center col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <h2 data-translate="employeeList">Employee List</h2>
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0;">
                    <button id="addEmployeeBtn" class="add-employee-btn" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="bi bi-person-plus"></i>
                        <span data-translate="add">Add</span>
                    </button>
                    <!-- Optional Delete Button -->
                    <button id="deleteSelectedBtn" class="delete-employee-btn">
                        <i class="bi bi-person-x"></i>
                        <span data-translate="delete">Delete</span>
                    </button>
                </div>
            </div>
            <!-- Search  -->
            <div class="justify-content-center search-bar">
                <div class="col-md-10 ms-sm-auto col-lg-10">
                    <form action="employee.php" method="GET" class="mb-4" autocomplete="off">
                        <div class="d-flex justify-content-between align-items-center input-group">
                            <input type="text" name="searchName" class="form-control" placeholder="Search by name..." data-translate="searchByName" aria-label="Search by name" aria-describedby="basic-addon1">
                            <button class="btn btn-outline-secondary" type="submit" data-translate="search">Search</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-10 ms-sm-auto col-lg-10">
                    <select id="viewSelector">
                        <option data-translate="employeeList" value="employeeList">Employee List</option>
                        <option data-translate="loginAudit" value="loginAudit">Login Audit</option>
                    </select>
                </div>
            </div>
            <!--  Form div for Adding New Employee -->
            <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header ">
                            <h5 class="modal-title" id="addEmployeeModalLabel" data-translate="addNewEmployee">Add New Employee</h5>
                            <button tabindex="0" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form for adding new employee -->
                            <form id="addEmployeeForm" action="employee.php" method="post">
                                <div class="mb-3">
                                    <input autocomplete="off" type="text" class="form-control" id="first_name" name="first_name" data-translate="firstName" placeholder="First Name" minlength="2" maxlength="30" required>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" type="text" class="form-control" id="last_name" name="last_name" data-translate="lastName" placeholder="Last Name" minlength="2" maxlength="30" required>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" type="text" class="form-control" id="username" name="username" data-translate="username" placeholder="Username" minlength="4" maxlength="20" required>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" type="email" class="form-control" id="email" name="email" size="30" data-translate="email" placeholder="Email" required>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" type="password" class="form-control" id="password" name="password" required data-translate="setPassword" placeholder="Set Password" minlength="8"> <br>
                                    <label style="cursor: pointer; display: inline-block; font-size: 1rem;" data-translate="showPassword">
                                        <input type="checkbox" onclick="myFunction()" style="margin-right: 15px;">
                                        Show Password
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="" disabled selected data-translate="selectRole">Select a role</option>
                                        <option data-translate="Admin" value="Admin">Admin</option>
                                        <option data-translate="Manager" value="Manager">Manager</option>
                                        <option data-translate="Staff" value="Staff">Staff</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" pattern="[0-9]{10,11}" placeholder="1234-567-8901" type="tel" class="form-control" id="contact_info" name="contact_info" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="submit" form="addEmployeeForm" class="save-btn" data-translate="saveEmployee">Save Employee</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-translate="close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="loginAuditTable" class="col-md-10 ms-sm-auto col-lg-10 table-responsive" style="display: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th data-translate="username">Username</th>
                            <th data-translate="loginTime">Login Time</th>
                            <th data-translate="logoutTime">Logout Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loginAudits as $audit) : ?>
                            <tr>
                                <td></td>
                                <td><?php echo htmlspecialchars($audit['username']); ?></td>
                                <td><?php echo htmlspecialchars($audit['login_time']); ?></td>
                                <td><?php echo htmlspecialchars($audit['logout_time']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="employeeTable" class="col-md-10 ms-sm-auto col-lg-10 table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>

                            <th data-translate="username">Username</th>
                            <th data-translate="email">Email</th>
                            <th data-translate="name">Name</th>
                            <th data-translate="role">Role</th>
                            <th data-translate="contact">Contact</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee) : ?>
                            <tr>
                                <td><input tabindex="0" type="checkbox" class="employeeCheckbox" value="<?php echo $employee['employee_id']; ?>"></td>
                                <td class="edit" data-id="<?php echo $employee['employee_id']; ?>" data-field="username"><?php echo htmlspecialchars($employee['username']); ?></td>
                                <td class="edit" data-id="<?php echo $employee['employee_id']; ?>" data-field="email"><?php echo htmlspecialchars($employee['email']); ?></td>
                                <td class="edit" data-id="<?php echo $employee['employee_id']; ?>" data-field="name"><?php echo htmlspecialchars($employee['name']); ?></td>
                                <td class="edit" data-id="<?php echo $employee['employee_id']; ?>" data-field="role"><?php echo htmlspecialchars($employee['role']); ?></td>
                                <td class="edit" data-id="<?php echo $employee['employee_id']; ?>" data-field="contact_info"><?php echo htmlspecialchars($employee['contact_info']); ?></td>
                                <td class="editIconsContainer">
                                    <i tabindex="0" class="bi bi-pencil-square editIcon" style="font-size: 1.3rem;" data-id="<?php echo $employee['employee_id']; ?>"></i>
                                    <i tabindex="0" class="bi bi-check saveBtn" data-id="<?php echo $employee['employee_id']; ?>" style="display: none;"></i>
                                    <i tabindex="0" class="bi bi-x cancelBtn" style="display: none;"></i>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
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
        const themeSelection = document.getElementById('themeSelection');

        // Function to apply or remove the theme
        function applyTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }

        // Load and apply the saved theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                themeSelection.value = savedTheme; // Set the select element to match the saved theme
                applyTheme(savedTheme);
            }
        });

        themeSelection.addEventListener('change', function() {
            const selectedTheme = this.value;
            applyTheme(selectedTheme);
            localStorage.setItem('theme', selectedTheme); // Save the selected theme to localStorage
        });

        document.addEventListener('DOMContentLoaded', function() {
            const viewSelector = document.getElementById('viewSelector');
            const employeeTable = document.getElementById('employeeTable');
            const loginAuditTable = document.getElementById('loginAuditTable');

            viewSelector.addEventListener('change', function() {
                if (this.value === 'employeeList') {
                    employeeTable.style.display = ''; // Show employee table
                    loginAuditTable.style.display = 'none'; // Hide login audit table
                } else if (this.value === 'loginAudit') {
                    employeeTable.style.display = 'none'; // Hide employee table
                    loginAuditTable.style.display = ''; // Show login audit table
                }
            });
        });

        const translations = {
            en: {
                "inventory": "Inventory",
                "menuItems": "Menu Items",
                "order": "Order",
                "employee": "Employee",
                "supplier": "Supplier",
                "settings": "Settings",
                "theme": "Theme",
                "default": "Default",
                "darkMode": "Dark Mode",
                "lightMode": "Light Mode",
                "languagepreference": "Language Preference",
                "anglaise": "English",
                "french": "Français",
                "logOut": "Log Out",
                "employeeManagement": "Employee Management",
                "employeeList": "Employee List",
                "add": "Add",
                "delete": "Delete",
                "loginAudit": "Login Audit",
                "searchByName": "Search by name...",
                "search": "Search",
                "firstName": "First Name",
                "lastName": "Last Name",
                "username": "Username",
                "email": "Email",
                "setPassword": "Set Password",
                "showPassword": "Show Password",
                "role": "Role",
                "selectRole": "Select a role",
                "contactInfo": "Contact Info",
                "saveEmployee": "Save Employee",
                "close": "Close",
                "addNewEmployee": "Add New Employee",
                "loginTime": "Login Time",
                "logoutTime": "Logout Time",
                "Admin": "Admin",
                "Manager": "Manager",
                "Staff": "Staff"
            },
            fr: {
                "inventory": "Inventaire",
                "menuItems": "Menu",
                "order": "Ordre",
                "employee": "Employé",
                "supplier": "Fournisseur",
                "settings": "Paramètres",
                "theme": "Thème",
                "default": "Défaut",
                "darkMode": "Mode Sombre",
                "lightMode": "Mode Clair",
                "languagePreference": "Préférence de langue",
                "anglaise": "English",
                "french": "Français",
                "logOut": "Déconnexion",
                "employeeManagement": "Gestion des employés",
                "employeeList": "Liste des employés",
                "add": "Ajouter",
                "delete": "Rayer",
                "loginAudit": "Vérification de connexion",
                "searchByName": "Rechercher par nom...",
                "search": "Recherche",
                "firstName": "Prénom",
                "lastName": "Nom de Famille",
                "username": "Nom d'utilisateur",
                "email": "Email",
                "setPassword": "Définir le mot de passe",
                "showPassword": "Montrer le mot de passe",
                "role": "Rôle",
                "selectRole": "Sélectionnez un rôle",
                "contactInfo": "Informations de contact",
                "saveEmployee": "Sauvegarder l'employé",
                "close": "Fermer",
                "addNewEmployee": "Ajouter un nouvel employé",
                "loginTime": "Heure de connexion",
                "logoutTime": "Heure de déconnexion",
                "Admin": "Administrateur",
                "Manager": "Gestionnaire",
                "Staff": "Personnel"
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const savedLanguage = localStorage.getItem('languagePreference') || 'en';
            document.getElementById('languageSelection').value = savedLanguage;
            updatePageLanguage(savedLanguage);

            document.getElementById('languageSelection').addEventListener('change', function() {
                const selectedLanguage = this.value;
                localStorage.setItem('languagePreference', selectedLanguage); // Save the selected language preference
                updatePageLanguage(selectedLanguage);
            });
        });

        function updatePageLanguage(language) {
            document.querySelectorAll("[data-translate]").forEach(element => {
                const key = element.getAttribute("data-translate");
                const translation = translations[language][key];

                // Check if the element is an input with a placeholder
                if (element.tagName === "INPUT" && element.hasAttribute("placeholder")) {
                    element.placeholder = translation; // Set the translated text as placeholder
                } else if (element.tagName === "OPTION") {
                    // For <option> elements inside <select>, translate text content only
                    element.textContent = translation;
                    // Do not modify the value attribute here to preserve functionality
                } else {
                    // For other elements, set the textContent
                    element.textContent = translation;
                }
            });

            // Special handling for dynamic content like roles, if necessary
            document.querySelectorAll(".edit[data-field='role']").forEach(element => {
                const role = element.textContent.trim();
                if (translations[language][role]) {
                    element.textContent = translations[language][role];
                }
            });
        }

        document.getElementById('languageSelection').addEventListener('change', function() {
            const selectedLanguage = this.value;
            localStorage.setItem('languagePreference', selectedLanguage); // Persist language selection
            updatePageLanguage(selectedLanguage);
        });
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="js/employee.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html>