<?php
require_once '../config/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateToken($_POST['csrf_token']);
        
        // Check if user is logged in
        if (!isLoggedIn()) {
            throw new Exception('Please login to post a product request');
        }
        
        // Validate and sanitize input
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $website = sanitize($_POST['website']);
        $company = sanitize($_POST['company']);
        
        if (empty($title) || empty($description) || empty($website) || empty($company)) {
            throw new Exception('Please fill in all required fields');
        }

        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
            }
            
            $file_name = uniqid() . '_' . $_FILES['image']['name'];
            $upload_path = UPLOADS_PATH . '/posts/' . $file_name;
            
            // Create posts directory if it doesn't exist
            if (!file_exists(UPLOADS_PATH . '/posts')) {
                mkdir(UPLOADS_PATH . '/posts', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = '/uploads/posts/' . $file_name;
            } else {
                throw new Exception('Failed to upload image');
            }
        } else {
            throw new Exception('Product image is required');
        }

        // Insert post into database
        $stmt = $db->prepare("
            INSERT INTO user_posts (
                user_id, title, description, image_path,
                website, company, votes, status
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 'pending')
        ");
        
        if ($stmt->execute([
            $_SESSION['user_id'], $title, $description,
            $image_path, $website, $company
        ])) {
            $_SESSION['success'] = 'Product request posted successfully';
            redirect('/products/browse.php');
        } else {
            throw new Exception('Failed to post product request');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Request a Product</h1>
            <p class="mt-2 text-gray-600">
                Submit a product you'd like to buy in bulk. If it receives enough votes, 
                it will be reviewed by our admins for potential listing.
            </p>
        </div>

        <!-- Post Form -->
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
            
            <!-- Basic Information -->
            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Product Title *</label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           placeholder="Enter a clear, descriptive title">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                    <textarea name="description" 
                              id="description" 
                              rows="4" 
                              required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                              placeholder="Describe the product, its features, and why people should be interested in bulk buying it"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700">Product Website *</label>
                        <input type="url" 
                               name="website" 
                               id="website" 
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="https://example.com/product">
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700">Company/Store Name *</label>
                        <input type="text" 
                               name="company" 
                               id="company" 
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Enter company or store name">
                    </div>
                </div>
            </div>

            <!-- Image Upload -->
            <div class="space-y-6 pt-6 border-t border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Image *</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                            <div class="flex text-sm text-gray-600">
                                <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="image" name="image" type="file" class="sr-only" accept="image/*" required>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Information Box -->
            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">How it works</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Your product request needs <?php echo VOTES_REQUIRED_FOR_ADMIN; ?> votes to be reviewed by admins</li>
                                <li>Each vote requires a minimum deposit of R<?php echo MIN_VOTE_DEPOSIT; ?></li>
                                <li>Deposits are refundable if the product is not approved</li>
                                <li>If approved, the product will be listed for bulk buying</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6">
                <?php if (isLoggedIn()): ?>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Submit Product Request
                    </button>
                <?php else: ?>
                    <a href="/auth/login.php" 
                       class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Login to Submit Request
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
// Preview uploaded image
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('img');
            preview.src = e.target.result;
            preview.className = 'mt-4 mx-auto h-32 w-32 object-cover rounded-lg';
            
            const container = document.querySelector('.text-center');
            const existingPreview = container.querySelector('img');
            if (existingPreview) {
                existingPreview.remove();
            }
            container.appendChild(preview);
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../components/footer.php'; ?>
