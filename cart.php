<?php
session_start();
require_once(__DIR__ . "/app/config/Directories.php");

// Redirect non-logged-in users to login or register
if (!isset($_SESSION["username"])) {
    $_SESSION['error'] = "Please log in or register to access the cart.";
    header("Location: login.php");
    exit();
}

// Database connection
include(ROOT_DIR . "app/config/DatabaseConnect.php");
$db = new DatabaseConnect();
$conn = $db->connectDB();

// Fetch cart items for logged-in user
$carts = [];
$userId = $_SESSION["user_id"];
$subtotal = 0;
$shipping = 1500;

if ($userId) {
    try {
        $sql = "SELECT carts.order_id, products.product_name, carts.quantity, products.unit_price, 
                (carts.quantity * products.unit_price) as total_price, 
                carts.size, products.img_url_front AS image_url, products.product_id
        FROM carts
        LEFT JOIN products ON products.product_id = carts.product_id
        WHERE carts.user_id = :userId;";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $carts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate subtotal properly using float values
        $subtotal = 0;
        foreach ($carts as $item) {
            $subtotal += floatval($item['total_price']);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Calculate total including shipping
$total = $subtotal + $shipping;

include(__DIR__ . '/includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Base styles for the cart page */
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
        }

        .container {
            max-width: 1200px;
            margin-top: 50px;
        }

        .cart-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .cart-header h1 {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .cart-header p {
            font-size: 18px;
            color: #555;
        }

        /* Cart items styling */
        .cart-item {
            background-color: #fff;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .cart-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }

        .cart-item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-left: 20px;
            width: 100%;
        }

        .cart-item-info {
            flex-grow: 1;
        }

        .cart-item-details .product-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .cart-item-details .price {
            font-size: 18px;
            color: #4CAF50;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .quantity-controls button {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            color: #333;
            font-size: 16px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-controls button:hover {
            background-color: #e0e0e0;
        }

        .quantity-controls input {
            width: 40px;
            text-align: center;
            font-size: 16px;
            border: 1px solid #ddd;
            margin: 0 5px;
        }

        .total-price {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
        }

        .price-summary {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .price-summary .subtotal,
        .price-summary .shipping,
        .price-summary .total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .price-summary .total {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .checkout-btn {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 5px;
            width: 100%;
            cursor: pointer;
        }

        .checkout-btn:hover {
            background-color: #45a049;
        }

        .continue-shopping-btn {
            display: inline-block;
            background-color: #f8f8f8;
            color: black;
            padding: 10px 20px;
            font-size: 16px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            margin-right: 10px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .continue-shopping-btn:hover {
            background-color: #e0e0e0;
            border-color: #bbb;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: #f8f9fa;
            padding: 15px;
            font-weight: 600;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        .table tr {
            border-bottom: 1px solid #dee2e6;
        }

        .table tr:last-child {
            border-bottom: none;
        }

        .delete-item {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
        }
        .delete-item:hover {
            color: #bd2130;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cart-header">
            <h1>Your Cart (<span id="item-count"><?php echo count($carts); ?></span> items)</h1>
            <p>Review your items and proceed to checkout</p>
        </div>

        <?php if (isset($_SESSION['last_product_id'])): ?>
            <a href="<?php echo BASE_URL . 'product.php?id=' . $_SESSION['last_product_id']; ?>" class="continue-shopping-btn">
                <i class="fas fa-arrow-left"></i> Continue shopping
            </a>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Item</th>
                        <th style="padding-left: 200px;">Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carts as $item): ?>
                    <tr class="cart-item" data-order-id="<?php echo $item['order_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo $item['image_url']; ?>" alt="Product Image" style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <p class="product-name mb-1"><?php echo $item['product_name']; ?></p>
                                    <?php if (!empty($item['size'])): ?>
                                        <p class="mb-0"><strong>Size:</strong> <?php echo htmlspecialchars(html_entity_decode($item['size'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-start" style="padding-left: 100px;">₱ <span class="unit-price"><?php echo number_format($item['unit_price'], 2); ?></span></td>
                        <td>
                            <div class="quantity-controls d-flex justify-content-center align-items-center">
                                <button class="decrease" data-order-id="<?php echo $item['order_id']; ?>">-</button>
                                <input type="text" value="<?php echo $item['quantity']; ?>" readonly>
                                <button class="increase" data-order-id="<?php echo $item['order_id']; ?>">+</button>
                            </div>
                        </td>
                        <td class="text-end">₱ <span class="item-total-price"><?php echo number_format($item['total_price'], 2); ?></span></td>
                        <td class="text-end">
                            <button class="delete-item" data-order-id="<?php echo $item['order_id']; ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="price-summary">
            <div class="subtotal">
                <p>Subtotal</p>
                <p>₱ <span id="subtotal"><?php echo number_format($subtotal, 2); ?></span></p>
            </div>
            <div class="shipping">
                <p>Shipping</p>
                <p>₱ <span id="shipping"><?php echo number_format($shipping, 2); ?></span></p>
            </div>
            <div class="total">
                <p>Total (Incl. taxes)</p>
                <p>₱ <span id="total"><?php echo number_format($total, 2); ?></span></p>
            </div>

            <button class="checkout-btn">Proceed to Checkout</button>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this item?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once(ROOT_DIR."includes/footer.php"); ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let itemToDelete = null;

            $('.delete-item').click(function() {
                itemToDelete = $(this).data('order-id');
                $('#deleteConfirmModal').modal('show');
            });

            $('#confirmDelete').click(function() {
                if (itemToDelete) {
                    $.ajax({
                        url: 'app/cart/delete_item.php',
                        method: 'POST',
                        data: { order_id: itemToDelete },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $(`tr[data-order-id="${itemToDelete}"]`).remove();
                                updateTotals(response);
                            } else {
                                alert('Error: ' + response.error);
                            }
                        },
                        error: function() {
                            alert('An error occurred while trying to delete the item.');
                        }
                    });
                }
                $('#deleteConfirmModal').modal('hide');
            });

            $('.increase, .decrease').click(function() {
                var orderId = $(this).data('order-id');
                var change = $(this).hasClass('increase') ? 1 : -1;
                updateQuantity(orderId, change);
            });

            function updateQuantity(orderId, change) {
                var row = $(`tr[data-order-id="${orderId}"]`);
                var productId = row.data('product-id');
                $.ajax({
                    url: 'app/cart/update_cart.php',
                    method: 'POST',
                    data: { order_id: orderId, product_id: productId, quantity_change: change },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            updateUI(orderId, response);
                            updateTotals(response);
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('An error occurred while trying to update the quantity.');
                    }
                });
            }

            function updateUI(orderId, response) {
                var row = $(`tr[data-order-id="${orderId}"]`);
                row.find('input[type="text"]').val(response.newQuantity);
    
                // Remove any existing commas and convert to float for calculation
                var unitPrice = parseFloat(response.newUnitPrice.replace(/,/g, ''));
                var totalPrice = parseFloat(response.newTotalPrice.replace(/,/g, ''));
    
                row.find('.unit-price').text(formatNumber(unitPrice));
                row.find('.item-total-price').text(formatNumber(totalPrice));
            }

            function updateTotals(response) {
                // Convert string values to floats before calculations
                var subtotal = parseFloat(response.newSubtotal.replace(/,/g, ''));
                var shipping = parseFloat(response.shipping.replace(/,/g, ''));
                var total = subtotal + shipping;

                // Update the display
                $('#subtotal').text(formatNumber(subtotal));
                $('#shipping').text(formatNumber(shipping));
                $('#total').text(formatNumber(total));
                $('#item-count').text(response.itemCount);
            }

            function formatNumber(number) {
                // Ensure the number is treated as a float
                var num = parseFloat(number);
                // Format with 2 decimal places and add commas for thousands
                return num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });
    </script>
</body>
</html>

