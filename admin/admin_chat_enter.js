document.addEventListener('DOMContentLoaded', function() {
  var textarea = document.querySelector('.chat-area textarea');
  if (textarea) {
    textarea.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        var form = this.closest('form');
        if (form) form.submit();
      }
    });
  }
  // Smooth scroll to bottom after load
  var messages = document.querySelector('.messages');
  if (messages) {
    setTimeout(function() {
      messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });
    }, 120);
  }
});
