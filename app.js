// Shared utility functions
function formatPrice(price) {
    return '₱' + Number(price).toFixed(2);
}

function getStatusClass(stock, reorderLevel) {
    if (stock <= 0) return 'out_of_stock';
    if (stock <= reorderLevel) return 'low_stock';
    return 'available';
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});