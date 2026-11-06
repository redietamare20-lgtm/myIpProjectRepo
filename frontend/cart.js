const cartItems = document.getElementById("cartItems");
const totalPrice = document.getElementById("totalPrice");
const clearCart = document.getElementById("clearCart");

let cart = JSON.parse(localStorage.getItem("cart")) || [];

function displayCart() {
    cartItems.innerHTML = "";

    let total = 0;

    if (cart.length === 0) {
        cartItems.innerHTML = "<p>Your cart is empty.</p>";
    }

    cart.forEach(function(book, index) {
        const item = document.createElement("p");

        item.textContent =
            book.title + " - " + book.price + " ETB ";

        const removeButton = document.createElement("button");

        removeButton.textContent = "Remove";

        removeButton.addEventListener("click", function() {
            cart.splice(index, 1);

            localStorage.setItem("cart", JSON.stringify(cart));

            displayCart();
        });

        item.appendChild(removeButton);
        cartItems.appendChild(item);

        total += book.price;
    });

    totalPrice.textContent = total;
}

clearCart.addEventListener("click", function() {
    cart = [];

    localStorage.removeItem("cart");

    displayCart();
});

displayCart();