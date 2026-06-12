-- Supabase PostgreSQL seed data for Portalia
-- Password hash for 'password' is: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Users (1 Admin, 3 Students)
INSERT INTO users (id, username, email, password, role, nim, phone, avatar, status) VALUES
(1, 'Administrator', 'admin@portalia.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '08123456789', 'assets/images/avatar/avatar.jpg', 'active'),
(2, 'Budi Santoso', 'budi@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2201010041', '08129876543', 'assets/images/avatar/avatar-1.jpg', 'active'),
(3, 'Siti Rahma', 'siti@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2201010052', '08571234567', 'assets/images/avatar/avatar-2.jpg', 'active'),
(4, 'Andi Wijaya', 'andi@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2101010012', '08781234588', 'assets/images/avatar/avatar-3.jpg', 'active');

-- Reset users sequence
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));

-- Categories
INSERT INTO categories (id, name, icon) VALUES
(1, 'Books', 'bi-book-half'),
(2, 'Electronics', 'bi-laptop'),
(3, 'Fashion & Clothes', 'bi-tags-fill'),
(4, 'Food & Drinks', 'bi-cup-hot-fill'),
(5, 'Campus Services', 'bi-gear-wide-connected'),
(6, 'Stationery', 'bi-pencil-square');

-- Reset categories sequence
SELECT setval('categories_id_seq', (SELECT MAX(id) FROM categories));

-- Products
INSERT INTO products (id, seller_id, category_id, name, description, price, image, item_condition, stock, status, expiration_date) VALUES
(1, 2, 1, 'Calculus 9th Edition - Varberg Purcell Rigdon', 'Used calculus book for informatics engineering students. Minor notes inside but pages are complete and clean.', 85000.00, 'assets/images/products/calculus.jpg', 'used', 1, 'active', '2026-12-31'),
(2, 3, 2, 'iPad Pro 11-inch M1 128GB Wi-Fi', 'Selling my iPad Pro M1 because of graduation. In excellent condition, no scratches, battery health is at 92%. Box and original charger included.', 9800000.00, 'assets/images/products/ipad.jpg', 'like_new', 1, 'active', '2026-09-30'),
(3, 4, 3, 'Almamater Jacket ITB Size L', 'Almamater jacket in prime condition. Only used during graduation ceremonies and formal campus seminars.', 150000.00, 'assets/images/products/jacket.jpg', 'like_new', 1, 'active', '2026-11-15'),
(4, 2, 4, 'Home Brew Cold Brew Coffee 250ml', 'Refreshing cold brew coffee made daily from premium Gayo Arabica beans. Freshly brewed and ready to drink.', 18000.00, 'assets/images/products/coffee.jpg', 'new', 20, 'active', '2026-06-15'),
(5, 3, 5, 'Academic Writing & Translation Service', 'Professional english academic translation and proofreading for thesis proposals, journals, and essays. Price per 500 words.', 50000.00, 'assets/images/products/service.jpg', 'new', 99, 'active', '2027-01-01'),
(6, 4, 1, 'Intro to Algorithms - CLRS 3rd Edition', 'Essential textbook for algorithm and data structure classes. Condition is decent with some highlighting on chapters 1-5.', 120000.00, 'assets/images/products/clrs.jpg', 'used', 1, 'pending', '2026-08-20'),
(7, 2, 2, 'Mechanical Keyboard Keychron K2 V2', 'Gateron Brown Switches, RGB Backlight, Wireless/Wired. Excellent mechanical keyboard for coding.', 850000.00, 'assets/images/products/keyboard.jpg', 'used', 1, 'pending', '2026-10-10');

-- Reset products sequence
SELECT setval('products_id_seq', (SELECT MAX(id) FROM products));

-- Wishlist
INSERT INTO wishlist (user_id, product_id) VALUES
(3, 1),
(4, 2);

-- Chat Messages
INSERT INTO chat_messages (sender_id, receiver_id, product_id, message, is_read) VALUES
(3, 2, 1, 'Hi Budi, is the Calculus book still available?', 1),
(2, 3, 1, 'Yes, Siti! Still available. I can meet you tomorrow near the central library if you want to inspect it.', 1),
(3, 2, 1, 'Great! How about tomorrow at 10 AM?', 0),
(4, 3, 2, 'Hi Siti, can I nego the iPad Pro to 9.2 million?', 1),
(3, 4, 2, 'Hello Andi, 9.5 million is the lowest I can go. It already includes the original stylus pen!', 0);

-- Transactions
INSERT INTO transactions (buyer_id, seller_id, product_id, price, admin_fee, net_amount, created_at) VALUES
(3, 4, 3, 150000.00, 7500.00, 142500.00, '2026-05-15 14:32:00'),
(4, 2, 1, 85000.00, 4250.00, 80750.00, '2026-05-20 09:15:00'),
(2, 3, 5, 50000.00, 2500.00, 47500.00, '2026-06-01 11:20:00');
