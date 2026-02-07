<?php
require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'stripe_intent') {
    $amount = $_POST['amount'] ?? 0;
    $credits = $_POST['credits'] ?? 0;
    
    // Create transaction record
    $conn->query("INSERT INTO transactions (user_id, amount, credits, provider) VALUES ({$user['id']}, $amount, $credits, 'stripe')");
    
    json_response(true, 'Payment intent created', ['amount' => $amount, 'credits' => $credits]);
}

if ($action === 'stripe_confirm') {
    $transaction_id = $_POST['transaction_id'] ?? 0;
    
    // Update transaction
    $conn->query("UPDATE transactions SET status = 'completed' WHERE id = $transaction_id");
    
    // Get transaction details
    $tx = $conn->query("SELECT * FROM transactions WHERE id = $transaction_id")->fetch_assoc();
    
    // Add credits
    $conn->query("UPDATE users SET credits = credits + {$tx['credits']} WHERE id = {$user['id']}");
    
    json_response(true, 'Payment confirmed');
}

if ($action === 'balance') {
    json_response(true, 'Balance', ['balance' => $user['credits']]);
}

json_response(false, 'Invalid action');
?>
