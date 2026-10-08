let editingId = null;

document.addEventListener("DOMContentLoaded", function () {
    if (!requireLogin()) return;

    const params = new URLSearchParams(window.location.search);
    const editId = params.get("edit");

    if (editId !== null) loadItemForEdit(Number(editId));

    document.getElementById("itemForm").addEventListener("submit", saveItem);
});

function loadItemForEdit(id) {
    const item = getInventory().find(product => Number(product.id) === id);
    if (!item) {
        alert("Item not found.");
        window.location.href = "inventory.html";
        return;
    }

    editingId = id;
    document.getElementById("formTitle").textContent = "Edit Item";
    document.getElementById("pageTitle").textContent = "Edit Item";
    document.getElementById("saveButton").textContent = "Update Item";
    document.getElementById("itemName").value = item.name;
    document.getElementById("category").value = item.category;
    document.getElementById("price").value = item.price;
    document.getElementById("stock").value = item.stock;
    document.getElementById("description").value = item.description || "";
}

function saveItem(event) {
    event.preventDefault();

    const name = document.getElementById("itemName").value.trim();
    const category = document.getElementById("category").value;
    const price = Number(document.getElementById("price").value);
    const stock = Number(document.getElementById("stock").value);
    const description = document.getElementById("description").value.trim();

    if (!name) return alert("Please enter an item name.");
    if (!category) return alert("Please select a category.");
    if (!Number.isFinite(price) || price < 0) return alert("Please enter a valid price.");
    if (!Number.isInteger(stock) || stock < 0) return alert("Stock must be a whole number of 0 or more.");

    const items = getInventory();

    if (editingId !== null) {
        const item = items.find(product => Number(product.id) === editingId);
        if (!item) return alert("Item no longer exists.");
        item.name = name;
        item.category = category;
        item.price = price;
        item.stock = stock;
        item.description = description;
        saveInventory(items);
        alert("Item updated successfully!");
    } else {
        items.push({ id: nextId(items), name, category, price, stock, description });
        saveInventory(items);
        alert("Item added successfully!");
    }

    window.location.href = "inventory.html";
}

function cancelForm() {
    window.location.href = "inventory.html";
}
