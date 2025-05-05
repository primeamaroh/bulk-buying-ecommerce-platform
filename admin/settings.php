<?php
require_once '../config/config.php';

// Ensure user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = 'Unauthorized access';
    redirect('/auth/login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateToken($_POST['csrf_token']);
        
        $admin_fee = floatval($_POST['admin_fee']);
        $shipping_fee = floatval($_POST['shipping_fee']);
        
        // Validate input
        if ($admin_fee < 0 || $admin_fee > 100) {
            throw new Exception('Admin fee must be between 0 and 100 percent');
        }
        
        if ($shipping_fee < 0) {
            throw new Exception('Shipping fee per kg must be greater than or equal to 0');
        }
        
        // Update settings
        $stmt = $db->prepare("
            UPDATE admin_settings 
            SET admin_fee_percentage = ?, 
                shipping_fee_per_kg = ?,
                updated_at = CURRENT_TIMESTAMP
        ");
        
        if ($stmt->execute([$admin_fee, $shipping_fee])) {
            $_SESSION['success'] = 'Settings updated successfully';
        } else {
            throw new Exception('Failed to update settings');
        }
        
        redirect('/admin/settings.php');
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get current settings
$stmt = $db->query("SELECT * FROM admin_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

include '../components/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Settings</h1>
            <a href="/admin/dashboard.php" class="text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <!-- Settings Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                
                <!-- Admin Fee -->
                <div>
                    <label for="admin_fee" class="block text-sm font-medium text-gray-700">
                        Admin Fee Percentage
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" 
                               name="admin_fee" 
                               id="admin_fee" 
                               step="0.01" 
                               min="0" 
                               max="100"
                               value="<?php echo htmlspecialchars($settings['admin_fee_percentage']); ?>"
                               class="block w-full pr-12 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        This fee will be applied to all orders as a percentage of the total order value.
                    </p>
                </div>

                <!-- Shipping Fee -->
                <div>
                    <label for="shipping_fee" class="block text-sm font-medium text-gray-700">
                        Shipping Fee per KG
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">R</span>
                        </div>
                        <input type="number" 
                               name="shipping_fee" 
                               id="shipping_fee" 
                               step="0.01" 
                               min="0"
                               value="<?php echo htmlspecialchars($settings['shipping_fee_per_kg']); ?>"
                               class="block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">/kg</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        This fee will be multiplied by the product weight to calculate shipping costs.
                    </p>
                </div>

                <!-- Last Updated -->
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        Last updated: 
                        <?php echo date('M j, Y g:i A', strtotime($settings['updated_at'])); ?>
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Fee Calculator -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Fee Calculator</h2>
            <div class="space-y-4">
                <div>
                    <label for="calc_amount" class="block text-sm font-medium text-gray-700">
                        Order Amount (R)
                    </label>
                    <input type="number" 
                           id="calc_amount" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="0"
                           step="0.01">
                </div>
                <div>
                    <label for="calc_weight" class="block text-sm font-medium text-gray-700">
                        Weight (kg)
                    </label>
                    <input type="number" 
                           id="calc_weight" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           min="0"
                           step="0.01">
                </div>
                <div class="pt-4 space-y-2">
                    <p class="text-sm text-gray-600">
                        Admin Fee: <span id="calc_admin_fee" class="font-medium">R 0.00</span>
                    </p>
                    <p class="text-sm text-gray-600">
                        Shipping Fee: <span id="calc_shipping_fee" class="font-medium">R 0.00</span>
                    </p>
                    <p class="text-sm font-semibold text-gray-900">
                        Total Fees: <span id="calc_total_fee" class="font-medium">R 0.00</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fee Calculator
document.addEventListener('DOMContentLoaded', function() {
    const adminFeePercent = <?php echo $settings['admin_fee_percentage']; ?>;
    const shippingFeePerKg = <?php echo $settings['shipping_fee_per_kg']; ?>;
    
    const calcAmount = document.getElementById('calc_amount');
    const calcWeight = document.getElementById('calc_weight');
    const calcAdminFee = document.getElementById('calc_admin_fee');
    const calcShippingFee = document.getElementById('calc_shipping_fee');
    const calcTotalFee = document.getElementById('calc_total_fee');
    
    function updateCalculator() {
        const amount = parseFloat(calcAmount.value) || 0;
        const weight = parseFloat(calcWeight.value) || 0;
        
        const adminFee = (amount * adminFeePercent / 100);
        const shippingFee = (weight * shippingFeePerKg);
        const totalFee = adminFee + shippingFee;
        
        calcAdminFee.textContent = `R ${adminFee.toFixed(2)}`;
        calcShippingFee.textContent = `R ${shippingFee.toFixed(2)}`;
        calcTotalFee.textContent = `R ${totalFee.toFixed(2)}`;
    }
    
    calcAmount.addEventListener('input', updateCalculator);
    calcWeight.addEventListener('input', updateCalculator);
});
</script>

<?php include '../components/footer.php'; ?>
