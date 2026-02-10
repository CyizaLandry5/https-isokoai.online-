<?php
// ========================
// PHP SECTION - BACKEND
// ========================
session_start();
require 'db_connection.php';

// Check logged in
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT first_name, last_name, email, profile_image FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$firstName = htmlspecialchars($user['first_name'] ?? 'User');
$lastName = htmlspecialchars($user['last_name'] ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$profileImage = $user['profile_image'] ? 'uploads/' . $user['profile_image'] : 'uploads/profile.avif';

// Function to get AI response from DeepSeek
function getDeepSeekResponse($messages)
{
  $apiKey = 'sk-3038aad8eb914f10b53a0660372422ae';
  $apiUrl = 'https://api.deepseek.com/v1/chat/completions';

  $data = [
    'model' => 'deepseek-chat',
    'messages' => $messages,
    'max_tokens' => 2000,
    'temperature' => 0.7,
    'stream' => false
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $apiUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
  ]);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    if (isset($responseData['choices'][0]['message']['content'])) {
      return $responseData['choices'][0]['message']['content'];
    }
  }

  return "I apologize, but I'm having trouble connecting right now. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IsōkoAI — Murakaza neza</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="shortcut icon" href="images/header.png" type="image/x-icon">
  <style>
    /* ========================
           CSS SECTION - STYLES
           ======================== */
    :root {
      --accent-1: #00d4ff;
      --accent-2: #006cff;
      --accent-gradient: linear-gradient(90deg, var(--accent-1), var(--accent-2));
      --deep-blue: #08193a;
      --dark-bg: #001025;
      --card-bg: rgba(255, 255, 255, 0.02);
      --card-border: rgba(255, 255, 255, 0.06);
      --text-primary: #e6f7ff;
      --text-secondary: #a0c8e6;
      --success: #00ff88;
      --warning: #ffaa00;
      --danger: #ff6b6b;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: radial-gradient(circle at top, var(--deep-blue), var(--dark-bg));
      color: var(--text-primary);
      height: 100vh;
      overflow: hidden;
    }

    .container {
      display: flex;
      height: 100vh;
      padding: 15px;
      gap: 15px;
    }

    /* SIDEBAR */
    .sidebar {
      width: 280px;
      background: rgba(0, 20, 40, 0.7);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      backdrop-filter: blur(10px);
      border: 1px solid var(--card-border);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .profile-section {
      text-align: center;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 20px;
    }

    .profile-pic-container {
      position: relative;
      width: 100px;
      height: 100px;
      margin: 0 auto 15px;
    }

    .profile-pic {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--accent-1);
      box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
    }

    .profile-name {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 5px;
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .profile-email {
      font-size: 0.9rem;
      color: var(--text-secondary);
      margin-bottom: 15px;
    }

    .upload-btn {
      display: inline-block;
      padding: 8px 16px;
      background: rgba(0, 212, 255, 0.1);
      color: var(--accent-1);
      border: 1px solid rgba(0, 212, 255, 0.3);
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s;
    }

    .upload-btn:hover {
      background: rgba(0, 212, 255, 0.2);
      transform: translateY(-2px);
    }

    .nav-section {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 10px;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
    }

    .nav-item:hover,
    .nav-item.active {
      background: rgba(0, 212, 255, 0.1);
      color: var(--accent-1);
    }

    .nav-item i {
      font-size: 1.2rem;
      width: 24px;
    }

    .logout-btn {
      margin-top: auto;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: rgba(255, 107, 107, 0.1);
      color: var(--danger);
      border: 1px solid rgba(255, 107, 107, 0.3);
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 1rem;
    }

    .logout-btn:hover {
      background: rgba(255, 107, 107, 0.2);
      transform: translateY(-2px);
    }

    /* MAIN CHAT AREA */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .header {
      background: rgba(0, 20, 40, 0.7);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      backdrop-filter: blur(10px);
      border: 1px solid var(--card-border);
    }

    .welcome-text h1 {
      font-size: 1.8rem;
      margin-bottom: 5px;
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .welcome-text p {
      color: var(--text-secondary);
      font-size: 0.95rem;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background: var(--accent-gradient);
      color: #001f3f;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-primary);
      border: 1px solid var(--card-border);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
    }

    .chat-container {
      flex: 1;
      background: rgba(0, 20, 40, 0.7);
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      backdrop-filter: blur(10px);
      border: 1px solid var(--card-border);
      overflow: hidden;
    }

    .chat-header {
      padding: 20px;
      border-bottom: 1px solid var(--card-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .chat-title {
      font-size: 1.3rem;
      font-weight: 600;
    }

    .chat-actions {
      display: flex;
      gap: 10px;
    }

    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .message {
      display: flex;
      gap: 15px;
      max-width: 85%;
      animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .message.user {
      align-self: flex-end;
      flex-direction: row-reverse;
    }

    .message-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
      overflow: hidden;
    }

    .message-avatar-img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--accent-1);
    }

    .message.user .message-avatar {
      background: transparent;
    }

    .message.user .message-avatar-img {
      border-color: var(--accent-1);
    }

    .message.assistant .message-avatar {
      background: transparent;
    }

    .message.assistant .message-avatar-img {
      border-color: var(--accent-2);
    }

    .message-content {
      padding: 15px;
      border-radius: 15px;
      line-height: 1.5;
    }

    .message.user .message-content {
      background: var(--accent-gradient);
      color: #001f3f;
      border-bottom-right-radius: 5px;
    }

    .message.assistant .message-content {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-primary);
      border-bottom-left-radius: 5px;
    }

    .message-text {
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .message-time {
      font-size: 0.8rem;
      opacity: 0.7;
      margin-top: 5px;
    }

    .chat-input-area {
      padding: 20px;
      border-top: 1px solid var(--card-border);
      background: rgba(0, 10, 20, 0.5);
    }

    .input-wrapper {
      display: flex;
      gap: 10px;
      align-items: flex-end;
    }

    .input-field {
      flex: 1;
      padding: 15px;
      border-radius: 12px;
      border: 1px solid var(--card-border);
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-primary);
      font-size: 1rem;
      resize: none;
      min-height: 56px;
      max-height: 150px;
      font-family: inherit;
    }

    .input-field:focus {
      outline: none;
      border-color: var(--accent-1);
      box-shadow: 0 0 0 2px rgba(0, 212, 255, 0.2);
    }

    .send-btn {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      background: var(--accent-gradient);
      border: none;
      color: #001f3f;
      cursor: pointer;
      font-size: 1.2rem;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .send-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
    }

    .send-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* SIDEBAR CHATS */
    .chats-sidebar {
      width: 320px;
      background: rgba(0, 20, 40, 0.7);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      backdrop-filter: blur(10px);
      border: 1px solid var(--card-border);
    }

    .chats-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .chats-header h2 {
      font-size: 1.3rem;
    }

    .chats-list {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .chat-item {
      padding: 15px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid transparent;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .chat-item:hover {
      background: rgba(255, 255, 255, 0.05);
      border-color: var(--card-border);
    }

    .chat-item.active {
      background: rgba(0, 212, 255, 0.1);
      border-color: rgba(0, 212, 255, 0.3);
    }

    .chat-item-title {
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .chat-item-preview {
      font-size: 0.9rem;
      color: var(--text-secondary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .chat-item-time {
      font-size: 0.8rem;
      color: var(--text-secondary);
    }

    .delete-chat-btn {
      opacity: 0;
      color: var(--danger);
      background: none;
      border: none;
      cursor: pointer;
      padding: 2px 6px;
      border-radius: 4px;
      transition: all 0.3s;
    }

    .chat-item:hover .delete-chat-btn {
      opacity: 1;
    }

    .delete-chat-btn:hover {
      background: rgba(255, 107, 107, 0.1);
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-secondary);
    }

    .empty-state i {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    .typing-indicator {
      display: flex;
      gap: 5px;
      padding: 10px;
    }

    .typing-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent-1);
      animation: typing 1.4s infinite ease-in-out;
    }

    .typing-dot:nth-child(1) {
      animation-delay: -0.32s;
    }

    .typing-dot:nth-child(2) {
      animation-delay: -0.16s;
    }

    @keyframes typing {

      0%,
      80%,
      100% {
        transform: scale(0);
      }

      40% {
        transform: scale(1);
      }
    }

    /* MODAL */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(5px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: var(--deep-blue);
      border-radius: 20px;
      padding: 30px;
      width: 90%;
      max-width: 500px;
      border: 1px solid var(--card-border);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-header h2 {
      font-size: 1.5rem;
    }

    .close-modal {
      background: none;
      border: none;
      color: var(--text-secondary);
      font-size: 1.5rem;
      cursor: pointer;
      padding: 5px;
    }

    .modal-body {
      margin-bottom: 20px;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    /* RESPONSIVE */
    @media (max-width: 1200px) {
      .chats-sidebar {
        width: 280px;
      }
    }

    @media (max-width: 992px) {
      .container {
        flex-direction: column;
        height: auto;
        min-height: 100vh;
      }

      .sidebar,
      .chats-sidebar {
        width: 100%;
      }

      .chats-sidebar {
        order: -1;
      }
    }

    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }

      .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }

      .action-buttons {
        width: 100%;
        justify-content: center;
      }
    }

    /* UTILITY CLASSES */
    .text-gradient {
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-success {
      background: rgba(0, 255, 136, 0.1);
      color: var(--success);
      border: 1px solid rgba(0, 255, 136, 0.3);
    }

    .scrollbar-hidden::-webkit-scrollbar {
      display: none;
    }
  </style>
</head>

<body>
  <!-- ========================
         HTML SECTION - LAYOUT
         ======================== -->
  <div class="container">
    <!-- LEFT SIDEBAR -->
    <div class="sidebar">
      <div class="profile-section">
        <div class="profile-pic-container">
          <img id="profileImg" class="profile-pic" src="<?php echo $profileImage; ?>" alt="Profile">
          <div class="upload-btn" onclick="document.getElementById('profileUpload').click()">
            <i class="fas fa-camera"></i> Update
          </div>
          <input type="file" id="profileUpload" accept="image/*" style="display: none;">
        </div>
        <div class="profile-name"><?php echo $firstName . ' ' . $lastName; ?></div>
        <div class="profile-email"><?php echo $email; ?></div>
        <span class="badge badge-success">Premium User</span>
      </div>

      <div class="nav-section">
        <a href="#" class="nav-item active">
          <i class="fas fa-comments"></i>
          <span>Chat</span>
        </a>
        <a href="#" class="nav-item">
          <i class="fas fa-microphone"></i>
          <span>Kinyarwanda Voice & pronounciation - <i>soon!</i></span>
        </a>
        <a href="#" class="nav-item">
          <i class="fas fa-cog"></i>
          <span>Settings</span>
        </a>
        <a href="#" class="nav-item">
          <i class="fas fa-question-circle"></i>
          <span>Help & Support</span>
        </a>
      </div>

      <button class="logout-btn" onclick="window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </button>
    </div>

    <!-- MAIN CHAT AREA -->
    <div class="main-content">
      <div class="header">
        <div class="welcome-text">
          <h1>Welcome to IsōkoAI</h1>
          <p>Murakaza neza!- Ask anything | Baza icyo ushaka</p>
        </div>
        <div class="action-buttons">
          <button class="btn btn-secondary" onclick="toggleDarkMode()">
            <i class="fas fa-moon"></i> Theme
          </button>
          <button class="btn btn-primary" onclick="createNewChat()">
            <i class="fas fa-plus"></i> New Chat
          </button>
        </div>
      </div>

      <div class="chat-container">
        <div class="chat-header">
          <div class="chat-title" id="currentChatTitle">Start a new conversation</div>
          <div class="chat-actions">
            <button class="btn btn-secondary" onclick="clearChat()" id="clearBtn" disabled>
              <i class="fas fa-trash"></i> Clear
            </button>
            <button class="btn btn-secondary" onclick="shareChat()" id="shareBtn" disabled>
              <i class="fas fa-share"></i> Share
            </button>
          </div>
        </div>

        <div class="chat-messages" id="chatMessages">
          <!-- Messages will appear here -->
          <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h3>No messages yet</h3>
            <p>Start a conversation by typing a message below</p>
          </div>
        </div>

        <div class="chat-input-area">
          <div class="input-wrapper">
            <textarea
              class="input-field"
              id="messageInput"
              placeholder="Ask IsōkoAI anything... (Press Shift+Enter for new line)"
              rows="1"
              onkeydown="handleKeyPress(event)"></textarea>
            <button class="send-btn" id="sendBtn" onclick="sendMessage()">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT CHATS SIDEBAR -->
    <div class="chats-sidebar">
      <div class="chats-header">
        <h2>Conversations</h2>
        <div class="action-buttons">
          <button class="btn btn-secondary" onclick="refreshChats()">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>

      <div class="chats-list" id="chatsList">
        <!-- Chat items will appear here -->
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>No conversations yet</p>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL FOR PROFILE UPLOAD -->
  <div class="modal" id="uploadModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Upload Profile Picture</h2>
        <button class="close-modal" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div id="uploadPreview" style="text-align: center; margin-bottom: 20px;">
          <img id="previewImage" src="" style="max-width: 200px; border-radius: 10px; display: none;">
        </div>
        <input type="file" id="modalProfileUpload" accept="image/*" style="width: 100%; padding: 10px;">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="uploadProfile()">Upload</button>
      </div>
    </div>
  </div>

  <!-- ========================
         JAVASCRIPT SECTION
         ======================== -->

<script>
    // ========================
    // CONFIGURATION - UPDATED FOR PRODUCTION
    // ========================
    const userId = <?php echo $userId; ?>;
    const userProfileImage = '<?php echo $profileImage; ?>';
    const aiLogoImage = 'isokologo.jpg';
    
    // DYNAMIC API URL - Works for both localhost and production
    const apiBaseUrl = (function() {
        const hostname = window.location.hostname;
        const pathname = window.location.pathname;
        
        console.log('Hostname:', hostname, 'Pathname:', pathname);
        
        // Production domain
        if (hostname === 'isokoai.online') {
            return '/api'; // If api is in root
        }
        
        // Local development
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            if (pathname.includes('/isokoai/')) {
                return '/isokoai/api'; // Your local setup
            }
            return '/api';
        }
        
        // Default - relative path (usually works)
        return './api';
    })();
    
    console.log('API Base URL set to:', apiBaseUrl);
    
    let currentChatId = null;
    let isProcessing = false;
    let isOnline = navigator.onLine;

    // ========================
    // GLOBAL AJAX SETUP
    // ========================
    $.ajaxSetup({
        timeout: 30000,
        cache: false,
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        crossDomain: true,
        beforeSend: function(xhr, settings) {
            // Add cache buster for GET requests
            if (settings.type === 'GET' && settings.url.indexOf('?_') === -1) {
                settings.url += (settings.url.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();
            }
        }
    });

    // ========================
    // INITIALIZATION
    // ========================
    $(document).ready(function() {
        console.log('Dashboard initialized for user:', userId);
        
        // Test connection first
        testApiConnection();
        
        // Load user chats
        loadChats();
        
        // Setup UI
        autoResizeTextarea();
        setupNetworkMonitoring();
        
        // Auto-create first chat if none exists
        setTimeout(() => {
            if ($('#chatsList .chat-item').length === 0) {
                createNewChat();
            }
        }, 1500);
    });

    // ========================
    // NETWORK & CONNECTION
    // ========================
    function setupNetworkMonitoring() {
        updateNetworkStatus();
        
        $(window).on('online offline', function() {
            isOnline = navigator.onLine;
            updateNetworkStatus();
        });
    }
    
    function updateNetworkStatus() {
        if (!isOnline) {
            showNotification('You are offline. Some features may not work.', 'warning', 5000);
        }
    }
    
    function testApiConnection() {
        $.ajax({
            url: apiBaseUrl + '/test.php',
            method: 'GET',
            timeout: 5000,
            success: function(response) {
                console.log('✅ API Connection Test Successful:', response);
            },
            error: function(xhr) {
                console.warn('⚠️ API Connection Test Failed. Check API URL:', apiBaseUrl);
                console.warn('Status:', xhr.status, 'Text:', xhr.statusText);
                
                // Try alternative API path
                const altApiBase = apiBaseUrl === './api' ? '/api' : './api';
                console.log('Trying alternative API path:', altApiBase);
            }
        });
    }

    // ========================
    // CHAT MANAGEMENT
    // ========================
    function loadChats() {
        $.ajax({
            url: apiBaseUrl + '/fetch_chats.php',
            method: 'GET',
            data: { user_id: userId },
            success: function(response) {
                if (response.success) {
                    renderChatsList(response.chats);
                } else {
                    console.error('Failed to load chats:', response.message);
                    showNotification('Failed to load conversations', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading chats:', status, error);
                showNotification('Network error loading chats', 'error');
                
                // Show empty state
                $('#chatsList').html(`
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Failed to load chats</p>
                        <button class="btn btn-secondary" onclick="loadChats()" style="margin-top: 10px;">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `);
            }
        });
    }

    function renderChatsList(chats) {
        const $chatsList = $('#chatsList');

        if (!chats || chats.length === 0) {
            $chatsList.html(`
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No conversations yet</p>
                    <button class="btn btn-primary" onclick="createNewChat()" style="margin-top: 10px;">
                        <i class="fas fa-plus"></i> Start New Chat
                    </button>
                </div>
            `);
            return;
        }

        $chatsList.empty();

        chats.forEach(chat => {
            const preview = chat.last_message_preview ?
                chat.last_message_preview.substring(0, 50) + (chat.last_message_preview.length > 50 ? '...' : '') :
                'No messages yet';

            const time = chat.formatted_date || formatDate(chat.last_message_at);

            const $chatItem = $(`
                <div class="chat-item ${chat.id === currentChatId ? 'active' : ''}" data-chat-id="${chat.id}">
                    <div class="chat-item-title">
                        <span>${chat.title || 'New Chat'}</span>
                        <button class="delete-chat-btn" onclick="deleteChat(${chat.id}, event)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="chat-item-preview">${preview}</div>
                    <div class="chat-item-time">${time}</div>
                </div>
            `);

            $chatItem.click(function(e) {
                if (!$(e.target).closest('.delete-chat-btn').length) {
                    openChat(chat.id, chat.title);
                }
            });

            $chatsList.append($chatItem);
        });
    }

    function createNewChat() {
        if (isProcessing) return;
        
        isProcessing = true;
        showNotification('Creating new chat...', 'info');
        
        $.ajax({
            url: apiBaseUrl + '/chat_action.php',
            method: 'POST',
            data: {
                action: 'new_chat',
                user_id: userId
            },
            success: function(response) {
                isProcessing = false;
                if (response.success) {
                    loadChats();
                    openChat(response.chat_id, 'New Chat');
                    showNotification('New chat created!', 'success');
                } else {
                    showNotification('Failed to create chat: ' + (response.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                isProcessing = false;
                handleAjaxError(xhr, status, error, 'createNewChat');
                showNotification('Failed to create chat. Please try again.', 'error');
            }
        });
    }

    function openChat(chatId, title = 'Chat') {
        if (!chatId) return;
        
        currentChatId = chatId;
        $('#currentChatTitle').text(title || 'Chat');
        $('#clearBtn').prop('disabled', false);
        $('#shareBtn').prop('disabled', false);

        // Update active state in sidebar
        $('#chatsList .chat-item').removeClass('active');
        $(`[data-chat-id="${chatId}"]`).addClass('active');

        // Clear and load messages
        const $chatMessages = $('#chatMessages');
        $chatMessages.html('<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p></div>');

        $.ajax({
            url: apiBaseUrl + '/get_chat.php',
            method: 'GET',
            data: {
                chat_id: chatId,
                _: Date.now() // Prevent caching
            },
            success: function(response) {
                if (response.success) {
                    renderMessages(response.messages);
                } else {
                    $chatMessages.html(`
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>${response.message || 'Failed to load messages'}</p>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $chatMessages.html(`
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Failed to load messages</p>
                        <button class="btn btn-secondary" onclick="openChat(${chatId}, '${title}')" style="margin-top: 10px;">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `);
                console.error('Error loading chat:', error);
            }
        });
    }

    function renderMessages(messages) {
        const $chatMessages = $('#chatMessages');

        if (!messages || messages.length === 0) {
            $chatMessages.html(`
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>No messages yet</h3>
                    <p>Start a conversation by typing a message below</p>
                </div>
            `);
            return;
        }

        $chatMessages.empty();

        messages.forEach(message => {
            const messageElement = createMessageElement(message);
            $chatMessages.append(messageElement);
        });

        scrollToBottom();
    }

    function createMessageElement(message) {
        const isUser = message.role === 'user';
        const time = message.formatted_time || formatDate(message.created_at);
        const currentProfileImage = $('#profileImg').attr('src') || userProfileImage;

        // Use profile picture for user, IsōkoAI logo for assistant
        const avatarContent = isUser ?
            `<img src="${currentProfileImage}" class="message-avatar-img" alt="User">` :
            `<img src="${aiLogoImage}" class="message-avatar-img" alt="IsōkoAI">`;

        return `
            <div class="message ${isUser ? 'user' : 'assistant'}">
                <div class="message-avatar">
                    ${avatarContent}
                </div>
                <div class="message-content">
                    <div class="message-text">${escapeHtml(message.content)}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
    }

    // ========================
    // MESSAGE SENDING
    // ========================
    function sendMessage() {
        const messageInput = $('#messageInput');
        const text = messageInput.val().trim();

        if (!text || isProcessing) return;

        // If no chat selected, create one
        if (!currentChatId) {
            createNewChat();
            setTimeout(() => {
                if (currentChatId) {
                    // Set the message back and send
                    $('#messageInput').val(text);
                    sendMessage();
                }
            }, 1000);
            return;
        }

        isProcessing = true;
        messageInput.val('');
        autoResizeTextarea();
        $('#sendBtn').prop('disabled', true);

        // Add user message immediately
        addMessageToChat({
            role: 'user',
            content: text,
            created_at: new Date().toISOString()
        });

        // Show typing indicator
        showTypingIndicator();

        // Send to server
        $.ajax({
            url: apiBaseUrl + '/chat_action.php',
            method: 'POST',
            data: {
                action: 'send_message',
                chat_id: currentChatId,
                user_id: userId,
                content: text
            },
            success: function(response) {
                hideTypingIndicator();
                $('#sendBtn').prop('disabled', false);
                
                if (response.success) {
                    // Add AI response
                    addMessageToChat({
                        role: 'assistant',
                        content: response.assistant,
                        created_at: new Date().toISOString()
                    });

                    // Update chats list
                    loadChats();

                    // Update chat title if it's the first message
                    if (response.is_first_message) {
                        $('#currentChatTitle').text(text.substring(0, 30) + (text.length > 30 ? '...' : ''));
                    }
                } else {
                    addMessageToChat({
                        role: 'assistant',
                        content: 'Error: ' + (response.message || 'Failed to get response'),
                        created_at: new Date().toISOString()
                    });
                }
                
                isProcessing = false;
            },
            error: function(xhr, status, error) {
                hideTypingIndicator();
                $('#sendBtn').prop('disabled', false);
                
                const errorMessage = handleAjaxError(xhr, status, error, 'sendMessage');
                addMessageToChat({
                    role: 'assistant',
                    content: errorMessage,
                    created_at: new Date().toISOString()
                });
                isProcessing = false;
            }
        });
    }

    function addMessageToChat(message) {
        const $chatMessages = $('#chatMessages');

        // Remove empty state if present
        if ($chatMessages.find('.empty-state').length) {
            $chatMessages.empty();
        }

        // Remove typing indicator
        $chatMessages.find('.typing-indicator').remove();

        // Add message
        const messageElement = createMessageElement(message);
        $chatMessages.append(messageElement);

        scrollToBottom();
    }

    function showTypingIndicator() {
        const $chatMessages = $('#chatMessages');

        // Remove empty state if present
        if ($chatMessages.find('.empty-state').length) {
            $chatMessages.empty();
        }

        $chatMessages.append(`
            <div class="message assistant typing-indicator">
                <div class="message-avatar">
                    <img src="${aiLogoImage}" class="message-avatar-img" alt="IsōkoAI">
                </div>
                <div class="message-content">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `);

        scrollToBottom();
    }

    function hideTypingIndicator() {
        $('#chatMessages .typing-indicator').remove();
    }

    // ========================
    // UI CONTROLS
    // ========================
    function handleKeyPress(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }

        // Auto-resize textarea
        setTimeout(autoResizeTextarea, 0);
    }

    function autoResizeTextarea() {
        const textarea = $('#messageInput')[0];
        if (textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
        }
    }

    function clearChat() {
        if (!currentChatId) return;

        if (confirm('Are you sure you want to clear this chat? This will remove all messages.')) {
            $('#chatMessages').html(`
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Chat cleared</h3>
                    <p>Start a new conversation</p>
                </div>
            `);

            showNotification('Chat cleared', 'info');
        }
    }

    function shareChat() {
        if (!currentChatId) return;

        // Copy chat URL to clipboard
        const chatUrl = `${window.location.origin}/chat/${currentChatId}`;
        navigator.clipboard.writeText(chatUrl)
            .then(() => showNotification('Chat link copied to clipboard!', 'success'))
            .catch(() => showNotification('Failed to copy link', 'error'));
    }

    function deleteChat(chatId, event) {
        if (event) event.stopPropagation();

        if (confirm('Are you sure you want to delete this chat? This action cannot be undone.')) {
            $.ajax({
                url: apiBaseUrl + '/chat_action.php',
                method: 'POST',
                data: {
                    action: 'delete_chat',
                    chat_id: chatId,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Chat deleted', 'success');
                        loadChats();

                        // If current chat was deleted, clear the chat area
                        if (currentChatId === chatId) {
                            currentChatId = null;
                            $('#chatMessages').html(`
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <h3>Select a chat</h3>
                                    <p>Choose a conversation from the sidebar or create a new one</p>
                                </div>
                            `);
                            $('#currentChatTitle').text('Start a new conversation');
                            $('#clearBtn').prop('disabled', true);
                            $('#shareBtn').prop('disabled', true);
                        }
                    } else {
                        showNotification('Failed to delete chat: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    handleAjaxError(xhr, status, error, 'deleteChat');
                    showNotification('Failed to delete chat', 'error');
                }
            });
        }
    }

    function refreshChats() {
        loadChats();
        showNotification('Chats refreshed', 'info');
    }

    // ========================
    // PROFILE MANAGEMENT
    // ========================
    $('#profileUpload').change(function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Image must be less than 5MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            $('#profileImg').attr('src', e.target.result);
            uploadProfileImage(file);
        };
        reader.readAsDataURL(file);
    });

    function updateProfilePictureInChat(newImageSrc) {
        // Update all user message avatars with new profile picture
        $('#chatMessages .message.user .message-avatar').each(function() {
            $(this).html(`<img src="${newImageSrc}" class="message-avatar-img" alt="User">`);
        });
    }

    function uploadProfileImage(file) {
        const formData = new FormData();
        formData.append('profile', file);
        formData.append('user_id', userId);

        $.ajax({
            url: apiBaseUrl + '/upload_profile.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 60000,
            success: function(response) {
                if (response.success) {
                    const newImageSrc = response.image_url || $('#profileImg').attr('src');
                    $('#profileImg').attr('src', newImageSrc);
                    updateProfilePictureInChat(newImageSrc);
                    showNotification('Profile picture updated!', 'success');
                } else {
                    showNotification(response.message || 'Upload failed', 'error');
                }
            },
            error: function(xhr) {
                const errorMsg = handleAjaxError(xhr, 'error', '', 'uploadProfile');
                showNotification('Upload failed: ' + errorMsg, 'error');
            }
        });
    }

    // ========================
    // MODAL FUNCTIONS
    // ========================
    function showModal(modalId) {
        $('#' + modalId).fadeIn();
        $('body').css('overflow', 'hidden');
    }

    function closeModal() {
        $('.modal').fadeOut();
        $('body').css('overflow', 'auto');
    }

    // ========================
    // UTILITY FUNCTIONS
    // ========================
    function formatDate(dateString) {
        if (!dateString) return 'Just now';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Just now';
        
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: diffDays < 365 ? undefined : 'numeric'
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function scrollToBottom() {
        const $chatMessages = $('#chatMessages');
        $chatMessages.scrollTop($chatMessages[0].scrollHeight);
    }

    function handleAjaxError(xhr, status, error, context = 'unknown') {
        console.error(`AJAX Error (${context}):`, {
            status: status,
            error: error,
            xhrStatus: xhr.status,
            response: xhr.responseText?.substring(0, 200),
            url: xhr.responseURL
        });
        
        let errorMessage = 'Network error. Please check your connection.';
        
        if (status === 'timeout') {
            errorMessage = 'Request timeout. Please try again.';
        } else if (xhr.status === 0) {
            errorMessage = 'Cannot connect to server. Check your internet connection.';
        } else if (xhr.status === 401) {
            errorMessage = 'Session expired. Please refresh the page.';
            setTimeout(() => window.location.reload(), 2000);
        } else if (xhr.status === 403) {
            errorMessage = 'Access denied.';
        } else if (xhr.status === 404) {
            errorMessage = 'API endpoint not found.';
        } else if (xhr.status === 500) {
            errorMessage = 'Server error. Please try again later.';
        } else if (xhr.responseText) {
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {
                // Not JSON, keep default message
            }
        }
        
        return errorMessage;
    }

    function showNotification(message, type = 'info', duration = 3000) {
        // Remove existing notifications
        $('.custom-notification').remove();
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const colors = {
            success: '#00ff88',
            error: '#ff6b6b',
            warning: '#ffaa00',
            info: '#00d4ff'
        };
        
        const notification = $(`
            <div class="custom-notification" style="
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 10px;
                background: rgba(0, 0, 0, 0.9);
                color: ${colors[type] || colors.info};
                border-left: 4px solid ${colors[type] || colors.info};
                backdrop-filter: blur(10px);
                z-index: 10000;
                max-width: 400px;
                word-wrap: break-word;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                animation: slideInRight 0.3s ease-out;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
            ">
                <i class="fas ${icons[type] || icons.info}" style="font-size: 1.2em;"></i>
                <span>${message}</span>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-remove after duration
        setTimeout(() => {
            notification.css('animation', 'slideOutRight 0.3s ease-out');
            setTimeout(() => notification.remove(), 300);
        }, duration);
        
        // Add CSS animations if not present
        if (!$('#notification-animations').length) {
            $('<style id="notification-animations">')
                .text(`
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOutRight {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `)
                .appendTo('head');
        }
    }

    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        showNotification('Theme toggled', 'info');
    }
    
    // ========================
    // ERROR HANDLING
    // ========================
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        // Don't show notification for aborted requests or specific URLs
        if (thrownError === 'abort' || settings.url.includes('test.php')) {
            return;
        }
        
        if (settings.url.includes('api/')) {
            console.error('Global AJAX Error:', {
                url: settings.url,
                status: jqxhr.status,
                error: thrownError
            });
        }
    });
    
    // ========================
    // DEBUG HELPER
    // ========================
    function debugApiCall(endpoint, data = {}) {
        console.log(`Testing API: ${apiBaseUrl}/${endpoint}`);
        $.ajax({
            url: apiBaseUrl + '/' + endpoint,
            method: 'GET',
            data: data,
            success: function(response) {
                console.log(`✅ ${endpoint} Success:`, response);
            },
            error: function(xhr) {
                console.error(`❌ ${endpoint} Failed:`, xhr.status, xhr.statusText);
            }
        });
    }
    
    // Test all endpoints (for debugging)
    // debugApiCall('test.php');
    // debugApiCall('fetch_chats.php', {user_id: userId});
</script>

</body>

</html>