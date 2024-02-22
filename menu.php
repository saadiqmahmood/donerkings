<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management</title>
    <link rel="icon" type="image/png" href="img/2.png">
    <link href="css/menu.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
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

    // Handle deletion of menu items
    if (isset($_POST['menu_id']) && is_array($_POST['menu_id'])) {
        $menu_id = $_POST['menu_id'];
        $idsString = implode(',', array_map('intval', $menu_id));

        $query = "DELETE FROM Menu WHERE menu_id IN ($idsString)";
        if (mysqli_query($con, $query)) {
            $message = "Selected menu items deleted successfully.";
        } else {
            $message = "Error deleting menu items: " . mysqli_error($con);
        }
    }

    // Handle update of menu items
    if (isset($_POST['id']) && isset($_POST['data'])) {
        $id = $_POST['id'];
        $data = $_POST['data'];

        foreach ($data as $field => $value) {
            $value = mysqli_real_escape_string($con, $value);
            $query = "UPDATE Menu SET $field = '$value' WHERE menu_id = $id";
            if (mysqli_query($con, $query)) {
                $message = "Menu item updated successfully.";
            } else {
                $message = "Error updating menu item: " . mysqli_error($con);
            }
        }
    }

    // Handle addition of new menu items
    if (isset($_POST['submit']) && !isset($_POST['id'])) {
        $item_name = mysqli_real_escape_string($con, $_POST['item_name']);
        $description = mysqli_real_escape_string($con, $_POST['description']);
        $price = mysqli_real_escape_string($con, $_POST['price']);

        $query = "INSERT INTO Menu (item_name, description, price) VALUES ('$item_name', '$description', '$price')";
        if (mysqli_query($con, $query)) {
            header('Location: menu.php?addSuccess=true');
        } else {
            $message = "Error adding new menu item: " . mysqli_error($con);
        }
    }

    $searchTerm = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
    $menuItems = [];

    if (!empty($searchTerm)) {
        $likeSearchTerm = "%" . $searchTerm . "%";
        $stmt = $con->prepare("SELECT * FROM Menu WHERE item_name LIKE ?");
        $stmt->bind_param("s", $likeSearchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $menuItems[] = $row;
        }
        $stmt->close();
    } else {
        $result = mysqli_query($con, "SELECT * FROM Menu");
        while ($row = mysqli_fetch_assoc($result)) {
            $menuItems[] = $row;
        }
    }

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
                        <button tabindex="0" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <h3 data-bs-dismiss="menuManagement">Menu Management</h3>
            </header>
        </div>
        <main class="row">
            <div class="d-flex justify-content-between align-items-center col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <h2 data-bs-dismiss="menuList">Menu List</h2>
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0;">
                    <button id="addMenuBtn" class="add-menu-btn" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                        <i class="bi bi-plus-lg"></i>
                        <span data-translate="add">Add</span>
                    </button>
                    <button id="deleteSelectedBtn" class="delete-menu-btn" alt="Delete Selected">
                        <i class="bi bi-trash"></i>
                        <span data-translate="delete">Delete</span>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-start col-md-10 ms-sm-auto col-lg-10 search-bar">
                <form action="menu.php" method="GET" class="mb-4" autocomplete="off">
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1"><i class="bi bi-search"></i></span>
                        <input type="text" data-translate="searchByName" name="searchName" class="form-control" placeholder="Search by name..." aria-label="Search by name" aria-describedby="basic-addon1">
                        <button class="btn btn-outline-secondary" type="submit" data-translate="search">Search</button>
                    </div>
                </form>
            </div>
            <!-- Form div for Adding New Menu Item -->
            <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 modal fade" id="addMenuModal" tabindex="-1" aria-labelledby="addMenuModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header ">
                            <h5 class="modal-title" id="addMenuModalLabel" data-translate="addNewMenuItems">Add New Menu Item</h5>
                            <button tabindex="0" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form for adding new menu item -->
                            <form id="addMenuForm" action="menu.php" method="post">
                                <div class="mb-3">
                                    <label for="item_name" class="form-label" data-translate="itemName">Item Name</label>
                                    <input autocomplete="off" type="text" class="form-control" id="item_name" name="item_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label" data-translate="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="price" class="form-label" data-translate="price">Price</label>
                                    <input autocomplete="off" type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="submit" form="addMenuForm" class="save-btn" data-translate="saveItem">Save Item</button>
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
                            <th data-translate="itemName">Item Name</th>
                            <th data-translate="description">Description</th>
                            <th data-translate="price">Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $menuItem) : ?>
                            <tr>
                                <td><input tabindex="0" type="checkbox" class="menuCheckbox" value="<?php echo $menuItem['menu_id']; ?>"></td>
                                <td class="edit" data-id="<?php echo $menuItem['menu_id']; ?>" data-field="item_name"><?php echo htmlspecialchars($menuItem['item_name']); ?></td>
                                <td class="edit" data-id="<?php echo $menuItem['menu_id']; ?>" data-field="description"><?php echo htmlspecialchars($menuItem['description']); ?></td>
                                <td class="edit" data-id="<?php echo $menuItem['menu_id']; ?>" data-field="price">&pound;<?php echo htmlspecialchars($menuItem['price']); ?></td>
                                <td class="editIconsContainer">
                                    <i tabindex="0" class="bi bi-pencil-square editIcon" style="font-size: 1.3rem;" data-id="<?php echo $menuItem['menu_id']; ?>"></i>
                                    <i tabindex="0" class="bi bi-check saveBtn" data-id="<?php echo $menuItem['menu_id']; ?>" style="display: none;"></i>
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
                "menuManagement": "Menu Management",
                "menuList": "Menu List",
                "add": "Add",
                "delete": "Delete",
                "searchByName": "Search by name...",
                "search": "Search",
                "itemName": "Item Name",
                "description": "Description",
                "price": "Price",
                "addNewMenuItems": "Add New Menu Item",
                "saveItem": "Save Item",
                "close": "Close",
                "Beef Doner Kebab": "Beef Doner Kebab",
                "Chicken Doner Kebab": "Chicken Doner Kebab",
                "Mixed Doner Kebab": "Mixed Doner Kebab",
                "French Fries": "French Fries",
                "Sprite": "Sprite",
                "Chilli Sauce": "Chilli Sauce",
                "Mayonnaise": "Mayonnaise",
                "Fanta": "Fanta",
                "Tender strips of seasoned beef served in a warm bread with fries, a drink, and fresh salad.": "Tender strips of seasoned beef served in a warm bread with fries, a drink, and fresh salad.",
                "Grilled chicken slices served in a warm bread with fries, a drink, and fresh salad.": "Grilled chicken slices served in a warm bread with fries, a drink, and fresh salad.",
                "A blend of tender beef and chicken wrapped in a warm bread with fries, a drink, and fresh salad.": "A blend of tender beef and chicken wrapped in a warm bread with fries, a drink, and fresh salad.",
                "Crispy golden fries": "Crispy golden fries"
            },
            fr: {
                "inventory": "Inventaire",
                "menuItems": "Menu",
                "order": "Ordre",
                "employee": "Employé",
                "supplier": "Fournisseur",
                "settings": "Paramètres",
                "theme": "Thème",
                "default": "Par Défaut",
                "darkMode": "Mode Sombre",
                "lightMode": "Mode Lumineux",
                "languagePreference": "Préférence de Langue",
                "anglaise": "Anglais",
                "french": "Français",
                "logOut": "Déconnexion",
                "menuManagement": "Gestion du Menu",
                "menuList": "Liste du Menu",
                "add": "Ajouter",
                "delete": "Rayer",
                "searchByName": "Recherche par Nom...",
                "search": "Rechercher",
                "itemName": "Nom de l'Article",
                "description": "Description",
                "price": "Prix",
                "addNewMenuItems": "Ajouter un Nouvel Article au Menu",
                "saveItem": "Enregistrer l'Article",
                "close": "Fermer",
                "Beef Doner Kebab": "Kebab de Boeuf",
                "Chicken Doner Kebab": "Kebab de Poulet",
                "Mixed Doner Kebab": "Kebab Mixte",
                "French Fries": "Frites",
                "Sprite": "Sprite",
                "Chilli Sauce": "Sauce Chili",
                "Mayonnaise": "Mayonnaise",
                "Fanta": "Fanta",
                "Tender strips of seasoned beef served in a warm bread with fries, a drink, and fresh salad.": "Lanières tendres de bœuf assaisonné servies dans un pain chaud avec des frites, une boisson et une salade fraîche.",
                "Grilled chicken slices served in a warm bread with fries, a drink, and fresh salad.": "Tranches de poulet grillé servies dans un pain chaud avec des frites, une boisson et une salade fraîche.",
                "A blend of tender beef and chicken wrapped in a warm bread with fries, a drink, and fresh salad.": "Un mélange de bœuf tendre et de poulet enveloppé dans un pain chaud avec des frites, une boisson et une salade fraîche.",
                "Crispy golden fries": "Frites dorées croustillantes",
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

            document.querySelectorAll(".edit[data-field='description'], .edit[data-field='item_name']").forEach(element => {
                const content = element.textContent.trim();
                const field = element.getAttribute("data-field");
                if (translations[language][content]) {
                    element.textContent = translations[language][content];
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
    <script src="js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html>