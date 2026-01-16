<?php
require_once 'config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function sanitize_html($html) {
    if (!is_string($html)) {
        return '';
    }
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    // Allow basic formatting + lists
    $allowedTags = '<p><br><strong><em><u><ol><ul><li><span><div><b><i><del><s>';
    $clean = strip_tags($html, $allowedTags);

    // Strip inline event/style and other attributes except class/data-checked
    // Remove style attributes
    $clean = preg_replace('/\sstyle="[^"]*"/i', '', $clean);
    // Remove event handlers like onclick
    $clean = preg_replace('/\son[a-zA-Z]+="[^"]*"/i', '', $clean);
    // Remove javascript: urls
    $clean = preg_replace('/javascript:/i', '', $clean);
    // Allow only class and data-checked attributes; drop the rest
    $clean = preg_replace('/\s(?!class=|data-checked=)[a-zA-Z-]+="[^"]*"/i', '', $clean);

    // As a final fallback, if empty but original not empty, return plain text
    if (trim(strip_tags($clean)) === '' && $html !== '') {
        return strip_tags($html);
    }

    return $clean;
}

function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Auto-cleanup user activity log (notes + todos) to maintain max 1000 entries per user
 * Deletes oldest entries when limit is reached
 */
function cleanupUserActivityLog($user_id, $table_name) {
    global $conn;
    
    $max_log_limit = 1000;
    
    // Count total activities (notes + todos) for this user
    $countQuery = "
        SELECT 
            (SELECT COUNT(*) FROM notes WHERE user_id = :user_id1) + 
            (SELECT COUNT(*) FROM todos WHERE user_id = :user_id2) as total_count
    ";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindParam(':user_id1', $user_id, PDO::PARAM_INT);
    $countStmt->bindParam(':user_id2', $user_id, PDO::PARAM_INT);
    $countStmt->execute();
    $result = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total_count = $result['total_count'];
    
    // If limit reached, delete oldest entries
    if ($total_count >= $max_log_limit) {
        // Get all activities sorted by date
        $activitiesQuery = "
            SELECT 'note' as type, id, created_at FROM notes WHERE user_id = :user_id1
            UNION ALL
            SELECT 'todo' as type, id, created_at FROM todos WHERE user_id = :user_id2
            ORDER BY created_at ASC
        ";
        $activitiesStmt = $conn->prepare($activitiesQuery);
        $activitiesStmt->bindParam(':user_id1', $user_id, PDO::PARAM_INT);
        $activitiesStmt->bindParam(':user_id2', $user_id, PDO::PARAM_INT);
        $activitiesStmt->execute();
        $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete oldest entries to make room (delete 10% of limit = 100 entries)
        $delete_count = (int)($max_log_limit * 0.1);
        $to_delete = array_slice($activities, 0, $delete_count);
        
        foreach ($to_delete as $activity) {
            if ($activity['type'] === 'note') {
                $deleteStmt = $conn->prepare("DELETE FROM notes WHERE id = :id AND user_id = :user_id");
            } else {
                $deleteStmt = $conn->prepare("DELETE FROM todos WHERE id = :id AND user_id = :user_id");
            }
            $deleteStmt->bindParam(':id', $activity['id'], PDO::PARAM_INT);
            $deleteStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $deleteStmt->execute();
        }
    }
}

function setToast($type, $message) {
    $_SESSION['toast'] = [
        'type' => $type,
        'message' => $message
    ];
}

function displayToast() {
    if(isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        unset($_SESSION['toast']);
        
        return "
        <script>
        $(document).ready(function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            Toast.fire({
                icon: '{$toast['type']}',
                title: '{$toast['message']}'
            });
        });
        </script>";
    }
    return '';
}
?>