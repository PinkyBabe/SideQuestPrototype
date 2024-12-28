<?php
function createDefaultAvatar() {
    $avatarPath = '../images/default_avatar.png';
    
    // Check if directory exists, if not create it
    if (!file_exists('../images')) {
        mkdir('../images', 0777, true);
    }
    
    // If avatar doesn't exist, create a simple one
    if (!file_exists($avatarPath)) {
        $image = imagecreatetruecolor(200, 200);
        
        // Set background color (light gray)
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bgColor);
        
        // Set foreground color (dark gray)
        $fgColor = imagecolorallocate($image, 180, 180, 180);
        
        // Draw a simple avatar shape
        imagefilledellipse($image, 100, 80, 80, 80, $fgColor); // Head
        imagefilledrectangle($image, 60, 120, 140, 200, $fgColor); // Body
        
        // Save the image
        imagepng($image, $avatarPath);
        imagedestroy($image);
    }
    
    return true;
}

// Create default avatar when this script is run
createDefaultAvatar();
?> 