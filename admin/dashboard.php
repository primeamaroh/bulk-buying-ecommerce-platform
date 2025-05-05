<?php
require_once '../config/config.php';

echo '<pre>SESSION: ';
print_r($_SESSION);
echo '</pre>';

// Ensure user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = 'Unauthorized access';
    redirect('/auth/login.php');
}

include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Admin Dashboard</h1>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Products -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-500">
                    <i class="fas fa-box-open text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase">Total Products</p>
                    <?php
                    $stmt = $db->query("SELECT COUNT(*) FROM products");
                    $total_products = $stmt->fetchColumn();
                    ?>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $total_products; ?></p>
                </div>
            </div>
        </div>

        <!-- Active Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-500">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase">Active Orders</p>
                    <?php
                    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'active'");
                    $active_orders = $stmt->fetchColumn();
                    ?>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $active_orders; ?></p>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase">Pending Orders</p>
                    <?php
                    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
                    $pending_orders = $stmt->fetchColumn();
                    ?>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $pending_orders; ?></p>
                </div>
            </div>
        </div>

        <!-- Product Requests -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                    <i class="fas fa-vote-yea text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase">Product Requests</p>
                    <?php
                    $stmt = $db->query("SELECT COUNT(*) FROM user_posts WHERE votes >= 100 AND status = 'pending'");
                    $product_requests = $stmt->fetchColumn();
                    ?>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $product_requests; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Add New Product -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="space-y-4">
                <a href="/admin/products/add.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="p-2 rounded-full bg-indigo-100 text-indigo-500">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-lg font-medium text-gray-900">Add New Product</p>
                        <p class="text-sm text-gray-500">Upload a new product for bulk buying</p>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                </a>

                <a href="/admin/settings.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="p-2 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-lg font-medium text-gray-900">Manage Settings</p>
                        <p class="text-sm text-gray-500">Update admin and shipping fees</p>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                </a>

                <a href="/admin/posts/review.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="p-2 rounded-full bg-purple-100 text-purple-500">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-lg font-medium text-gray-900">Review Product Requests</p>
                        <p class="text-sm text-gray-500">Check and approve user submitted products</p>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Activity</h2>
            <div class="space-y-4">
                <?php
                $stmt = $db->query("
                    SELECT * FROM (
                        SELECT 'order' as type, o.created_at, u.name as user_name, p.name as product_name, o.status
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        JOIN products p ON o.product_id = p.id
                        UNION ALL
                        SELECT 'post' as type, up.created_at, u.name as user_name, up.title as product_name, up.status
                        FROM user_posts up
                        JOIN users u ON up.user_id = u.id
                    ) ORDER BY created_at DESC
                    LIMIT 5
                ");
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($activities as $activity): ?>
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <div class="p-2 rounded-full <?php echo $activity['type'] === 'order' ? 'bg-blue-100 text-blue-500' : 'bg-purple-100 text-purple-500'; ?>">
                            <i class="fas <?php echo $activity['type'] === 'order' ? 'fa-shopping-cart' : 'fa-clipboard-list'; ?>"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($activity['user_name']); ?>
                                <?php echo $activity['type'] === 'order' ? 'placed an order for' : 'requested'; ?>
                                <?php echo htmlspecialchars($activity['product_name']); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                        <span class="ml-auto px-2 py-1 text-xs rounded-full 
                            <?php
                            switch($activity['status']) {
                                case 'pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'active':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'fulfilled':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($activity['status']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>
