<?php
require_once __DIR__ . '/../models/Product.php';

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
        try {
            $products = $this->product->getAllProductsWithDetails();
            error_log("ProductController: Got " . count($products) . " products from model");
            return $products;
        } catch (Exception $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return [];
        }
    }

    // Get all farmers (directly from the Product model)
    public function getAllFarmers()
    {
        return $this->product->getAllFarmers(); // Fetch all farmers from the Product model
    }

    // Show the add product form
    public function showAddForm()
    {
        // Get all farmers for the dropdown
        $farmers = $this->getAllFarmers();
        
        // Include the add product view
        include_once '../../views/admin/add-product.php';
        exit;
    }
    
    // Show the edit product form
    public function showEditForm($product_id)
    {
        // Get the product details
        $product = $this->product->getProductById($product_id);
        
        // Get all farmers for the dropdown
        $farmers = $this->getAllFarmers();
        
        // Include the edit product view
        include_once '../../views/admin/edit-product.php';
        exit;
    }

    // Add a new product with stock parameter
    public function addProduct($name, $description, $price, $stock, $farmer_id, $image = null)
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
        return $this->product->addProduct($name, $description, $price, $farmer_id, $stock, $image_path);
    }

    // Enhanced edit product with stock parameter
    public function editProduct($id, $name, $description, $price, $stock, $farmer_id, $image, $current_image)
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

        // Update product via the Product model
        return $this->product->updateProduct($id, $name, $description, $price, $stock, $farmer_id, $image_path, 'pending');
    }

    // Assign product to category
    public function assignProductCategory($product_id, $category_id)
    {
        return $this->product->assignProductCategory($product_id, $category_id);
    }

    // Update product category
    public function updateProductCategory($product_id, $category_id)
    {
        return $this->product->updateProductCategory($product_id, $category_id);
    }
    
    // Handle Approve and Reject
    public function updateProductStatus($product_id, $status)
    {
        // Ensure valid status value (approved or rejected)
        if (!in_array($status, ['approved', 'rejected'])) {
            return false;
        }

        return $this->product->updateProductStatus($product_id, $status);
    }

    // Delete a product
    public function deleteProduct($id)
    {
        return $this->product->deleteProduct($id); // Delete product via the Product model
    }

    // Get product by ID
    public function getProductById($id)
    {
        return $this->product->getProductById($id);
    }

    // Handle image upload securely
    private function handleImageUpload($image)
    {
        // Make sure the uploads directory exists
        $target_dir = "../../uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
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
        $file_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
            return false;
        }

        // Move the uploaded file to the target directory
        if (!move_uploaded_file($image["tmp_name"], $target_file)) {
            echo "Sorry, there was an error uploading your file.";
            return false;
        }

        return $target_file; // Return the full path for database storage
    }
    
    // Process the action parameter and call the appropriate method
    public function processRequest()
    {
        // Check if action parameter is set
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            
            switch ($action) {
                case 'showAddForm':
                    $this->showAddForm();
                    break;
                
                case 'showEditForm':
                    if (isset($_GET['id'])) {
                        $this->showEditForm($_GET['id']);
                    } else {
                        echo "Error: Product ID is required";
                    }
                    break;
                
                case 'add':
                    // Process form submission for adding product
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $name = $_POST['name'] ?? '';
                        $description = $_POST['description'] ?? '';
                        $price = $_POST['price'] ?? 0;
                        $farmer_id = $_POST['farmer_id'] ?? null;
                        $stock = $_POST['stock'] ?? 0;
                        $category_id = $_POST['category_id'] ?? null;
                        $image = $_FILES['image'] ?? null;
                        
                        $result = $this->addProduct($name, $description, $price, $stock, $farmer_id, $image);
                        
                        // Return JSON response for AJAX request
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => $result ? true : false,
                            'message' => $result ? 'Product added successfully' : 'Failed to add product'
                        ]);
                        exit;
                    }
                    break;
                
                case 'update':
                    // Process form submission for updating product
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                        $id = $_POST['id'];
                        $name = $_POST['name'] ?? '';
                        $description = $_POST['description'] ?? '';
                        $price = $_POST['price'] ?? 0;
                        $current_image = $_POST['current_image'] ?? '';
                        $image = $_FILES['image'] ?? null;
                        
                        if ($this->editProduct($id, $name, $description, $price, $stock, $farmer_id, $image, $current_image)) {
                            header("Location: ../../views/admin/manage-products.php?success=Product updated successfully");
                        } else {
                            header("Location: ../../views/admin/manage-products.php?error=Failed to update product");
                        }
                        exit;
                    }
                    break;
                
                case 'delete':
                    if (isset($_GET['id'])) {
                        if ($this->deleteProduct($_GET['id'])) {
                            header("Location: ../../views/admin/manage-products.php?success=Product deleted successfully");
                        } else {
                            header("Location: ../../views/admin/manage-products.php?error=Failed to delete product");
                        }
                        exit;
                    }
                    break;
                
                case 'updateStatus':
                    if (isset($_GET['id']) && isset($_GET['status'])) {
                        if ($this->updateProductStatus($_GET['id'], $_GET['status'])) {
                            header("Location: ../../views/admin/manage-products.php?success=Product status updated");
                        } else {
                            header("Location: ../../views/admin/manage-products.php?error=Failed to update status");
                        }
                        exit;
                    }
                    break;
                
                default:
                    echo "Invalid action specified";
                    break;
            }
        } else {
            // Default action - show all products
            header("Location: ../../views/admin/manage-products.php");
            exit;
        }
    }

    // Close the product model connection (optional)
    public function close()
    {
        $this->product->close();
    }

    public function getProductCount() {
        return $this->product->getProductCount();
    }

    public function getProductCountByStatus($status) {
        return $this->product->getProductCountByStatus($status);
    }

    public function getLowStockProducts($threshold = 10) {
        return $this->product->getLowStockProducts($threshold);
    }

    // Add this new method
    public function getAllProductsWithDetails()
    {
        try {
            return $this->product->getAllProductsWithDetails();
        } catch (Exception $e) {
            error_log("Error in getAllProductsWithDetails: " . $e->getMessage());
            return [];
        }
    }
}

// Instantiate the controller and process the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $controller = new ProductController();
    $controller->processRequest();
}
?>
