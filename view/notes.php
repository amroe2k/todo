<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if(!isLoggedIn()) {
    redirect('../index.php?page=login');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

// CRUD Operations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        $action = $_POST['action'];
        $user_id = getCurrentUserId();
        
        switch($action) {
            case 'create':
                $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
                $content = isset($_POST['content']) ? sanitize_html($_POST['content']) : '';
                $color = isset($_POST['color']) ? sanitize($_POST['color']) : '#fffacd';
                
                // Auto-cleanup if activity log limit reached
                cleanupUserActivityLog($user_id, 'notes');
                
                $query = "INSERT INTO notes (user_id, title, content, color) VALUES (:user_id, :title, :content, :color)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                $stmt->bindParam(':color', $color, PDO::PARAM_STR);
                if($stmt->execute()) {
                    $note_id = $conn->lastInsertId();
                    echo json_encode(['success' => true, 'id' => $note_id, 'message' => 'Note created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create note']);
                }
                exit();
                
            case 'update':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
                $content = isset($_POST['content']) ? sanitize_html($_POST['content']) : '';
                $color = isset($_POST['color']) ? sanitize($_POST['color']) : '#fffacd';
                
                $query = "UPDATE notes SET title = :title, content = :content, color = :color WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                $stmt->bindParam(':color', $color, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update note']);
                }
                exit();
                
            case 'delete':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                
                // First check if note exists
                $checkQuery = "SELECT id FROM notes WHERE id = :id AND user_id = :user_id";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $checkStmt->execute();
                
                if($checkStmt->rowCount() == 0) {
                    echo json_encode(['success' => false, 'message' => 'Note not found or already deleted']);
                    exit();
                }
                
                $query = "DELETE FROM notes WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if($stmt->execute()) {
                    if($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete note. Please try again.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error. Please contact administrator.']);
                }
                exit();
                
            case 'archive':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                
                $query = "UPDATE notes SET is_archived = 1 WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Note archived successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to archive note']);
                }
                exit();
                
            case 'unarchive':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                
                $query = "UPDATE notes SET is_archived = 0 WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Note restored successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to restore note']);
                }
                exit();
                
            case 'update_position':
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $position_x = isset($_POST['position_x']) ? (int)$_POST['position_x'] : 100;
                $position_y = isset($_POST['position_y']) ? (int)$_POST['position_y'] : 100;
                
                $query = "UPDATE notes SET position_x = :position_x, position_y = :position_y WHERE id = :id AND user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':position_x', $position_x, PDO::PARAM_INT);
                $stmt->bindParam(':position_y', $position_y, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                echo json_encode(['success' => true]);
                exit();
        }
    }
}

// Handle AJAX request for archived notes
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_archived') {
    $user_id = getCurrentUserId();
    $query = "SELECT * FROM notes WHERE user_id = :user_id AND is_archived = 1 ORDER BY updated_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $archivedNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'notes' => $archivedNotes]);
    exit();
}

// Get all active (non-archived) notes for current user
$query = "SELECT * FROM notes WHERE user_id = :user_id AND (is_archived = 0 OR is_archived IS NULL) ORDER BY updated_at DESC";
$stmt = $conn->prepare($query);
$current_user_id = getCurrentUserId();
$stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
$stmt->execute();
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get archived notes count
$queryArchived = "SELECT COUNT(*) as count FROM notes WHERE user_id = :user_id AND is_archived = 1";
$stmtArchived = $conn->prepare($queryArchived);
$stmtArchived->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
$stmtArchived->execute();
$archivedCount = $stmtArchived->fetch(PDO::FETCH_ASSOC)['count'];

