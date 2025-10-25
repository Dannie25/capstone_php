<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<head>
  <meta charset="UTF-8">
  <title>Help & Support - MTC Clothing</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: Arial, sans-serif; background: #fafafa; margin: 0; }
    .container { max-width: 1100px; margin: 0 auto; padding: 0 30px; }
    .hero { background: #5b6b46; color: #e2e2e2; padding: 40px 0; }
    .hero h1 { margin: 0 0 8px; font-size: 28px; }
    .hero p { margin: 0; opacity: .95; }

    .grid { display: grid; grid-template-columns: 1fr; gap: 20px; padding: 24px 0; }
    @media (min-width: 900px) { .grid { grid-template-columns: 2fr 1fr; } }

    .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); padding: 18px 20px; }
    .card h2 { margin: 0 0 10px; font-size: 1.3rem; color: #333; border-bottom: 2px solid #d9e6a7; padding-bottom: 8px; }
    .list { margin: 0; padding-left: 18px; color: #444; }
    .list li { margin: 8px 0; }

    .faq-item { border-bottom: 1px solid #eee; }
    .faq-q { display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 12px 0; font-weight: 600; color: #333; }
    .faq-a { display: none; padding: 0 0 12px; color: #555; }

    .contact-box label { display:block; margin: 10px 0 6px; font-weight: 600; color:#444; }
    .contact-box input, .contact-box textarea { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; }
    .contact-box textarea { min-height: 100px; resize: vertical; }
    .btn { background:#5b6b46; color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
    .btn:hover { background:#4a5938; }

    .quick-links a { display:block; text-decoration:none; color:#333; padding:10px 12px; border:1px solid #e7e7e7; border-radius:8px; margin-bottom:10px; background:#fff; }
    .quick-links a:hover { background:#f8f8f8; }
  </style>
</head>
<body>

  <section class="hero">
    <div class="container">
      <h1>Help & Support</h1>
      <p>Find answers to common questions or reach our friendly team for assistance.</p>
    </div>
  </section>

  <div class="container">
    <div class="grid">
      <!-- Left: FAQs and Contact Form -->
      <div class="left">
        <div class="card">
          <h2>Frequently Asked Questions</h2>
          <div class="faq-item">
            <div class="faq-q">How can I track my order? <span>+</span></div>
            <div class="faq-a">After placing an order, you can check your order status in your account's Orders page. We also send updates via email.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q">What is your return policy? <span>+</span></div>
            <div class="faq-a">We accept returns within 7 days of delivery as long as the item is unused and in original packaging. Please contact us to initiate a return.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q">Do you offer custom tailoring? <span>+</span></div>
            <div class="faq-a">Yes! Visit our Sub-Contract page for custom projects or message us with your requirements.</div>
          </div>
        </div>

        <div class="card contact-box" style="margin-top:20px;">
          <h2>Contact Us</h2>
          <p style="color:#555; margin: 6px 0 16px;">Send us a message and we’ll get back to you as soon as we can.</p>
          <form id="contactForm" onsubmit="return sendMailto(event)">
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" placeholder="Juan Dela Cruz" required>
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" placeholder="Question about order #1234" required>
            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Type your message here..." required></textarea>
            <div style="margin-top:12px;"><button class="btn" type="submit">Send Message</button></div>
          </form>
          <p style="color:#777; font-size: 13px; margin-top:10px;">Or email us directly at <a href="mailto:support@example.com">support@example.com</a></p>
        </div>
      </div>

      <!-- Right: Quick Links and Hours -->
      <div class="right">
        <div class="card quick-links">
          <h2>Quick Links</h2>
          <a href="products.php">All Products</a>
          <a href="arrivals.php">New Arrivals</a>
          <a href="subcon.php">Sub-Contract / Custom</a>
          <a href="about.php">About Us</a>
        </div>
        <div class="card" style="margin-top:20px;">
          <h2>Business Hours</h2>
          <ul class="list">
            <li>Mon–Fri: 9:00 AM – 6:00 PM</li>
            <li>Sat: 10:00 AM – 4:00 PM</li>
            <li>Sun & Holidays: Closed</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Simple FAQ toggles
    document.querySelectorAll('.faq-q').forEach(function(el){
      el.addEventListener('click', function(){
        const ans = this.nextElementSibling;
        const open = ans.style.display === 'block';
        document.querySelectorAll('.faq-a').forEach(a => a.style.display = 'none');
        document.querySelectorAll('.faq-q span').forEach(s => s.textContent = '+');
        if (!open) {
          ans.style.display = 'block';
          this.querySelector('span').textContent = '−';
        }
      });
    });

    // Mailto-based submission (no backend required)
    function sendMailto(e){
      e.preventDefault();
      const name = document.getElementById('name').value.trim();
      const email = document.getElementById('email').value.trim();
      const subject = document.getElementById('subject').value.trim();
      const message = document.getElementById('message').value.trim();
      const body = encodeURIComponent(`From: ${name} <${email}>\n\n${message}`);
      const mail = `mailto:support@example.com?subject=${encodeURIComponent(subject)}&body=${body}`;
      window.location.href = mail;
      return false;
    }
  </script>

</body>
</html>

