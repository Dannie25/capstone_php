<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle reply submission
if ($_POST && isset($_POST['send_reply'])) {
    $user_id = $_POST['user_id'];
    $message = trim($_POST['message']);
    if (!empty($message) && !empty($user_id)) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender_type, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $user_id, $message);
        if ($stmt->execute()) {
            $success_message = "Reply sent successfully!";
        }
    }
}

// Get all customers with messages
$customers_query = "SELECT DISTINCT cm.user_id, u.name as customer_name, u.email,
                           (SELECT COUNT(*) FROM chat_messages WHERE user_id = cm.user_id AND sender_type = 'customer' AND is_read = FALSE) as unread_count,
                           (SELECT message FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC LIMIT 1) as last_message,
                           (SELECT created_at FROM chat_messages WHERE user_id = cm.user_id ORDER BY created_at DESC LIMIT 1) as last_message_time
                    FROM chat_messages cm 
                    LEFT JOIN users u ON cm.user_id = u.id 
                    ORDER BY last_message_time DESC";
$customers_result = $conn->query($customers_query);
$customers = $customers_result ? $customers_result->fetch_all(MYSQLI_ASSOC) : [];

// Get selected customer's messages
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$messages = [];
$selected_customer = null;

if ($selected_user_id > 0) {
    // Get customer info
    $customer_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $selected_customer = $stmt->get_result()->fetch_assoc();
    
    // Get messages
    $messages_query = "SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mark customer messages as read
    $conn->query("UPDATE chat_messages SET is_read = TRUE WHERE user_id = $selected_user_id AND sender_type = 'customer'");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Chat - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c8d99a 100%);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-title {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }
        
        .back-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background-color: #4a5a36;
            color: white;
            transform: translateY(-2px);
        }
        
        .chat-container {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            height: 600px;
        }
        
        .customers-sidebar {
            background: var(--light-gray);
            border-right: 1px solid #dee2e6;
            height: 100%;
            overflow-y: auto;
        }
        
        .customer-item {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.3s;
            position: relative;
        }
        
        .customer-item:hover {
            background-color: #e9ecef;
        }
        
        .customer-item.active {
            background-color: var(--secondary-color);
        }
        
        .customer-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .last-message {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .unread-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-area {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: var(--secondary-color);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .chat-title {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fafafa;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .message.customer {
            justify-content: flex-start;
        }
        
        .message.admin {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message.customer .message-bubble {
            background: var(--white);
            color: #333;
            border: 1px solid #dee2e6;
            border-bottom-left-radius: 5px;
        }
        
        .message.admin .message-bubble {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message-time-stamp {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
            text-align: center;
        }
        
        .message-form {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background: var(--white);
        }
        
        .message-input {
            border: 1px solid #dee2e6;
            border-radius: 25px;
            padding: 12px 20px;
            resize: none;
        }
        
        .message-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(91, 107, 70, 0.25);
        }
        
        .send-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .send-btn:hover {
            background-color: #4a5a36;
            border-color: #4a5a36;
            transform: scale(1.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                height: 500px;
            }
            
            .customers-sidebar {
                height: auto;
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="admin-title">
                    <i class="fas fa-comments me-3"></i>Customer Chat
                </h1>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="chat-container">
            <div class="row g-0 h-100">
                <!-- Customers Sidebar -->
                <div class="col-md-4">
                    <div class="customers-sidebar">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0">Customer Messages</h5>
                        </div>
                        <?php if (empty($customers)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No messages yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <div class="customer-item <?php echo $customer['user_id'] == $selected_user_id ? 'active' : ''; ?>" 
                                     onclick="window.location.href='customer_chat.php?user_id=<?php echo $customer['user_id']; ?>'">
                                    <?php if ($customer['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?php echo $customer['unread_count']; ?></div>
                                    <?php endif; ?>
                                    <div class="customer-name"><?php echo htmlspecialchars($customer['customer_name'] ?: 'Unknown Customer'); ?></div>
                                    <div class="last-message"><?php echo htmlspecialchars($customer['last_message']); ?></div>
                                    <div class="message-time"><?php echo date('M j, g:i A', strtotime($customer['last_message_time'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div class="col-md-8">
                    <div class="chat-area">
                        <?php if ($selected_customer): ?>
                            <div class="chat-header">
                                <h4 class="chat-title">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($selected_customer['name']); ?>
                                </h4>
                                <small class="text-muted"><?php echo htmlspecialchars($selected_customer['email']); ?></small>
                            </div>
                            
                            <div class="chat-messages" id="chatMessages">
                                <?php if (empty($messages)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-comments empty-icon"></i>
                                        <h4>No messages yet</h4>
                                        <p>Start a conversation with this customer</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <div class="message <?php echo $message['sender_type']; ?>">
                                            <div class="message-bubble">
                                                <?php echo htmlspecialchars($message['message']); ?>
                                                <div class="message-time-stamp">
                                                    <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" class="message-form">
                                <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
                                <div class="row align-items-end">
                                    <div class="col">
                                        <textarea name="message" class="form-control message-input" 
                                                placeholder="Type your reply here..." 
                                                rows="2" required></textarea>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" name="send_reply" class="btn btn-primary send-btn">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comments empty-icon"></i>
                                <h4>Select a Customer</h4>
                                <p>Choose a customer from the sidebar to start chatting</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // Scroll to bottom on page load
        document.addEventListener('DOMContentLoaded', scrollToBottom);
        
        // Auto-refresh every 10 seconds
        setInterval(function() {
            if (window.location.search.includes('user_id=')) {
                location.reload();
            }
        }, 10000);
        
        // Handle Enter key to send message
        const messageInput = document.querySelector('textarea[name="message"]');
        if (messageInput) {
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.closest('form').submit();
                }
            });
        }
    </script>
</body>
</html>
