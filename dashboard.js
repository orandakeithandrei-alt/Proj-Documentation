document.addEventListener("DOMContentLoaded", function () {
    if (!requireLogin()) return;
    updateDashboard();
});

function updateDashboard() {
    const items = getInventory();
    const totalStock = items.reduce((sum, item) => sum + Number(item.stock), 0);
    const lowStock = items.filter(item => Number(item.stock) <= 10).length;
    const value = items.reduce((sum, item) => sum + Number(item.price) * Number(item.stock), 0);

    document.getElementById("totalItems").textContent = items.length;
    document.getElementById("totalStock").textContent = totalStock;
    document.getElementById("lowStockCount").textContent = lowStock;
    document.getElementById("inventoryValue").textContent = formatPrice(value);

    const recent = document.getElementById("recentItems");
    const latest = items.slice(-5).reverse();

    if (!latest.length) {
        recent.innerHTML = '<tr><td colspan="5" class="empty">No inventory items yet.</td></tr>';
        return;
    }

    recent.innerHTML = latest.map(item => `
        <tr>
            <td>${escapeHTML(item.name)}</td>
            <td>${escapeHTML(item.category)}</td>
            <td>${formatPrice(item.price)}</td>
            <td>${Number(item.stock)}</td>
            <td><span class="status ${getStatusClass(item.stock)}">${getStatus(item.stock)}</span></td>
        </tr>
    `).join("");
}
