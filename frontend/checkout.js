let cart = JSON.parse(localStorage.getItem("cart")) || [];

const orderItems = document.getElementById("orderItems");
const orderTotal = document.getElementById("orderTotal");

let total = 0;

cart.forEach(function(book) {
    const item = document.createElement("p");

    item.textContent = book.title + " - " + book.price + " ETB";

    orderItems.appendChild(item);

    total += book.price;
});

orderTotal.textContent = total;

const checkoutForm = document.getElementById("checkoutForm");

checkoutForm.addEventListener("submit", function(event) {
    event.preventDefault();

    if (cart.length === 0) {
        alert("Your cart is empty!");
        return;
    }

    alert("Your order has been submitted successfully!");

    localStorage.removeItem("cart");

    window.location.href = "index.html";
});