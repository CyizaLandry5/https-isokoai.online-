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

$chatId = (int)($_GET['chat_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if ($chatId === 0) {
    echo json_encode(['success' => false, 'message' => 'Missing chat ID']);
    exit;
}

try {
    // Verify ownership with detailed error handling
    $stmt = $conn->prepare("SELECT id, title, created_at, last_message_at FROM chats WHERE id = ? AND user_id = ?");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $chatId, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Chat not found or access denied',
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $stmt->bind_result($id, $title, $createdAt, $lastMessageAt);
    $stmt->fetch();
    $stmt->close();
    
    // Get messages with improved ordering
    $stmt = $conn->prepare("
        SELECT 
            id,
            role, 
            content, 
            created_at,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_ago
        FROM messages 
        WHERE chat_id = ? 
        ORDER BY created_at ASC
    ");
    
    $stmt->bind_param("i", $chatId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Decode HTML entities and format time
        $content = htmlspecialchars_decode($row['content'], ENT_QUOTES);
        
        // Format relative time
        $secondsAgo = $row['seconds_ago'];
        $formattedTime = 'Just now';
        
        if ($secondsAgo >= 60) {
            $minutesAgo = floor($secondsAgo / 60);
            if ($minutesAgo >= 60) {
                $hoursAgo = floor($minutesAgo / 60);
                if ($hoursAgo >= 24) {
                    $daysAgo = floor($hoursAgo / 24);
                    $formattedTime = $daysAgo . 'd ago';
                } else {
                    $formattedTime = $hoursAgo . 'h ago';
                }
            } else {
                $formattedTime = $minutesAgo . 'm ago';
            }
        }
        
        $messages[] = [
            'id' => $row['id'],
            'role' => $row['role'],
            'content' => $content,
            'created_at' => $row['created_at'],
            'formatted_time' => $formattedTime,
            'timestamp' => date('H:i', strtotime($row['created_at']))
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'title' => $title ?: 'New Chat',
        'chat_id' => $chatId,
        'metadata' => [
            'message_count' => count($messages),
            'created_at' => $createdAt,
            'last_message_at' => $lastMessageAt,
            'has_messages' => count($messages) > 0
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get chat error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load chat messages',
        'error' => (ENVIRONMENT === 'development') ? $e->getMessage() : null,
        'chat_id' => $chatId
    ]);
    
    if (isset($conn)) {
        $conn->close();
    }
}
?>