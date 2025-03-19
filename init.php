<?php
// Ensure required directories exist
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/public/assets'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Add default product image if it doesn't exist
$defaultImagePath = __DIR__ . '/public/assets/default-product.jpg';
if (!file_exists($defaultImagePath)) {
    // Create a simple default image
    $image = imagecreatetruecolor(200, 200);
    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 100, 100, 100);
    imagefill($image, 0, 0, $bgColor);
    imagestring($image, 5, 40, 90, 'No Image Available', $textColor);
    imagejpeg($image, $defaultImagePath);
    imagedestroy($image);
}
