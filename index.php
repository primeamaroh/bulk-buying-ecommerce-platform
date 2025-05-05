<?php
require_once 'config/config.php';

// Get featured products (active and pending)
$stmt = $db->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM orders WHERE product_id = p.id AND status != 'cancelled') as order_count,
           (SELECT SUM(quantity) FROM orders WHERE product_id = p.id AND status != 'cancelled') as total_ordered
    FROM products p 
    WHERE p.status IN ('active', 'pending')
    ORDER BY p.created_at DESC 
    LIMIT 6
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get trending product requests (most votes)
$stmt = $db->query("
    SELECT up.*, u.name as user_name,
           (SELECT COUNT(*) FROM votes WHERE post_id = up.id) as vote_count
    FROM user_posts up
    JOIN users u ON up.user_id = u.id
    WHERE up.status = 'pending'
    ORDER BY vote_count DESC
    LIMIT 4
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'components/header.php';
?>

<!-- Hero Section -->
<div class="relative bg-indigo-800">
    <div class="absolute inset-0">
        <img class="w-full h-full object-cover" src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg" alt="People working together">
        <div class="absolute inset-0 bg-indigo-800 mix-blend-multiply"></div>
    </div>
    <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
            Bulk Buying Made Simple
        </h1>
        <p class="mt-6 text-xl text-indigo-100 max-w-3xl">
            Join forces with other buyers to unlock wholesale prices. 
            Submit your product requests or join existing bulk orders.
        </p>
        <div class="mt-10 flex space-x-4">
            <a href="/products/browse.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-indigo-700 bg-white hover:bg-indigo-50">
                Browse Products
            </a>
            <a href="/products/post.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Post a Request
            </a>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">
                How It Works
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Join our community and start saving with collective buying power
            </p>
        </div>

        <div class="mt-16">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 mx-auto">
                        <i class="fas fa-search text-2xl"></i>
                    </div>
                    <h3 class="mt-6 text-lg font-medium text-gray-900">Find or Request</h3>
                    <p class="mt-2 text-base text-gray-500">
                        Browse available products or submit your own product request for bulk buying
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 mx-auto">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="mt-6 text-lg font-medium text-gray-900">Join or Vote</h3>
                    <p class="mt-2 text-base text-gray-500">
                        Place your order or vote on product requests you're interested in
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 mx-auto">
                        <i class="fas fa-percent text-2xl"></i>
                    </div>
                    <h3 class="mt-6 text-lg font-medium text-gray-900">Save Together</h3>
                    <p class="mt-2 text-base text-gray-500">
                        Once enough orders are placed, everyone benefits from wholesale prices
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Products Section -->
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">
                Featured Products
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Join these active bulk buying opportunities
            </p>
        </div>

        <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($products as $product): ?>
                <?php
                $progress = ($product['total_ordered'] / $product['quantity']) * 100;
                $remaining = $product['quantity'] - $product['total_ordered'];
                ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="aspect-w-16 aspect-h-9">
                        <img src="<?php echo $product['image_path']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-48 object-cover">
                    </div>
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </p>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-base font-medium text-gray-900">
                                <p>R <?php echo number_format($product['price'], 2); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $product['order_count']; ?> orders</p>
                            </div>
                            <div class="mt-4">
                                <div class="relative pt-1">
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                        <div style="width: <?php echo $progress; ?>%" 
                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500">
                                        </div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        <?php echo $remaining; ?> units remaining
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <a href="/products/view.php?id=<?php echo $product['id']; ?>" 
                               class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 text-center">
            <a href="/products/browse.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                View All Products
            </a>
        </div>
    </div>
</div>

<!-- Trending Requests Section -->
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">
                Trending Requests
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Vote on products you want to buy in bulk
            </p>
        </div>

        <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($requests as $request): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="aspect-w-16 aspect-h-9">
                        <img src="<?php echo $request['image_path']; ?>" 
                             alt="<?php echo htmlspecialchars($request['title']); ?>"
                             class="w-full h-40 object-cover">
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <?php echo htmlspecialchars($request['title']); ?>
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Posted by <?php echo htmlspecialchars($request['user_name']); ?>
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm font-medium text-indigo-600">
                                <?php echo $request['vote_count']; ?> votes
                            </span>
                            <span class="text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                            </span>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <button onclick="showVotePopup(<?php echo $request['id']; ?>)"
                                    class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                Vote Now
                            </button>
                        <?php else: ?>
                            <a href="/auth/login.php" 
                               class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                Login to Vote
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 text-center">
            <a href="/products/browse.php?type=requests" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                View All Requests
            </a>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-indigo-700">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
            <span class="block">Ready to start saving?</span>
            <span class="block text-indigo-200">Join our bulk buying community today.</span>
        </h2>
        <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
                <a href="/auth/signup.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                    Get Started
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>
