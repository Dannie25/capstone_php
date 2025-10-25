// Function to update cart count
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Add event listeners to all Add to Cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', addToCart);
    });
    
    // Add event listener to cart icon
    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
        cartIcon.addEventListener('click', function(e) {
            // If the click is on the cart icon itself (not a child element), navigate to cart
            if (e.target === this) {
                window.location.href = 'cart.php';
            }
        });
    }
});

// Function to add item to cart
function addToCart(event) {
    const button = event.target;
    const productId = button.getAttribute('data-product-id');
    
    // Show loading state
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = 'Adding...';
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            updateCartCount();
            // Show success message
            alert('Item added to cart!');
            // Optional: Show a nice toast notification instead of alert
            // showToast('Item added to cart!');
        } else {
            if (data.redirect) {
                // If not logged in, redirect to login page
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Failed to add item to cart');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        // Reset button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// Function to update cart count in header
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('cartCount');
            if (cartCount) {
                cartCount.textContent = data.count;
                cartCount.style.display = data.count > 0 ? 'flex' : 'none';
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
        });
}

// Function to show toast notification (optional)
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Add show class
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Add styles for toast (if using)
const style = document.createElement('style');
style.textContent = `
    .toast {
        visibility: hidden;
        min-width: 250px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 4px;
        padding: 16px;
        position: fixed;
        z-index: 1000;
        right: 20px;
        bottom: 30px;
        font-size: 14px;
        opacity: 0;
        transition: opacity 0.3s, visibility 0.3s;
    }
    
    .toast.show {
        visibility: visible;
        opacity: 1;
    }
`;
document.head.appendChild(style);
