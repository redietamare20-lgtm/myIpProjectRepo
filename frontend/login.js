const loginForm = document.getElementById("loginForm");

loginForm.addEventListener("submit", function(event) {

    event.preventDefault();

    const email = document.getElementById("email").value;

    const password = document.getElementById("password").value;

    const message = document.getElementById("loginMessage");

    if (email.trim() === "" || password.trim() === "") {

        message.textContent = "Please fill all fields.";

    } else {

        message.textContent = "Login form completed successfully!";

    }

});