// Polls admin_chat_sidebar_ajax.php every 3 seconds and updates the sidebar chat list
setInterval(function() {
  var btn = document.getElementById('toggle-archive-btn');
  var type = btn && btn.dataset.type === 'archived' ? 'archived' : 'active';
  var url = type === 'archived' ? 'admin_chat_sidebar_archived.php' : 'admin_chat_sidebar_ajax.php';
  fetch(url)
    .then(res => res.text())
    .then(html => {
      var sidebar = document.querySelector('.sidebar .chat-list');
      if (sidebar) sidebar.outerHTML = html;
    });
}, 3000);
