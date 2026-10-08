document.addEventListener("DOMContentLoaded", function () {
    getInventory();

    if (isLoggedIn()) {
        window.location.href = "dashboard.html";
        return;
    }

    document.getElementById("loginForm").addEventListener("submit", function (event) {
        event.preventDefault();

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value;
        const error = document.getElementById("loginError");

        if (username === "admin" && password === "admin1234") {
            localStorage.setItem(LOGIN_KEY, "true");
            window.location.href = "dashboard.html";
        } else {
            error.textContent = "Invalid username or password.";
        }
    });
});
