<?php
require_once '../../models/Farmer.php'; // Include the Farmer model

class FarmerController {
    private $farmer;

    // Constructor to initialize the Farmer model
    public function __construct() {
        $this->farmer = new Farmer();
    }

    /**
     * Get farmer details by farmer_id
     *
     * @param int $farmer_id
     * @return array
     */
    public function getFarmerDetails($farmer_id) {
        // Fetch both farmer and user details
        $farmerDetails = $this->farmer->getFarmerDetails($farmer_id);
        $userDetails = $this->farmer->getUserDetails($farmer_id);

        // Return details as an associative array
        return [
            'farmerDetails' => $farmerDetails,
            'userDetails' => $userDetails
        ];
    }

    /**
     * Update farmer's personal details
     *
     * @param int $farmer_id
     * @param string $firstName
     * @param string $lastName
     * @param string $contactNumber
     * @param string $address
     * @return bool
     */
    public function updatePersonalDetails($farmer_id, $firstName, $lastName, $contactNumber, $address) {
        return $this->farmer->updateUserDetails($farmer_id, $firstName, $lastName, $contactNumber, $address);
    }

    /**
     * Update farm-related details for the farmer
     *
     * @param int $farmer_id
     * @param string $farmName
     * @param string $farmLocation
     * @param string $farmType
     * @param string $certifications
     * @param string $cropVarieties
     * @param string $machineryUsed
     * @param float $farmSize
     * @param float $income
     * @return bool
     */
    public function updateFarmDetails($farmer_id, $farmName, $farmLocation, $farmType, $certifications, $cropVarieties, $machineryUsed, $farmSize, $income) {
        // Update multiple fields for farm details
        $updateSuccess = true;
        $fields = [
            'farm_name' => $farmName,
            'farm_location' => $farmLocation,
            'farm_type' => $farmType,
            'certifications' => $certifications,
            'crop_varieties' => $cropVarieties,
            'machinery_used' => $machineryUsed,
            'farm_size' => $farmSize,
            'income' => $income
        ];

        foreach ($fields as $field => $value) {
            $updateSuccess &= $this->farmer->updateFarmerAdditionalDetails($farmer_id, $field, $value);
        }

        // Return success status
        return $updateSuccess;
    }

    /**
     * Add a new product for the farmer
     *
     * @param int $farmer_id
     * @param string $productName
     * @param string $description
     * @param float $price
     * @param string $status
     * @param array $image
     * @return array
     */
    public function addProduct($farmer_id, $productName, $description, $price, $status, $image) {
        // Handle image upload and validation
        $imageResult = $this->handleImageUpload($image);
        if (isset($imageResult['error'])) {
            return ['error' => $imageResult['error']]; // Return image error if any
        }

        // Call model method to add the product with image path
        $product = $this->farmer->addProduct($farmer_id, $productName, $description, $price, $status, $imageResult['success']);

        return $product ? [
            'id' => $product['product_id'],
            'name' => $productName,
            'description' => $description,
            'price' => $price,
            'status' => $status,
            'image' => $imageResult['success']
        ] : ['error' => 'Error adding product to the database.'];
    }

    /**
     * Delete a product by its ID
     *
     * @param int $productId
     * @return bool
     */
    public function deleteProduct($productId) {
        return $this->farmer->deleteProduct($productId);
    }

    /**
     * Update a product's details, including image if provided
     *
     * @param int $productId
     * @param string $productName
     * @param string $description
     * @param float $price
     * @param string $status
     * @param array|null $image
     * @return array
     */
    public function updateProduct($productId, $productName, $description, $price, $status, $image = null) {
        // If image is provided, handle the upload and validation
        if ($image && $image['error'] === UPLOAD_ERR_OK) {
            $imageResult = $this->handleImageUpload($image);
            if (isset($imageResult['error'])) {
                return ['error' => $imageResult['error']]; // Return image error if any
            }
            return $this->farmer->updateProduct($productId, $productName, $description, $price, $status, $imageResult['success']);
        } else {
            // If no image is provided, just update the product
            return $this->farmer->updateProduct($productId, $productName, $description, $price, $status);
        }
    }

    /**
     * Handle farmer logout
     */
    public function farmerLogout() {
        session_unset(); // Unset session variables
        session_destroy(); // Destroy the session
        header("Location: farmer-login.php"); // Redirect to login page
        exit();
    }

    /**
     * Helper function to handle image upload and validation
     *
     * @param array $image
     * @return array
     */
    private function handleImageUpload($image) {
        $upload_dir = '../../public/uploads/';

        // Ensure the upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Create the directory if it doesn't exist
        }

        $image_name = basename($image['name']);
        $upload_path = $upload_dir . $image_name;

        // Validate file type
        $fileType = strtolower(pathinfo($upload_path, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileType, $allowedTypes)) {
            return ["error" => "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed."];
        }

        // Validate file size (max 2MB)
        if ($image['size'] > 2097152) { // 2MB limit
            return ["error" => "File size exceeds 2MB."];
        }

        // Move the uploaded file to the desired folder
        if (move_uploaded_file($image['tmp_name'], $upload_path)) {
            return ["success" => 'uploads/' . $image_name]; // Return the image path
        } else {
            return ["error" => "Failed to upload image. Please try again."];
        }
    }
}
