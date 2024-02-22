<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="icon" type="image/png" href="img/2.png">
    <link href="css/inventory.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
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
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $idsString = implode(',', array_map('intval', $product_id)); // Ensuring IDs are integers

        $query = "DELETE FROM Product WHERE product_id IN ($idsString)";
        if (mysqli_query($con, $query)) {
            $message = "Selected products deleted successfully.";
        } else {
            $message = "Error deleting products: " . mysqli_error($con);
        }
    }

    // Handle update
    if (isset($_POST['id']) && isset($_POST['data'])) {
        $id = (int) $_POST['id'];
        $data = $_POST['data']; // This is an associative array of field => value 

        foreach ($data as $field => $value) {
            $value = mysqli_real_escape_string($con, $value);
            $query = "UPDATE Product SET $field = '$value' WHERE product_id = $id";
            if (mysqli_query($con, $query)) {
                $message = "Update successful";
            } else {
                $message = "Error updating product: " . mysqli_error($con);
            }
        }
    }

    // Handle addition
    if (isset($_POST['submit']) && !isset($_POST['id'])) {
        $name = mysqli_real_escape_string($con, $_POST['name']);
        $quantity_in_stock = mysqli_real_escape_string($con, $_POST['quantity_in_stock']);
        $reorder_level = mysqli_real_escape_string($con, $_POST['reorder_level']);
        $unit = mysqli_real_escape_string($con, $_POST['unit']);
        $supplier_id = mysqli_real_escape_string($con, $_POST['supplier_id']); // Capture supplier_id

        $query = "INSERT INTO Product (name, quantity_in_stock, reorder_level, unit, supplier_id) VALUES ('$name', '$quantity_in_stock', '$reorder_level', '$unit', '$supplier_id')";
        if (mysqli_query($con, $query)) {
            header('Location: inventory.php?addSuccess=true'); // Prevent form resubmission
        } else {
            $message = "Error adding new product: " . mysqli_error($con);
        }
    }

    $searchTerm = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
    $products = []; // Initialize the array to hold product records

    if (!empty($searchTerm)) {
        // If there is a search term, fetch matching records
        $likeSearchTerm = "%" . $searchTerm . "%";
        $stmt = $con->prepare("SELECT p.*, s.name AS supplier_name FROM Product p JOIN Supplier s ON p.supplier_id = s.supplier_id WHERE p.name LIKE ?");
        $stmt->bind_param("s", $likeSearchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
    } else {
        // If there is no search term, fetch all records
        $result = mysqli_query($con, "SELECT p.*, s.name AS supplier_name FROM Product p JOIN Supplier s ON p.supplier_id = s.supplier_id");
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            <header id="title" class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <h3 data-translate="inventory">Inventory</h3>
            </header>
        </div>
        <main class="row">
            <div class="d-flex justify-content-between align-items-center col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <h2 data-translate="inventoryList">Inventory List</h2>
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0;">
                    <button id="addProductBtn" class="add-product-btn" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-lg"></i>
                        <span data-translate="add">Add</span>
                    </button>
                    <!-- Optional Delete Button -->
                    <button id="deleteSelectedBtn" class="delete-product-btn" alt="Delete Selected">
                        <i class="bi bi-trash"></i>
                        <span data-translate="delete">Delete</span>
                    </button>
                </div>
            </div>
            <!-- Search -->
            <div class="d-flex justify-content-start col-md-10 ms-sm-auto col-lg-10 search-bar">
                <form action="inventory.php" method="GET" class="mb-4" autocomplete="off">
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1"><i class="bi bi-search"></i></span>
                        <input type="text" name="searchName" class="form-control" placeholder="Search by name..." data-translate="searchByName" aria-label="Search by name" aria-describedby="basic-addon1">
                        <button class="btn btn-outline-secondary" type="submit" data-translate="search">Search</button>
                    </div>
                </form>
            </div>
            <!-- Form div for Adding New Product -->
            <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProductModalLabel" data-translate="addNewProduct">Add New Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form for adding new product -->
                            <form id="addProductForm" action="inventory.php" method="post">
                                <div class="mb-3">
                                    <input autocomplete="off" data-translate="productName" placeholder="Product Name" type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <select class="form-control" id="unit" name="unit" required>
                                        <option value="" disabled selected data-translate="selectUnit">Select a unit</option>
                                        <option value="150g">150g</option>
                                        <option value="12oz">12oz</option>
                                        <option value="2oz">2oz</option>
                                        <option value="5oz">5oz</option>
                                        <option value="330ml">330ml</option>
                                        <option value="1sve.">1sve.</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select class="form-control" id="supplier" name="supplier_id" required>
                                        <option value="" disabled selected data-translate="selectSupplier">Select a supplier</option>
                                        <option value="1">Halal Butchers Co.</option>
                                        <option value="2">Golden Wheat Bakery</option>
                                        <option value="3">Veggie Delight Wholesalers</option>
                                        <option value="4">Flavor Fusion Sauces</option>
                                        <option value="5">Crispy Edge Potatoes</option>
                                        <option value="6">Refresh Beverage Distributors</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" type="number" class="form-control" id="quantity_in_stock" data-translate="quantityInStock" placeholder="Quantity in Stock" name="quantity_in_stock" min="10" max="200" required>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" type="number" class="form-control" id="reorder_level" data-translate="reorderLevel" placeholder="Reorder Level" name="reorder_level" min="10" max="20" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="submit" form="addProductForm" class="save-btn" data-translate="saveProduct">Save Product</button>
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
                            <th data-translate="unit">Unit</th>
                            <th data-translate="supplier">Supplier</th>
                            <th data-translate="quantityInStock">Quantity in Stock</th>
                            <th data-translate="reorderLevel">Reorder Level</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product) : ?>
                            <tr>
                                <td><input tabindex="0" type="checkbox" class="productCheckbox" value="<?php echo $product['product_id']; ?>"></td>
                                <td class="edit" data-id="<?php echo $product['product_id']; ?>" data-field="name"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="edit" data-id="<?php echo $product['product_id']; ?>" data-field="unit"><?php echo htmlspecialchars($product['unit']) ?></td>
                                <td class="edit" data-id="<?php echo $product['product_id']; ?>" data-field="supplier_id" data-value="<?php echo $product['supplier_id']; ?>"><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                                <td class="edit" data-id="<?php echo $product['product_id']; ?>" data-field="quantity_in_stock"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                                <td class="edit" data-id="<?php echo $product['product_id']; ?>" data-field="reorder_level"><?php echo htmlspecialchars($product['reorder_level']); ?></td>
                                <td class="editIconsContainer">
                                    <i tabindex="0" class="bi bi-pencil-square editIcon" style="font-size: 1.3rem;" data-id="<?php echo $product['product_id']; ?>"></i>
                                    <i tabindex="0" class="bi bi-check saveBtn" data-id="<?php echo $product['product_id']; ?>" style="display: none;"></i>
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
                "inventory": "Inventory",
                "inventoryList": "Inventory List",
                "add": "Add",
                "delete": "Delete",
                "addNewProduct": "Add New Product",
                "productName": "Product Name",
                "unit": "Unit",
                "selectSupplier": "Select a supplier",
                "selectUnit": "Select a unit",
                "quantityInStock": "Quantity in Stock",
                "reorderLevel": "Reorder Level",
                "saveProduct": "Save Product",
                "close": "Close",
                "searchByName": "Search by name...",
                "search": "Search",
                "name": "Name",
                "username": "Username",
                "email": "Email",
                "setPassword": "Set Password",
                "role": "Role",
                "contactInfo": "Contact Info",
                "saveEmployee": "Save Employee",
                "add": "Add",
                "delete": "Delete",
                "employeeManagement": "Employee Management",
                "employeeList": "Employee List",
                "loginAudit": "Login Audit",
                "Admin": "Admin",
                "Manager": "Manager",
                "Staff": "Staff",
                "Beef": "Beef",
                "Chicken": "Chicken",
                "Salad": "Salad",
                "Mayonnaise": "Mayonnaise",
                "French Fries": "French Fries",
                "Can Soft Drink": "Can Soft Drink",
                "Chilli Sauce": "Chilli Sauce",
                "Pitta Bread": "Pitta Bread"
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
                "languagePreference": "Préférence de Langue",
                "anglaise": "English",
                "french": "Français",
                "logOut": "Déconnexion",
                "inventory": "Inventaire",
                "inventoryList": "Liste d'inventaire",
                "add": "Ajouter",
                "addNewProduct": "Ajouter un Nouveau Produit",
                "productName": "Nom du Produit",
                "unit": "Unité",
                "selectSupplier": "Sélectionner un Fournisseur",
                "selectUnit": "Sélectionnez une unité",
                "quantityInStock": "Quantité en Stock",
                "reorderLevel": "Niveau de Réapprovisionnement",
                "saveProduct": "Enregistrer le Produit",
                "close": "Fermer",
                "searchByName": "Rechercher par Nom...",
                "search": "Recherche",
                "name": "Nom",
                "username": "Nom d'Utilisateur",
                "email": "Email",
                "setPassword": "Définir le Mot de Passe",
                "role": "Rôle",
                "contactInfo": "Informations de Contact",
                "saveEmployee": "Enregistrer l'Employé",
                "add": "Ajouter",
                "delete": "Rayer",
                "employeeManagement": "Gestion des Employés",
                "employeeList": "Liste des Employés",
                "loginAudit": "Audit de Connexion",
                "Admin": "Administrateur",
                "Manager": "Gestionnaire",
                "Staff": "Personnel",
                "Beef": "Boeuf",
                "Chicken": "Poulet",
                "Salad": "Salade",
                "French Fries": "Frites",
                "Can Soft Drink": "Boisson Gazeuse en Canette",
                "Chilli Sauce": "Sauce Chili",
                "Pitta Bread": "Pain Pitta"
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

            // Special handling for dynamic content like name of product
            document.querySelectorAll(".edit[data-field='name']").forEach(element => {
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
    <script src="js/inventory.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html>