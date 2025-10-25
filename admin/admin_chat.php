<?php
// admin_chat.php - Admin interface for customer chat
session_start();
include '../db.php';

// Simple admin check (replace with your own auth logic)
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Get all unique sessions/users with messages
function getConvoList($conn) {
    $sql = "
        SELECT
            COALESCE(user_id, session_id) as chat_id,
            MAX(created_at) as last_msg,
            COUNT(*) as msg_count,
            (
                SELECT message FROM chatbot_conversations c2
                WHERE (c2.user_id = c1.user_id OR c2.session_id = c1.session_id)
                  AND c2.archived = 0
                ORDER BY c2.created_at DESC LIMIT 1
            ) as last_message,
            (
                SELECT sender FROM chatbot_conversations c2
                WHERE (c2.user_id = c1.user_id OR c2.session_id = c1.session_id)
                  AND c2.archived = 0
                ORDER BY c2.created_at DESC LIMIT 1
            ) as last_sender,
            (
                SELECT COUNT(*) FROM chatbot_conversations c3
                WHERE (c3.user_id = c1.user_id OR c3.session_id = c1.session_id)
                  AND c3.sender = 'user' AND c3.is_read = 0 AND c3.archived = 0
            ) as unread_count
        FROM chatbot_conversations c1
        WHERE c1.archived = 0
        GROUP BY chat_id
        ORDER BY last_msg DESC
    ";
    $res = $conn->query($sql);
    $list = [];
    while ($row = $res->fetch_assoc()) $list[] = $row;
    return $list;
}

// Get messages for a chat
function getChatMessages($conn, $chat_id) {
    $uid = is_numeric($chat_id) ? intval($chat_id) : 0;
    $sid = $chat_id;
    $stmt = $conn->prepare("SELECT * FROM chatbot_conversations WHERE (user_id = ? OR session_id = ?) ORDER BY id ASC");
    $stmt->bind_param('is', $uid, $sid);
    $stmt->execute();
    $res = $stmt->get_result();
    $msgs = [];
    while ($row = $res->fetch_assoc()) $msgs[] = $row;
    $stmt->close();
    return $msgs;
}

$chat_id = $_GET['chat_id'] ?? null;
$convos = getConvoList($conn);
$messages = $chat_id ? getChatMessages($conn, $chat_id) : [];

