<?php
session_start();
require_once '../../models/farmer.php';
require_once '../../models/farmerfield.php';
require_once '../../models/log.php';

// Check organization head authentication
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    $logModel = new Log();
    $logModel->logActivity(null, "Unauthorized access attempt to farmer field management by organization head");
    header("Location: organization-head-login.php");
    exit();
}

// Get organization head's user ID from session
$organization_head_user_id = isset($_SESSION['organization_head_user_id']) ? (int)$_SESSION['organization_head_user_id'] : null;
if (!$organization_head_user_id) {
    // Handle case where organization head ID is not in session
    session_unset();
    session_destroy();
    header("Location: organization-head-login.php");
    exit();
}

// Make sure we have a user_id in the session for AJAX requests to use
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $organization_head_user_id;
}

// Get farmer ID from URL if provided
$farmerId = isset($_GET['farmer_id']) ? $_GET['farmer_id'] : null;

// Create instances of required models
$farmerModel = new Farmer();
$farmerFieldModel = new FarmerField();

// Get farmer details if ID is provided
$farmerDetails = null;
if($farmerId) {
    // Use getUserDetails to get basic user information
    $userDetails = $farmerModel->getUserDetails($farmerId);
    
    // Use getFarmerDetails to get farmer-specific details
    $farmerSpecificDetails = $farmerModel->getFarmerDetails($farmerId);
    
    // Merge the user details and farmer details
    $farmerDetails = array_merge($userDetails ?: [], $farmerSpecificDetails ?: []);
    
    if(empty($userDetails)) {
        // Invalid farmer ID
        header("Location: organization-head-farmers.php");
        exit;
    }
}

// Load all farmers for dropdown
$farmers = $farmerModel->getAllFarmers();

// Get all barangays for dropdown
$barangays = $farmerModel->getAllBarangays();

