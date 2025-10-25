<?php
// AJAX endpoint: returns chat sidebar HTML for polling
include '../db.php';
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
$convos = getConvoList($conn);
?>
<ul class="chat-list">
<?php foreach ($convos as $c): ?>
    <?php
    $is_guest = !$c['chat_id'] || !is_numeric($c['chat_id']);
    $user_id = is_numeric($c['chat_id']) ? $c['chat_id'] : null;
    $user_name = $user_id ? ('User #' . $user_id) : 'Guest';
    $avatar_letter = $user_id ? strtoupper(substr($user_id,0,1)) : 'G';
    ?>
    <li class="d-flex align-items-center justify-content-between" style="padding-right:10px;">
        <a href="?chat_id=<?php echo urlencode($c['chat_id']); ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;width:100%;gap:10px;">
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
