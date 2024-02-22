<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="icon" type="image/png" href="img/2.png">
    <link href="css/order.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
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

    // Handle deletion of orders and their associated order details
    if (isset($_POST['order_id']) && is_array($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        $idsString = implode(',', array_map('intval', $order_id)); // Ensuring IDs are integers

        // Start transaction
        mysqli_begin_transaction($con);

        try {
            // First, delete order details associated with the orders
            $deleteOrderDetailsQuery = "DELETE FROM order_details WHERE order_id IN ($idsString)";
            if (!mysqli_query($con, $deleteOrderDetailsQuery)) {
                throw new Exception("Error deleting order details: " . mysqli_error($con));
            }

            // Then, delete the orders themselves
            $deleteOrdersQuery = "DELETE FROM orders WHERE order_id IN ($idsString)";
            if (!mysqli_query($con, $deleteOrdersQuery)) {
                throw new Exception("Error deleting orders: " . mysqli_error($con));
            }

            // If everything is fine, commit the transaction
            mysqli_commit($con);
            header('Location: order.php?deleteSuccess=true');
            exit();
        } catch (Exception $e) {
            // An error occurred, roll back the transaction
            mysqli_rollback($con);
            $message = $e->getMessage();
        }
    }

    // Check if this is a status update request
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
        $status = mysqli_real_escape_string($con, $_POST['status']);

        $query = "UPDATE orders SET status = '$status' WHERE order_id = '$order_id'";
        if (mysqli_query($con, $query)) {
            echo "success";
        } else {
            echo "error updating status: " . mysqli_error($con);
        }
        exit(); // Stop script execution after handling status update
    }

    // Handle update of orders
    if (isset($_POST['id']) && isset($_POST['data'])) {
        $id = $_POST['id'];
        $data = $_POST['data']; // This is an associative array of field => value

        foreach ($data as $field => $value) {
            $value = mysqli_real_escape_string($con, $value);
            $query = "UPDATE orders SET $field = '$value' WHERE order_id = $id";
            if (mysqli_query($con, $query)) {
                $message = "Update successful";
            } else {
                $message = "Error updating order: " . mysqli_error($con);
            }
        }
    }

    // Fetch menu items from the database for the dropdown
    $menuItems = [];
    $query = "SELECT * FROM menu ORDER BY item_name ASC";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $menuItems[] = $row;
    }

    $menuItemsDropdown = '<select class="order-detail-form-control" name="menu_items[]" required>
    <option value="">Select Menu Item</option>';
    foreach ($menuItems as $menuItem) {
        $menuItemsDropdown .= '<option value="' . htmlspecialchars($menuItem['menu_id']) . '">' .
            htmlspecialchars($menuItem['item_name']) . ' - $' . htmlspecialchars(number_format($menuItem['price'], 2)) .
            '</option>';
    }
    $menuItemsDropdown .= '</select>';

    if (isset($_POST['submit'])) {
        $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($con, $_POST['last_name']);     
        $customer_name = trim($first_name) . " " . trim($last_name);   
        $customer_contact_info = mysqli_real_escape_string($con, $_POST['customer_contact_info']);
        $order_date = date('Y-m-d');
        $status = 'processing';

        // Initialize variables to track stock availability and total amount
        $isStockAvailable = true;
        $total_amount = 0;
        $productQuantitiesNeeded = [];

        // First, check stock levels for each product required by the menu items in the order
        foreach ($_POST['menu_items'] as $index => $menu_id) {
            $quantity = $_POST['quantities'][$index];
            $ingredientQuery = "SELECT mi.product_id, mi.quantity * $quantity AS needed_quantity, p.quantity_in_stock, p.reorder_level
                                FROM MenuItemIngredients mi
                                JOIN Product p ON mi.product_id = p.product_id
                                WHERE mi.menu_id = '$menu_id'";
            $ingredientResult = mysqli_query($con, $ingredientQuery);
            while ($ingredient = mysqli_fetch_assoc($ingredientResult)) {
                if ($ingredient['quantity_in_stock'] < $ingredient['needed_quantity'] || $ingredient['quantity_in_stock'] <= $ingredient['reorder_level']) {
                    $isStockAvailable = false;
                    break 2; // Exit the loop if any ingredient is below stock or reorder level
                }
                $productQuantitiesNeeded[$ingredient['product_id']] = ($productQuantitiesNeeded[$ingredient['product_id']] ?? 0) + $ingredient['needed_quantity'];
            }
        }

        if ($isStockAvailable) {
            // Proceed with order creation only if all products are available in required quantities
            foreach ($_POST['menu_items'] as $index => $menu_id) {
                $quantity = $_POST['quantities'][$index];
                // Fetch the price of the menu item directly
                $menuPriceQuery = "SELECT price FROM menu WHERE menu_id = '$menu_id'";
                $menuPriceResult = mysqli_query($con, $menuPriceQuery);
                if ($menuPriceRow = mysqli_fetch_assoc($menuPriceResult)) {
                    $menuPrice = $menuPriceRow['price'];
                    // Calculate the total price for this menu item based on quantity
                    $totalItemPrice = $quantity * $menuPrice;
                    $total_amount += $totalItemPrice;
                }
            }

            // Insert the order into the database
            $query = "INSERT INTO orders (customer_name, customer_contact_info, order_date, status, total_amount) VALUES ('$customer_name', '$customer_contact_info', '$order_date', '$status', '$total_amount')";
            if (mysqli_query($con, $query)) {
                $order_id = mysqli_insert_id($con);

                // Insert each order detail
                foreach ($_POST['menu_items'] as $index => $menu_id) {
                    $quantity = $_POST['quantities'][$index];
                    $detailQuery = "INSERT INTO order_details (order_id, menu_id, quantity, price) VALUES ('$order_id', '$menu_id', '$quantity', '$totalItemPrice')";
                    mysqli_query($con, $detailQuery);
                }

                // Deduct quantities from stock for each product used
                foreach ($productQuantitiesNeeded as $product_id => $quantityNeeded) {
                    $updateStockQuery = "UPDATE Product SET quantity_in_stock = quantity_in_stock - $quantityNeeded WHERE product_id = '$product_id'";
                    mysqli_query($con, $updateStockQuery);
                }

                header('Location: order.php?addSuccess=true');
                exit();
            } else {
                echo "Error: " . mysqli_error($con);
            }
        } else {
            // Redirect with an error if stock levels are insufficient
            header('Location: order.php?stockError=true');
            exit();
        }
    }


    function fetchOrderDetailsHtml($con, $order_id)
    {
        $html = "<table class='table'><thead><tr><th>Item Name</th><th>Quantity</th><th>Price</th></tr></thead><tbody>";

        $query = "SELECT od.quantity, od.price, m.item_name FROM order_details od JOIN menu m ON od.menu_id = m.menu_id WHERE od.order_id = ?";
        error_log("Executing query: $query with order_id: $order_id"); // Debugging line
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $html .= "<tr><td>" . htmlspecialchars($row['item_name']) . "</td><td>" . htmlspecialchars($row['quantity']) . "</td><td>$" . htmlspecialchars(number_format($row['price'], 2)) . "</td></tr>";
            }
        } else {
            $html .= "<tr><td colspan='3'>No details found for this order.</td></tr>";
        }

        $html .= "</tbody></table>";
        return $html;
    }
    if (isset($_GET['view_order_details']) && $_GET['view_order_details'] == 'true' && !empty($_GET['order_id'])) {
        $order_id = (int)$_GET['order_id'];
        error_log("Fetching details for order ID: $order_id");
        header('Content-Type: text/html; charset=utf-8'); // Set correct content type for HTML
        echo fetchOrderDetailsHtml($con, $order_id);
        exit(); // Ensure no further execution
    }

    $searchTerm = isset($_GET['searchName']) ? trim($_GET['searchName']) : '';
    $orders = []; // Initialize the array to hold order records

    if (!empty($searchTerm)) {
        // If there is a search term, fetch matching records
        $likeSearchTerm = "%" . $searchTerm . "%";
        $stmt = $con->prepare("SELECT * FROM orders WHERE customer_name LIKE ?");
        $stmt->bind_param("s", $likeSearchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
    } else {
        // If there is no search term, fetch all records
        $result = mysqli_query($con, "SELECT * FROM orders");
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
    }

    // Close the connection
    mysqli_close($con);
    ?>

    <script>
        // This script tag should be in the PHP file where $menuItemsDropdown is defined
        var menuItemsDropdown = `<?php echo str_replace(["\n", "\r"], '', addslashes($menuItemsDropdown)); ?>`;
    </script>
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
                            <a class="nav-link" tabindex="0" href="#" id="navSettings" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear nav-icon"></i><span data-translate="settings">Settings</span></a>
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
                <h3 data-translate="orderManagement">Order Management</h3>
            </header>
        </div>
        <main class="row">
            <div class="d-flex justify-content-between align-items-center col-md-10 ms-sm-auto col-lg-10 px-md-4">
                <h2 data-translate="orderList">Order List</h2>
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0;">
                    <button id="addOrderBtn" class="add-order-btn" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                        <i class="bi bi-plus-lg"></i>
                        <span data-translate="add">Add</span>
                    </button>
                    <!-- Optional Delete Button -->
                    <button id="deleteSelectedBtn" class="delete-order-btn" alt="Delete Selected">
                        <i class="bi bi-trash"></i>
                        <span data-translate="delete">Delete</span>
                    </button>
                </div>
            </div>
            <!-- Search -->
            <div class="d-flex justify-content-start col-md-10 ms-sm-auto col-lg-10 search-bar">
                <form action="order.php" method="GET" class="mb-4" autocomplete="off">
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1"><i class="bi bi-search"></i></span>
                        <input type="text" data-translate="searchByName" name="searchName" class="form-control" placeholder="Search by name..." aria-label="Search by name" aria-describedby="basic-addon1">
                        <button class="btn btn-outline-secondary" data-translate="search" type="submit">Search</button>
                    </div>
                </form>
            </div>
            <!--  Form div for Adding New Order -->
            <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4 modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header ">
                            <h5 class="modal-title" id="addOrderModalLabel" data-translate="addNew">Add New Order</h5>
                            <button tabindex="0" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form for adding a new order -->
                            <form id="addOrderForm" action="order.php" method="post">
                                <div class="mb-3">
                                    <input autocomplete="off" placeholder="First Name" type="text" class="form-control" id="first_name" data-translate="firstName" name="first_name" minlength="2" maxlength="30" required>
                                </div>

                                <div class="mb-3">
                                    <input autocomplete="off" placeholder="Last Name" type="text" class="form-control" id="last_name" data-translate="lastName" name="last_name" minlength="2" maxlength="30" required>
                                </div>
                                <div class="mb-3">
                                    <input autocomplete="off" pattern="[0-9]{10,11}" placeholder="1234-567-8901" type="tel" class="form-control" id="customer_contact_info" name="customer_contact_info" required>
                                </div>
                                <!-- Order Details Section -->
                                <div class="mb-3" id="orderDetailsSection">
                                    <div class="d-flex justify-content-center align-items-center mb-3">
                                        <div class="col">
                                            <?php echo $menuItemsDropdown; ?>
                                        </div>
                                        <div class="col">
                                            <input type="number" class="order-detail-form-control" name="quantities[]" data-translate="quantity" placeholder="Quantity">
                                        </div>

                                    </div>
                                </div>
                                <div class="col">
                                    <button type="button" class="btn btn-primary" id="addOrderDetail" data-translate="addItem">Add Item</button>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="submit" form="addOrderForm" class="save-btn" data-translate="saveOrder">Save Order</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-translate="close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Order Details Modal -->
            <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="orderDetailsModalLabel" data-translate="orderDetails">Order Details</h5>
                            <button tabindex="0" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="orderDetailsContent">Loading...</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-translate="close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-10 ms-sm-auto col-lg-10 table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th data-translate="customerName">Customer Name</th>
                            <th data-translate="customerContactInfo">Contact</th>
                            <th data-translate="orderDate">Order Date</th>
                            <th data-translate="totalAmount">Total Amount</th>
                            <th data-translate="status">Status</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order) : ?>
                            <tr data-order-id="<?php echo $order['order_id']; ?>">
                                <td><input tabindex="0" type="checkbox" class="orderCheckbox" value="<?php echo $order['order_id']; ?>"></td>
                                <td class="edit" data-id="<?php echo $order['order_id']; ?>" data-field="customer_name"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="edit" data-id="<?php echo $order['order_id']; ?>" data-field="customer_contact_info"><?php echo htmlspecialchars($order['customer_contact_info']); ?></td>
                                <td class="edit" data-id="<?php echo $order['order_id']; ?>" data-field="order_date"><?php echo htmlspecialchars($order['order_date']); ?></td>
                                <td class="edit" data-id="<?php echo $order['order_id']; ?>" data-field="total_amount"><?php echo htmlspecialchars($order['total_amount']); ?></td>
                                <td class="status">
                                    <select class="status-select" data-id="<?php echo $order['order_id']; ?>">
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td class="editIconsContainer">
                                    <i tabindex="0" class="bi bi-pencil-square editIcon" style="font-size: 1.3rem;" data-id="<?php echo $order['order_id']; ?>"></i>
                                    <i tabindex="0" class="bi bi-check saveBtn" data-id="<?php echo $order['order_id']; ?>" style="display: none;"></i>
                                    <i tabindex="0" class="bi bi-x cancelBtn" style="display: none;"></i>
                                </td>
                                <td class="editIconsContainer">
                                    <i tabindex="0" class="bi bi-three-dots-vertical optionsIcon" style="font-size: 1.3rem;" data-order-id="<?php echo $order['order_id']; ?>"></i>
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
                "quantity": "Quantity",
                "settings": "Settings",
                "theme": "Theme",
                "default": "Default",
                "darkMode": "Dark Mode",
                "lightMode": "Light Mode",
                "languagePreference": "Language Preference",
                "anglaise": "English",
                "french": "Français",
                "orderManagement": "Order Management",
                "orderList": "Order List",
                "add": "Add",
                "delete": "Delete",
                "searchByName": "Search by name...",
                "search": "Search",
                "addNew": "Add New Order",
                "firstName": "First Name",
                "lastName": "Last Name",
                "customerContactInfo": "Customer Contact Info",
                "orderDate": "Order Date",
                "totalAmount": "Total Amount",
                "addItem": "Add Item",
                "saveOrder": "Save Order",
                "close": "Close",
                "logOut": "Log Out",
                "orderDetails": "Order Details",
                "status": "Status",
                "processing": "Processing",
                "completed": "Completed",
                "cancelled": "Cancelled"
            },
            fr: {
                "inventory": "Inventaire",
                "menuItems": "Menu",
                "order": "Ordre",
                "employee": "Employé",
                "supplier": "Fournisseur",
                "quantity": "Quantité",
                "settings": "Paramètres",
                "theme": "Thème",
                "default": "Défaut",
                "darkMode": "Mode Sombre",
                "lightMode": "Mode Clair",
                "languagePreference": "Préférence de langue",
                "anglaise": "Anglaise",
                "french": "Français",
                "orderManagement": "Gestion des commandes",
                "orderList": "Liste des commandes",
                "add": "Ajouter",
                "delete": "Rayer",
                "searchByName": "Rechercher par nom...",
                "search": "Recherche",
                "addNew": "Ajouter Nouvelle Commande",
                "firstName": "Prénom",
                "lastName": "Nom de Famille",                
                "customerContactInfo": "Info Contact du Client",
                "orderDate": "Date de la Commande",
                "totalAmount": "Montant total",
                "addItem": "Ajouter Article",
                "saveOrder": "Sauvegarder la Commande",
                "close": "Fermer",
                "logOut": "Déconnexion",
                "orderDetails": "Détails de la Commande",
                "status": "Statut"
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

                if (element.tagName === "INPUT" && element.hasAttribute("placeholder")) {
                    element.placeholder = translation; // Set the translated text as placeholder
                } else if (element.tagName === "SELECT" && element.classList.contains("status-select")) {
                    // Special handling for <select> elements with status-select class
                    element.querySelectorAll("option").forEach(option => {
                        const optionKey = option.value; // Use the option's value as the key for translation
                        if (translations[language][optionKey]) {
                            option.textContent = translations[language][optionKey];
                        }
                    });
                } else if (element.tagName === "OPTION") {
                    // For <option> elements inside other <select>, translate text content only
                    element.textContent = translation;
                    // Do not modify the value attribute here to preserve functionality
                } else {
                    // For other elements, set the textContent
                    element.textContent = translation;
                }
            });

            // Update event listener for language selection
            document.getElementById('languageSelection').addEventListener('change', function() {
                const selectedLanguage = this.value;
                localStorage.setItem('languagePreference', selectedLanguage); // Persist language selection
                updatePageLanguage(selectedLanguage);
            });
        }
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="js/order.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html>