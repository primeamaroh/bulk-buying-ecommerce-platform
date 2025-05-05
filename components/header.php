<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Logo and primary nav -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-xl font-bold text-indigo-600">
                            <?php echo SITE_NAME; ?>
                        </a>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="/" class="inline-flex items-center px-1 pt-1 text-gray-700 hover:text-indigo-600">
                            Home
                        </a>
                        <a href="/products/browse.php" class="inline-flex items-center px-1 pt-1 text-gray-700 hover:text-indigo-600">
                            Browse Products
                        </a>
                        <a href="/products/post.php" class="inline-flex items-center px-1 pt-1 text-gray-700 hover:text-indigo-600">
                            Post Product
                        </a>
                    </div>
                </div>

                <!-- Secondary nav -->
                <div class="flex items-center">
                    <?php if (isLoggedIn()): ?>
                        <div class="hidden md:ml-4 md:flex md:items-center md:space-x-4">
                            <?php if (isAdmin()): ?>
                                <a href="/admin/dashboard.php" class="text-gray-700 hover:text-indigo-600">
                                    Admin Dashboard
                                </a>
                            <?php else: ?>
                                <a href="/user/dashboard.php" class="text-gray-700 hover:text-indigo-600">
                                    My Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="/auth/logout.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="hidden md:ml-4 md:flex md:items-center md:space-x-4">
                            <a href="/auth/login.php" class="text-gray-700 hover:text-indigo-600">
                                Login
                            </a>
                            <a href="/auth/signup.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Sign Up
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Mobile menu button -->
                    <div class="flex items-center md:hidden">
                        <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="hidden mobile-menu md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                    Home
                </a>
                <a href="/products/browse.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                    Browse Products
                </a>
                <a href="/products/post.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                    Post Product
                </a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="/admin/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                            Admin Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/user/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                            My Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="/auth/logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/auth/login.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                        Login
                    </a>
                    <a href="/auth/signup.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 py-6">
