<?php
require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function productQuery(PDO $conn, string $where = '', array $params = [], string $orderBy = 'p.created_at DESC'): array {
    $sql = "SELECT p.*, c.name AS category_name, COALESCE(AVG(r.rating), 0) AS average_rating, COUNT(r.id) AS review_count
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN reviews r ON r.product_id = p.id";
    if ($where) {
        $sql .= ' WHERE ' . $where;
    }
    $sql .= " GROUP BY p.id, c.name ORDER BY $orderBy";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

switch ($action) {
    case 'products':
        $search = sanitizeString($_GET['search'] ?? '');
        $category = sanitizeString($_GET['category'] ?? '');
        $sort = sanitizeString($_GET['sort'] ?? 'latest');
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(LOWER(p.name) LIKE LOWER(?) OR LOWER(p.description) LIKE LOWER(?))';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($category !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $category;
        }
        $orderBy = match ($sort) {
            'price_low' => 'p.price ASC',
            'price_high' => 'p.price DESC',
            'rating' => 'average_rating DESC',
            default => 'p.created_at DESC',
        };
        jsonResponse(['products' => productQuery($conn, implode(' AND ', $where), $params, $orderBy)]);
        break;

    case 'product':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT p.*, c.name AS category_name, COALESCE(AVG(r.rating), 0) AS average_rating, COUNT(r.id) AS review_count
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN reviews r ON r.product_id = p.id
            WHERE p.id = ? GROUP BY p.id, c.name");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            jsonResponse(['message' => 'Product not found'], 404);
        }
        $reviewStmt = $conn->prepare("SELECT r.*, u.name FROM reviews r INNER JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
        $reviewStmt->execute([$id]);
        jsonResponse(['product' => $product, 'reviews' => $reviewStmt->fetchAll()]);
        break;

    case 'categories':
        $stmt = $conn->query('SELECT * FROM categories ORDER BY name ASC');
        jsonResponse(['categories' => $stmt->fetchAll()]);
        break;

    case 'register':
        $data = $_POST ?: getJsonInput();
        $name = sanitizeString($data['name'] ?? '');
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        if (!$name || !$email || strlen($password) < 6) {
            jsonResponse(['message' => 'Please provide valid registration details.'], 422);
        }
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['message' => 'Email is already registered.'], 409);
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insert = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?) RETURNING id');
        $insert->execute([$name, $email, $hash]);
        $userId = $insert->fetchColumn();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        jsonResponse(['message' => 'Registration successful.', 'user_id' => $userId]);
        break;

    case 'login':
        $data = $_POST ?: getJsonInput();
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        jsonResponse(['message' => 'Login successful.', 'user' => ['id' => $user['id'], 'name' => $user['name']]]);
        break;

    case 'logout':
        session_destroy();
        jsonResponse(['message' => 'Logged out successfully.']);
        break;

    case 'cart_add':
        $data = $_POST ?: getJsonInput();
        $productId = (int) ($data['product_id'] ?? 0);
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        if (isLoggedIn()) {
            $stmt = $conn->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON CONFLICT (user_id, product_id) DO UPDATE SET quantity = cart.quantity + EXCLUDED.quantity, updated_at = CURRENT_TIMESTAMP');
            $stmt->execute([$_SESSION['user_id'], $productId, $quantity]);
        }
        jsonResponse(['message' => 'Product added to cart.', 'guest' => !isLoggedIn()]);
        break;

    case 'cart_fetch':
        if (!isLoggedIn()) {
            jsonResponse(['items' => []]);
        }
        $stmt = $conn->prepare('SELECT c.*, p.name, p.price, p.image_url FROM cart c INNER JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.created_at DESC');
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(['items' => $stmt->fetchAll()]);
        break;

    case 'cart_update':
        if (!isLoggedIn()) jsonResponse(['message' => 'Unauthorized'], 401);
        $data = $_POST ?: getJsonInput();
        $stmt = $conn->prepare('UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?');
        $stmt->execute([max(1, (int) $data['quantity']), $_SESSION['user_id'], (int) $data['product_id']]);
        jsonResponse(['message' => 'Cart updated.']);
        break;

    case 'cart_remove':
        if (!isLoggedIn()) jsonResponse(['message' => 'Unauthorized'], 401);
        $data = $_POST ?: getJsonInput();
        $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$_SESSION['user_id'], (int) $data['product_id']]);
        jsonResponse(['message' => 'Item removed.']);
        break;

    case 'checkout':
        $data = $_POST ?: getJsonInput();
        $items = $data['items'] ?? [];
        if (!$items) jsonResponse(['message' => 'Cart is empty.'], 422);
        $name = sanitizeString($data['name'] ?? '');
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = sanitizeString($data['phone'] ?? '');
        $billing = sanitizeString($data['billing_address'] ?? '');
        $shipping = sanitizeString($data['shipping_address'] ?? '');
        if (!$name || !$email || !$billing || !$shipping) {
            jsonResponse(['message' => 'Please complete billing and shipping details.'], 422);
        }
        $productIds = array_map(fn($item) => (int) $item['product_id'], $items);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = [];
        foreach ($stmt->fetchAll() as $product) {
            $products[$product['id']] = $product;
        }
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($products[(int)$item['product_id']]['price'] ?? 0) * max(1, (int) $item['quantity']);
        }
        $orderNumber = 'LC-' . time();
        $orderStmt = $conn->prepare('INSERT INTO orders (user_id, order_number, customer_name, customer_email, customer_phone, billing_address, shipping_address, payment_method, subtotal, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $orderStmt->execute([isLoggedIn() ? $_SESSION['user_id'] : null, $orderNumber, $name, $email, $phone, $billing, $shipping, 'Cash on Delivery', $subtotal, $subtotal, 'Confirmed']);
        $orderId = $orderStmt->fetchColumn();
        $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            $product = $products[(int)$item['product_id']];
            $qty = max(1, (int) $item['quantity']);
            $itemStmt->execute([$orderId, $product['id'], $product['name'], $product['price'], $qty, $product['price'] * $qty]);
        }
        if (isLoggedIn()) {
            $clear = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
            $clear->execute([$_SESSION['user_id']]);
        }
        jsonResponse(['message' => 'Order placed successfully.', 'order_number' => $orderNumber]);
        break;

    case 'dashboard':
        if (!isLoggedIn()) jsonResponse(['message' => 'Unauthorized'], 401);
        $orders = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $orders->execute([$_SESSION['user_id']]);
        $user = $conn->prepare('SELECT id, name, email, phone, address, city, country, created_at FROM users WHERE id = ?');
        $user->execute([$_SESSION['user_id']]);
        jsonResponse(['user' => $user->fetch(), 'orders' => $orders->fetchAll()]);
        break;

    case 'profile_update':
        if (!isLoggedIn()) jsonResponse(['message' => 'Unauthorized'], 401);
        $data = $_POST ?: getJsonInput();
        $stmt = $conn->prepare('UPDATE users SET name = ?, phone = ?, address = ?, city = ?, country = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([
            sanitizeString($data['name'] ?? ''),
            sanitizeString($data['phone'] ?? ''),
            sanitizeString($data['address'] ?? ''),
            sanitizeString($data['city'] ?? ''),
            sanitizeString($data['country'] ?? 'Pakistan'),
            $_SESSION['user_id']
        ]);
        jsonResponse(['message' => 'Profile updated.']);
        break;

    case 'admin_login':
        $data = $_POST ?: getJsonInput();
        $stmt = $conn->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$data['email'] ?? '']);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify($data['password'] ?? '', $admin['password'])) {
            jsonResponse(['message' => 'Invalid admin credentials.'], 401);
        }
        $_SESSION['admin_id'] = $admin['id'];
        jsonResponse(['message' => 'Admin logged in.']);
        break;

    case 'admin_stats':
        if (!isAdmin()) jsonResponse(['message' => 'Unauthorized'], 401);
        $stats = [
            'products' => $conn->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'users' => $conn->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'orders' => $conn->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'revenue' => $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('Confirmed', 'Completed')")->fetchColumn(),
        ];
        jsonResponse(['stats' => $stats]);
        break;

    case 'admin_products':
        if (!isAdmin()) jsonResponse(['message' => 'Unauthorized'], 401);
        jsonResponse(['products' => productQuery($conn)]);
        break;

    case 'admin_save_product':
        if (!isAdmin()) jsonResponse(['message' => 'Unauthorized'], 401);
        $id = (int) ($_POST['id'] ?? 0);
        $imagePath = $_POST['image_url'] ?? 'images/product-placeholder.svg';
        if (!empty($_FILES['image']['name'])) {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['image']['name']);
            $destination = __DIR__ . '/../uploads/' . $filename;
            move_uploaded_file($_FILES['image']['tmp_name'], $destination);
            $imagePath = 'uploads/' . $filename;
        }
        $payload = [
            sanitizeString($_POST['name'] ?? ''),
            (int) ($_POST['category_id'] ?? 0),
            sanitizeString($_POST['slug'] ?? ''),
            sanitizeString($_POST['description'] ?? ''),
            sanitizeString($_POST['short_description'] ?? ''),
            (float) ($_POST['price'] ?? 0),
            (float) ($_POST['compare_price'] ?? 0),
            (int) ($_POST['stock'] ?? 0),
            $imagePath,
            isset($_POST['featured']) ? 'true' : 'false',
            sanitizeString($_POST['seo_title'] ?? ''),
            sanitizeString($_POST['seo_description'] ?? ''),
        ];
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE products SET name=?, category_id=?, slug=?, description=?, short_description=?, price=?, compare_price=?, stock=?, image_url=?, featured=?, seo_title=?, seo_description=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([...$payload, $id]);
        } else {
            $stmt = $conn->prepare('INSERT INTO products (name, category_id, slug, description, short_description, price, compare_price, stock, image_url, featured, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute($payload);
        }
        jsonResponse(['message' => 'Product saved successfully.']);
        break;

    case 'admin_delete_product':
        if (!isAdmin()) jsonResponse(['message' => 'Unauthorized'], 401);
        $data = $_POST ?: getJsonInput();
        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([(int) $data['id']]);
        jsonResponse(['message' => 'Product deleted.']);
        break;

    default:
        jsonResponse(['message' => 'Invalid action.'], 400);
}
?>