// Handle admin reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $chat_id && isset($_POST['admin_message'])) {
    $msg = trim($_POST['admin_message']);
    if ($msg) {
        $uid = is_numeric($chat_id) ? intval($chat_id) : null;
        $sid = !is_numeric($chat_id) ? $chat_id : null;
        $stmt = $conn->prepare('INSERT INTO chatbot_conversations (user_id, sender, message, session_id) VALUES (?, "bot", ?, ?)');
        $stmt->bind_param('iss', $uid, $msg, $sid);
        $stmt->execute();
        $stmt->close();
        // Insert notification for user if user_id exists
        if ($uid) {
            $notif_stmt = $conn->prepare('INSERT INTO notifications (user_id, type, message) VALUES (?, "admin_reply", ?)');
            $notif_msg = 'You have a new reply from admin: ' . $msg;
            $notif_stmt->bind_param('is', $uid, $notif_msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        header('Location: admin_chat.php?chat_id=' . urlencode($chat_id));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Chat - MTC Clothing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
      body { background: #f5f5f5; }
      .main-container { 
        display: flex; 
        gap: 1.5rem; 
        padding: 0; 
        width: 100%;
      }
      .chat-sidebar-panel {
        width: 320px; 
        min-width: 280px; 
        background: #fff; 
        border-radius: 12px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.07); 
        padding: 0; 
        height: calc(100vh - 200px);
        overflow-y: auto;
        display: flex; 
        flex-direction: column;
      }
      .chat-sidebar-panel h2 {
        font-size: 1.2em; 
        color: #5b6b46; 
        font-weight: 600; 
        background: #f7fbe7; 
        margin: 0; 
        padding: 18px 20px;
        border-radius: 12px 12px 0 0;
        position: sticky;
        top: 0;
        z-index: 10;
      }
      .chat-list { 
        list-style: none; 
        padding: 0; 
        margin: 0; 
      }
      .chat-list li { 
        padding: 13px 20px; 
        border-bottom: 1px solid #f0f0f0; 
        cursor: pointer; 
        transition: all 0.2s ease;
      }
      .chat-list li.active { 
        background: #e9f5d9; 
        border-left: 3px solid #5b6b46;
      }
      .chat-list li:hover { 
        background: #f5f9f0; 
      }
      .chat-list .meta { 
        font-size: 12px; 
        color: #888; 
      }
      .chat-area {
        flex: 1; 
        background: #fff; 
        border-radius: 12px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.07); 
        padding: 24px; 
        display: flex; 
        flex-direction: column; 
        height: calc(100vh - 200px);
        min-width: 0;
      }
      .messages { 
        flex: 1; 
        overflow-y: auto; 
        margin-bottom: 18px; 
        padding: 10px;
        background: #fafafa;
        border-radius: 8px;
      }
      .msg-row { 
        margin-bottom: 14px; 
        display: flex; 
      }
      .msg-row.bot { 
        justify-content: flex-end; 
      }
      .msg {
        max-width: 70%; 
        padding: 12px 16px; 
        border-radius: 16px; 
        font-size: 0.95em;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        word-wrap: break-word;
      }
      .msg.user { 
        background: #f2f5ea; 
        color: #333; 
        border-bottom-left-radius: 4px; 
      }
      .msg.bot { 
        background: #5b6b46; 
        color: #fff; 
        border-bottom-right-radius: 4px; 
      }
      .chat-area form { 
        display: flex; 
        gap: 10px; 
        margin-top: 0; 
      }
      .chat-area textarea { 
        flex: 1; 
        resize: none; 
        padding: 12px; 
        border-radius: 8px; 
        border: 1.5px solid #d9e6a7; 
        font-size: 0.95em; 
        background: #f8f9fa;
        font-family: inherit;
      }
      .chat-area button { 
        background: #5b6b46; 
        color: #fff; 
        border: none; 
        padding: 10px 24px; 
        border-radius: 8px; 
        font-size: 0.95em; 
        cursor: pointer; 
        font-weight: 600;
        transition: background 0.2s ease;
      }
      .chat-area button:hover { 
        background: #4a5938; 
      }
      .empty { 
        color: #aaa; 
        text-align: center; 
        margin-top: 40px; 
        font-size: 1.1em; 
      }
      .conversation-badge {
        background: #e41e3f;
        color: white;
        border-radius: 10px;
        padding: 2px 8px;
        font-size: 0.7em;
        margin-left: 6px;
        font-weight: 600;
      }
      @media (max-width: 1200px) {
        .main-container { 
          flex-direction: column; 
          gap: 1rem; 
        }
        .chat-sidebar-panel { 
          width: 100%; 
          min-width: 0; 
          height: auto; 
          max-height: 300px;
        }
        .chat-area { 
          height: 500px;
        }
      }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-chat-dots"></i> Admin Chat</h1>
        </div>
        
        <div class="content-body">
            <div class="main-container">
                <div class="chat-sidebar-panel">
                    <h2>Customer Chats</h2>
<ul class="chat-list">
<?php foreach ($convos as $c): ?>
    <?php
    $is_guest = !$c['chat_id'] || !is_numeric($c['chat_id']);
    $user_id = is_numeric($c['chat_id']) ? $c['chat_id'] : null;
    $user_name = $user_id ? ('User #' . $user_id) : 'Guest';
    $avatar_letter = $user_id ? strtoupper(substr($user_id,0,1)) : 'G';
    ?>
    <li class="<?php if($chat_id == $c['chat_id']) echo 'active'; ?> d-flex align-items-center justify-content-between" style="padding-right:10px;position:relative;">
    <a href="?chat_id=<?php echo urlencode($c['chat_id']); ?><?php echo (isset($_GET['archive']) && $_GET['archive'] == '1') ? '&archive=1' : ''; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;width:100%;gap:10px;">
        <div style="width:36px;height:36px;border-radius:50%;background:#5b6b46;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:1.1em;">
            <?php echo $avatar_letter; ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?php echo $user_name ? htmlspecialchars($user_name) : 'Guest'; ?>
                <span style="color:#888;font-size:12px;margin-left:4px;">(<?php echo htmlspecialchars($c['chat_id']); ?>)</span>
                <?php if ($c['unread_count'] > 0): ?>
                    <span class="conversation-badge" style="background:#e41e3f;color:white;border-radius:10px;padding:2px 6px;font-size:0.75em;margin-left:6px;">New</span>
                <?php endif; ?>
            </div>
            <div class="meta" style="font-size:13px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">
                <?php echo htmlspecialchars($c['last_message']); ?>
            </div>
        </div>
    </a>
    </li>
<?php endforeach; ?>
</ul>
                </div>
                
                <div class="chat-area">
        <div class="messages" id="admin-chat-messages">
            <?php if (!$messages): ?>
                <div class="empty">Select a chat to view messages.</div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <div class="msg-row <?php echo $m['sender'] === 'bot' ? 'bot' : 'user'; ?>">
                        <div class="msg <?php echo $m['sender'] === 'bot' ? 'bot' : 'user'; ?>">
                            <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($chat_id): ?>
        <form method="post" action="?chat_id=<?php echo urlencode($chat_id); ?>">
            <textarea name="admin_message" rows="2" required placeholder="Type your reply as admin..."></textarea>
            <button type="submit">Send</button>
        </form>
        <?php endif; ?>
                </div>
            </div>
            
<script src="admin_chat_scroll.js"></script>
<script src="admin_chat_poll.js"></script>
<script src="admin_chat_enter.js"></script>
<script src="admin_chat_sidebar_poll.js"></script>
            </div>
        </div>
    </div>
</div>
</body>
</html>
