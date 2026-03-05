document.addEventListener("DOMContentLoaded", function() {
    
    const passwordInput = document.getElementById("password");
    const toggleButton = document.getElementById("toggle-password");
    const loginForm = document.getElementById("login-form");
    const errorMessage = document.getElementById("error-message");

    toggleButton.addEventListener("click", function() {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleButton.textContent = "Hide";
            toggleButton.setAttribute("aria-label", "Hide password");
        } else {
            passwordInput.type = "password";
            toggleButton.textContent = "Show";
            toggleButton.setAttribute("aria-label", "Show password");
        }
    });

    loginForm.addEventListener("submit", function(event) {
        const email = document.getElementById("email").value.trim();
        const password = passwordInput.value.trim();

        if (email === "" || password === "") {
            event.preventDefault();
            
            errorMessage.textContent = "Error: Please enter both your email and password.";
            errorMessage.className = "error-visible";

            errorMessage.setAttribute("tabindex", "-1");
            errorMessage.focus();
        } else {
            errorMessage.className = "error-hidden";
        }
    });
});