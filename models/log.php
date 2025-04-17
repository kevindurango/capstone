<?php

require_once 'Database.php';

class Log
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    public function getActivityLogs(int $offset = 0, int $limit = 10): array
    {
        try {
            $offset = max(0, $offset);
            $limit  = max(1, min(100, $limit)); // Set a reasonable max limit

            $sql = "SELECT logs.log_id, users.username, logs.action, logs.action_date
                    FROM activitylogs AS logs
                    JOIN users ON logs.user_id = users.user_id
                    ORDER BY logs.action_date DESC
                    LIMIT :limit OFFSET :offset";  // Use named parameters

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching logs: " . $e->getMessage());  // Log the error
            return [];
        }
    }

    public function getTotalLogsCount(): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM activitylogs";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error fetching total log count: " . $e->getMessage()); // Log the error
            return 0;
        }
    }

    public function searchActivityLogs(string $searchTerm, int $offset = 0, int $limit = 10): array
    {
        try {
            $offset = max(0, $offset);
            $limit  = max(1, min(100, $limit)); // Set a reasonable max limit

            $sql = "SELECT logs.log_id, users.username, logs.action, logs.action_date
                    FROM activitylogs AS logs
                    JOIN users ON logs.user_id = users.user_id
                    WHERE users.username LIKE :search OR logs.action LIKE :search
                    ORDER BY logs.action_date DESC
                    LIMIT :limit OFFSET :offset"; // Use named parameters

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':search', "%$searchTerm%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching logs: " . $e->getMessage()); // Log the error
            return [];
        }
    }

    public function getSearchLogsCount(string $searchTerm): int
    {
        try {
            $sql = "SELECT COUNT(*) as total
                    FROM activitylogs AS logs
                    JOIN users ON logs.user_id = users.user_id
                    WHERE users.username LIKE :search OR logs.action LIKE :search";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':search', "%$searchTerm%", PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error fetching search result count: " . $e->getMessage()); // Log the error
            return 0;
        }
    }

    /**
     * Retrieves activity logs within a specified date range with optional search filtering.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param string|null $searchTerm Optional search term to filter results
     * @param int $offset Pagination offset
     * @param int $limit Maximum number of records to return
     * @return array List of activity logs within the specified date range
     */
    public function getActivityLogsByDateRange(string $startDate, string $endDate, ?string $searchTerm = null, int $offset = 0, int $limit = 100): array
    {
        try {
            $offset = max(0, $offset);
            $limit = max(1, min(1000, $limit)); // Higher limit for exports

            $params = [
                ':start_date' => $startDate . ' 00:00:00',
                ':end_date' => $endDate . ' 23:59:59',
                ':limit' => $limit,
                ':offset' => $offset
            ];

            $searchCondition = '';
            if ($searchTerm !== null && trim($searchTerm) !== '') {
                $searchCondition = "AND (users.username LIKE :search OR logs.action LIKE :search)";
                $params[':search'] = "%$searchTerm%";
            }

            $sql = "SELECT logs.log_id, users.username, logs.action, logs.action_date
                    FROM activitylogs AS logs
                    LEFT JOIN users ON logs.user_id = users.user_id
                    WHERE logs.action_date BETWEEN :start_date AND :end_date
                    $searchCondition
                    ORDER BY logs.action_date DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                if (in_array($key, [':limit', ':offset'])) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching logs by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gets the total count of logs within a date range with optional search filtering.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param string|null $searchTerm Optional search term to filter results
     * @return int Total number of matching logs
     */
    public function getDateRangeLogsCount(string $startDate, string $endDate, ?string $searchTerm = null): int
    {
        try {
            $params = [
                ':start_date' => $startDate . ' 00:00:00',
                ':end_date' => $endDate . ' 23:59:59'
            ];

            $searchCondition = '';
            if ($searchTerm !== null && trim($searchTerm) !== '') {
                $searchCondition = "AND (users.username LIKE :search OR logs.action LIKE :search)";
                $params[':search'] = "%$searchTerm%";
            }

            $sql = "SELECT COUNT(*) as total
                    FROM activitylogs AS logs
                    LEFT JOIN users ON logs.user_id = users.user_id
                    WHERE logs.action_date BETWEEN :start_date AND :end_date
                    $searchCondition";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error counting logs by date range: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieves filtered logs based on filter type and date range.
     *
     * @param string $filter Filter type (all, users, system, etc.)
     * @param string|null $startDate Start date in YYYY-MM-DD format
     * @param string|null $endDate End date in YYYY-MM-DD format
     * @return array List of filtered logs
     */
    public function getFilteredLogs(string $filter = 'all', ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $params = [];
            $conditions = [];
            
            // Apply date range filter if provided
            if ($startDate && $endDate) {
                $conditions[] = "logs.action_date BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate . ' 00:00:00';
                $params[':end_date'] = $endDate . ' 23:59:59';
            }
            
            // Apply filter based on type
            switch ($filter) {
                case 'users':
                    $conditions[] = "logs.user_id IS NOT NULL";
                    break;
                case 'system':
                    $conditions[] = "logs.user_id IS NULL";
                    break;
                case 'login':
                    $conditions[] = "logs.action LIKE '%login%'";
                    break;
                case 'products':
                    $conditions[] = "logs.action LIKE '%product%'";
                    break;
                case 'orders':
                    $conditions[] = "logs.action LIKE '%order%'";
                    break;
                // Add more filter types as needed
                case 'all':
                default:
                    // No additional conditions for 'all'
                    break;
            }
            
            // Build the WHERE clause
            $whereClause = "";
            if (!empty($conditions)) {
                $whereClause = "WHERE " . implode(" AND ", $conditions);
            }
            
            $sql = "SELECT logs.log_id, users.username, logs.action, logs.action_date
                    FROM activitylogs AS logs
                    LEFT JOIN users ON logs.user_id = users.user_id
                    $whereClause
                    ORDER BY logs.action_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters if any
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching filtered logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Logs an activity to the activitylogs table.
     *
     * @param int|null $userId The ID of the user performing the action or null for system actions.
     * @param string $action The description of the action performed.
     * @param array $context Optional array of contextual information to include in the log.
     * @return bool True on success, false on failure.
     */
    public function logActivity($userId, string $action, array $context = []): bool {
        try {
            // Check if the PDO connection is valid
            if (!$this->pdo) {
                error_log("Error: No database connection in logActivity method.");
                return false;
            }

            // Start building the log message
            $logMessage = $action;

            // Add contextual information to the log message
            if (!empty($context)) {
                // Ensure we have safe JSON encoding with error handling
                $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
                if ($contextJson === false) {
                    $contextJson = json_encode(['error' => 'Failed to encode context']);
                }
                $logMessage .= " Context: " . $contextJson;
            }

            // Insert the activity log with the current timestamp (NOW()).
            if ($userId === null) {
                // For system or unidentified user actions
                $sql = "INSERT INTO activitylogs (user_id, action, action_date)
                        VALUES (NULL, :action, NOW())";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':action', $logMessage, PDO::PARAM_STR);
            } else {
                $sql = "INSERT INTO activitylogs (user_id, action, action_date)
                        VALUES (:user_id, :action, NOW())";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':action', $logMessage, PDO::PARAM_STR);
            }

            $stmt->execute();
            return true; // Successfully added log
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false; // Failed to insert log
        }
    }

}
?>
