// AJAX Polling for My Orders Page
(function() {
    let pollingInterval = null;
    const POLL_INTERVAL = 10000; // Poll every 10 seconds
    
    // Store current data hashes to detect changes
    let lastDataHash = {
        orders: '',
        subcontracts: '',
        customizations: ''
    };
    
    // Function to generate simple hash from JSON
    function generateHash(data) {
        return JSON.stringify(data);
    }
    
    // Function to reattach event listeners for feedback modal
    function reattachEventListeners() {
        // Reattach feedback modal listeners
        document.querySelectorAll('.open-feedback-modal').forEach(function(btn) {
            btn.removeEventListener('click', openFeedbackModal);
            btn.addEventListener('click', openFeedbackModal);
        });
    }
    
    // Feedback modal handler
    function openFeedbackModal(e) {
        const orderId = e.currentTarget.getAttribute('data-order-id');
        const feedbackModal = document.getElementById('feedbackModal');
        if (feedbackModal) {
            const orderIdInput = document.getElementById('feedbackModalOrderId');
            const feedbackText = document.getElementById('feedbackModalText');
            const ratingInput = document.getElementById('feedbackModalRating');
            const msgSpan = document.getElementById('feedbackModalMsg');
            const stars = document.querySelectorAll('#starRating .star');
            
            if (orderIdInput) orderIdInput.value = orderId;
            if (feedbackText) feedbackText.value = '';
            if (ratingInput) ratingInput.value = '';
            if (msgSpan) msgSpan.textContent = '';
            stars.forEach(function(star) { star.classList.remove('selected'); });
            
            const modal = new bootstrap.Modal(feedbackModal);
            modal.show();
        }
    }
    
    // Function to update status filters
    function updateStatusFilters(orders) {
        const ordersTab = document.getElementById('orders');
        let filtersDiv = ordersTab.querySelector('.status-filters');
        
        if (orders.length === 0) {
            // Remove filters if no orders
            if (filtersDiv) filtersDiv.remove();
            return;
        }
        
        // Always show all possible statuses (static filters)
        const allStatuses = ['pending', 'shipped', 'completed', 'cancelled'];
        
        // Get current active filter status
        const activeFilter = document.querySelector('#orders .status-filter.active');
        const activeStatus = activeFilter ? activeFilter.getAttribute('data-status') : 'all';
        
        // Build filters HTML with all statuses
        let filtersHTML = '<div class="status-filters mb-4">';
        filtersHTML += `<div class="status-filter ${activeStatus === 'all' ? 'active' : ''}" data-status="all">All Orders</div>`;
        
        allStatuses.forEach(status => {
            const label = status.charAt(0).toUpperCase() + status.slice(1);
            filtersHTML += `<div class="status-filter ${activeStatus === status ? 'active' : ''}" data-status="${status}">${label}</div>`;
        });
        
        filtersHTML += '</div>';
        
        // Replace or create filters
        if (filtersDiv) {
            filtersDiv.outerHTML = filtersHTML;
        } else {
            ordersTab.insertAdjacentHTML('afterbegin', filtersHTML);
        }
        
        // Reattach filter click events
        attachFilterEvents();
    }
    
    // Function to attach filter events
    function attachFilterEvents() {
        const filters = document.querySelectorAll('#orders .status-filter');
        
        filters.forEach(filter => {
            filter.addEventListener('click', function() {
                const status = this.getAttribute('data-status');
                
                // Update active state
                filters.forEach(f => f.classList.remove('active'));
                this.classList.add('active');
                
                // Get fresh order cards (in case they were updated)
                const orderCards = document.querySelectorAll('#orders .order-card');
                
                // Filter orders
                orderCards.forEach(card => {
                    const cardStatus = card.getAttribute('data-status');
                    if (status === 'all' || cardStatus === status) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }
    
    // Function to update orders tab
    function updateOrdersTab(orders) {
        const ordersContainer = document.querySelector('#orders .order-card');
        if (!ordersContainer && orders.length === 0) return;
        
        const currentHash = generateHash(orders);
        if (lastDataHash.orders === currentHash) return;
        
        lastDataHash.orders = currentHash;
        
        // Rebuild orders section
        const ordersTab = document.getElementById('orders');
        const emptyState = ordersTab.querySelector('.empty-state');
        const orderCards = ordersTab.querySelectorAll('.order-card');
        
        if (orders.length === 0) {
            // Remove filters
            const filtersDiv = ordersTab.querySelector('.status-filters');
            if (filtersDiv) filtersDiv.remove();
            
            if (!emptyState) {
                orderCards.forEach(card => card.remove());
                const emptyHTML = `
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag empty-icon"></i>
                        <h2 class="empty-title">No Orders Yet</h2>
                        <p class="empty-text">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                        <a href="home.php" class="btn btn-primary-custom">
                            <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                        </a>
                    </div>`;
                ordersTab.insertAdjacentHTML('beforeend', emptyHTML);
            }
        } else {
            if (emptyState) emptyState.remove();
            
            // Update filters first
            updateStatusFilters(orders);
            
            // Clear existing order cards
            orderCards.forEach(card => card.remove());
            
            // Render new orders (build all HTML first, then insert once)
            const allOrdersHTML = orders.map(order => renderOrderCard(order)).join('');
            const filtersDiv = ordersTab.querySelector('.status-filters');
            if (filtersDiv) {
                filtersDiv.insertAdjacentHTML('afterend', allOrdersHTML);
            } else {
                ordersTab.insertAdjacentHTML('beforeend', allOrdersHTML);
            }
            
            // Reattach event listeners
            reattachEventListeners();
            
            // Reapply active filter
            const activeFilter = document.querySelector('#orders .status-filter.active');
            if (activeFilter) {
                activeFilter.click();
            }
        }
        
        console.log('Orders updated:', orders.length);
    }
    
    // Function to update subcontracts tab
    function updateSubcontractsTab(subcontracts) {
        const currentHash = generateHash(subcontracts);
        if (lastDataHash.subcontracts === currentHash) return;
        
        lastDataHash.subcontracts = currentHash;
        
        const subcontractTab = document.getElementById('subcontract');
        const emptyState = subcontractTab.querySelector('.empty-state');
        const orderCards = subcontractTab.querySelectorAll('.order-card');
        
        if (subcontracts.length === 0) {
            if (!emptyState) {
                orderCards.forEach(card => card.remove());
                const emptyHTML = `
                    <div class="empty-state">
                        <i class="fas fa-file-contract empty-icon"></i>
                        <h2 class="empty-title">No Subcontract Requests Yet</h2>
                        <p class="empty-text">You haven't submitted any subcontract requests yet.</p>
                        <a href="subcon.php" class="btn btn-primary-custom">
                            <i class="fas fa-plus me-2"></i>Create Request
                        </a>
                    </div>`;
                subcontractTab.insertAdjacentHTML('beforeend', emptyHTML);
            }
        } else {
            if (emptyState) emptyState.remove();
            orderCards.forEach(card => card.remove());
            
            subcontracts.forEach(subcon => {
                const subconHTML = renderSubcontractCard(subcon);
                subcontractTab.insertAdjacentHTML('beforeend', subconHTML);
            });
        }
        
        console.log('Subcontracts updated:', subcontracts.length);
    }
    
    // Function to update customizations tab
    function updateCustomizationsTab(customizations) {
        const currentHash = generateHash(customizations);
        if (lastDataHash.customizations === currentHash) return;
        
        lastDataHash.customizations = currentHash;
        
        const customTab = document.getElementById('customization');
        const emptyState = customTab.querySelector('.empty-state');
        const orderCards = customTab.querySelectorAll('.order-card');
        
        if (customizations.length === 0) {
            if (!emptyState) {
                orderCards.forEach(card => card.remove());
                const emptyHTML = `
                    <div class="empty-state">
                        <i class="fas fa-tshirt empty-icon"></i>
                        <h4>No Customization Requests</h4>
                        <p>You haven't made any customization requests yet.</p>
                        <a href="customization.php" class="btn btn-primary">Create Custom Design</a>
                    </div>`;
                customTab.insertAdjacentHTML('beforeend', emptyHTML);
            }
        } else {
            if (emptyState) emptyState.remove();
            orderCards.forEach(card => card.remove());
            
            customizations.forEach(custom => {
                const customHTML = renderCustomizationCard(custom);
                customTab.insertAdjacentHTML('beforeend', customHTML);
            });
        }
        
        console.log('Customizations updated:', customizations.length);
    }
    
    // Function to render order card HTML
    function renderOrderCard(order) {
        const orderId = String(order.id).padStart(6, '0');
        const orderDate = new Date(order.created_at).toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        const paymentMethod = order.payment_method === 'cod' ? 'Cash on Delivery' : 
                             order.payment_method === 'gcash' ? 'GCash' : 
                             order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1);
        
        const deliveryMode = order.delivery_mode === 'pickup' ? 'Pick Up' :
                           order.delivery_mode === 'lalamove' ? 'Lalamove' :
                           order.delivery_mode === 'jnt' ? 'J&T Express' :
                           order.delivery_mode ? order.delivery_mode.charAt(0).toUpperCase() + order.delivery_mode.slice(1) : '';
        
        let itemsHTML = '';
        if (order.items && order.items.length > 0) {
            itemsHTML = order.items.map(item => {
                // Use display_image from server (already processed with Image 1 priority)
                const imagePath = item.display_image || 'img/no-image.jpg';
                
                return `
                    <div class="order-item">
                        <div class="item-image">
                            <a href="product_detail.php?id=${item.product_id}" 
                               title="View product details"
                               style="display: block; width: 100%; height: 100%; text-decoration: none;">
                                <img src="${imagePath}" 
                                     alt="${item.product_name}" 
                                     style="width: 100%; height: 100%; object-fit: contain; cursor: pointer; transition: transform 0.2s;"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'"
                                     onerror="this.onerror=null; this.src='img/no-image.jpg';">
                            </a>
                        </div>
                        <div class="item-details">
                            <div class="item-name" title="${item.product_name}">${item.product_name}</div>
                            ${item.size ? `<div class="item-size">Size: ${item.size}</div>` : ''}
                            <div class="item-quantity">Qty: ${item.quantity}</div>
                            <div class="item-price">₱${(parseFloat(item.product_price) * parseInt(item.quantity)).toFixed(2)}</div>
                        </div>
                    </div>`;
            }).join('');
        }
        
        const cancelButtons = (order.status === 'pending' || order.status === 'processing') ? `
            <button class="btn btn-outline-primary btn-sm" 
                    data-bs-toggle="modal" 
                    data-bs-target="#reviewOrderModal" 
                    data-order-id="${order.id}">
                <i class="fas fa-eye me-1"></i> Review Order
            </button>
            <button class="btn btn-outline-danger btn-sm" 
                    data-bs-toggle="modal" 
                    data-bs-target="#cancelOrderModal" 
                    data-order-id="${order.id}">
                <i class="fas fa-times me-1"></i> Cancel Order
            </button>` : `
            <button class="btn btn-outline-primary btn-sm" 
                    data-bs-toggle="modal" 
                    data-bs-target="#reviewOrderModal" 
                    data-order-id="${order.id}">
                <i class="fas fa-eye me-1"></i> Review Order
            </button>`;
        
        const feedbackButton = order.status === 'completed' && !order.has_feedback ? `
            <button class="btn btn-success btn-sm open-feedback-modal" data-order-id="${order.id}">Submit Feedback</button>` :
            order.status === 'completed' && order.has_feedback ? `
            <div class="alert alert-info p-2 mb-0">Feedback already submitted for this order.</div>` : '';
        
        return `
            <div class="order-card" data-status="${order.status}">
                <div class="order-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="order-number">Order #${orderId}</h3>
                            <p class="order-date">Placed on ${orderDate}</p>
                        </div>
                        <span class="status-badge status-${order.status}">
                            ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                        </span>
                    </div>
                </div>
                
                <div class="order-body">
                    <div class="order-info">
                        <div><strong>Payment Method:</strong> ${paymentMethod}</div>
                        <div><strong>Delivery Option:</strong> ${deliveryMode}</div>
                        <div class="order-total">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                    </div>
                    
                    <div class="items-section">
                        <h4 class="items-title">
                            <i class="fas fa-box"></i>
                            Order Items (${order.item_count} item${order.item_count > 1 ? 's' : ''})
                        </h4>
                        <div class="order-items-container">
                            <div class="order-items">
                                ${itemsHTML}
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Shipping Address:</strong><br>
                            ${order.address}, ${order.city}${order.postal_code ? ' ' + order.postal_code : ''}
                            <div class="mt-3 d-flex gap-2">
                                ${cancelButtons}
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <div><strong>Subtotal:</strong> ₱${parseFloat(order.subtotal).toFixed(2)}</div>
                            <div><strong>Shipping:</strong> ₱${parseFloat(order.shipping).toFixed(2)}</div>
                            <div><strong>Tax:</strong> ₱${parseFloat(order.tax).toFixed(2)}</div>
                            <div class="mt-2 fs-5"><strong>Total: ₱${parseFloat(order.total_amount).toFixed(2)}</strong></div>
                        </div>
                    </div>
                    
                    ${order.status === 'cancelled' && order.cancel_reason ? `
                    <div class="alert alert-danger mt-3">
                        <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Cancellation Reason:</h6>
                        <p class="mb-0">${order.cancel_reason}</p>
                        ${order.cancelled_at ? `
                            <small class="text-muted d-block mt-2">
                                <i class="far fa-clock me-1"></i>Cancelled on: ${new Date(order.cancelled_at).toLocaleString()}
                            </small>
                        ` : ''}
                    </div>
                    ` : ''}
                    
                    <div class="mt-3">${feedbackButton}</div>
                </div>
            </div>`;
    }
    
    // Function to render subcontract card HTML (simplified version)
    function renderSubcontractCard(subcon) {
        const requestId = String(subcon.id).padStart(6, '0');
        const requestDate = new Date(subcon.created_at).toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        const targetDate = new Date(subcon.date_needed + ' ' + subcon.time_needed).toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        let designFilesHTML = '';
        if (subcon.design_file) {
            try {
                const files = JSON.parse(subcon.design_file);
                if (Array.isArray(files)) {
                    designFilesHTML = files.map(file => 
                        `<img src="${file}" alt="Design" onclick="window.open(this.src, '_blank')" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer;">`
                    ).join('');
                } else {
                    designFilesHTML = `<img src="${subcon.design_file}" alt="Design" onclick="window.open(this.src, '_blank')" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer;">`;
                }
            } catch (e) {
                designFilesHTML = `<img src="${subcon.design_file}" alt="Design" onclick="window.open(this.src, '_blank')" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer;">`;
            }
        }
        
        let statusAlert = '';
        if (subcon.status === 'pending') {
            statusAlert = `
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Your request is pending admin approval. You will be notified about the price once reviewed.
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger" onclick="cancelSubcontractRequest(${subcon.id})">
                        <i class="fas fa-times me-2"></i>Cancel Request
                    </button>
                </div>`;
        } else if (subcon.status === 'awaiting_confirmation') {
            if (subcon.price && subcon.price > 0) {
                statusAlert = `
                    <!-- Quoted Price Box -->
                    <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f0f7ed 0%, #e8f5e9 100%); border-radius: 12px; border: 2px solid #8bc34a;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0" style="color: #5b6b46; font-weight: 700;">
                                    <i class="fas fa-tag me-2"></i>Quoted Price
                                </h5>
                            </div>
                            <div class="h4 mb-0" style="color:#2e7d32; font-weight:800;">₱${parseFloat(subcon.price).toFixed(2)}</div>
                        </div>
                    </div>
                    
                    <!-- Info Message -->
                    <div class="alert alert-info mt-3 mb-3" style="border-left: 4px solid #0dcaf0;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Price Quote Received - Awaiting Your Confirmation</strong>
                        <p class="mb-0 mt-2"><small>Please review the quoted price above. Click "Accept" to proceed with the customization or "Decline" to cancel the request.</small></p>
                    </div>
                    
                    <!-- Accept/Decline Buttons -->
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success flex-fill" onclick="openSubcontractCheckout(${subcon.id}, ${subcon.price}, '${(subcon.what_for || '').replace(/'/g, "\\'")}', ${subcon.quantity || 0})">
                            <i class="fas fa-check me-2"></i>Accept Price & Proceed
                        </button>
                        <button type="button" class="btn btn-outline-danger flex-fill" onclick="rejectSubcontractPrice(${subcon.id})">
                            <i class="fas fa-times me-2"></i>Decline
                        </button>
                    </div>`;
            } else {
                statusAlert = `<div class="alert alert-info mt-3"><i class="fas fa-clock me-2"></i>Waiting for admin to set the price.</div>`;
            }
        } else if (subcon.status === 'in_progress') {
            statusAlert = `<div class="alert alert-primary mt-3"><i class="fas fa-spinner me-2"></i>Your request is currently being processed.</div>`;
        } else if (subcon.status === 'completed') {
            statusAlert = `<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i>Your request has been completed!</div>`;
        } else if (subcon.status === 'cancelled') {
            statusAlert = `
                <div class="alert alert-danger mt-3">
                    <h6 class="mb-2"><i class="fas fa-times-circle me-2"></i>Cancelled</h6>
                    ${subcon.cancel_reason ? `<p class="mb-0"><strong>Reason:</strong> ${subcon.cancel_reason}</p>` : ''}
                    ${subcon.cancelled_at ? `<small class="text-muted d-block mt-2"><i class="far fa-clock me-1"></i>Cancelled on: ${new Date(subcon.cancelled_at).toLocaleString()}</small>` : ''}
                </div>`;
        }
        
        return `
            <div class="order-card" data-status="${subcon.status}">
                <div class="order-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="order-number">Request #${requestId}</h3>
                            <p class="order-date">Submitted on ${requestDate}</p>
                        </div>
                        <span class="status-badge status-${subcon.status}">
                            ${subcon.status.replace('_', ' ').charAt(0).toUpperCase() + subcon.status.replace('_', ' ').slice(1)}
                        </span>
                    </div>
                </div>
                <div class="order-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3" style="color: var(--primary-color);">Request Details</h5>
                            <p><strong>What for:</strong> ${subcon.what_for}</p>
                            <p><strong>Quantity:</strong> ${subcon.quantity}</p>
                            <p><strong>Target Date:</strong> ${targetDate}</p>
                            <p><strong>Delivery Method:</strong> ${subcon.delivery_method}</p>
                            ${subcon.note ? `<p><strong>Note:</strong> ${subcon.note}</p>` : ''}
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3" style="color: var(--primary-color);">Customer Details</h5>
                            <p><strong>Name:</strong> ${subcon.customer_name}</p>
                            <p><strong>Address:</strong> ${subcon.address}</p>
                            <p><strong>Email:</strong> ${subcon.email}</p>
                        </div>
                    </div>
                    ${designFilesHTML ? `<div class="mt-3"><h5 class="mb-3" style="color: var(--primary-color);">Design Files</h5><div class="subcontract-images">${designFilesHTML}</div></div>` : ''}
                    ${statusAlert}
                </div>
            </div>`;
    }
    
    // Function to render customization card HTML (clean layout with canvas preview)
    function renderCustomizationCard(custom) {
        const customId = String(custom.id).padStart(6, '0');
        const customDate = new Date(custom.created_at).toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
        
        // Get size from description if available
        let sizeInfo = '';
        if (custom.description && custom.description.includes('Size:')) {
            const match = custom.description.match(/Size:\s*([A-Z0-9]+)/i);
            if (match && match[1]) {
                sizeInfo = match[1];
            }
        }
        
        // Canvas preview section (only if image exists)
        const canvasPreview = custom.reference_image_path ? `
            <div class="col-md-5">
                <div style="background: #f8f9fa; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center;">
                    <h6 style="color: var(--primary-color); font-weight: 700; margin-bottom: 12px;">
                        <i class="fas fa-tshirt me-2"></i>Design Preview
                    </h6>
                    <img src="${custom.reference_image_path}" 
                         alt="Design Preview" 
                         class="img-fluid rounded" 
                         style="max-height: 280px; width: auto; object-fit: contain; background: white; padding: 10px; border: 1px solid #ddd;">
                </div>
            </div>` : '';
        
        const detailsColClass = custom.reference_image_path ? 'col-md-7' : 'col-md-12';
        
        return `
            <div class="order-card" data-status="${custom.status}">
                <div class="order-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="order-number">Custom #${customId}</h3>
                            <div class="order-date"><i class="far fa-calendar-alt me-1"></i>${customDate}</div>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-${custom.status}">
                                ${custom.status.replace('_', ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="order-body">
                    <div class="row align-items-start">
                        ${canvasPreview}
                        <div class="${detailsColClass}">
                            <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px; border-bottom: 2px solid var(--secondary-color); padding-bottom: 8px;">
                                <i class="fas fa-info-circle me-2"></i>Design Details
                            </h5>
                            <div style="background: #fff; padding: 15px; border-radius: 8px;">
                                ${custom.neckline_type ? `
                                    <div class="mb-2">
                                        <strong style="color: #555; display: inline-block; width: 120px;">Neck Style:</strong>
                                        <span style="color: #2d3748;">${custom.neckline_type.charAt(0).toUpperCase() + custom.neckline_type.slice(1)}</span>
                                    </div>` : ''}
                                ${custom.sleeve_type ? `
                                    <div class="mb-2">
                                        <strong style="color: #555; display: inline-block; width: 120px;">Sleeve Length:</strong>
                                        <span style="color: #2d3748;">${custom.sleeve_type.charAt(0).toUpperCase() + custom.sleeve_type.slice(1)}</span>
                                    </div>` : ''}
                                ${custom.fit_type ? `
                                    <div class="mb-2">
                                        <strong style="color: #555; display: inline-block; width: 120px;">Fit Style:</strong>
                                        <span style="color: #2d3748;">${custom.fit_type.charAt(0).toUpperCase() + custom.fit_type.slice(1)}</span>
                                    </div>` : ''}
                                ${custom.color_preference_1 ? `
                                    <div class="mb-2">
                                        <strong style="color: #555; display: inline-block; width: 120px;">Color:</strong>
                                        <span style="display: inline-block; width: 20px; height: 20px; background-color: ${custom.color_preference_1}; border: 1px solid #ddd; border-radius: 4px; vertical-align: middle; margin-right: 8px;"></span>
                                        <span style="color: #2d3748;">${custom.color_preference_1}</span>
                                    </div>` : ''}
                                ${sizeInfo ? `
                                    <div class="mb-2">
                                        <strong style="color: #555; display: inline-block; width: 120px;">Size:</strong>
                                        <span style="color: #2d3748; font-weight: 600;">${sizeInfo}</span>
                                    </div>` : ''}
                            </div>
                        </div>
                    </div>
                    ${custom.price && custom.price > 0 ? `
                        <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f0f7ed 0%, #e8f5e9 100%); border-radius: 12px; border: 2px solid #8bc34a;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5 class="mb-1" style="color: #5b6b46; font-weight: 700;"><i class="fas fa-tag me-2"></i>Quoted Price</h5></div>
                                <div class="text-end"><div style="font-size: 28px; font-weight: 800; color: #5b6b46;">₱${parseFloat(custom.price).toFixed(2)}</div></div>
                            </div>
                        </div>` : ''}
                    ${custom.status === 'submitted' ? `
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Request Submitted</strong><br>
                            <small>Your customization request has been submitted and is waiting for admin review.</small>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-danger" onclick="cancelCustomizationRequest(${custom.id})">
                                <i class="fas fa-times me-2"></i>Cancel Request
                            </button>
                        </div>` : ''}
                    ${custom.status === 'pending' ? `
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Waiting for Price Quote</strong><br>
                            <small>Your customization request is pending admin review. Once approved, you'll receive a price quote for your custom design.</small>
                        </div>` : ''}
                    ${custom.status === 'approved' ? `
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Price Quote Received - Awaiting Your Confirmation</strong><br>
                            <small>Please review the quoted price above. Click "Accept" to proceed with the customization or "Decline" to cancel the request.</small>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-success flex-fill" onclick="openCustomizationCheckout(${custom.id}, ${custom.price || 0}, '${custom.reference_image_path || ''}', '${custom.neckline_type || ''}', '${custom.sleeve_type || ''}', '${custom.fit_type || ''}', '${custom.color_preference_1 || ''}')">
                                <i class="fas fa-check me-2"></i>Accept Price & Proceed
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill" onclick="confirmCustomizationPrice(${custom.id}, 'decline')">
                                <i class="fas fa-times me-2"></i>Decline
                            </button>
                        </div>` : ''}
                    ${custom.status === 'verifying' ? `
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-check-double me-2"></i>
                            <strong>Order Verification</strong><br>
                            <small>Your order is being verified by our team. We'll start production once verification is complete.</small>
                        </div>` : ''}
                    ${custom.status === 'in_progress' ? `
                        <div class="alert alert-primary mt-3">
                            <i class="fas fa-spinner me-2"></i>
                            <strong>In Production</strong><br>
                            <small>Your customization is currently being processed.</small>
                        </div>` : ''}
                    ${custom.status === 'completed' ? `
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Completed</strong><br>
                            <small>Your customization has been completed and is ready for pickup/delivery!</small>
                        </div>` : ''}
                    ${custom.status === 'cancelled' && custom.cancel_reason ? `
                        <div class="alert alert-danger mt-3">
                            <h6 class="mb-2"><i class="fas fa-times-circle me-2"></i>Cancelled</h6>
                            <p class="mb-0"><strong>Reason:</strong> ${custom.cancel_reason}</p>
                            ${custom.cancelled_at ? `<small class="text-muted d-block mt-2"><i class="far fa-clock me-1"></i>Cancelled on: ${new Date(custom.cancelled_at).toLocaleString()}</small>` : ''}
                        </div>` : ''}
                </div>
            </div>`;
    }
    
    // Main polling function
    function pollOrders() {
        fetch('get_orders_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateOrdersTab(data.orders);
                    updateSubcontractsTab(data.subcontracts);
                    updateCustomizationsTab(data.customizations);
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
            });
    }
    
    // Start polling when page loads
    function startPolling() {
        // Attach filter events on initial load
        attachFilterEvents();
        
        // Initial poll
        setTimeout(() => pollOrders(), 2000);
        
        // Set up interval
        pollingInterval = setInterval(pollOrders, POLL_INTERVAL);
        console.log('Orders polling started (every ' + (POLL_INTERVAL / 1000) + ' seconds)');
    }
    
    // Stop polling (e.g., when user leaves page)
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
            console.log('Orders polling stopped');
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startPolling);
    } else {
        startPolling();
    }
    
    // Stop polling when page is hidden/unloaded
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    });
    
    window.addEventListener('beforeunload', stopPolling);
    
})();
