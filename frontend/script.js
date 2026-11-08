(function () {
    'use strict';

    var books = [];
    var activeCategory = '';
    var form = document.getElementById('bookSearchForm');
    var searchInput = document.getElementById('searchInput');
    var bookContainer = document.getElementById('bookContainer');
    var categoryList = document.getElementById('categoryList');
    var searchMessage = document.getElementById('searchMessage');
    var menuToggle = document.querySelector('.store-menu-toggle');
    var navigation = document.getElementById('store-links');
    var displayLimit = 8;

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function createBookCard(book) {
        var card = element('article', 'store-book-card');
        var visual = element('div', 'store-book-visual');
        if (book.cover_image) {
            var cover = element('img', 'store-book-cover');
            cover.src = '../uploads/books/' + encodeURIComponent(book.cover_image);
            cover.alt = 'Cover of ' + book.title;
            cover.loading = 'lazy';
            cover.onerror = function () {
                var fallback = element('span', 'store-book-placeholder', (book.title || '?').trim().charAt(0).toUpperCase());
                cover.replaceWith(fallback);
            };
            visual.appendChild(cover);
        } else {
            visual.appendChild(element('span', 'store-book-placeholder', (book.title || '?').trim().charAt(0).toUpperCase()));
        }

        var info = element('div', 'store-book-info');
        info.appendChild(element('span', 'store-book-category', book.category_name || 'From the collection'));
        info.appendChild(element('h3', 'store-book-title', book.title || 'Untitled'));
        info.appendChild(element('p', 'store-book-author', book.author_name || 'Independent author'));
        var footer = element('div', 'store-book-footer');
        var price = Number(book.price);
        footer.appendChild(element('span', 'store-book-price', Number.isFinite(price)
            ? new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(price)
            : 'Ask for price'));
        var link = element('a', 'store-book-link', 'Book details →');
        link.href = '../book-details.php?id=' + encodeURIComponent(book.id);
        footer.appendChild(link);
        info.appendChild(footer);
        card.append(visual, info);
        return card;
    }

    function getVisibleBooks() {
        var query = searchInput.value.trim().toLocaleLowerCase();
        return books.filter(function (book) {
            var matchesCategory = !activeCategory || book.category_name === activeCategory;
            var matchesQuery = !query ||
                String(book.title || '').toLocaleLowerCase().includes(query) ||
                String(book.author_name || '').toLocaleLowerCase().includes(query);
            return matchesCategory && matchesQuery;
        });
    }

    function renderBooks() {
        var matched = getVisibleBooks();
        var visible = matched.slice(0, displayLimit);
        bookContainer.replaceChildren();

        if (!visible.length) {
            bookContainer.appendChild(element('p', 'store-empty-note',
                books.length ? 'No books match that search. Try another title or category.' : 'There are no books to show just yet.'));
        } else {
            visible.forEach(function (book) { bookContainer.appendChild(createBookCard(book)); });
        }

        if (matched.length > visible.length) {
            var more = element('a', 'store-more-link', 'Browse all books →');
            more.href = '../books.php';
            bookContainer.appendChild(more);
        }

        searchMessage.textContent = matched.length
            ? 'Showing ' + visible.length + ' of ' + matched.length + ' matching book' + (matched.length === 1 ? '' : 's')
            : 'No matching books';
    }

    function renderCategories() {
        var names = Array.from(new Set(books.map(function (book) {
            return book.category_name;
        }).filter(Boolean))).sort(function (a, b) { return a.localeCompare(b); });
        categoryList.replaceChildren();

        var all = element('button', 'store-category-chip is-active', 'All books');
        all.type = 'button';
        all.dataset.category = '';
        all.setAttribute('aria-pressed', 'true');
        categoryList.appendChild(all);

        names.forEach(function (name) {
            var button = element('button', 'store-category-chip', name);
            button.type = 'button';
            button.dataset.category = name;
            button.setAttribute('aria-pressed', 'false');
            categoryList.appendChild(button);
        });
    }

    async function loadBooks() {
        searchMessage.textContent = 'Finding books for you…';
        try {
            var response = await fetch('../api/books.php?sort=newest&limit=100', {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Books could not be loaded (' + response.status + ').');
            var result = await response.json();
            if (!result || result.ok !== true || !Array.isArray(result.books)) {
                throw new Error(result && result.message ? result.message : 'The book list could not be read.');
            }
            books = result.books;
            renderCategories();
            renderBooks();
        } catch (error) {
            console.error('Unable to load books:', error);
            bookContainer.replaceChildren();
            searchMessage.textContent = 'We could not connect to the book catalogue.';
            var retry = element('button', 'store-retry-button', 'Try again');
            retry.type = 'button';
            retry.addEventListener('click', loadBooks);
            bookContainer.appendChild(retry);
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        renderBooks();
        document.getElementById('books').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    searchInput.addEventListener('input', renderBooks);

    categoryList.addEventListener('click', function (event) {
        var button = event.target.closest('[data-category]');
        if (!button) return;
        activeCategory = button.dataset.category;
        categoryList.querySelectorAll('.store-category-chip').forEach(function (chip) {
            var selected = chip === button;
            chip.classList.toggle('is-active', selected);
            chip.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        renderBooks();
        document.getElementById('books').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    menuToggle.addEventListener('click', function () {
        var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        menuToggle.setAttribute('aria-label', expanded ? 'Open menu' : 'Close menu');
        navigation.classList.toggle('is-open', !expanded);
    });

    navigation.addEventListener('click', function (event) {
        if (!event.target.closest('a')) return;
        navigation.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Open menu');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        navigation.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Open menu');
        menuToggle.focus();
    });

    loadBooks();
})();
