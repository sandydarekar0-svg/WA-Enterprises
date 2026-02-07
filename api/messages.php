<?php
require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'send') {
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Deduct credits
    $cost = 1; // 1 credit per message
    
    if ($user['credits'] < $cost) {
        json_response(false, 'Insufficient credits');
    }
    
    // Send via WhatsApp API
    $result = send_whatsapp_message($phone, $message);
    
    // Save to database
    $conn->query("INSERT INTO messages (user_id, phone, message, status) VALUES ({$user['id']}, '$phone', '$message', 'sent')");
    
    // Deduct credits
    $conn->query("UPDATE users SET credits = credits - $cost WHERE id = {$user['id']}");
    
    json_response(true, 'Message sent', $result);
}

if ($action === 'list') {
    $result = $conn->query("SELECT * FROM messages WHERE user_id = {$user['id']} ORDER BY created_at DESC LIMIT 100");
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    json_response(true, 'Messages', $messages);
}

json_response(false, 'Invalid action');
?>
