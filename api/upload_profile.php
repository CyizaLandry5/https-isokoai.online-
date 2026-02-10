<?php
// api/upload_profile.php
session_start();
header('Content-Type: application/json');
require '../db_connection.php';

$user_id = intval($_POST['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user']);
    exit;
}

if (empty($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file or upload error']);
    exit;
}

$allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/avif'];
$mime = mime_content_type($_FILES['profile']['tmp_name']);
if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
    exit;
}

$ext = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
$dir = __DIR__ . '/../uploads';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$dest = $dir . '/' . $filename;
if (!move_uploaded_file($_FILES['profile']['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move file']);
    exit;
}

// update DB
$stmt = $conn->prepare("UPDATE users SET profile_image=? WHERE id=?");
$stmt->bind_param("si", $filename, $user_id);
$stmt->execute();

echo json_encode(['success' => true, 'url' => 'uploads/' . $filename]);
