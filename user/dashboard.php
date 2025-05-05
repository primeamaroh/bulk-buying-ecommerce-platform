<?php
require_once '../config/config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to access your dashboard';
    redirect('/auth/login.php');
}

// Get user's orders
$stmt = $db->prepare("
    SELECT o.*, p.name as product_name, p.image_path
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's votes
$stmt = $db->prepare("
    SELECT v.*, up.title, up.image_path, up.status as post_status
    FROM votes v
    JOIN user_posts up ON v.post_id = up.id
    WHERE v.user_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    try {
        validateToken($_POST['csrf_token']);
        
        $order_id = intval($_POST['order_id']);
        
        // Get order details
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        if ($order['status'] !== 'pending') {
            throw new Exception('Only pending orders can be cancelled');
        }
        
        // Calculate cancellation fee
        $cancellation_fee = ($order['total_amount'] * CANCELLATION_FEE_PERCENTAGE) / 100;
        
        // Update order status
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'cancelled',
                cancellation_fee = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$cancellation_fee, $order_id])) {
            $_SESSION['success'] = 'Order cancelled successfully. A ' . CANCELLATION_FEE_PERCENTAGE . '% cancellation fee will be charged.';
            redirect('/user/dashboard.php');
        } else {
            throw new Exception('Failed to cancel order');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h1 class="text-2xl font-bold text-gray-900">
            Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
        </h1>
        <p class="mt-2 text-gray-600">
            Manage your orders and track your product votes from your personal dashboard.
        </p>
    </div>

    <!-- Orders Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Your Orders</h2>
        </div>

        <?php if (empty($orders)): ?>
            <div class="p-6 text-center">
                <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-600">You haven't placed any orders yet.</p>
                <a href="/products/browse.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img src="<?php echo $order['image_path']; ?>" 
                                                 alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                                 class="h-10 w-10 rounded-full object-cover">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($order['product_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Order #<?php echo $order['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $order['quantity']; ?> units
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    R <?php echo number_format($order['total_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch($order['status']) {
                                            case 'pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'active':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'fulfilled':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this order? A <?php echo CANCELLATION_FEE_PERCENTAGE; ?>% fee will be charged.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" 
                                                    name="cancel_order"
                                                    class="text-red-600 hover:text-red-900">
                                                Cancel Order
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Votes Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Your Product Votes</h2>
        </div>

        <?php if (empty($votes)): ?>
            <div class="p-6 text-center">
                <i class="fas fa-vote-yea text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-600">You haven't voted on any products yet.</p>
                <a href="/products/browse.php?type=requests" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Browse Product Requests
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Potential Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deposit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($votes as $vote): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img src="<?php echo $vote['image_path']; ?>" 
                                                 alt="<?php echo htmlspecialchars($vote['title']); ?>"
                                                 class="h-10 w-10 rounded-full object-cover">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($vote['title']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $vote['potential_quantity']; ?> units
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    R <?php echo number_format($vote['deposit_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch($vote['post_status']) {
                                            case 'pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'approved':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'rejected':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>">
                                        <?php echo ucfirst($vote['post_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($vote['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../components/footer.php'; ?>
