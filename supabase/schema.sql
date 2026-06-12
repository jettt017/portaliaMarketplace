-- Supabase PostgreSQL schema for Portalia

CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role TEXT DEFAULT 'student' CHECK (role IN ('student', 'admin')),
  nim VARCHAR(20) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT 'assets/images/avatar/avatar.jpg',
  status TEXT DEFAULT 'active' CHECK (status IN ('active', 'suspended')),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  icon VARCHAR(50) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id SERIAL PRIMARY KEY,
  seller_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(12, 2) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  item_condition TEXT DEFAULT 'used' CHECK (item_condition IN ('new', 'like_new', 'used')),
  stock INT DEFAULT 1,
  status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'rejected', 'expired')),
  rejection_reason TEXT DEFAULT NULL,
  expiration_date DATE DEFAULT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE wishlist (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, product_id)
);

CREATE TABLE chat_messages (
  id SERIAL PRIMARY KEY,
  sender_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  receiver_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  product_id INT DEFAULT NULL REFERENCES products(id) ON DELETE SET NULL,
  message TEXT NOT NULL,
  is_read SMALLINT DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
  id SERIAL PRIMARY KEY,
  buyer_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  seller_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  price DECIMAL(12, 2) NOT NULL,
  admin_fee DECIMAL(12, 2) NOT NULL,
  net_amount DECIMAL(12, 2) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
