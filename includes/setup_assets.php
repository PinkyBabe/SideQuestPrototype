<?php
// Create images directory if it doesn't exist
$imagesPath = __DIR__ . '/../images';
if (!file_exists($imagesPath)) {
    mkdir($imagesPath, 0777, true);
}

// Create default avatar if it doesn't exist
$avatarPath = $imagesPath . '/default_avatar.png';
if (!file_exists($avatarPath)) {
    // Create a 200x200 image
    $image = imagecreatetruecolor(200, 200);
    
    // Set background color (light gray)
    $bg = imagecolorallocate($image, 240, 240, 240);
    imagefill($image, 0, 0, $bg);
    
    // Set circle color (darker gray)
    $circle = imagecolorallocate($image, 200, 200, 200);
    
    // Draw a circle for head
    imagefilledellipse($image, 100, 80, 120, 120, $circle);
    
    // Draw body
    imagefilledrectangle($image, 60, 140, 140, 200, $circle);
    
    // Save the image
    imagepng($image, $avatarPath);
    imagedestroy($image);
}

echo "Assets setup completed successfully!"; 