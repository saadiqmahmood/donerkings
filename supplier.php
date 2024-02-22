<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management</title>
    <link rel="icon" type="image/png" href="img/2.png">
    <link href="css/supplier.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <?php
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
    if (isset($_POST['supplier_id']) && is_array($_POST['supplier_id'])) {
        $supplier_id = $_POST['supplier_id'];
        $idsString = implode(',', array_map('intval', $supplier_id)); // Ensuring IDs are integers

        $query = "DELETE FROM Supplier WHERE supplier_id IN ($idsString)";
        if (mysqli_query($con, $query)) {
            $message = "Selected suppliers deleted successfully.";
        } else {
            $message = "Error deleting suppliers: " . mysqli_error($con);
        }
    }

    // Handle update
    if (isset($_POST['id']) && isset($_POST['data'])) {
        $id = $_POST['id'];
        $data = $_POST['data']; // This is an associative array of field => value

        foreach ($data as $field => $value) {
            $value = mysqli_real_escape_string($con, $value);
            $query = "UPDATE Supplier SET $field = '$value' WHERE supplier_id = $id";
            if (mysqli_query($con, $query)) {
                $message = "Update successful";
            } else {
                $message = "Error updating supplier: " . mysqli_error($con);
            }
        }
    }

    // Handle addition
    if (isset($_POST['submit']) && !isset($_POST['id'])) {
        $name = mysqli_real_escape_string($con, $_POST['name']);
        $contact_info = mysqli_real_escape_string($con, $_POST['contact_info']);
        $description = mysqli_real_escape_string($con, $_POST['description']); // New line to handle description

        $query = "INSERT INTO Supplier (name, contact_info, description) VALUES ('$name', '$contact_info', '$description')";
        if (mysqli_query($con, $query)) {
            header('Location: supplier.php?addSuccess=true'); // Prevent form resubmission
        } else {
            $message = "Error adding new supplier: " . mysqli_error($con);
        }
    }

    $searchTerm = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
    $suppliers = []; // Initialize the array to hold supplier records

    if (!empty($searchTerm)) {
        // If there is a search term, fetch matching records
        $likeSearchTerm = "%" . $searchTerm . "%";
        $stmt = $con->prepare("SELECT * FROM Supplier WHERE name LIKE ?");
        $stmt->bind_param("s", $likeSearchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        $stmt->close();
    } else {
        // If there is no search term, fetch all records
        $result = mysqli_query($con, "SELECT * FROM Supplier");
        while ($row = mysqli_fetch_assoc($result)) {
            $suppliers[] = $row;
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <h3 data-translate="supplierManagement">Supplier Management</h3>
            </header>
        </div>
        <main class="row">
            <div class="d-flex justify-content-between align-items-center col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <h2 data-translate="supplierList">Supplier List</h2>
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0;">
                    <button id="addSupplierBtn" class="add-supplier-btn" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                        <i class="bi bi-plus-lg"></i>
                        <span data-translate="add">Add</span>
                    </button>
                    <button id="deleteSelectedBtn" class="delete-supplier-btn" alt="Delete Selected">
                        <i class="bi bi-trash"></i>
                        <span data-translate="delete">Delete</span>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-start col-md-10 ms-sm-auto col-lg-10 search-bar">
                <form action="supplier.php" method="GET" class="mb-4" autocomplete="off">
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1"><i class="bi bi-search"></i></span>
                        <input type="text" name="searchName" data-translate="searchByName" class="form-control" placeholder="Search by name..." aria-label="Search by name" aria-describedby="basic-addon1">
                        <button class="btn btn-outline-secondary" data-translate="search" type="submit">Search</button>
                    </div>
                </form>
            </div>
            <!-- Form div for Adding New Supplier -->
            <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header ">
                            <h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form for adding new supplier -->
                            <form id="addSupplierForm" action="supplier.php" method="post">
                                <div class="mb-3">
                                    <label for="name" class="form-label" data-translate="name">Name</label>
                                    <input autocomplete="off" type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_info" class="form-label" data-translate="contactInfo">Contact Info</label>
                                    <input autocomplete="off" type="text" class="form-control" id="contact_info" name="contact_info" required>
                                </div>
                                <div class="mb-3"> <!-- New field for description -->
                                    <label for="description" class="form-label" data-translate="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="submit" form="addSupplierForm" class="save-btn" data-translate="saveSupplier">Save Supplier</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-translate="close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-10 ms-sm-auto col-lg-10 table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th data-translate="name">Name</th>
                            <th data-translate="contact">Contact</th>
                            <th data-translate="description">Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier) : ?>
                            <tr>
                                <td><input tabindex="0" type="checkbox" class="supplierCheckbox" value="<?php echo $supplier['supplier_id']; ?>"></td>
                                <td class="edit" data-id="<?php echo $supplier['supplier_id']; ?>" data-field="name"><?php echo htmlspecialchars($supplier['name']); ?></td>
                                <td class="edit" data-id="<?php echo $supplier['supplier_id']; ?>" data-field="contact_info"><?php echo htmlspecialchars($supplier['contact_info']); ?></td>
                                <td class="edit" data-id="<?php echo $supplier['supplier_id']; ?>" data-field="description"><?php echo htmlspecialchars($supplier['description']); ?></td>
                                <td class="editIconsContainer">
                                    <i tabindex="0" class="bi bi-pencil-square editIcon" style="font-size: 1.3rem;" data-id="<?php echo $supplier['supplier_id']; ?>"></i>
                                    <i tabindex="0" class="bi bi-check saveBtn" data-id="<?php echo $supplier['supplier_id']; ?>" style="display: none;"></i>
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
                "languagePreference": "Language Preference",
                "anglaise": "English",
                "french": "Français",
                "logOut": "Log Out",
                "supplierManagement": "Supplier Management",
                "supplierList": "Supplier List",
                "add": "Add",
                "delete": "Delete",
                "searchByName": "Search by name...",
                "search": "Search",
                "name": "Name",
                "contactInfo": "ContactInfo",
                "description": "Description",
                "saveSupplier": "Save Supplier",
                "close": "Close",
                "addNewSupplier": "Add New Supplier",
                "Supplier of high-quality halal meats.": "Supplier of high-quality halal meats.",
                "Artisan bakery specialising in wheat-based products.": "Artisan bakery specialising in wheat-based products.",
                "Wholesale distributor of vegetable products.": "Wholesale distributor of vegetable products.",
                "Manufacturer of sauces and condiments.": "Manufacturer of sauces and condiments.",
                "Supplier of premium-quality potato products.": "Supplier of premium-quality potato products.",
                "Distributor of refreshing beverages.": "Distributor of refreshing beverages."
            },
            fr: {
                "inventory": "Inventaire",
                "menuItems": "Menu",
                "order": "Ordre",
                "employee": "Employé",
                "supplier": "Fournisseur",
                "settings": "Paramètres",
                "theme": "Thème",
                "default": "Par défaut",
                "darkMode": "Mode sombre",
                "lightMode": "Mode clair",
                "languagePreference": "Préférence de langue",
                "anglaise": "English",
                "french": "Français",
                "logOut": "Déconnexion",
                "supplierManagement": "Gestion des fournisseurs",
                "supplierList": "Liste des fournisseurs",
                "add": "Ajouter",
                "delete": "Rayer",
                "searchByName": "Rechercher par nom...",
                "search": "Rechercher",
                "name": "Nom",
                "contactInfo": "Informations de contact",
                "description": "Description",
                "saveSupplier": "Enregistrer le fournisseur",
                "close": "Fermer",
                "addNewSupplier": "Ajouter un nouveau fournisseur",
                "Supplier of high-quality halal meats.": "Fournisseur de viandes halal de haute qualité.",
                "Artisan bakery specialising in wheat-based products.": "Artisan bakery specialising in wheat-based products.",
                "Wholesale distributor of vegetable products.": "Distributeur en gros de produits végétaux.",
                "Manufacturer of sauces and condiments.": "Fabricant de sauces et condiments.",
                "Supplier of premium-quality potato products.": "Fournisseur de produits de pomme de terre de qualité supérieure.",
                "Distributor of refreshing beverages.": "Distributeur de boissons rafraîchissantes."
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
            document.querySelectorAll(".edit[data-field='description']").forEach(element => {
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
    <script src="js/supplier.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html>