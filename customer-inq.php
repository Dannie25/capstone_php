<?php 
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$current_inquiry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$success_message = '';
$error_message = '';
$selectedInquiry = null;
$conversation = [];
$inquiries = [];

// If no inquiry is selected, create one automatically
if ($current_inquiry_id === 0) {
    // Create a new inquiry automatically
    $category = 'General Inquiry';
    $message = 'Hello, I have a question.';
    
    $stmt = $conn->prepare("INSERT INTO customer_inquiries (user_id, user_name, user_email, category, message, status) VALUES (?, ?, ?, ?, ?, 'processing')");
    $stmt->bind_param("issss", $user_id, $user_name, $user_email, $category, $message);
    
    if ($stmt->execute()) {
        $inquiry_id = $conn->insert_id;
        // Save first message
        $stmt2 = $conn->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'customer', ?)");
        $stmt2->bind_param("is", $inquiry_id, $message);
        $stmt2->execute();
        
        // Redirect to the new inquiry
        header("Location: customer-inq.php?id=" . $inquiry_id);
        exit();
    }
}

// Get user's current inquiry and conversation
if ($current_inquiry_id > 0) {
    // Get inquiry details
    $stmt = $conn->prepare("SELECT * FROM customer_inquiries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $current_inquiry_id, $user_id);
    $stmt->execute();
    $selectedInquiry = $stmt->get_result()->fetch_assoc();
    
    if ($selectedInquiry) {
        // Get conversation
        $stmt = $conn->prepare("SELECT * FROM inquiry_messages WHERE inquiry_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $current_inquiry_id);
        $stmt->execute();
        $conversation = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Get all user's inquiries with unread counts
$inquiries_query = "SELECT ci.*, 
                  (SELECT COUNT(*) FROM inquiry_messages 
                   WHERE inquiry_id = ci.id AND sender_type = 'admin' AND is_read = 0) as unread_count
                  FROM customer_inquiries ci 
                  WHERE ci.user_id = ?
                  ORDER BY ci.created_at DESC";
$stmt = $conn->prepare($inquiries_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    $inquiry_id = (int)$_POST['inquiry_id'];
    
    if (!empty($message) && $inquiry_id > 0) {
        // Save message
        $stmt = $conn->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'customer', ?)");
        $stmt->bind_param("is", $inquiry_id, $message);
        
        if ($stmt->execute()) {
            // Update inquiry status
            $update = $conn->prepare("UPDATE customer_inquiries SET status = 'processing' WHERE id = ?");
            $update->bind_param("i", $inquiry_id);
            $update->execute();
            
            // Redirect to prevent form resubmission
            header("Location: customer-inq.php?id=" . $inquiry_id);
            exit();
        }
    }
}

// Get success/error messages from session
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
$selectedInquiry = null;
$conversation = [];
$inquiries = [];
$unread_counts = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_inquiry'])) {
        // Handle new inquiry
        $category = trim($_POST['category']);
        $message = trim($_POST['message']);
        
        if (!empty($category) && !empty($message)) {
            $stmt = $conn->prepare("INSERT INTO customer_inquiries (user_id, user_name, user_email, category, message, status) VALUES (?, ?, ?, ?, ?, 'processing')");
            $stmt->bind_param("issss", $user_id, $user_name, $user_email, $category, $message);
            
            if ($stmt->execute()) {
                $inquiry_id = $conn->insert_id;
                // Save first message
                $stmt2 = $conn->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'customer', ?)");
                $stmt2->bind_param("is", $inquiry_id, $message);
                $stmt2->execute();
                
                $_SESSION['success'] = "Your inquiry has been submitted successfully!";
                header("Location: customer-inq.php?id=" . $inquiry_id);
                exit();
            } else {
                $error_message = "Error submitting inquiry. Please try again.";
            }
        } else {
            $error_message = "Please fill in all fields.";
        }
    } 
    elseif (isset($_POST['send_message'])) {
        // Handle new message
        $inquiry_id = (int)$_POST['inquiry_id'];
        $message = trim($_POST['message']);
        
        if (!empty($message) && $inquiry_id > 0) {
            // Verify inquiry belongs to user
            $check = $conn->prepare("SELECT id FROM customer_inquiries WHERE id = ? AND user_id = ?");
            $check->bind_param("ii", $inquiry_id, $user_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                // Update status and save message
                $update = $conn->prepare("UPDATE customer_inquiries SET status = 'processing' WHERE id = ?");
                $update->bind_param("i", $inquiry_id);
                $update->execute();
                
                $stmt = $conn->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_type, message) VALUES (?, 'customer', ?)");
                $stmt->bind_param("is", $inquiry_id, $message);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Message sent!";
                    header("Location: customer-inq.php?id=" . $inquiry_id);
                    exit();
                } else {
                    $error_message = "Error sending message. Please try again.";
                }
            } else {
                $error_message = "Invalid inquiry.";
            }
        } else {
            $error_message = "Message cannot be empty.";
        }
    }
}

