<?php
require_once 'includes/config.php';

try {
    // Create images directory with proper permissions
    $imagesPath = __DIR__ . '/images';
    if (!file_exists($imagesPath)) {
        if (!mkdir($imagesPath, 0777, true)) {
            throw new Exception("Failed to create images directory");
        }
        chmod($imagesPath, 0777);
    }

    // Create default avatar
    $avatarPath = $imagesPath . '/default_avatar.png';
    if (!file_exists($avatarPath)) {
        // Check if GD library is available
        if (!extension_loaded('gd')) {
            throw new Exception("GD library is not installed");
        }

        // Create image
        $image = imagecreatetruecolor(200, 200);
        if (!$image) {
            throw new Exception("Failed to create image");
        }

        // Set colors
        $bg = imagecolorallocate($image, 240, 240, 240);
        $circle = imagecolorallocate($image, 200, 200, 200);

        // Draw image
        imagefill($image, 0, 0, $bg);
        imagefilledellipse($image, 100, 80, 120, 120, $circle);
        imagefilledrectangle($image, 60, 140, 140, 200, $circle);

        // Save image
        if (!imagepng($image, $avatarPath)) {
            throw new Exception("Failed to save default avatar");
        }
        
        // Set proper permissions
        chmod($avatarPath, 0644);
        
        imagedestroy($image);
    }

    // Initialize database
    $conn = Database::getInstance();

    // Create necessary tables
    require_once 'includes/update_database.php';

    echo "Setup completed successfully!";
    
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
} 