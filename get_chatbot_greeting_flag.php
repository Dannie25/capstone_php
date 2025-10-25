<?php
session_start();
header('Content-Type: application/json');
$out = ['show' => false];
if (isset($_SESSION['show_chatbot_greeting']) && $_SESSION['show_chatbot_greeting']) {
    $out['show'] = true;
    $_SESSION['show_chatbot_greeting'] = false;
}
echo json_encode($out);
