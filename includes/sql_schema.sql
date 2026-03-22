CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Pakistan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'super_admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    short_description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),
    stock INT DEFAULT 0,
    image_url VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    seo_title VARCHAR(180),
    seo_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cart (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, product_id)
);

CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30),
    billing_address TEXT NOT NULL,
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery',
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INT REFERENCES products(id) ON DELETE SET NULL,
    product_name VARCHAR(150) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    line_total DECIMAL(10,2) NOT NULL
);

INSERT INTO categories (name, slug, description) VALUES
('Bags', 'bags', 'Handmade crochet bags and totes'),
('Home Decor', 'home-decor', 'Warm decor accents for modern spaces'),
('Accessories', 'accessories', 'Wearable craft accessories'),
('Baby Items', 'baby-items', 'Soft handmade baby-friendly crochet products')
ON CONFLICT (slug) DO NOTHING;


INSERT INTO users (name, email, password, phone, address, city, country)
VALUES ('Demo Shopper', 'demo@lomi.local', '$2y$12$uoo6ECQLXjIiw5u/Y63jP.TUGMPP/P06tEAqaltmzaSY8MNZyoa1a', '+92 300 1234567', 'Clifton Block 5', 'Karachi', 'Pakistan')
ON CONFLICT (email) DO NOTHING;

INSERT INTO admin (name, email, password)
VALUES ('Admin User', 'admin@[WEBSITE_NAME].com', '$2y$12$R8C49NH5cy1oztNRTLmZjek.2Y3kxSpvpuQzQDl5doZ.jvLOGPx7O')
ON CONFLICT (email) DO NOTHING;

INSERT INTO products (category_id, name, slug, description, short_description, price, compare_price, stock, image_url, featured, seo_title, seo_description)
SELECT c.id, 'Aurora Tote Bag', 'aurora-tote-bag', 'A premium handmade crochet tote bag designed for daily use with strong handles and a modern textured weave.', 'Elegant tote for everyday carry.', 3499.00, 3999.00, 12, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80', TRUE, 'Aurora Tote Bag | Lomi Crochet', 'Shop the Aurora handmade crochet tote bag in Pakistan.' FROM categories c WHERE c.slug='bags'
ON CONFLICT (slug) DO NOTHING;

INSERT INTO products (category_id, name, slug, description, short_description, price, compare_price, stock, image_url, featured, seo_title, seo_description)
SELECT c.id, 'Cozy Nursery Basket', 'cozy-nursery-basket', 'A soft and stylish crochet storage basket perfect for nurseries, toy storage, and modern home organization.', 'Soft storage basket for modern homes.', 2799.00, 3299.00, 8, 'https://images.unsplash.com/photo-1517705008128-361805f42e86?auto=format&fit=crop&w=900&q=80', TRUE, 'Cozy Nursery Basket | Lomi Crochet', 'Handmade crochet storage basket for stylish spaces.' FROM categories c WHERE c.slug='home-decor'
ON CONFLICT (slug) DO NOTHING;

INSERT INTO products (category_id, name, slug, description, short_description, price, compare_price, stock, image_url, featured, seo_title, seo_description)
SELECT c.id, 'Blush Baby Booties', 'blush-baby-booties', 'Gentle handmade crochet baby booties crafted with soft yarn for comfort and gifting.', 'Soft baby booties for gifting.', 1599.00, 1899.00, 20, 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=900&q=80', FALSE, 'Blush Baby Booties | Lomi Crochet', 'Cute handmade crochet booties for babies in Pakistan.' FROM categories c WHERE c.slug='baby-items'
ON CONFLICT (slug) DO NOTHING;

INSERT INTO reviews (product_id, user_id, rating, review)
SELECT p.id, u.id, 5, 'Excellent finish and really beautiful in person.'
FROM products p, users u
WHERE p.slug = 'aurora-tote-bag' AND u.email = 'demo@lomi.local'
ON CONFLICT DO NOTHING;
