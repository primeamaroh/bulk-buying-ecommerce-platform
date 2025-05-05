<?php
require_once '../config/config.php';

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    $_SESSION['error'] = 'Invalid product ID';
    redirect('/products/browse.php');
}

$stmt = $db->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM orders WHERE product_id = p.id AND status != 'cancelled') as order_count,
           (SELECT SUM(quantity) FROM orders WHERE product_id = p.id AND status != 'cancelled') as total_ordered
    FROM products p 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    redirect('/products/browse.php');
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantity'])) {
    try {
        validateToken($_POST['csrf_token']);
        
        if (!isLoggedIn()) {
            throw new Exception('Please login to place an order');
        }
        
        $quantity = intval($_POST['quantity']);
        $remaining_quantity = $product['quantity'] - $product['total_ordered'];
        $max_allowed = floor($product['quantity'] * 0.5); // 50% of total quantity
        
        // Validate quantity
        if ($quantity < MIN_ORDER_QUANTITY) {
            throw new Exception('Minimum order quantity is ' . MIN_ORDER_QUANTITY . ' units');
        }
        
        if ($quantity > $max_allowed) {
            throw new Exception('Maximum order quantity is ' . $max_allowed . ' units (50% of total)');
        }
        
        if ($quantity > $remaining_quantity) {
            throw new Exception('Only ' . $remaining_quantity . ' units remaining');
        }
        
        // Calculate fees
        $subtotal = $quantity * $product['price'];
        $admin_fee = calculateAdminFee($subtotal);
        $shipping_fee = calculateShippingFee($quantity * $product['weight']);
        $total = $subtotal + $admin_fee + $shipping_fee;
        
        // Create order
        $stmt = $db->prepare("
            INSERT INTO orders (
                product_id, user_id, quantity, status,
                shipping_fee, admin_fee, total_amount
            ) VALUES (?, ?, ?, 'pending', ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $product_id, $_SESSION['user_id'], $quantity,
            $shipping_fee, $admin_fee, $total
        ])) {
            $order_id = $db->lastInsertId();
            
            // Check if total ordered equals product quantity
            $stmt = $db->prepare("
                SELECT SUM(quantity) as total
                FROM orders 
                WHERE product_id = ? AND status != 'cancelled'
            ");
            $stmt->execute([$product_id]);
            $total_ordered = $stmt->fetchColumn();
            
            if ($total_ordered >= $product['quantity']) {
                // Update product status to active
                $stmt = $db->prepare("UPDATE products SET status = 'active' WHERE id = ?");
                $stmt->execute([$product_id]);
                
                // Update all pending orders to active
                $stmt = $db->prepare("UPDATE orders SET status = 'active' WHERE product_id = ? AND status = 'pending'");
                $stmt->execute([$product_id]);
            }
            
            $_SESSION['success'] = 'Order placed successfully';
            redirect('/user/orders.php');
        } else {
            throw new Exception('Failed to place order');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Product Details -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Product Image -->
                <div class="aspect-w-16 aspect-h-9 md:aspect-none">
                    <img src="<?php echo $product['image_path']; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-96 object-cover">
                </div>

                <!-- Product Info -->
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php
                            switch($product['status']) {
                                case 'pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'active':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'fulfilled':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                            }
                            ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </div>

                    <div class="mt-4">
                        <h2 class="sr-only">Product information</h2>
                        <p class="text-3xl text-gray-900">R <?php echo number_format($product['price'], 2); ?></p>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-900">Description</h3>
                        <div class="mt-2 text-sm text-gray-600 space-y-4">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <?php
                    $progress = ($product['total_ordered'] / $product['quantity']) * 100;
                    $remaining = $product['quantity'] - $product['total_ordered'];
                    ?>
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-900">Order Progress</h3>
                        <div class="mt-2">
                            <div class="relative pt-1">
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                    <div style="width: <?php echo $progress; ?>%" 
                                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500">
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-600 mt-1">
                                    <span><?php echo $product['total_ordered']; ?> ordered</span>
                                    <span><?php echo $remaining; ?> remaining</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="mt-6 border-t border-gray-200 pt-6">
                        <h3 class="text-sm font-medium text-gray-900">Details</h3>
                        <div class="mt-2 space-y-2 text-sm text-gray-600">
                            <p>Source: <a href="<?php echo htmlspecialchars($product['source_website']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-500"><?php echo htmlspecialchars($product['source_website']); ?></a></p>
                            <p>Dimensions: <?php echo htmlspecialchars($product['dimensions']); ?></p>
                            <p>Weight: <?php echo $product['weight']; ?> kg</p>
                            <?php if ($product['status'] === 'pending'): ?>
                                <p>Ends: <?php echo date('M j, Y', strtotime($product['duration'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($product['variations']): ?>
                        <!-- Variations -->
                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-medium text-gray-900">Available Variations</h3>
                            <div class="mt-2 space-y-2">
                                <?php foreach (json_decode($product['variations'], true) as $variation): ?>
                                    <div>
                                        <h4 class="text-sm text-gray-600"><?php echo htmlspecialchars($variation['name']); ?></h4>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            <?php foreach ($variation['values'] as $value): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <?php echo htmlspecialchars($value); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Order Form -->
                    <?php if ($product['status'] === 'pending' && $remaining > 0): ?>
                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-medium text-gray-900">Place Order</h3>
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" class="mt-4 space-y-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                    
                                    <div>
                                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                        <div class="mt-1">
                                            <input type="number" 
                                                   name="quantity" 
                                                   id="quantity" 
                                                   min="<?php echo MIN_ORDER_QUANTITY; ?>"
                                                   max="<?php echo min($remaining, floor($product['quantity'] * 0.5)); ?>"
                                                   required
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                   onchange="calculateTotal()">
                                        </div>
                                        <p class="mt-2 text-sm text-gray-500">
                                            Minimum: <?php echo MIN_ORDER_QUANTITY; ?> units | 
                                            Maximum: <?php echo min($remaining, floor($product['quantity'] * 0.5)); ?> units
                                        </p>
                                    </div>

                                    <!-- Order Summary -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900">Order Summary</h4>
                                        <div class="mt-2 space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Subtotal:</span>
                                                <span id="subtotal" class="text-gray-900">R 0.00</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Admin Fee (<?php echo $settings['admin_fee_percentage']; ?>%):</span>
                                                <span id="admin_fee" class="text-gray-900">R 0.00</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Shipping (R<?php echo $settings['shipping_fee_per_kg']; ?>/kg):</span>
                                                <span id="shipping_fee" class="text-gray-900">R 0.00</span>
                                            </div>
                                            <div class="flex justify-between font-medium border-t border-gray-200 pt-2">
                                                <span class="text-gray-900">Total:</span>
                                                <span id="total" class="text-gray-900">R 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" 
                                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Place Order
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="mt-4">
                                    <a href="/auth/login.php" 
                                       class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        Login to Place Order
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const price = <?php echo $product['price']; ?>;
const weight = <?php echo $product['weight']; ?>;
const adminFeePercent = <?php echo $settings['admin_fee_percentage']; ?>;
const shippingFeePerKg = <?php echo $settings['shipping_fee_per_kg']; ?>;

function calculateTotal() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const subtotal = quantity * price;
    const adminFee = (subtotal * adminFeePercent) / 100;
    const shippingFee = (quantity * weight * shippingFeePerKg);
    const total = subtotal + adminFee + shippingFee;
    
    document.getElementById('subtotal').textContent = `R ${subtotal.toFixed(2)}`;
    document.getElementById('admin_fee').textContent = `R ${adminFee.toFixed(2)}`;
    document.getElementById('shipping_fee').textContent = `R ${shippingFee.toFixed(2)}`;
    document.getElementById('total').textContent = `R ${total.toFixed(2)}`;
}

// Initialize calculation
document.addEventListener('DOMContentLoaded', calculateTotal);
</script>

<?php include '../components/footer.php'; ?>