// Page title
$pageTitle = $farmerDetails ? "Manage Fields for {$farmerDetails['first_name']} {$farmerDetails['last_name']}" : "Farmer Field Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Organization Head Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <style>
        /* Add organization head header styling */
        .organization-header {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            padding: 10px 0;
        }
        .organization-badge {
            background-color: #157347;
            color: white;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }
        
        .dashboard-card {
            transition: transform 0.3s ease;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            background-color: #fff;
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        /* Fix for action buttons alignment */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .action-buttons .btn {
            margin-bottom: 5px;
            flex: 0 0 auto;
            min-width: 38px;
        }
        
        /* Responsive adjustments for small screens */
        @media (max-width: 767.98px) {
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
        
        /* Fix for table button groups */
        .btn-group {
            display: flex;
            flex-direction: row;
            gap: 5px;
        }
        
        .btn-group .btn {
            flex: 0 0 auto;
            white-space: nowrap;
            margin: 0;
        }
        
        /* Card design improvements */
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: none;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 0.75rem 1rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .farmer-info-icon {
            font-size: 1.25rem;
            color: #198754;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        
        /* Table styling improvements */
        .table thead th {
            border-bottom: 2px solid #198754;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-geo-alt-fill"></i> FARMER FIELD MANAGEMENT <span class="organization-badge">Organization Head Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Add Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white custom-card">
                        <li class="breadcrumb-item"><a href="organization-head-dashboard.php">Dashboard</a></li>
                        <?php if($farmerId): ?>
                        <li class="breadcrumb-item"><a href="organization-head-farmers.php">Farmers</a></li>
                        <li class="breadcrumb-item active">Fields for <?php echo $farmerDetails['first_name'] . ' ' . $farmerDetails['last_name']; ?></li>
                        <?php else: ?>
                        <li class="breadcrumb-item active">Farmer Field Management</li>
                        <?php endif; ?>
                    </ol>
                </nav>
                
                <!-- Enhanced Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-1">
                            <?php if($farmerId): ?>
                                Manage Fields for <?php echo $farmerDetails['first_name'] . ' ' . $farmerDetails['last_name']; ?>
                            <?php else: ?>
                                Farmer Field Management
                            <?php endif; ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <?php if($farmerId): ?>
                                View and manage field information for this farmer
                            <?php else: ?>
                                Select a farmer to manage their fields and crop production
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <?php if($farmerId): ?>
                        <a href="organization-head-farmers.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Farmers
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Farmer Selection (when no farmer is selected) -->
                <?php if(!$farmerId): ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-friends me-1"></i>
                                Select a Farmer
                            </div>
                            <div class="card-body">
                                <form id="farmerSelectForm" method="get">
                                    <div class="mb-3">
                                        <label for="farmer_id" class="form-label">Select Farmer:</label>
                                        <select class="form-select form-control" id="farmer_id" name="farmer_id" required>
                                            <option value="" selected disabled>-- Select Farmer --</option>
                                            <?php foreach($farmers as $farmer): ?>
                                            <option value="<?php echo $farmer['user_id']; ?>"><?php echo $farmer['first_name'] . ' ' . $farmer['last_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Manage Fields</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Fields Management (when a farmer is selected) -->
                <?php if($farmerId): ?>
                <div class="row mb-4">
                    <!-- Fields List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-map-marked-alt me-1"></i>
                                    Farm Fields
                                </div>
                                <button type="button" class="btn btn-sm btn-success" id="addFieldBtn">
                                    <i class="fas fa-plus"></i> Add Field
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="fieldsTable">
                                        <thead>
                                            <tr>
                                                <th>Field Name</th>
                                                <th>Barangay</th>
                                                <th>Size</th>
                                                <th>Type</th>
                                                <th>Crops</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fieldsTableBody">
                                            <!-- Fields will be loaded here via AJAX -->
                                            <tr>
                                                <td colspan="6" class="text-center">Loading fields...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- No fields message -->
                                <div id="noFieldsMessage" class="alert alert-info text-center" style="display: none;">
                                    <i class="bi bi-info-circle mr-2"></i>
                                    No fields have been added yet for this farmer.
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-success" id="noFieldsAddBtn">
                                            <i class="fas fa-plus"></i> Add First Field
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Farmer Details -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user me-1"></i>
                                Farmer Details
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $farmerDetails['first_name'] . ' ' . $farmerDetails['last_name']; ?></h5>
                                <div class="farmer-details mt-3">
                                    <p class="mb-2">
                                        <i class="bi bi-telephone farmer-info-icon"></i>
                                        <strong>Contact:</strong> <?php echo $farmerDetails['contact_number'] ?: 'N/A'; ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-envelope farmer-info-icon"></i>
                                        <strong>Email:</strong> <?php echo isset($farmerDetails['email']) ? $farmerDetails['email'] : 'N/A'; ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-geo-alt farmer-info-icon"></i>
                                        <strong>Primary Barangay:</strong> <?php echo $farmerDetails['barangay_name'] ?: 'N/A'; ?>
                                    </p>
                                    <?php if(!empty($farmerDetails['farm_name'])): ?>
                                    <p class="mb-2">
                                        <i class="bi bi-house farmer-info-icon"></i>
                                        <strong>Farm Name:</strong> <?php echo $farmerDetails['farm_name']; ?>
                                    </p>
                                    <?php endif; ?>
                                    <?php if(!empty($farmerDetails['farm_type'])): ?>
                                    <p class="mb-2">
                                        <i class="bi bi-flower1 farmer-info-icon"></i>
                                        <strong>Farm Type:</strong> <?php echo $farmerDetails['farm_type']; ?>
                                    </p>
                                    <?php endif; ?>
                                    <?php if(!empty($farmerDetails['farm_size'])): ?>
                                    <p class="mb-2">
                                        <i class="bi bi-rulers farmer-info-icon"></i>
                                        <strong>Total Farm Size:</strong> <?php echo $farmerDetails['farm_size']; ?> hectares
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="organization-head-farmers.php" class="btn btn-outline-secondary btn-block">
                                        <i class="fas fa-arrow-left"></i> Back to Farmers List
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seasonal Information Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Seasonal Information
                            </div>
                            <div class="card-body">
                                <div id="seasonInfo">
                                    <!-- This will be populated with AJAX -->
                                    <p class="text-center text-muted">
                                        <i class="fas fa-sync fa-spin"></i> Loading seasonal information...
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Products (shown when a field is selected) -->
                <div class="row mb-4" id="fieldProductsSection" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-seedling me-1"></i>
                                    <span id="fieldProductsTitle">Products in Field</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="closeFieldProductsBtn">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="fieldProductsTable">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Season</th>
                                                <th>Estimated Production</th>
                                                <th>Production Unit</th>
                                                <th>Planted Area</th>
                                                <th>Area Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fieldProductsTableBody">
                                            <!-- Products will be loaded here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- No products message -->
                                <div id="noProductsMessage" class="alert alert-info text-center" style="display: none;">
                                    <i class="fas fa-info-circle"></i> No products have been assigned to this field yet.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add/Edit Field Modal -->
    <div class="modal fade" id="fieldModal" tabindex="-1" aria-labelledby="fieldModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fieldModalLabel">Add New Field</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="fieldForm">
                        <input type="hidden" id="field_id" name="field_id">
                        <input type="hidden" id="action" name="action" value="create">
                        <input type="hidden" id="farmer_id" name="farmer_id" value="<?php echo $farmerId; ?>">

                        <div class="mb-3">
                            <label for="field_name" class="form-label">Field Name*</label>
                            <input type="text" class="form-control" id="field_name" name="field_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="barangay_id" class="form-label">Barangay*</label>
                            <select class="form-control" id="barangay_id" name="barangay_id" required>
                                <option value="" selected disabled>-- Select Barangay --</option>
                                <?php foreach($barangays as $barangay): ?>
                                <option value="<?php echo $barangay['barangay_id']; ?>"><?php echo $barangay['barangay_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="field_size" class="form-label">Field Size (hectares)</label>
                            <input type="number" class="form-control" id="field_size" name="field_size" step="0.01" min="0">
                        </div>

                        <div class="mb-3">
                            <label for="field_type" class="form-label">Field Type</label>
                            <select class="form-control" id="field_type" name="field_type">
                                <option value="">-- Select Type --</option>
                                <option value="Vegetable Farm">Vegetable Farm</option>
                                <option value="Rice Field">Rice Field</option>
                                <option value="Fruit Orchard">Fruit Orchard</option>
                                <option value="Root Crop Farm">Root Crop Farm</option>
                                <option value="Mixed Crop">Mixed Crop</option>
                                <option value="Herb Garden">Herb Garden</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="coordinates" class="form-label">Coordinates (optional)</label>
                            <input type="text" class="form-control" id="coordinates" name="coordinates" 
                                placeholder="e.g. 9.2639,123.3055">
                            <small class="text-muted">Format: latitude,longitude</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveFieldBtn">Save Field</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this field? This will remove all crop associations with this field.</p>
                    <p><strong>Field:</strong> <span id="deleteFieldName"></span></p>
                    <input type="hidden" id="deleteFieldId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Field</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // If a farmer is selected, load their fields
        <?php if($farmerId): ?>
            loadFarmerFields(<?php echo $farmerId; ?>);
            loadSeasonalInfo(<?php echo $farmerId; ?>);
        <?php endif; ?>
        
        // Open add field modal
        $('#addFieldBtn, #noFieldsAddBtn').click(function() {
            $('#fieldModalLabel').text('Add New Field');
            $('#fieldForm')[0].reset();
            $('#action').val('create');
            $('#field_id').val('');
            $('#fieldModal').modal('show');
        });
        
        // Save field (create or update)
        $('#saveFieldBtn').click(function() {
            const form = $('#fieldForm');
            
            // Basic validation
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }
            
            // Collect form data
            const formData = new FormData(form[0]);
            
            // Send AJAX request
            $.ajax({
                url: '../../ajax/farmer-fields.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Display success message
                        alert(response.message);
                        
                        // Close modal
                        $('#fieldModal').modal('hide');
                        
                        // Reload fields
                        loadFarmerFields(<?php echo $farmerId ?: 0; ?>);
                    } else {
                        // Display error message
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request.');
                }
            });
        });
        
        // Handle edit field button clicks
        $(document).on('click', '.edit-field-btn', function() {
            const fieldId = $(this).data('field-id');
            
            // Get field details via AJAX
            $.ajax({
                url: '../../ajax/farmer-fields.php',
                type: 'GET',
                data: {
                    action: 'get-details',
                    field_id: fieldId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const field = response.data;
                        
                        // Populate form
                        $('#fieldModalLabel').text('Edit Field');
                        $('#field_id').val(field.field_id);
                        $('#action').val('update');
                        $('#field_name').val(field.field_name);
                        $('#barangay_id').val(field.barangay_id);
                        $('#field_size').val(field.field_size);
                        $('#field_type').val(field.field_type);
                        $('#notes').val(field.notes);
                        $('#coordinates').val(field.coordinates);
                        
                        // Show modal
                        $('#fieldModal').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching field details.');
                }
            });
        });
        
        // Handle delete field button clicks
        $(document).on('click', '.delete-field-btn', function() {
            const fieldId = $(this).data('field-id');
            const fieldName = $(this).data('field-name');
            
            // Populate delete confirmation modal
            $('#deleteFieldId').val(fieldId);
            $('#deleteFieldName').text(fieldName);
            
            // Show delete confirmation modal
            $('#deleteConfirmModal').modal('show');
        });
        
        // Handle delete confirmation
        $('#confirmDeleteBtn').click(function() {
            const fieldId = $('#deleteFieldId').val();
            
            // Send delete request via AJAX
            $.ajax({
                url: '../../ajax/farmer-fields.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    field_id: fieldId,
                    farmer_id: <?php echo $farmerId ?: 0; ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Display success message
                        alert(response.message);
                        
                        // Close modal
                        $('#deleteConfirmModal').modal('hide');
                        
                        // Reload fields
                        loadFarmerFields(<?php echo $farmerId ?: 0; ?>);
                        
                        // Hide field products section if open
                        $('#fieldProductsSection').hide();
                    } else {
                        // Display error message
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the field.');
                }
            });
        });
        
        // Handle view products button clicks
        $(document).on('click', '.view-products-btn', function() {
            const fieldId = $(this).data('field-id');
            const fieldName = $(this).data('field-name');
            
            // Set field products title
            $('#fieldProductsTitle').text('Products in Field: ' + fieldName);
            
            // Load field products
            loadFieldProducts(fieldId);
            
            // Show field products section
            $('#fieldProductsSection').show();
            
            // Scroll to field products section
            $('html, body').animate({
                scrollTop: $('#fieldProductsSection').offset().top - 20
            }, 500);
        });
        
        // Close field products section
        $('#closeFieldProductsBtn').click(function() {
            $('#fieldProductsSection').hide();
        });
    });

    // Function to load farmer fields
    function loadFarmerFields(farmerId) {
        $.ajax({
            url: '../../ajax/farmer-fields.php',
            type: 'GET',
            data: {
                action: 'list',
                farmer_id: farmerId
            },
            dataType: 'json',
            success: function(response) {
                console.log("Fields response:", response); // Debug log
                
                const tableBody = $('#fieldsTableBody');
                tableBody.empty(); // Always clear first
                
                if (response.success) {
                    const fields = response.data || [];
                    
                    if (fields && fields.length > 0) {
                        // Hide no fields message
                        $('#noFieldsMessage').hide();
                        
                        // Populate table
                        fields.forEach(function(field) {
                            const fieldSize = field.field_size ? parseFloat(field.field_size).toFixed(2) + ' ha' : 'N/A';
                            const cropCount = field.crop_count || 0;
                            
                            tableBody.append(`
                                <tr>
                                    <td>${field.field_name || ''}</td>
                                    <td>${field.barangay_name || 'N/A'}</td>
                                    <td>${fieldSize}</td>
                                    <td>${field.field_type || 'N/A'}</td>
                                    <td><span class="badge badge-info">${cropCount} crops</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info view-products-btn" 
                                                    data-field-id="${field.field_id}" 
                                                    data-field-name="${field.field_name}">
                                                <i class="fas fa-seedling"></i> View Crops
                                            </button>
                                            <button class="btn btn-sm btn-primary edit-field-btn" 
                                                    data-field-id="${field.field_id}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-field-btn" 
                                                    data-field-id="${field.field_id}" 
                                                    data-field-name="${field.field_name}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        // Show no fields message if the array is empty
                        $('#noFieldsMessage').show();
                    }
                } else {
                    // Show error message and no fields message
                    $('#noFieldsMessage').show();
                    console.error("Error loading fields:", response.message);
                    // Optionally show user-facing error
                    // alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                // Clear loading message
                const tableBody = $('#fieldsTableBody');
                tableBody.empty();
                
                // Show no fields message with error context
                $('#noFieldsMessage').show();
                
                // Log detailed error for debugging
                console.error("AJAX request failed:", status, error);
                console.log("Response:", xhr.responseText);
            }
        });
    }

    // Function to load field products
    function loadFieldProducts(fieldId) {
        $.ajax({
            url: '../../ajax/farmer-fields.php',
            type: 'GET',
            data: {
                action: 'get-products',
                field_id: fieldId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const products = response.data;
                    const tableBody = $('#fieldProductsTableBody');
                    
                    // Clear table body
                    tableBody.empty();
                    
                    if (products.length > 0) {
                        // Hide no products message
                        $('#noProductsMessage').hide();
                        
                        // Populate table
                        products.forEach(function(product) {
                            const estimatedProduction = parseFloat(product.estimated_production).toFixed(2);
                            const plantedArea = parseFloat(product.planted_area).toFixed(2);
                            
                            tableBody.append(`
                                <tr>
                                    <td>${product.product_name}</td>
                                    <td>${product.season_name || 'N/A'}</td>
                                    <td>${estimatedProduction}</td>
                                    <td>${product.production_unit || 'kilogram'}</td>
                                    <td>${plantedArea}</td>
                                    <td>${product.area_unit || 'hectare'}</td>
                                </tr>
                            `);
                        });
                    } else {
                        // Show no products message
                        $('#noProductsMessage').show();
                    }
                } else {
                    // Display error message
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while fetching field products.');
            }
        });
    }
    
    // Function to load seasonal information
    function loadSeasonalInfo(farmerId) {
        // This is a simplified placeholder. In a real application, you would call an API
        // to get current seasonal information for this farmer.
        
        // Get current date
        const currentDate = new Date();
        const month = currentDate.toLocaleString('default', { month: 'long' });
        
        // Set seasonal information
        $('#seasonInfo').html(`
            <div class="seasonal-info">
                <p class="mb-2">
                    <i class="fas fa-calendar text-success"></i>
                    <strong>Current Month:</strong> ${month}
                </p>
                <p class="mb-2">
                    <i class="fas fa-seedling text-success"></i>
                    <strong>Planting Season:</strong> 
                    ${(currentDate.getMonth() >= 4 && currentDate.getMonth() <= 9) ? 'Wet Season' : 'Dry Season'}
                </p>
                <p>
                    <i class="fas fa-sun text-warning"></i>
                    <strong>Recommended Focus:</strong>
                    <span class="badge badge-pill badge-success">
                        ${(currentDate.getMonth() >= 4 && currentDate.getMonth() <= 9) ? 
                        'Rice Cultivation' : 'Vegetable Production'}
                    </span>
                </p>
            </div>
        `);
    }
    </script>
</body>
</html>