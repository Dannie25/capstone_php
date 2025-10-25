window.addEventListener('DOMContentLoaded', function() {
  var msgBox = document.getElementById('admin-chat-messages');
  if (msgBox) {
    requestAnimationFrame(function() {
      msgBox.scrollTop = msgBox.scrollHeight;
    });
  }
});
