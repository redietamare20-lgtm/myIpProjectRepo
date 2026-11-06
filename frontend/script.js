// Get HTML elements
const searchInput = document.getElementById("searchInput");

const searchButton = document.getElementById("searchButton");

const searchMessage = document.getElementById("searchMessage");

// Search function
function searchBooks() {

    let searchValue = searchInput.value.toLowerCase().trim();

    let books = document.querySelectorAll(".book");

    let found = 0;

    books.forEach(function(book) {

        let bookName = book.querySelector("h3").textContent.toLowerCase();

        if (bookName.includes(searchValue) && searchValue !== "") {

            book.style.display = "block";
            found++;

        } else if (searchValue === "") {

            book.style.display = "block";

        } else {

            book.style.display = "none";

        }

    });

    if (searchValue === "") {
        searchMessage.textContent = "Please enter a book name.";
    } else if (found > 0) {
        searchMessage.textContent = found + " book(s) found!";
    } else {
        searchMessage.textContent = "No books found!";
    }
}

// Button click event
searchButton.addEventListener("click", searchBooks);
const bookDetails = document.getElementById("bookDetails");

function showBookDetails(book) {
    document.getElementById("detailTitle").textContent =
        "Book Name: " + book.title;

    document.getElementById("detailAuthor").textContent =
        "Author: " + book.author;

    document.getElementById("detailPrice").textContent =
        "Price: " + book.price + " ETB";

    document.getElementById("detailCategory").textContent =
        "Category: " + book.category;

    bookDetails.classList.add("show");
}

document.getElementById("closeDetails").addEventListener("click", function() {
    bookDetails.classList.remove("show");
});
detailButtons.forEach(function(button) {
detailsButton.addEventListener("click", function() {
    showBookDetails(book);
});
   

});

// Close details
document.getElementById("closeDetails").addEventListener("click", function() {
    bookDetails.classList.remove("show");
});
// Add books to shopping cart
const addButtons = document.querySelectorAll(".addToCart");

addButtons.forEach(function(button) {
    button.addEventListener("click", function() {

        const title = button.dataset.title;
        const price = Number(button.dataset.price);

        let cart = JSON.parse(localStorage.getItem("cart")) || [];

        cart.push({
            title: title,
            price: price
        });

        localStorage.setItem("cart", JSON.stringify(cart));

        alert(title + " added to cart!");
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const bookContainer = document.getElementById("bookContainer");
console.log("Book container:", bookContainer);
    function loadBooks() {
        fetch("../backend/get_books.php")
            .then(response => {
                if (!response.ok) {
                    throw new Error("Failed to load books");
                }
                return response.json();
            })
            .then(books => {
                bookContainer.innerHTML = "";

               books.forEach(book => {
    const card = document.createElement("div");
    card.className = "book";

    const title = document.createElement("h3");
    title.textContent = book.title;

    const author = document.createElement("p");
    author.textContent = "Author: " + book.author;

    const price = document.createElement("p");
    price.textContent = "Price: " + book.price + " ETB";

    const detailsButton = document.createElement("button");
detailsButton.textContent = "View Details";

detailsButton.addEventListener("click", function() {
    alert(
        "Book: " + book.title +
        "\nAuthor: " + book.author +
        "\nPrice: " + book.price + " ETB" +
        "\nCategory: " + book.category
    );
});

const cartButton = document.createElement("button");
cartButton.textContent = "Add to Cart";

cartButton.addEventListener("click", function() {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    cart.push({
        title: book.title,
        price: Number(book.price)
    });

    localStorage.setItem("cart", JSON.stringify(cart));

    alert(book.title + " added to cart!");
});

card.append(title, author, price, detailsButton, cartButton);
bookContainer.appendChild(card);
});
            .catch(error => {
                console.error("Error:", error);
            });
    }

    loadBooks();

    setInterval(loadBooks, 5000);
});