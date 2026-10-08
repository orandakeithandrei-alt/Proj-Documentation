let cart = [];

document.addEventListener('DOMContentLoaded', function() {
    // Product click handler
    document.querySelectorAll('.product-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const stock = parseInt(this.dataset.stock);
            
            // Check if already in cart
            const existing = cart.find(function(c) { return c.item_id == id; });
            if (existing) {
                if (existing.quantity < stock) {
                    existing.quantity++;
                } else {
                    alert('Not enough stock!');
                    return;
                }
            } else {
                cart.push({
                    item_id: parseInt(id),
                    name: name,
                    price: price,
                    quantity: 1,
                    stock: stock
                });
            }
            updateCart();
        });
    });
    
    // Category filter
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const category = this.dataset.category;
            document.querySelectorAll('.product-item').forEach(function(item) {
                if (category === 'all' || item.dataset.category == category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Search
    document.getElementById('productSearch').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(function(item) {
            const name = item.dataset.name.toLowerCase();
            if (name.includes(search)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

function formatPrice(price) {
    return '₱' + Number(price).toFixed(2);
}

function updateCart() {
    const cartContainer = document.getElementById('cartItems');
    const totalElement = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        cartContainer.innerHTML = '<p class="empty">No items in cart</p>';
        totalElement.textContent = '₱0.00';
        checkoutBtn.disabled = true;
        return;
    }
    
    let total = 0;
    let html = '';
    cart.forEach(function(item, index) {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <strong>${item.name}</strong>
                    <span>₱${item.price.toFixed(2)} x ${item.quantity}</span>
                    <span class="cart-item-subtotal">₱${subtotal.toFixed(2)}</span>
                </div>
                <div class="cart-item-actions">
                    <button onclick="updateQuantity(${index}, -1)" class="qty-btn">-</button>
                    <span class="qty-display">${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, 1)" class="qty-btn">+</button>
                    <button onclick="removeFromCart(${index})" class="delete-button" style="margin-left:5px;">×</button>
                </div>
            </div>
        `;
    });
    
    cartContainer.innerHTML = html;
    totalElement.textContent = formatPrice(total);
    checkoutBtn.disabled = false;
    
    // Update form data
    document.getElementById('cartData').value = JSON.stringify(cart);
    document.getElementById('totalAmount').value = total;
    document.getElementById('grandTotal').value = total;
}

function updateQuantity(index, change) {
    const item = cart[index];
    const newQty = item.quantity + change;
    if (newQty <= 0) {
        cart.splice(index, 1);
    } else if (newQty <= item.stock) {
        item.quantity = newQty;
    } else {
        alert('Not enough stock!');
        return;
    }
    updateCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
}

function clearCart() {
    cart = [];
    updateCart();
}

function calculateChange() {
    const total = parseFloat(document.getElementById('totalAmount').value) || 0;
    const received = parseFloat(document.getElementById('paymentReceived').value) || 0;
    const change = received - total;
    document.getElementById('changeAmount').value = change >= 0 ? change.toFixed(2) : 0;
}