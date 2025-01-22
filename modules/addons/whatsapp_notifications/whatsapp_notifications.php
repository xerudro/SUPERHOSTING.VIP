<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function whatsapp_notifications_config() {
    return [
        'name' => 'WhatsApp Notifications',
        'description' => 'Sends WhatsApp notifications for new orders',
        'version' => '1.0',
        'author' => 'SuperHosting',
        'fields' => [
            'whatsapp_token' => [
                'FriendlyName' => 'WhatsApp Business API Token',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Enter your WhatsApp Business API token',
            ],
            'phone_number' => [
                'FriendlyName' => 'Your Phone Number',
                'Type' => 'text',
                'Size' => '20',
                'Description' => 'Enter your phone number with country code (e.g., +401234567890)',
            ],
        ]
    ];
}

function whatsapp_notifications_activate() {
    // Create necessary database tables if needed
    return [
        'status' => 'success',
        'description' => 'WhatsApp Notifications module activated successfully.',
    ];
}

function whatsapp_notifications_deactivate() {
    return [
        'status' => 'success',
        'description' => 'WhatsApp Notifications module deactivated successfully.',
    ];
}

// Hook into WHMCS order placement
add_hook('OrderPaid', 1, function($vars) {
    // Get module settings
    $moduleSettings = whatsapp_get_settings();
    
    // Order details
    $orderid = $vars['orderid'];
    $userid = $vars['userid'];
    $amount = $vars['amount'];
    
    // Get client details
    $client = localAPI('GetClientsDetails', ['clientid' => $userid]);
    $clientName = $client['client']['firstname'] . ' ' . $client['client']['lastname'];
    $clientEmail = $client['client']['email'];
    $clientPhone = $client['client']['phonenumber'] ? $client['client']['phonenumber'] : 'Not provided';
    
    // Get order details
    $order = localAPI('GetOrders', ['orderid' => $orderid]);
    $orderDetails = $order['orders']['order'][0];
    
    // Format currency with symbol
    $currency = localAPI('GetCurrencies', []);
    $currencySymbol = '$'; // Default
    foreach ($currency['currencies']['currency'] as $curr) {
        if ($curr['id'] == $orderDetails['currencyid']) {
            $currencySymbol = $curr['prefix'] ? $curr['prefix'] : $curr['suffix'];
            break;
        }
    }
    
    // Prepare message
    $message = "🔔 *New Order Alert!*\n\n";
    $message .= "*Order Details:*\n";
    $message .= "Order ID: #" . $orderid . "\n";
    $message .= "Amount: " . $currencySymbol . $amount . "\n\n";
    
    $message .= "*Client Details:*\n";
    $message .= "Name: " . $clientName . "\n";
    $message .= "Email: " . $clientEmail . "\n";
    $message .= "Phone: " . $clientPhone . "\n\n";
    
    $message .= "*Products Ordered:*\n";
    foreach ($orderDetails['lineitems']['lineitem'] as $item) {
        $message .= "• " . $item['product'] . "\n";
        if (!empty($item['configoptions'])) {
            foreach ($item['configoptions'] as $option) {
                $message .= "  - " . $option['name'] . ": " . $option['value'] . "\n";
            }
        }
    }
    
    // Add payment method
    $message .= "\n*Payment Method:* " . $orderDetails['paymentmethod'] . "\n";
    
    // Add timestamp
    $message .= "\n📅 " . date('Y-m-d H:i:s') . "\n";
    
    // Send WhatsApp message
    sendWhatsAppMessage($moduleSettings['whatsapp_token'], $moduleSettings['phone_number'], $message);
});

function whatsapp_get_settings() {
    $settings = [];
    $results = Capsule::table('tbladdonmodules')
        ->where('module', 'whatsapp_notifications')
        ->get();
        
    foreach ($results as $row) {
        $settings[$row->setting] = $row->value;
    }
    return $settings;
}

function whatsapp_add_order_note($orderId, $notes) {
    Capsule::table('tblorders_notes')->insert([
        'orderid' => (int)$orderId,
        'notes' => $notes,
        'created' => Capsule::raw('NOW()')
    ]);
}

function sendWhatsAppMessage($token, $to, $message) {
    $url = 'https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID/messages';
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => [
            'body' => $message,
            'preview_url' => false
        ]
    ];
    
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the response
    logActivity("WhatsApp Notification Response (HTTP $httpCode): " . $response);
    
    // Log failed notifications
    if ($httpCode !== 200) {
        logActivity("Failed to send WhatsApp notification for message: " . $message);
    }
}
