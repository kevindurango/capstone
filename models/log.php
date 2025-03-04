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
     * Logs an activity to the activitylogs table.
     *
     * @param int $userId The ID of the user performing the action.
     * @param string $action The description of the action performed.
     * @param array $context Optional array of contextual information to include in the log (e.g., old status, new status).
     * @return bool True on success, false on failure.
     */
    public function logActivity(int $userId, string $action, array $context = []): bool {
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
                $logMessage .= " Context: " . json_encode($context); // Convert context array to JSON string
            }

            // Insert the activity log with the current timestamp (NOW()).
            $sql = "INSERT INTO activitylogs (user_id, action, action_date)
                    VALUES (:user_id, :action, NOW())"; // NOW() will insert the current timestamp

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':action', $logMessage, PDO::PARAM_STR); // Save the combined log message

            $stmt->execute();

            return true; // Successfully added log
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false; // Failed to insert log
        }
    }
}
?>
