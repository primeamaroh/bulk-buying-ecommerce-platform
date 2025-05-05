<?php
require_once '../config/config.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$type = isset($_GET['type']) ? $_GET['type'] : 'products';

// Prepare base queries
if ($type === 'products') {
    $base_query = "SELECT p.*, 
                          (SELECT COUNT(*) FROM orders WHERE product_id = p.id AND status != 'cancelled') as order_count
                   FROM products p";
    
    $where_clauses = [];
    if ($status !== 'all') {
        $where_clauses[] = "p.status = :status";
    }
    
    if (!empty($where_clauses)) {
        $base_query .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    // Add sorting
    $base_query .= match($sort) {
        'price_low' => " ORDER BY p.price ASC",
        'price_high' => " ORDER BY p.price DESC",
        'popular' => " ORDER BY order_count DESC",
        default => " ORDER BY p.created_at DESC"
    };
} else {
    $base_query = "SELECT up.*, u.name as user_name,
                          (SELECT COUNT(*) FROM votes WHERE post_id = up.id) as vote_count
                   FROM user_posts up
                   JOIN users u ON up.user_id = u.id";
    
    $where_clauses = [];
    if ($status !== 'all') {
        $where_clauses[] = "up.status = :status";
    }
    
    if (!empty($where_clauses)) {
        $base_query .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    // Add sorting
    $base_query .= match($sort) {
        'popular' => " ORDER BY vote_count DESC",
        default => " ORDER BY up.created_at DESC"
    };
}

// Execute query
$stmt = $db->prepare($base_query);
if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?php echo $type === 'products' ? 'Browse Products' : 'Product Requests'; ?>
            </h1>
            <p class="mt-2 text-gray-600">
                <?php echo $type === 'products' 
                    ? 'Find and join bulk buying opportunities' 
                    : 'Vote on products you want to buy in bulk'; ?>
            </p>
        </div>

        <?php if (!isLoggedIn()): ?>
            <a href="/auth/login.php" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Login to Place Orders
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-8">
        <div class="flex flex-wrap gap-4">
            <!-- Type Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select id="type" 
                        onchange="updateFilters()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="products" <?php echo $type === 'products' ? 'selected' : ''; ?>>Products</option>
                    <option value="requests" <?php echo $type === 'requests' ? 'selected' : ''; ?>>Product Requests</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" 
                        onchange="updateFilters()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                    <?php if ($type === 'products'): ?>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="fulfilled" <?php echo $status === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                    <?php else: ?>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Sort Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select id="sort" 
                        onchange="updateFilters()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <?php if ($type === 'products'): ?>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <?php endif; ?>
                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($items as $item): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Image -->
                <div class="aspect-w-16 aspect-h-9">
                    <img src="<?php echo $item['image_path']; ?>" 
                         alt="<?php echo htmlspecialchars($type === 'products' ? $item['name'] : $item['title']); ?>"
                         class="w-full h-48 object-cover">
                </div>

                <!-- Content -->
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($type === 'products' ? $item['name'] : $item['title']); ?>
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php
                            switch($item['status']) {
                                case 'pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'active':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'fulfilled':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'approved':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'rejected':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                            }
                            ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                    </div>

                    <?php if ($type === 'products'): ?>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-lg font-bold text-gray-900">
                                R <?php echo number_format($item['price'], 2); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo $item['order_count']; ?> orders
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            Quantity: <?php echo $item['quantity']; ?> units
                        </div>
                        <?php if ($item['status'] === 'pending'): ?>
                            <div class="mt-2 text-sm text-gray-500">
                                Ends: <?php echo date('M j, Y', strtotime($item['duration'])); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Posted by: <?php echo htmlspecialchars($item['user_name']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo $item['vote_count']; ?> votes
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            Source: <?php echo htmlspecialchars($item['company']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Button -->
                    <div class="mt-6">
                        <?php if ($type === 'products'): ?>
                            <?php if (isLoggedIn()): ?>
                                <a href="/products/view.php?id=<?php echo $item['id']; ?>" 
                                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    View Details
                                </a>
                            <?php else: ?>
                                <a href="/auth/login.php" 
                                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    Login to Order
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (isLoggedIn()): ?>
                                <button onclick="showVotePopup(<?php echo $item['id']; ?>)"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    Vote for This Product
                                </button>
                            <?php else: ?>
                                <a href="/auth/login.php" 
                                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    Login to Vote
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="text-center py-12">
            <i class="fas fa-box-open text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No items found</h3>
            <p class="mt-2 text-gray-500">
                <?php echo $type === 'products' 
                    ? 'No products match your filters. Try adjusting your search criteria.' 
                    : 'No product requests match your filters. Why not submit one?'; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
function updateFilters() {
    const type = document.getElementById('type').value;
    const status = document.getElementById('status').value;
    const sort = document.getElementById('sort').value;
    
    window.location.href = `/products/browse.php?type=${type}&status=${status}&sort=${sort}`;
}
</script>

<?php include '../components/footer.php'; ?>