// Colors for color picker
$colors = ['#fffacd', '#ffebcd', '#e0ffff', '#f0fff0', '#ffe4e1', '#f5f5f5', '#f0e68c', '#d8bfd8', 
           '#d4edda', '#f8d7da', '#d1ecf1', '#d6d8db', '#cce5ff', '#e2e3e5', '#fff3cd'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sticky Notes - Todo Talenta Digital</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notes.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Content -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-sticky-note text-warning"></i> Sticky Notes
                </h2>
                <p class="text-muted mb-0">Drag and drop notes to organize them</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="viewArchivedBtn">
                    <i class="fas fa-archive me-2"></i> Archived Notes
                    <?php if($archivedCount > 0): ?>
                    <span class="badge bg-secondary"><?php echo $archivedCount; ?></span>
                    <?php endif; ?>
                </button>
                <button class="btn" style="color: #12305b; background: linear-gradient(135deg, #eef4ff, #dbe7ff); border: 1px solid #c8d8ff;" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                    <i class="fas fa-plus-circle me-2"></i> Add New Note
                </button>
            </div>
        </div>
        
        <!-- Note Container -->
        <div class="note-container" id="noteContainer">
            <div class="note-placeholder">
                <i class="fas fa-plus text-muted"></i>
                <span class="text-muted">Notes baru muncul di sini</span>
            </div>
            <?php if(empty($notes)): ?>
                <div class="no-notes">
                    <i class="fas fa-sticky-note"></i>
                    <h4>No notes yet</h4>
                    <p>Create your first sticky note by clicking the button above</p>
                </div>
            <?php else: ?>
                <?php foreach($notes as $note): 
                    $html = $note['content'];
                    // Convert list items to bullet text before stripping tags for excerpt
                    $work = preg_replace('/\s*<li[^>]*>/i', '• ', $html);
                    $work = preg_replace('/\s*<\/li>/i', "\n", $work);
                    $work = preg_replace('/<\/(ul|ol)[^>]*>/i', "\n", $work);
                    $work = preg_replace('/<\/?(ul|ol)[^>]*>/i', '', $work);
                    $plainText = trim(strip_tags($work));
                    $plainText = preg_replace('/\s+/', ' ', $plainText);
                    $excerpt = mb_strlen($plainText, 'UTF-8') > 160
                        ? mb_substr($plainText, 0, 160, 'UTF-8') . '...'
                        : $plainText;
                ?>
                <div class="sticky-note ui-widget-content" 
                     style="background-color: <?php echo htmlspecialchars($note['color']); ?>; 
                            left: <?php echo (int)$note['position_x']; ?>px; 
                            top: <?php echo (int)$note['position_y']; ?>px;"
                     data-id="<?php echo (int)$note['id']; ?>"
                     data-title="<?php echo htmlspecialchars($note['title']); ?>"
                     data-content="<?php echo htmlspecialchars($note['content'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-color="<?php echo htmlspecialchars($note['color']); ?>"
                     data-updated="<?php echo date('d/m/Y H:i', strtotime($note['updated_at'])); ?>">
                    <div class="note-title"><?php echo htmlspecialchars($note['title']); ?></div>
                    <div class="note-content"><?php echo nl2br(htmlspecialchars($excerpt)); ?></div>
                    <div class="note-footer">
                        <div class="note-date">
                            <small><i class="fas fa-clock me-1"></i><?php echo date('d/m/Y', strtotime($note['updated_at'])); ?></small>
                        </div>
                        <div class="note-actions">
                            <button class="btn btn-sm btn-outline-dark note-action-btn edit-note" 
                                    data-id="<?php echo (int)$note['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($note['title']); ?>"
                                    data-content="<?php echo htmlspecialchars($note['content']); ?>"
                                    data-color="<?php echo htmlspecialchars($note['color']); ?>"
                                    title="Edit Note">
                                <i class="fas fa-edit fa-sm"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning note-action-btn archive-note" 
                                    data-id="<?php echo (int)$note['id']; ?>"
                                    title="Archive Note">
                                <i class="fas fa-archive fa-sm"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger note-action-btn delete-note" 
                                    data-id="<?php echo (int)$note['id']; ?>"
                                    title="Delete Note">
                                <i class="fas fa-trash fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #eef4ff, #dbe7ff); color: #12305b; border-bottom: 1px solid #c8d8ff;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i> Add New Note
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addNoteForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-1"></i> Title
                            </label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="100" placeholder="Enter note title">
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">
                                <i class="fas fa-align-left me-1"></i> Content
                            </label>
                            <div id="contentEditor" class="form-control" style="height: 200px;"></div>
                            <input type="hidden" id="content" name="content">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-palette me-1"></i> Color
                            </label><br>
                            <div class="color-picker d-flex flex-wrap gap-2">
                                <?php foreach($colors as $color): ?>
                                <div class="color-option <?php echo $color == '#fffacd' ? 'active' : ''; ?>" 
                                     style="background-color: <?php echo $color; ?>;" 
                                     data-color="<?php echo $color; ?>"
                                     title="<?php echo $color; ?>"></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="color" name="color" value="#fffacd">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="color: #12305b; background: linear-gradient(135deg, #eef4ff, #dbe7ff); border: 1px solid #c8d8ff;">
                            <i class="fas fa-save me-2"></i> Save Note
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Note Modal -->
    <div class="modal fade" id="editNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #fff6e6, #ffe8c7); color: #5c3b0a; border-bottom: 1px solid #f3d7a2;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Edit Note
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editNoteForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">
                                <i class="fas fa-heading me-1"></i> Title
                            </label>
                            <input type="text" class="form-control" id="edit_title" name="title" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">
                                <i class="fas fa-align-left me-1"></i> Content
                            </label>
                            <div id="editContentEditor" class="form-control" style="height: 200px;"></div>
                            <input type="hidden" id="edit_content" name="content">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-palette me-1"></i> Color
                            </label><br>
                            <div class="color-picker d-flex flex-wrap gap-2">
                                <?php foreach($colors as $color): ?>
                                <div class="color-option" 
                                     style="background-color: <?php echo $color; ?>;" 
                                     data-color="<?php echo $color; ?>"
                                     title="<?php echo $color; ?>"></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="edit_color" name="color" value="#fffacd">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="color: #5c3b0a; background: linear-gradient(135deg, #fff6e6, #ffe8c7); border: 1px solid #f3d7a2;">
                            <i class="fas fa-save me-2"></i> Update Note
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Note Modal -->
    <div class="modal fade" id="viewNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewNoteTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewNoteContent"></div>
                <div class="modal-footer">
                    <small class="text-muted" id="viewNoteUpdated"></small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archived Notes Modal -->
    <div class="modal fade" id="archivedNotesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-archive text-secondary me-2"></i>Archived Notes
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="archivedNotesContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading archived notes...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script src="../js/notes.js"></script>
    <script src="../js/notes.custom.js"></script>
    <div class="sticky-footer">
        <div class="container-fluid">
            <p class="mb-0">&copy; 2026 <strong>Alfa IT Solutions</strong>. All rights reserved. | Todo Talenta Digital</p>
        </div>
    </div>
</body>
</html>
