<?php
function getCMSContent($page_name) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM cms_pages WHERE page_name = ?");
    $stmt->bind_param("s", $page_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

function getAllPages() {
    global $conn;
    $result = $conn->query("SELECT page_name, page_title FROM cms_pages ORDER BY page_title");
    return $result->fetch_all(MYSQLI_ASSOC);
}