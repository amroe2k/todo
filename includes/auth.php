<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($username, $email, $password, $role = 'user', $is_approved = 0, $is_aktif = 1) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, role, is_approved, is_aktif) VALUES (:username, :email, :password, :role, :is_approved, :is_aktif)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':is_approved', $is_approved, PDO::PARAM_INT);
        $stmt->bindParam(':is_aktif', $is_aktif, PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            // Check for duplicate entry error
            if($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, email, password, role, is_approved FROM users WHERE username = :username OR email = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                // Check if user is approved
                if(!$row['is_approved']) {
                    return 'not_approved';
                }
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                return true;
            }
        }
        return false;
    }
    
    public function getUser($id) {
        $query = "SELECT id, username, email, role, created_at FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateUser($id, $username, $email, $role = null, $status = null) {
        // $status now represents is_aktif (1 active, 0 inactive)
        if($role && $status !== null && isAdmin()) {
            $query = "UPDATE users SET username = :username, email = :email, role = :role, is_aktif = :status WHERE id = :id";
        } elseif($role && isAdmin()) {
            $query = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
        } elseif($status !== null && isAdmin()) {
            $query = "UPDATE users SET username = :username, email = :email, is_aktif = :status WHERE id = :id";
        } else {
            $query = "UPDATE users SET username = :username, email = :email WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id);
        
        if($role && isAdmin()) {
            $stmt->bindParam(':role', $role);
        }
        if($status !== null && isAdmin()) {
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        }
        
        return $stmt->execute();
    }
    
    public function deleteUser($id) {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function getAllUsers() {
        $query = "SELECT id, username, email, role, is_approved, is_aktif, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function changePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        try {
            if (!$user_id || $user_id <= 0) {
                return false;
            }
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $user_id);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function generatePassword() {
        // Generate random password
        $length = 10;
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }
    
    public function validatePasswordStrength($password) {
        // Minimum 6 characters, no uppercase/special requirements
        $pattern = '/^.{6,}$/';
        return preg_match($pattern, $password);
    }
    
    public function getPendingUsers() {
        $query = "SELECT id, username, email, role, is_aktif, created_at FROM users WHERE is_approved = 0 AND is_aktif = 1 ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getApprovedUsers() {
        $query = "SELECT id, username, email, role, is_approved, is_aktif, approved_at, created_at FROM users WHERE is_approved = 1 ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getInactiveUsers() {
        $query = "SELECT id, username, email, role, is_approved, is_aktif, created_at FROM users WHERE is_aktif = 0 ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function approveUser($user_id, $admin_id) {
        try {
            if (!$user_id || $user_id <= 0 || !$admin_id || $admin_id <= 0) {
                return false;
            }
            $query = "UPDATE users SET is_approved = 1, approved_by = :admin_id, approved_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':admin_id', $admin_id);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function rejectUser($user_id) {
        try {
            if (!$user_id || $user_id <= 0) {
                return false;
            }
            $query = "DELETE FROM users WHERE id = :user_id AND is_approved = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Admin can view as another user
     * Stores original admin ID in session and switches to user session
     */
    public function viewAsUser($user_id) {
        if (!isAdmin() || !$user_id) {
            return false;
        }

        try {
            // Get the user to view
            $query = "SELECT id, username, email, role FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return false;
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Store original admin info
            $_SESSION['original_user_id'] = $_SESSION['user_id'];
            $_SESSION['original_role'] = $_SESSION['role'];
            
            // Switch to viewing as user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['viewing_as_user'] = true;
            $_SESSION['viewed_user_id'] = $user['id'];
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Return to original admin account
     */
    public function returnAsAdmin() {
        if (!isset($_SESSION['original_user_id'])) {
            return false;
        }

        try {
            // Get original admin user data
            $query = "SELECT id, username, email, role FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['original_user_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return false;
            }

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Restore original admin session
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];
            
            // Clear viewing flags
            unset($_SESSION['original_user_id']);
            unset($_SESSION['original_role']);
            unset($_SESSION['viewing_as_user']);
            unset($_SESSION['viewed_user_id']);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>