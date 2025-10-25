<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    // Ensure table exists (safe no-op if already created)
    $conn->query("CREATE TABLE IF NOT EXISTS chatbot_faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(255) NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed defaults if table is empty
    $seedCheck = $conn->query("SELECT COUNT(*) AS c FROM chatbot_faqs");
    $countRow = $seedCheck ? $seedCheck->fetch_assoc() : ['c' => 0];
    if ((int)$countRow['c'] === 0) {
        $defaults = [
            ['What are your store hours?', 'Our online store is open 24/7. Support hours: Mon–Fri, 9am–6pm.'],
            ['How long is shipping?', 'Standard shipping typically takes 3–7 business days depending on your location.'],
            ['Do you offer returns?', 'Yes. Returns are accepted within 7 days of delivery if items are unworn and with tags.'],
            ['How can I track my order?', 'Go to My Orders page after logging in to see your order status and tracking if available.'],
            ['Do you have Cash on Delivery?', 'Yes, COD may be available on select locations. See checkout for options.'],
        ];
        $stmt = $conn->prepare("INSERT INTO chatbot_faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, 1)");
        $order = 0;
        foreach ($defaults as $d) {
            $stmt->bind_param('ssi', $d[0], $d[1], $order);
            $stmt->execute();
            $order++;
        }
        $stmt->close();
    }

    $faqs = [];
    $sql = "SELECT question, answer FROM chatbot_faqs WHERE is_active=1 ORDER BY sort_order ASC, id ASC";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $faqs[] = [
                'q' => $row['question'],
                'a' => $row['answer']
            ];
        }
        $res->close();
    }

    echo json_encode([ 'success' => true, 'faqs' => $faqs ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([ 'success' => false, 'error' => 'Server error' ]);
}
