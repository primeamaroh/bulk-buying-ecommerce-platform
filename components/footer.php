</main>

    <!-- Footer -->
    <footer class="bg-gray-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4"><?php echo SITE_NAME; ?></h3>
                    <p class="text-gray-400 text-sm">
                        Your trusted platform for collective bulk buying. Join our community and get access to wholesale prices through group purchases.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="/products/browse.php" class="text-gray-400 hover:text-white text-sm">Browse Products</a>
                        </li>
                        <li>
                            <a href="/products/post.php" class="text-gray-400 hover:text-white text-sm">Post a Product</a>
                        </li>
                        <li>
                            <a href="/how-it-works.php" class="text-gray-400 hover:text-white text-sm">How It Works</a>
                        </li>
                        <li>
                            <a href="/faq.php" class="text-gray-400 hover:text-white text-sm">FAQ</a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-envelope w-5"></i>
                            <span>support@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com</span>
                        </li>
                        <li class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-phone w-5"></i>
                            <span>+27 123 456 789</span>
                        </li>
                        <!-- Social Media Links -->
                        <li class="flex space-x-4 mt-4">
                            <a href="#" class="text-gray-400 hover:text-white">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="mt-8 pt-8 border-t border-gray-700">
                <p class="text-center text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
    </script>

    <!-- Checkout Popup Container -->
    <div id="checkout-popup" class="hidden fixed top-20 right-4 w-80 bg-white rounded-lg shadow-xl z-50">
        <!-- Content will be dynamically inserted here -->
    </div>

    <!-- Vote Popup Container -->
    <div id="vote-popup" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <!-- Content will be dynamically inserted here -->
        </div>
    </div>

    <script>
        // Function to show checkout popup
        function showCheckoutPopup(content) {
            const popup = document.getElementById('checkout-popup');
            popup.innerHTML = content;
            popup.classList.remove('hidden');
            setTimeout(() => {
                popup.classList.add('hidden');
            }, 5000);
        }

        // Function to show vote popup
        function showVotePopup(postId) {
            const popup = document.getElementById('vote-popup');
            popup.querySelector('.relative').innerHTML = `
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Secure Your Spot</h3>
                    <div class="mt-2 px-7 py-3">
                        <form id="vote-form" class="space-y-4">
                            <input type="hidden" name="post_id" value="${postId}">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Potential Quantity</label>
                                <input type="number" name="potential_quantity" min="1" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Deposit Amount (min R5)</label>
                                <input type="number" name="deposit_amount" min="5" step="0.01"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Submit Vote
                            </button>
                            <button type="button" onclick="closeVotePopup()"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                        </form>
                    </div>
                </div>
            `;
            popup.classList.remove('hidden');
        }

        // Function to close vote popup
        function closeVotePopup() {
            document.getElementById('vote-popup').classList.add('hidden');
        }

        // Close vote popup when clicking outside
        document.getElementById('vote-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVotePopup();
            }
        });
    </script>
</body>
</html>
