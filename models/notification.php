<?php
class Notification {
    private $db;
    
    public function __construct() {
        require_once 'Database.php';
        $database = new Database();
        $this->db = $database->connect();
    }
    
    /**
     * Add a new notification with type and reference ID
     * 
     * @param int $user_id User ID to notify
     * @param string $message Notification message
     * @param string|null $type Type of notification (e.g., 'product_approved', 'product_rejected')
     * @param int|null $reference_id ID of the related entity (e.g., product_id)
     * @return bool Whether the operation succeeded
     */
    public function addNotification($user_id, $message, $type = null, $reference_id = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, message, type, reference_id, is_read, created_at) 
                 VALUES (?, ?, ?, ?, 0, NOW())"
            );
            
            return $stmt->execute([$user_id, $message, $type, $reference_id]);
        } catch (PDOException $e) {
            // Log error
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notifications for a user
     * 
     * @param int $user_id User ID
     * @param bool $unread_only Get only unread notifications
     * @param int $limit Maximum number of notifications to retrieve
     * @param string|null $type Filter by notification type
     * @return array Notifications
     */
    public function getNotificationsForUser($user_id, $unread_only = false, $limit = 10, $type = null) {
        try {
            $params = [$user_id];
            $sql = "SELECT * FROM notifications WHERE user_id = ?";
            
            if ($unread_only) {
                $sql .= " AND is_read = 0";
            }
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get notifications by reference ID
     * 
     * @param int $reference_id Reference ID (e.g., product_id)
     * @param string|null $type Notification type
     * @return array Notifications
     */
    public function getNotificationsByReference($reference_id, $type = null) {
        try {
            $params = [$reference_id];
            $sql = "SELECT * FROM notifications WHERE reference_id = ?";
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching notifications by reference: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notification_id Notification ID
     * @return bool Whether the operation succeeded
     */
    public function markAsRead($notification_id) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
            return $stmt->execute([$notification_id]);
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $user_id User ID
     * @param string|null $type Only mark notifications of this type as read
     * @return bool Whether the operation succeeded
     */
    public function markAllAsRead($user_id, $type = null) {
        try {
            $params = [$user_id];
            $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count unread notifications for a user
     * 
     * @param int $user_id User ID
     * @param string|null $type Only count notifications of this type
     * @return int Number of unread notifications
     */
    public function countUnreadNotifications($user_id, $type = null) {
        try {
            $params = [$user_id];
            $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting unread notifications: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete notifications by reference ID
     * 
     * @param int $reference_id Reference ID (e.g., product_id)
     * @param string|null $type Only delete notifications of this type
     * @return bool Whether the operation succeeded
     */
    public function deleteNotificationsByReference($reference_id, $type = null) {
        try {
            $params = [$reference_id];
            $sql = "DELETE FROM notifications WHERE reference_id = ?";
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error deleting notifications by reference: " . $e->getMessage());
            return false;
        }
    }
}