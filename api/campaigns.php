<?php
require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'create') {
    $name = $_POST['campaign_name'] ?? '';
    $template_id = $_POST['template_id'] ?? 0;
    
    $query = "INSERT INTO campaigns (user_id, campaign_name, template_id, status) VALUES ({$user['id']}, '$name', '$template_id', 'draft')";
    
    if ($conn->query($query)) {
        $campaign_id = $conn->insert_id;
        json_response(true, 'Campaign created', ['campaign_id' => $campaign_id]);
    } else {
        json_response(false, 'Error: ' . $conn->error);
    }
}

if ($action === 'add_contacts') {
    $campaign_id = $_POST['campaign_id'] ?? 0;
    $contacts = json_decode($_POST['contacts'] ?? '[]', true);
    
    foreach ($contacts as $contact) {
        $phone = $contact['phone'] ?? '';
        $conn->query("INSERT INTO campaign_contacts (campaign_id, phone, status) VALUES ($campaign_id, '$phone', 'pending')");
    }
    
    $count = count($contacts);
    $conn->query("UPDATE campaigns SET total_contacts = total_contacts + $count WHERE id = $campaign_id");
    
    json_response(true, 'Contacts added', ['count' => $count]);
}

if ($action === 'send') {
    $campaign_id = $_POST['campaign_id'] ?? 0;
    
    // Get campaign
    $campaign = $conn->query("SELECT * FROM campaigns WHERE id = $campaign_id")->fetch_assoc();
    
    // Get template
    $template = $conn->query("SELECT * FROM templates WHERE id = {$campaign['template_id']}")->fetch_assoc();
    
    // Get contacts
    $contacts = $conn->query("SELECT * FROM campaign_contacts WHERE campaign_id = $campaign_id AND status = 'pending'");
    
    $sent = 0;
    while ($contact = $contacts->fetch_assoc()) {
        $phone = $contact['phone'];
        $message = $template['template_content'];
        
        // Send
        send_whatsapp_message($phone, $message);
        
        // Update status
        $conn->query("UPDATE campaign_contacts SET status = 'sent' WHERE id = {$contact['id']}");
        
        $sent++;
    }
    
    // Update campaign
    $conn->query("UPDATE campaigns SET sent_count = sent_count + $sent, status = 'completed' WHERE id = $campaign_id");
    
    json_response(true, 'Campaign sent', ['sent' => $sent]);
}

if ($action === 'list') {
    $result = $conn->query("SELECT * FROM campaigns WHERE user_id = {$user['id']} ORDER BY created_at DESC");
    $campaigns = $result->fetch_all(MYSQLI_ASSOC);
    json_response(true, 'Campaigns', $campaigns);
}

json_response(false, 'Invalid action');
?>
