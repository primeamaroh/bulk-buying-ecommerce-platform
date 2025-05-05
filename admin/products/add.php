<?php
require_once '../../config/config.php';

// Ensure user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = 'Unauthorized access';
    redirect('/auth/login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateToken($_POST['csrf_token']);
        
        // Validate and sanitize input
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $quantity = intval($_POST['quantity']);
        $price = floatval($_POST['price']);
        $duration = $_POST['duration'];
        $source_website = sanitize($_POST['source_website']);
        $dimensions = sanitize($_POST['dimensions']);
        $weight = floatval($_POST['weight']);
        
        // Validate required fields
        if (empty($name) || empty($description) || $quantity <= 0 || $price <= 0 || empty($duration)) {
            throw new Exception('Please fill in all required fields');
        }

        // Handle variations (JSON)
        $variations = [];
        if (!empty($_POST['variation_names']) && is_array($_POST['variation_names'])) {
            foreach ($_POST['variation_names'] as $key => $name) {
                if (!empty($name) && !empty($_POST['variation_values'][$key])) {
                    $variations[] = [
                        'name' => sanitize($name),
                        'values' => array_map('trim', explode(',', sanitize($_POST['variation_values'][$key])))
                    ];
                }
            }
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
            $upload_path = UPLOADS_PATH . '/products/' . $file_name;
            
            // Create products directory if it doesn't exist
            if (!file_exists(UPLOADS_PATH . '/products')) {
                mkdir(UPLOADS_PATH . '/products', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = '/uploads/products/' . $file_name;
            } else {
                throw new Exception('Failed to upload image');
            }
        }

        // Insert product into database
        $stmt = $db->prepare("
            INSERT INTO products (
                name, description, quantity, price, variations, duration,
                source_website, dimensions, weight, image_path, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
            )
        ");
        
        if ($stmt->execute([
            $name, $description, $quantity, $price, 
            json_encode($variations), $duration, $source_website,
            $dimensions, $weight, $image_path
        ])) {
            $_SESSION['success'] = 'Product added successfully';
            redirect('/admin/dashboard.php');
        } else {
            throw new Exception('Failed to add product');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
            <a href="/admin/dashboard.php" class="text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <!-- Product Form -->
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
            
            <!-- Basic Information -->
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900">Basic Information</h2>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Product Name *</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                    <textarea name="description" 
                              id="description" 
                              rows="4" 
                              required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity *</label>
                        <input type="number" 
                               name="quantity" 
                               id="quantity" 
                               min="1" 
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price (R) *</label>
                        <input type="number" 
                               name="price" 
                               id="price" 
                               min="0.01" 
                               step="0.01" 
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700">Duration *</label>
                    <input type="datetime-local" 
                           name="duration" 
                           id="duration" 
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <!-- Product Details -->
            <div class="space-y-6 pt-6 border-t border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Product Details</h2>

                <div>
                    <label for="source_website" class="block text-sm font-medium text-gray-700">Source Website</label>
                    <input type="url" 
                           name="source_website" 
                           id="source_website" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="dimensions" class="block text-sm font-medium text-gray-700">Dimensions</label>
                        <input type="text" 
                               name="dimensions" 
                               id="dimensions" 
                               placeholder="e.g., 10x20x30 cm"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                        <input type="number" 
                               name="weight" 
                               id="weight" 
                               min="0" 
                               step="0.01"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>
            </div>

            <!-- Variations -->
            <div class="space-y-6 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Variations</h2>
                    <button type="button" 
                            onclick="addVariation()"
                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus mr-2"></i> Add Variation
                    </button>
                </div>

                <div id="variations-container" class="space-y-4">
                    <!-- Variations will be added here dynamically -->
                </div>
            </div>

            <!-- Image Upload -->
            <div class="space-y-6 pt-6 border-t border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Product Image</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Upload Image</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                            <div class="flex text-sm text-gray-600">
                                <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 border-t border-gray-200">
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add Product
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let variationCount = 0;

function addVariation() {
    const container = document.getElementById('variations-container');
    const variationHtml = `
        <div class="variation-item grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
            <div>
                <label class="block text-sm font-medium text-gray-700">Variation Name</label>
                <input type="text" 
                       name="variation_names[]" 
                       placeholder="e.g., Color, Size"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700">Values (comma-separated)</label>
                <input type="text" 
                       name="variation_values[]" 
                       placeholder="e.g., Red, Blue, Green"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <button type="button" 
                        onclick="this.closest('.variation-item').remove()"
                        class="absolute top-0 right-0 text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', variationHtml);
    variationCount++;
}

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

<?php include '../../components/footer.php'; ?>