// Get current inquiry and conversation
if ($current_inquiry_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM customer_inquiries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $current_inquiry_id, $user_id);
    $stmt->execute();
    $selectedInquiry = $stmt->get_result()->fetch_assoc();
    
    if ($selectedInquiry) {
        // Mark admin messages as read
        $update = $conn->prepare("UPDATE inquiry_messages SET is_read = 1 WHERE inquiry_id = ? AND sender_type = 'admin' AND is_read = 0");
        $update->bind_param("i", $current_inquiry_id);
        $update->execute();
        
        // Get conversation with proper ordering
        $stmt = $conn->prepare("SELECT * FROM inquiry_messages WHERE inquiry_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $current_inquiry_id);
        $stmt->execute();
        $conversation = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get conversation
        $stmt = $conn->prepare("SELECT * FROM inquiry_messages WHERE inquiry_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $current_inquiry_id);
        $stmt->execute();
        $conversation = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $current_inquiry_id = 0;
    }
}

// Get all user's inquiries with unread counts
$inquiries_query = "SELECT ci.*, 
                  (SELECT COUNT(*) FROM inquiry_messages 
                   WHERE inquiry_id = ci.id AND sender_type = 'admin' AND is_read = 0) as unread_count
                  FROM customer_inquiries ci 
                  WHERE ci.user_id = ?
                  ORDER BY ci.created_at DESC";
$stmt = $conn->prepare($inquiries_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unread counts
if (!empty($inquiries)) {
    $inquiry_ids = array_column($inquiries, 'id');
    $placeholders = rtrim(str_repeat('?,', count($inquiry_ids)), ',');
    
    $stmt = $conn->prepare("SELECT inquiry_id, COUNT(*) as unread_count 
                           FROM inquiry_messages 
                           WHERE inquiry_id IN ($placeholders) 
                           AND sender_type = 'admin' 
                           AND is_read = 0
                           GROUP BY inquiry_id");
    
    $types = str_repeat('i', count($inquiry_ids));
    $stmt->bind_param($types, ...$inquiry_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $unread_counts[$row['inquiry_id']] = $row['unread_count'];
    }
}

// Get messages from session
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Customer Support - MTC Clothing</title>
  <?php include 'header.php'; ?>
  <style>
    /* Basic Reset */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; }
    
    /* Chat Container */
    .chat-container {
      display: flex;
      flex-direction: column;
      max-width: 800px;
      margin: 0 auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      height: 70vh;
      overflow: hidden;
    }
    
    /* Chat Header */
    .chat-header {
      padding: 15px 20px;
      background: #5b6b46;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    /* Chat Messages */
    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #f9f9f9;
    }
    
    /* Message Styling */
    .message {
      margin-bottom: 15px;
      max-width: 80%;
      clear: both;
    }
    
    .message.sent {
      float: right;
      margin-left: auto;
    }
    
    .message.received {
      float: left;
      margin-right: auto;
    }
    
    .message-content {
      padding: 10px 15px;
      border-radius: 18px;
      position: relative;
      word-wrap: break-word;
    }
    
    .sent .message-content {
      background: #5b6b46;
      color: white;
      border-top-right-radius: 4px;
    }
    
    .received .message-content {
      background: #e9ecef;
      color: #333;
      border-top-left-radius: 4px;
    }
    
    .message-time {
      font-size: 0.7rem;
      color: #777;
      margin-top: 4px;
      text-align: right;
    }
    
    .admin-badge {
      background: #dc3545;
      color: white;
      font-size: 0.6rem;
      padding: 2px 6px;
      border-radius: 10px;
      margin-left: 8px;
    }
    
    /* Message Input */
    .message-input {
      display: flex;
      padding: 15px;
      background: #fff;
      border-top: 1px solid #eee;
    }
    
    .message-input input {
      flex: 1;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 20px;
      outline: none;
      font-family: 'Poppins', sans-serif;
    }
    
    .message-input button {
      background: #5b6b46;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 0 20px;
      margin-left: 10px;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .message-input button:hover {
      background: #3e4a32;
    }
    
    /* Layout */
    .chat-container {
      display: grid;
      grid-template-columns: 1fr;
      height: 70vh;
      max-width: 800px;
      margin: 0 auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    .inquiry-item:hover { background: #f9f9f9; }
    .inquiry-item.active { background: #f0f4e8; border-left: 3px solid #5b6b46; }
    .unread-count { position: absolute; top: 10px; right: 15px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; }
    
    /* Chat Area */
    .chat-area { flex: 1; display: flex; flex-direction: column; }
    .chat-header { padding: 1rem; border-bottom: 1px solid #e0e0e0; }
    .chat-messages { flex: 1; padding: 1rem; overflow-y: auto; }
    .message { margin-bottom: 1rem; display: flex; flex-direction: column; }
    .message.received { align-items: flex-start; }
    .message.sent { align-items: flex-end; }
    .message-bubble { max-width: 70%; padding: 0.75rem 1rem; border-radius: 18px; margin-bottom: 0.25rem; }
    .received .message-bubble { background: #f0f0f0; border-top-left-radius: 4px; }
    .sent .message-bubble { background: #5b6b46; color: white; border-top-right-radius: 4px; }
    .message-time { font-size: 0.7rem; color: #999; }
    .chat-input { padding: 1rem; border-top: 1px solid #e0e0e0; }
    .message-form { display: flex; gap: 0.5rem; }
    .message-input { flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 24px; resize: none; min-height: 48px; outline: none; }
    .send-btn { background: #5b6b46; color: white; border: none; border-radius: 50%; width: 48px; height: 48px; cursor: pointer; }
    
    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
    .modal-content { background: white; border-radius: 8px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
    .modal-header { padding: 1rem; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 1.5rem; }
    .form-group { margin-bottom: 1rem; }
    .form-control { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
    textarea.form-control { min-height: 120px; resize: vertical; }
    .modal-footer { padding: 1rem; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 0.5rem; }
    .btn { padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
    .btn-primary { background: #5b6b46; color: white; border: 1px solid #5b6b46; }
    .btn-outline { background: white; border: 1px solid #ddd; }
    
    /* Alerts */
    .alert { padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 4px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    /* Status Badges */
    .status-badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: capitalize;
    }
    
    .status-processing {
      background: #fff3cd;
      color: #856404;
    }
    
    .status-accepted {
      background: #d1ecf1;
      color: #0c5460;
    }
    
    .status-delivered {
      background: #d4edda;
      color: #155724;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .chat-container { flex-direction: column; height: auto; }
      .chat-sidebar { width: 100%; max-height: 300px; border-right: none; border-bottom: 1px solid #e0e0e0; }
      .chat-area { height: 60vh; }
      .message-bubble { max-width: 85%; }
    }
  </style>
</head>
<body>
  <div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 20px;">
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-bottom: 2.5rem;">
      <h1 style="margin: 0 0 0.5rem 0; font-size: 2.2rem; color: #333;">Customer Support</h1>
      <p style="margin: 0; color: #666; font-size: 1.1rem;">Need help? Contact our support team or check your existing inquiries.</p>
    </div>

    <div class="chat-container" style="grid-template-columns: 1fr; max-width: 1000px; margin: 0 auto; height: 80vh;">
        <!-- Chat Area -->
        <div class="chat-area" style="display: flex; flex-direction: column; height: 100%; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="chat-header">
              <div>
                <h3><?php echo htmlspecialchars($selectedInquiry['category']); ?></h3>
                <p>Inquiry #<?php echo $selectedInquiry['id']; ?></p>
              </div>
              <span class="status-badge status-<?php echo $selectedInquiry['status']; ?>">
                <?php echo ucfirst($selectedInquiry['status']); ?>
              </span>
            </div>
            
            <div class="chat-messages" id="chatMessages" style="flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; min-height: 0;">
              <div style="flex: 1;">
                <?php if (!empty($conversation)): ?>
                  <?php foreach ($conversation as $message): ?>
                    <div class="message <?php echo $message['sender_type'] === 'customer' ? 'sent' : 'received'; ?>" 
                         data-message-id="<?php echo $message['id']; ?>">
                      <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                      </div>
                      <div class="message-time">
                        <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                        <?php if ($message['sender_type'] === 'admin'): ?>
                          <span class="admin-badge">Admin</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-comment-dots" style="font-size: 2rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                    <p>No messages yet. Send a message to start the conversation!</p>
                  </div>
                <?php endif; ?>
              </div>
              
              <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #eee;">
                <form class="message-form" id="messageForm" method="POST">
                  <input type="hidden" name="inquiry_id" value="<?php echo $selectedInquiry['id']; ?>">
                  <div class="message-input" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="chatMessageInput" name="message" placeholder="Type your message..." list="chatSuggestions" autocomplete="on" required style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 20px; outline: none;">
                    <datalist id="chatSuggestions">
                      <option value="Hello! I need help with my order."></option>
                      <option value="What is the status of my order?"></option>
                      <option value="Can I change my delivery address?"></option>
                      <option value="I want to cancel my order."></option>
                      <option value="How do I return an item?"></option>
                      <option value="Do you have this item in stock?"></option>
                      <option value="Can I customize my order?"></option>
                      <option value="What are the payment options?"></option>
                      <option value="Can you provide an estimated delivery date?"></option>
                      <option value="Thank you!"></option>
                    </datalist>
                    <button type="submit" name="send_message" style="background: #5b6b46; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                      <i class="fas fa-paper-plane"></i>
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>

  <script>
    // Global variables
    let lastMessageId = <?php echo !empty($conversation) ? end($conversation)['id'] : 0; ?>;
    let currentInquiryId = <?php echo $current_inquiry_id; ?>;
    let pollInterval;
    
    // Scroll to bottom of chat
    function scrollToBottom() {
      const chatMessages = document.querySelector('.chat-messages');
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Add message to chat
    function addMessageToChat(message) {
      const chatMessages = document.querySelector('.chat-messages');
      
      // Check if message already exists
      if (document.querySelector(`[data-message-id="${message.id}"]`)) {
        return;
      }
      
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${message.sender_type === 'customer' ? 'sent' : 'received'}`;
      messageDiv.setAttribute('data-message-id', message.id);
      
      // Format message with line breaks
      const messageContent = message.message.replace(/\n/g, '<br>');
      
      messageDiv.innerHTML = `
        <div class="message-content">${messageContent}</div>
        <div class="message-time">
          ${formatMessageTime(message.created_at)}
          ${message.sender_type === 'admin' ? '<span class="admin-badge">Admin</span>' : ''}
        </div>
      `;
      
      chatMessages.appendChild(messageDiv);
      scrollToBottom();
    }
    
    // Format message time
    function formatMessageTime(timestamp) {
      const date = new Date(timestamp);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Poll for new messages
    function pollForNewMessages() {
      if (!currentInquiryId) return;
      
      fetch(`get_messages.php?inquiry_id=${currentInquiryId}&last_message_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.messages && data.messages.length > 0) {
            data.messages.forEach(message => {
              // Only add if not already in the chat
              if (message.id > lastMessageId) {
                addMessageToChat(message);
                lastMessageId = Math.max(lastMessageId, message.id);
              }
            });
          }
        })
        .catch(error => console.error('Error polling for messages:', error));
    }
    
    // Send message
    function sendMessage(form) {
      const formData = new FormData(form);
      
      fetch('customer-inq.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (response.redirected) {
          window.location.href = response.url;
        }
      })
      .catch(error => console.error('Error sending message:', error));
    }
    
    // Initialize chat
    document.addEventListener('DOMContentLoaded', function() {
      // Scroll to bottom on page load
      const chatMessages = document.querySelector('.chat-messages');
      if (chatMessages) {
        scrollToBottom();
        
        // Start polling for messages if there's an active inquiry
        if (currentInquiryId) {
          // Initial load
          fetch(`get_messages.php?inquiry_id=${currentInquiryId}`)
            .then(response => response.json())
            .then(data => {
              if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(message => {
                  addMessageToChat(message);
                  lastMessageId = Math.max(lastMessageId, message.id);
                });
                scrollToBottom();
              }
            });
            
          // Start polling
          pollInterval = setInterval(pollForNewMessages, 2000);
        }
      }
      
      // Handle message form submission
      const messageForm = document.getElementById('messageForm');
      if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const messageInput = this.querySelector('input[name="message"]');
          if (messageInput.value.trim() !== '') {
            sendMessage(this);
            messageInput.value = ''; // Clear input
          }
        });
      }
      
      // Handle new chat button
      const startNewChat = document.getElementById('startNewChat');
      if (startNewChat) {
        startNewChat.addEventListener('click', function() {
          // Show new inquiry modal or redirect to new inquiry page
          window.location.href = 'customer-inq.php?new=1';
        });
      }
    });
    
    // Clean up interval when leaving the page
    window.addEventListener('beforeunload', function() {
      if (pollInterval) clearInterval(pollInterval);
    });

    // Focus the message input when the page loads
    document.addEventListener('DOMContentLoaded', function() {
      const messageInput = document.querySelector('.message-input input');
      if (messageInput) {
        messageInput.focus();
      }
    });

    // Modal functionality
    const newInquiryBtn = document.getElementById('newInquiryBtn');
    const startNewInquiryBtn = document.getElementById('startNewInquiryBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelInquiryBtn = document.getElementById('cancelInquiryBtn');
    const modal = document.getElementById('newInquiryModal');

    if (newInquiryBtn) {
      newInquiryBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
      });
    }

    if (startNewInquiryBtn) {
      startNewInquiryBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
      });
    }

    if (closeModalBtn) {
      closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
      });
    }

    if (cancelInquiryBtn) {
      cancelInquiryBtn.addEventListener('click', () => {
        modal.style.display = 'none';
      });
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });

    // Auto-scroll to bottom of chat
    function scrollToBottom() {
      const chatMessages = document.querySelector('.chat-messages');
      if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    }
    
    // Poll for new messages
    function pollForNewMessages() {
        if (!currentInquiryId) return;
        
        fetch(`get_messages.php?inquiry_id=${currentInquiryId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(messages => {
                if (messages && messages.length > 0) {
                    messages.forEach(message => {
                        // Only add if not already in the chat
                        if (message.id > lastMessageId) {
                            addMessageToChat(message);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        }
                    });
                    scrollToBottom();
                }
            })
            .catch(error => console.error('Error polling for messages:', error));
                </div>
              `;
              chatMessages.appendChild(messageDiv);
              lastMessageId = Math.max(lastMessageId, msg.id);
            });
            scrollToBottom();
          }
        });
    }

    // Handle form submission
    const messageForm = document.querySelector('.message-form');
    if (messageForm) {
      messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const messageInput = this.querySelector('textarea[name="message"]');
        
        fetch('customer-inq.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (response.redirected) {
            window.location.href = response.url;
          }
        });
      });
    }

    // Start polling when page loads
    if (currentInquiryId) {
      pollInterval = setInterval(pollForNewMessages, 2000); // Poll every 2 seconds
      scrollToBottom();
    }

    // Clean up interval when leaving the page
    window.addEventListener('beforeunload', function() {
      if (pollInterval) clearInterval(pollInterval);
    });
  </script>
</body>
</html>
