<?php
// Include the Database class to handle the database connection
require_once 'Database.php';

class Log
{
    // Fetch activity logs with pagination
    public static function getActivityLogs($offset = 0, $limit = 10)
    {
        try {
            // Instantiate the Database class and get the connection
            $database = new Database();
            $conn = $database->connect();

            // SQL query to fetch logs with usernames using JOIN and LIMIT
            $sql = "SELECT logs.log_id, users.username, logs.action, logs.action_date 
                    FROM activitylogs AS logs
                    JOIN users ON logs.user_id = users.user_id
                    ORDER BY logs.action_date DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching logs: " . $e->getMessage();
            return [];
        }
    }

    // Fetch the total count of activity logs
    public static function getTotalLogsCount()
    {
        try {
            $database = new Database();
            $conn = $database->connect();

            // SQL query to count total logs
            $sql = "SELECT COUNT(*) as total FROM activitylogs";
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'];
        } catch (PDOException $e) {
            echo "Error fetching total log count: " . $e->getMessage();
            return 0;
        }
    }

    // Search activity logs by username or action with pagination
    public static function searchActivityLogs($searchTerm, $offset = 0, $limit = 10)
    {
        try {
            $database = new Database();
            $conn = $database->connect();

            // SQL query to search logs by username or action
            $sql = "SELECT logs.log_id, users.username, logs.action, logs.action_date 
                    FROM activitylogs AS logs
                    JOIN users ON logs.user_id = users.user_id
                    WHERE users.username LIKE :search OR logs.action LIKE :search
                    ORDER BY logs.action_date DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':search', "%$searchTerm%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error searching logs: " . $e->getMessage();
            return [];
        }
    }

    // Fetch total count of search results
    public static function getSearchLogsCount($searchTerm)
    {
        try {
            $database = new Database();
            $conn = $database->connect();

            // SQL query to count search results
            $sql = "SELECT COUNT(*) as total 
                    FROM activitylogs AS logs
                    JOIN users ON logs.user_id = users.user_id
                    WHERE users.username LIKE :search OR logs.action LIKE :search";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':search', "%$searchTerm%", PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            echo "Error fetching search result count: " . $e->getMessage();
            return 0;
        }
    }
}
?>
