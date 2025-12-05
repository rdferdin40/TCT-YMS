#!/usr/bin/env php
<?php
/**
 * TCT-YMS Installation Script
 *
 * This script sets up the database, creates the initial admin user,
 * and configures the application for first use.
 *
 * Usage: php scripts/install.php
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         TCT Yard Management System - Installer               ║\n";
echo "║                    Version 1.0.0                             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    die("Error: PHP 8.2 or higher is required. Current version: " . PHP_VERSION . "\n");
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}
if (!empty($missingExtensions)) {
    die("Error: Missing PHP extensions: " . implode(', ', $missingExtensions) . "\n");
}

echo "✓ PHP " . PHP_VERSION . " detected\n";
echo "✓ All required extensions loaded\n\n";

// Get base path
$basePath = dirname(__DIR__);

// Check if .env exists
$envFile = $basePath . '/.env';
$envExampleFile = $basePath . '/.env.example';

if (!file_exists($envFile)) {
    if (!file_exists($envExampleFile)) {
        die("Error: .env.example file not found.\n");
    }
    copy($envExampleFile, $envFile);
    echo "✓ Created .env file from .env.example\n";
}

// Interactive configuration
echo "─────────────────────────────────────────────────────────────────\n";
echo "Database Configuration\n";
echo "─────────────────────────────────────────────────────────────────\n";

$dbHost = prompt("Database Host", "localhost");
$dbPort = prompt("Database Port", "3306");
$dbName = prompt("Database Name", "tct_yms");
$dbUser = prompt("Database Username", "root");
$dbPass = prompt("Database Password", "", true);

echo "\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Application Configuration\n";
echo "─────────────────────────────────────────────────────────────────\n";

$appUrl = prompt("Application URL", "http://localhost");
$siteName = prompt("Yard/Site Name", "TCT Yard Management System");

echo "\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Admin User Configuration\n";
echo "─────────────────────────────────────────────────────────────────\n";

$adminUsername = prompt("Admin Username", "admin");
$adminEmail = prompt("Admin Email", "admin@example.com");
$adminPassword = prompt("Admin Password", "", true);

if (strlen($adminPassword) < 8) {
    die("Error: Admin password must be at least 8 characters.\n");
}

$adminFirstName = prompt("Admin First Name", "System");
$adminLastName = prompt("Admin Last Name", "Administrator");

// Generate APP_KEY
$appKey = bin2hex(random_bytes(32));

echo "\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Configuring Application...\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Update .env file
$envContent = file_get_contents($envFile);
$envContent = updateEnvValue($envContent, 'APP_NAME', $siteName);
$envContent = updateEnvValue($envContent, 'APP_URL', $appUrl);
$envContent = updateEnvValue($envContent, 'APP_KEY', $appKey);
$envContent = updateEnvValue($envContent, 'DB_HOST', $dbHost);
$envContent = updateEnvValue($envContent, 'DB_PORT', $dbPort);
$envContent = updateEnvValue($envContent, 'DB_DATABASE', $dbName);
$envContent = updateEnvValue($envContent, 'DB_USERNAME', $dbUser);
$envContent = updateEnvValue($envContent, 'DB_PASSWORD', $dbPass);

file_put_contents($envFile, $envContent);
echo "✓ Updated .env configuration\n";

// Test database connection
echo "Testing database connection...\n";

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    die("Error: Could not connect to database: " . $e->getMessage() . "\n");
}

// Create database if not exists
echo "Creating database if not exists...\n";
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$dbName}`");
echo "✓ Database '{$dbName}' ready\n";

// Run schema
echo "Creating database tables...\n";
$schemaFile = $basePath . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    die("Error: Schema file not found at {$schemaFile}\n");
}

$schema = file_get_contents($schemaFile);
$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    fn($s) => !empty($s) && !str_starts_with($s, '--') && !str_starts_with($s, 'SET')
);

foreach ($statements as $statement) {
    if (!empty(trim($statement))) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), '1050') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
}
echo "✓ Database schema created\n";

// Run seeders
echo "Seeding initial data...\n";
$seedFile = $basePath . '/database/seeders/seed.sql';
if (file_exists($seedFile)) {
    $seedSql = file_get_contents($seedFile);
    $statements = array_filter(
        array_map('trim', explode(';', $seedSql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--') && !str_starts_with($s, 'SET')
    );

    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore duplicate entry errors
                if (strpos($e->getMessage(), '1062') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}
echo "✓ Initial data seeded\n";

// Create admin user
echo "Creating admin user...\n";

// Get admin role ID
$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
$stmt->execute();
$adminRole = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminRole) {
    die("Error: Admin role not found. Please ensure seeders ran correctly.\n");
}

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$adminUsername, $adminEmail]);
$existingUser = $stmt->fetch();

$passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

if ($existingUser) {
    // Update existing user
    $stmt = $pdo->prepare("UPDATE users SET password = ?, role_id = ?, is_active = 1 WHERE id = ?");
    $stmt->execute([$passwordHash, $adminRole['id'], $existingUser['id']]);
    echo "✓ Admin user updated\n";
} else {
    // Create new user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt->execute([$adminUsername, $adminEmail, $passwordHash, $adminFirstName, $adminLastName, $adminRole['id']]);
    echo "✓ Admin user created\n";
}

// Update site name in settings
$stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'site_name'");
$stmt->execute([$siteName]);

// Create storage directories
echo "Creating storage directories...\n";
$directories = [
    $basePath . '/storage/logs',
    $basePath . '/storage/cache',
    $basePath . '/storage/sessions',
    $basePath . '/storage/backups',
    $basePath . '/public/uploads',
    $basePath . '/public/uploads/gate',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
echo "✓ Storage directories created\n";

// Set permissions
if (PHP_OS_FAMILY !== 'Windows') {
    chmod($basePath . '/storage', 0755);
    chmod($basePath . '/public/uploads', 0755);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                  Installation Complete!                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "You can now access your YMS at: {$appUrl}\n";
echo "\n";
echo "Login credentials:\n";
echo "  Username: {$adminUsername}\n";
echo "  Email: {$adminEmail}\n";
echo "  Password: [the password you entered]\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Configure your web server to point to the 'public' directory\n";
echo "  2. Set up SSL certificate (recommended for production)\n";
echo "  3. Configure email settings in .env for password reset\n";
echo "  4. Review and customize settings in the admin panel\n";
echo "\n";
echo "For support, visit: https://github.com/your-repo/tct-yms\n";
echo "\n";

// Helper functions
function prompt(string $question, string $default = '', bool $hidden = false): string
{
    $defaultStr = $default ? " [{$default}]" : '';
    echo "{$question}{$defaultStr}: ";

    if ($hidden && PHP_OS_FAMILY !== 'Windows') {
        system('stty -echo');
        $input = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $input = trim(fgets(STDIN));
    }

    return $input ?: $default;
}

function updateEnvValue(string $content, string $key, string $value): string
{
    $pattern = "/^{$key}=.*/m";
    $replacement = "{$key}=" . (str_contains($value, ' ') ? "\"{$value}\"" : $value);

    if (preg_match($pattern, $content)) {
        return preg_replace($pattern, $replacement, $content);
    }

    return $content . "\n{$replacement}";
}
