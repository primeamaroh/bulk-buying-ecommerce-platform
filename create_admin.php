<?php
require_once 'config/config.php';

try {
    $name = "Admin User";
    $email = "admin@example.com";
    $password = password_hash("AdminPass123!", PASSWORD_DEFAULT);
    $role = "admin";

    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $email, $password, $role])) {
        echo "Admin user created successfully!\n";
        echo "Email: admin@example.com\n";
        echo "Password: AdminPass123!\n";
    } else {
        echo "Failed to create admin user.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
