function pollAdminChat() {
  var chatId = (new URLSearchParams(window.location.search)).get('chat_id');
  if (!chatId) return;
  var msgBox = document.getElementById('admin-chat-messages');
  if (!msgBox) return;
  var xhr = new XMLHttpRequest();
  xhr.open('GET', '../get_chatbot_history.php?chat_id=' + encodeURIComponent(chatId), true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var data = JSON.parse(xhr.responseText);
        if (data && data.success && Array.isArray(data.history)) {
          msgBox.innerHTML = '';
          data.history.forEach(function(row) {
            var rowDiv = document.createElement('div');
            rowDiv.className = 'msg-row ' + (row.sender === 'bot' ? 'bot' : 'user');
            var msgDiv = document.createElement('div');
            msgDiv.className = 'msg ' + (row.sender === 'bot' ? 'bot' : 'user');
            msgDiv.innerText = row.message;
            rowDiv.appendChild(msgDiv);
            msgBox.appendChild(rowDiv);
          });
          msgBox.scrollTop = msgBox.scrollHeight;
        }
      } catch (e) {}
    }
  };
  xhr.send();
}
setInterval(pollAdminChat, 2000);
