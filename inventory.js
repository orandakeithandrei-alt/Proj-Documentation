document.addEventListener("DOMContentLoaded", function () {
    if (!requireLogin()) return;

    const searchInput = document.getElementById("searchInput");
    searchInput.addEventListener("input", displayInventory);
    displayInventory();

    const params = new URLSearchParams(window.location.search);
    if (params.get("filter") === "low") {
        searchInput.value = "";
        displayInventory(true);
    }
});

function displayInventory(lowOnly = false) {
    const table = document.getElementById("inventoryTable");
    const search = document.getElementById("searchInput").value.trim().toLowerCase();
    let items = getInventory();

    if (lowOnly) items = items.filter(item => Number(item.stock) <= 10);
    if (search) {
        items = items.filter(item =>
            String(item.name).toLowerCase().includes(search) ||
            String(item.category).toLowerCase().includes(search)
        );
    }

    if (!items.length) {
        table.innerHTML = '<tr><td colspan="7" class="empty">No items found.</td></tr>';
        return;
    }

    table.innerHTML = items.map(item => `
        <tr>
            <td>${String(item.id).padStart(3, "0")}</td>
            <td>${escapeHTML(item.name)}</td>
            <td>${escapeHTML(item.category)}</td>
            <td>${formatPrice(item.price)}</td>
            <td>${Number(item.stock)}</td>
            <td><span class="status ${getStatusClass(item.stock)}">${getStatus(item.stock)}</span></td>
            <td>
                <button class="edit-button" onclick="editItem(${Number(item.id)})">Edit</button>
                <button class="delete-button" onclick="deleteItem(${Number(item.id)})">Delete</button>
            </td>
        </tr>
    `).join("");
}
