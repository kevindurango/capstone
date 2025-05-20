<?php
session_start();
require_once '../../models/Database.php';
require_once '../../models/Cache.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die(json_encode(['error' => 'Unauthorized access']));
}

try {
    $database = new Database();
    $conn = $database->connect();
    $cache = new Cache();

    // Get request parameters
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $search = $_POST['search']['value'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    // Generate cache key based on parameters
    $cacheKey = "crops_data_{$start}_{$length}_{$search}_{$startDate}_{$endDate}";
    
    // Try to get data from cache
    $result = $cache->get($cacheKey);
    
    if ($result === null) {        // Build the base query with additional calculations
        $query = "SELECT 
                    b.barangay_name,
                    p.name AS product_name,
                    SUM(bp.estimated_production) AS total_production,
                    bp.production_unit,
                    SUM(bp.planted_area) AS total_planted_area,
                    bp.area_unit,
                    CASE 
                        WHEN SUM(bp.planted_area) > 0 
                        THEN ROUND(SUM(bp.estimated_production) / SUM(bp.planted_area), 2) 
                        ELSE 0 
                    END AS yield_rate
                FROM barangay_products bp
                JOIN barangays b ON bp.barangay_id = b.barangay_id
                JOIN products p ON bp.product_id = p.product_id";
        
        $conditions = [];
        $params = [];

        // Initialize variables to track maximum values
        $maxFarmingArea = 0;
        $maxYieldRate = 0;
        $maxAreaBarangay = '';
        $maxYieldBarangay = '';

        // Add date filters if provided
        if ($startDate && $endDate) {
            $conditions[] = "bp.planting_date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        // Add search condition
        if ($search) {
            $conditions[] = "(b.barangay_name LIKE :search OR p.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        // Form where clause
        $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

        // Get total count with proper grouping
        $countQuery = "SELECT COUNT(*) FROM (
            SELECT b.barangay_name, p.name
            FROM barangay_products bp
            JOIN barangays b ON bp.barangay_id = b.barangay_id
            JOIN products p ON bp.product_id = p.product_id
            {$whereClause}
            GROUP BY b.barangay_name, p.name
        ) t";

        // Add where clause to main query
        $query .= $whereClause;

        // Add grouping
        $query .= " GROUP BY b.barangay_name, p.name, bp.production_unit, bp.area_unit";

        // Get total count
        $stmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            if ($key !== ':start' && $key !== ':length') { // Skip pagination params for count
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $totalRecords = $stmt->fetchColumn();

        // Add pagination
        $query .= " LIMIT :start, :length";
        $params[':start'] = (int)$start;
        $params[':length'] = (int)$length;

        // Execute the main query
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            if ($key === ':start' || $key === ':length') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ];        // Format the data for DataTables
        $formattedData = [];        $maxFarmingArea = 0;
        $maxYieldRate = 0;
        $maxAreaBarangay = '';
        $maxYieldBarangay = '';

        foreach ($data as $row) {
            // Track maximum farming area and highest yield
            if ($row['total_planted_area'] > $maxFarmingArea) {
                $maxFarmingArea = $row['total_planted_area'];
                $maxAreaBarangay = $row['barangay_name'];
            }
            if ($row['yield_rate'] > $maxYieldRate) {
                $maxYieldRate = $row['yield_rate'];
                $maxYieldBarangay = $row['barangay_name'];
            }

            $formattedData[] = [
                $row['barangay_name'],
                $row['product_name'],
                number_format($row['total_production'], 2) . ' ' . $row['production_unit'],
                number_format($row['total_planted_area'], 2) . ' ' . $row['area_unit'],
                number_format($row['yield_rate'], 2) . ' ' . $row['production_unit'] . '/' . $row['area_unit']
            ];
        }

        // Add statistics to result
        $result['stats'] = [
            'maxFarmingArea' => [
                'barangay' => $maxAreaBarangay,
                'area' => number_format($maxFarmingArea, 2) . ' ' . ($data ? $data[0]['area_unit'] : 'ha')
            ],
            'maxYieldRate' => [
                'barangay' => $maxYieldBarangay,
                'rate' => number_format($maxYieldRate, 2) . ' ' . 
                    ($data ? $data[0]['production_unit'] . '/' . $data[0]['area_unit'] : 'kg/ha')
            ]
        ];

        $result['data'] = $formattedData;

        // Cache the result for 5 minutes
        $cache->set($cacheKey, $result, 300);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error in fetch_crops.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'An error occurred while fetching data',
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
?>
