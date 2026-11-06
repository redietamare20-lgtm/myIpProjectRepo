const form = document.getElementById("addBookForm");
const message = document.getElementById("message");

form.addEventListener("submit", function(event) {
    event.preventDefault();

    const book = {
        title: document.getElementById("title").value.trim(),
        author: document.getElementById("author").value.trim(),
        category: document.getElementById("category").value.trim(),
        price: document.getElementById("price").value,
        description: document.getElementById("description").value.trim()
    };

    fetch("../backend/add_book.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(book)
    })
    .then(response => response.json())
    .then(data => {
        message.textContent = data.message;

        if (data.success) {
            message.style.color = "green";
            form.reset();
        } else {
            message.style.color = "red";
        }
    })
    .catch(error => {
        message.textContent = "Something went wrong.";
        message.style.color = "red";
        console.error(error);
    });
});