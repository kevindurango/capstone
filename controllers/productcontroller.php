<?php
require_once '../../models/Product.php'; // Include the Product model

class ProductController
{
    private $product;

    public function __construct()
    {
        $this->product = new Product(); // Create a new Product instance
    }

    // Get all products, including farmer details
    public function getAllProducts()
    {
        return $this->product->getAllProductsWithFarmers(); // Fetch products with farmer details
    }

    // Get all farmers (directly from the Product model)
    public function getAllFarmers()
    {
        return $this->product->getAllFarmers(); // Fetch all farmers from the Product model
    }

    // Add a new product
    public function addProduct($name, $description, $price, $image, $farmer_id)
    {
        // Validate and handle image upload
        $image_path = null;
        if ($image && isset($image['tmp_name']) && $image['tmp_name']) {
            $image_path = $this->handleImageUpload($image);
            if (!$image_path) {
                return false; // Stop execution on image upload failure
            }
        }

        // Add product via the Product model
        return $this->product->addProduct($farmer_id, $name, $description, $price, $image_path);
    }

    public function editProduct($id, $name, $description, $price, $image, $current_image)
    {
        // Retain current image if no new image is uploaded
        $image_path = $current_image;
        if ($image && isset($image['tmp_name']) && $image['tmp_name']) {
            $uploaded_image = $this->handleImageUpload($image);
            if ($uploaded_image) {
                $image_path = $uploaded_image;
            } else {
                return false; // Stop execution on image upload failure
            }
        }
    
        // Update product via the Product model (make sure to add the status field)
        return $this->product->updateProduct($id, $name, $description, $price, $image_path, 'pending'); // Add status argument
    }
    

    // Delete a product
    public function deleteProduct($id)
    {
        return $this->product->deleteProduct($id); // Delete product via the Product model
    }

    // Handle image upload securely
    private function handleImageUpload($image)
    {
        $target_dir = "../../uploads/"; // Ensure uploads directory exists and is writable
        $image_name = basename($image["name"]);
        $target_file = $target_dir . uniqid() . "_" . $image_name;

        // Validate image file
        $check = getimagesize($image["tmp_name"]);
        if ($check === false) {
            echo "File is not a valid image.";
            return false;
        }

        // Validate file size (e.g., max 2MB)
        if ($image["size"] > 2 * 1024 * 1024) {
            echo "File size exceeds the maximum limit of 2MB.";
            return false;
        }

        // Allow only certain file types (e.g., JPG, PNG, GIF)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
            return false;
        }

        // Move the uploaded file to the target directory
        if (!move_uploaded_file($image["tmp_name"], $target_file)) {
            echo "Sorry, there was an error uploading your file.";
            return false;
        }

        return basename($target_file); // Return the image name for database storage
    }

    // Close the product model connection (optional)
    public function close()
    {
        $this->product->close();
    }
}
