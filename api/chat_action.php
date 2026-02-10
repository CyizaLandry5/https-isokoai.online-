<?php
// ========================
// CORS HEADERS FIRST
// ========================
header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? "*"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

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

// Set session cookie parameters for cross-origin
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
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'error_code' => 'AUTH_ERROR']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$userId = (int)$_SESSION['user_id'];

switch ($action) {
    case 'new_chat':
        createNewChat($conn, $userId);
        break;
        
    case 'send_message':
        sendMessage($conn, $userId);
        break;
        
    case 'delete_chat':
        deleteChat($conn, $userId);
        break;
        
    case 'test_connection':
        echo json_encode([
            'success' => true, 
            'message' => 'API is working',
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id()
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}

function createNewChat($conn, $userId) {
    try {
        $stmt = $conn->prepare("INSERT INTO chats (user_id, title, created_at, last_message_at) VALUES (?, 'New Chat', NOW(), NOW())");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $chatId = $stmt->insert_id;
            echo json_encode([
                'success' => true, 
                'chat_id' => $chatId,
                'message' => 'Chat created successfully'
            ]);
        } else {
            throw new Exception('Failed to create chat: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Create chat error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'debug' => $e->getMessage()]);
    }
}

function sendMessage($conn, $userId) {
    try {
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $userMessage = trim($_POST['content'] ?? '');
        
        if ($chatId === 0) {
            echo json_encode(['success' => false, 'message' => 'Missing chat ID']);
            return;
        }
        
        if (empty($userMessage)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            return;
        }
        
        // Verify ownership with better error handling
        $stmt = $conn->prepare("SELECT id, title FROM chats WHERE id = ? AND user_id = ?");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $chatId, $userId);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Chat not found or access denied']);
            $stmt->close();
            return;
        }
        
        $stmt->bind_result($dbChatId, $chatTitle);
        $stmt->fetch();
        $stmt->close();
        
        // Save user message
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, role, content, created_at) VALUES (?, 'user', ?, NOW())");
        $stmt->bind_param("is", $chatId, htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8'));
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save user message: ' . $stmt->error);
        }
        
        $userMsgId = $stmt->insert_id;
        $stmt->close();
        
        // Check if this is first user message
        $stmt = $conn->prepare("SELECT COUNT(*) as msg_count FROM messages WHERE chat_id = ? AND role = 'user'");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $isFirstMessage = ($result['msg_count'] == 1);
        $stmt->close();
        
        // Update title if first message
        if ($isFirstMessage) {
            $title = substr($userMessage, 0, 30) . (strlen($userMessage) > 30 ? '...' : '');
            $stmt = $conn->prepare("UPDATE chats SET title = ? WHERE id = ?");
            $stmt->bind_param("si", $title, $chatId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Get conversation history (last 10 messages for context)
        $stmt = $conn->prepare("
            SELECT role, content 
            FROM messages 
            WHERE chat_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = [
                'role' => $row['role'], 
                'content' => htmlspecialchars_decode($row['content'], ENT_QUOTES)
            ];
        }
        $stmt->close();
        
        // Reverse to chronological order
        $history = array_reverse($history);
        
        // Add system message if this is the first message
        if ($isFirstMessage) {
            array_unshift($history, [
                'role' => 'system',
                'content' => 'You are IsōkoAI, a helpful AI assistant. Respond in a friendly and informative manner.'
            ]);
        }
        
        // Get AI response
        $assistantResponse = getDeepSeekResponse($history);
        
        // Save assistant response
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, role, content, created_at) VALUES (?, 'assistant', ?, NOW())");
        $stmt->bind_param("is", $chatId, htmlspecialchars($assistantResponse, ENT_QUOTES, 'UTF-8'));
        $stmt->execute();
        $stmt->close();
        
        // Update last message timestamp
        $stmt = $conn->prepare("UPDATE chats SET last_message_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'assistant' => $assistantResponse,
            'is_first_message' => $isFirstMessage,
            'chat_id' => $chatId,
            'user_message_id' => $userMsgId
        ]);
        
    } catch (Exception $e) {
        error_log("Send message error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred while processing your message',
            'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
        ]);
    }
}

function deleteChat($conn, $userId) {
    try {
        $chatId = (int)($_POST['chat_id'] ?? 0);
        
        if ($chatId === 0) {
            echo json_encode(['success' => false, 'message' => 'Missing chat ID']);
            return;
        }
        
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chatId, $userId);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Chat not found or access denied']);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Use transactions for data integrity
        $conn->begin_transaction();
        
        try {
            // Delete messages
            $stmt = $conn->prepare("DELETE FROM messages WHERE chat_id = ?");
            $stmt->bind_param("i", $chatId);
            $stmt->execute();
            $stmt->close();
            
            // Delete chat
            $stmt = $conn->prepare("DELETE FROM chats WHERE id = ?");
            $stmt->bind_param("i", $chatId);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Chat deleted successfully',
                'chat_id' => $chatId
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Delete chat error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete chat']);
    }
}

function getDeepSeekResponse($messages) {
    $apiKey = 'sk-3038aad8eb914f10b53a0660372422ae';
    $apiUrl = 'https://api.deepseek.com/v1/chat/completions';
    
    $data = [
        'model' => 'deepseek-chat',
        'messages' => $messages,
        'max_tokens' => 2000,
        'temperature' => 0.7,
        'stream' => false
    ];
    
    // Log the request for debugging
    error_log("DeepSeek API Request: " . json_encode(['message_count' => count($messages)]));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 45, // Increased timeout
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'IsokoAI/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errorNo = curl_errno($ch);
    
    curl_close($ch);
    
    if ($errorNo) {
        error_log("DeepSeek cURL Error ($errorNo): $error");
        return "I apologize, but I'm experiencing network issues. Please check your connection and try again. (cURL Error: $errorNo)";
    }
    
    if ($httpCode !== 200) {
        error_log("DeepSeek API Error ($httpCode): " . substr($response, 0, 500));
        
        // Try to parse error response
        $errorResponse = json_decode($response, true);
        $errorMessage = $errorResponse['error']['message'] ?? 'Unknown error';
        
        if ($httpCode === 429) {
            return "I'm receiving too many requests right now. Please wait a moment and try again.";
        } elseif ($httpCode === 401) {
            return "There's an authentication issue with the AI service. Please contact support.";
        } elseif ($httpCode >= 500) {
            return "The AI service is currently unavailable. Please try again in a few minutes.";
        }
        
        return "I apologize, but I encountered an error while processing your request. (Code: $httpCode)";
    }
    
    $responseData = json_decode($response, true);
    
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        error_log("DeepSeek API Unexpected Response: " . print_r($responseData, true));
        return "I received an unexpected response format. Please try rephrasing your question.";
    }
}

// Close connection at the end
if (isset($conn)) {
    $conn->close();
}
?>