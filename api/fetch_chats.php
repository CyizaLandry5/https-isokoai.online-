<?php
// ========================
// CORS HEADERS FIRST
// ========================
header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? "*"));
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================
// MAIN CODE
// ========================
header('Content-Type: application/json');
session_start();

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

require '../db_connection.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'redirect' => true]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.title,
            c.created_at,
            c.last_message_at,
            (SELECT content FROM messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_preview,
            (SELECT COUNT(*) FROM messages WHERE chat_id = c.id) as message_count
        FROM chats c 
        WHERE c.user_id = ? 
        ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
        LIMIT 50
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $chats = [];
    while ($row = $result->fetch_assoc()) {
        // Generate title from first message if title is generic
        if (empty($row['title']) || $row['title'] === 'New Chat') {
            if (!empty($row['last_message_preview'])) {
                $row['title'] = substr($row['last_message_preview'], 0, 40) . 
                               (strlen($row['last_message_preview']) > 40 ? '...' : '');
            } else {
                $row['title'] = 'New Chat';
            }
        }
        
        // Format dates
        $row['formatted_date'] = 'No messages';
        if ($row['last_message_at']) {
            $timestamp = strtotime($row['last_message_at']);
            $now = time();
            $diff = $now - $timestamp;
            
            if ($diff < 60) {
                $row['formatted_date'] = 'Just now';
            } elseif ($diff < 3600) {
                $row['formatted_date'] = floor($diff / 60) . 'm ago';
            } elseif ($diff < 86400) {
                $row['formatted_date'] = floor($diff / 3600) . 'h ago';
            } elseif ($diff < 604800) {
                $row['formatted_date'] = floor($diff / 86400) . 'd ago';
            } else {
                $row['formatted_date'] = date('M d, Y', $timestamp);
            }
        }
        
        // Clean up the preview
        if ($row['last_message_preview']) {
            $row['last_message_preview'] = htmlspecialchars_decode($row['last_message_preview'], ENT_QUOTES);
            $row['last_message_preview'] = substr($row['last_message_preview'], 0, 60) . 
                                          (strlen($row['last_message_preview']) > 60 ? '...' : '');
        }
        
        $chats[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true, 
        'chats' => $chats,
        'count' => count($chats),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Fetch chats error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to load conversations',
        'error' => (ENVIRONMENT === 'development') ? $e->getMessage() : null,
        'chats' => []
    ]);
    
    if (isset($conn)) {
        $conn->close();
    }
}
?>