# Online Bookstore Management System (Real-Time Database Updates)

Pure HTML5 + CSS3 + vanilla JavaScript (Fetch API) + core PHP (PDO, sessions) + MySQL. No frameworks. Needs PHP 7.4 or newer (any current XAMPP).

## Install (XAMPP + VS Code)
1. Copy the `online_bookstore` folder to `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin -> **Import** -> choose `database/online_bookstore.sql` -> Import.
4. Open http://localhost/online_bookstore/

If your MySQL root user has a password, change it in `config/database.php`.
If the folder name or URL is different, change `BASE_URL` at the top of `includes/functions.php`.
For an existing installation, run the additive migration `database/migrations/001_create_reviews.sql` in phpMyAdmin.
Do not re-import `online_bookstore.sql` into an existing database; that setup script recreates its tables.

## Demo accounts
| Role     | Email                  | Password      |
|----------|------------------------|---------------|
| Admin    | admin@bookstore.com    | Admin@12345   |
| Customer | customer@bookstore.com | Customer@123  |

Change the admin password in Admin -> Settings after the first login.

## How the real-time updates work
`assets/js/realtime.js` calls `api/realtime.php` every 4 seconds. The API returns small checksums
("tokens") for books, orders and notifications. When a token changes, the browser reloads only the page
section marked `data-live="..."` (`BS.liveRefresh` in `main.js`). New notifications appear as toasts and
update the bell badge. Polling pauses while the browser tab is hidden and slows down if the server stops answering.

| Event                                   | Who sees the update                        |
|-----------------------------------------|--------------------------------------------|
| Admin adds/edits a book, price or stock | Customers/guests on Home, Books, Details, Cart |
| Customer places an order                | Admin dashboard, orders list + notification |
| Admin approves/rejects/updates an order | Customer orders pages + notification       |
| Customer buys a book                    | Stock drops for everyone                   |

## Folder structure
```
config/database.php      PDO connection          includes/   functions, auth, csrf, layout
api/                     JSON endpoints          assets/     css, js, images
admin/  customer/        panels                  database/   online_bookstore.sql
uploads/books/           cover images (PHP execution blocked by .htaccess)
```

## Security features
Prepared statements everywhere, `password_hash`/`password_verify`, CSRF token on every POST (forms and API),
output escaping with `e()`, role checks on every admin/customer page and API, session cookie flags +
`session_regenerate_id` on login, login attempt limit, image upload checks (real MIME type, size, random file name),
blocked accounts are logged out immediately, server-side validation, CSP and other security headers,
`.htaccess` deny rules for `config/`, `includes/` and `database/`.
Errors are logged (not shown). To see errors on your own PC set `APP_DEBUG` to `true` in `includes/functions.php`;
otherwise read `C:\xampp\apache\logs\error.log`.

## Order workflow
Cart -> checkout (address, phone) -> server re-checks stock and calculates the total inside a database
transaction (rows locked with `FOR UPDATE`, stock reduced with `stock >= qty` guard, so no overselling) ->
order + items + cash-on-delivery payment created -> cart cleared -> admin notified.
Rejecting/cancelling an order returns the stock. Delivered orders mark the payment as paid.
Online payment gateways are intentionally NOT implemented.

## Test checklist
1. Register a customer; log out; log in. 2. Log in as admin. 3. Add / edit / delete a book (with cover image).
4. Search by title, author; filter category; sort by price. 5. Add to cart, change quantity, remove.
6. Checkout; check stock dropped and admin got a notification. 7. Admin approves; customer sees new status without refresh.
8. Open Books in two browsers (one admin, one customer) and change a price: it updates in ~4 s.
9. Try to buy more than the stock: it is refused. 10. Block a customer: they are logged out on their next request.
11. Resize the window: layout adapts to tablet and phone widths.
12. Sign in as a customer with a delivered order; submit one review and confirm a second review is refused.

## Storefront and dashboard
The book catalogue is paginated (12 books per page) and keeps search, category, and sort filters when changing pages.
The home page highlights the newest books and ranks bestsellers from completed order data. The admin dashboard
draws its seven-day sales overview with the native Canvas API; it refreshes with the existing order polling.
Administrators can also create or block additional admin accounts and review order payments in the admin sidebar.
Admin accounts use the existing `users` table with the `admin` role; the project does not maintain a separate
`admins` table. Cash-on-delivery payment status follows order progress and can be reconciled in Admin -> Payments.
Verified customers can rate and review a book after its order is delivered; each customer can post one review per book.
Fresh database installs create the reviews table; existing installations should use the migration noted above.

## Not included
Email delivery, self-service password reset, newsletter subscriptions, and online payment gateways are not configured.
Checkout uses cash on delivery.
