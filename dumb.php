Sure! Here’s a more elaborated application for managing affiliates, calculating sales, and displaying users with a user-friendly interface using PHP, MySQL, and a bit of Bootstrap for styling.

### Application Structure

1. **Database Setup**
2. **User Management**
3. **Sales Recording**
4. **User and Sales Display**
5. **Styling with Bootstrap**

### 1. Database Setup

Use the same SQL script as before:

```sql
CREATE DATABASE affiliate_system;

USE affiliate_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    parent_id INT,
    level INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 2. File Structure

Create a folder named `affiliate_system` in your web server's root directory. Inside that folder, create the following files:

- `db_connection.php`
- `add_user.php`
- `record_sale.php`
- `users.php`
- `sales.php`
- `index.php`
- Include Bootstrap CSS in `index.php`

### 3. Code Implementation

#### `db_connection.php`

```php
<?php
$host = 'localhost';
$db = 'affiliate_system';
$user = 'root'; // Change as needed
$pass = ''; // Change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
```

#### `index.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Affiliate System</h1>
        <nav class="nav nav-pills flex-column flex-sm-row mb-4">
            <a class="flex-sm-fill text-sm-center nav-link" href="add_user.php">Add User</a>
            <a class="flex-sm-fill text-sm-center nav-link" href="record_sale.php">Record Sale</a>
            <a class="flex-sm-fill text-sm-center nav-link" href="users.php">View Users</a>
            <a class="flex-sm-fill text-sm-center nav-link" href="sales.php">View Sales</a>
        </nav>
    </div>
</body>
</html>
```

#### `add_user.php`

```php
<?php
require 'db_connection.php';

function addUser($name, $email, $parentId = null) {
    global $pdo;

    $level = 1;
    if ($parentId) {
        $stmt = $pdo->prepare("SELECT level FROM users WHERE id = ?");
        $stmt->execute([$parentId]);
        $parentLevel = $stmt->fetchColumn();
        $level = $parentLevel + 1;
    }

    if ($level > 5) {
        echo "Cannot add user beyond level 5.";
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, email, parent_id, level) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $parentId, $level]);
    echo "User added successfully!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    addUser($name, $email, $parentId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Add User</h1>
        <form method="post" action="">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="parent_id">Parent User ID (optional):</label>
                <input type="number" class="form-control" id="parent_id" name="parent_id">
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
    </div>
</body>
</html>
```

#### `record_sale.php`

```php
<?php
require 'db_connection.php';

function recordSale($userId, $amount) {
    global $pdo;

    $stmt = $pdo->prepare("INSERT INTO sales (user_id, amount) VALUES (?, ?)");
    $stmt->execute([$userId, $amount]);

    $commissionRates = [10, 5, 3, 2, 1];
    $currentUserId = $userId;
    $level = 0;

    $commissions = [];
    while ($level < 5) {
        $stmt = $pdo->prepare("SELECT parent_id FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $parentId = $stmt->fetchColumn();

        if (!$parentId) break;

        $commission = ($amount * $commissionRates[$level]) / 100;
        $commissions[] = ["level" => $level + 1, "amount" => number_format($commission, 2)];
        $currentUserId = $parentId;
        $level++;
    }

    return $commissions;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);

    $commissions = recordSale($userId, $amount);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Sale</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Record Sale</h1>
        <form method="post" action="">
            <div class="form-group">
                <label for="user_id">User ID:</label>
                <input type="number" class="form-control" id="user_id" name="user_id" required>
            </div>
            <div class="form-group">
                <label for="amount">Sale Amount:</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Record Sale</button>
        </form>
        <?php if (isset($commissions)): ?>
            <h2 class="mt-4">Payouts</h2>
            <ul class="list-group">
                <?php foreach ($commissions as $commission): ?>
                    <li class="list-group-item">Level <?= $commission['level'] ?>: $<?= $commission['amount'] ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
```

#### `users.php`

```php
<?php
require 'db_connection.php';

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Users</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Parent ID</th>
                    <th>Level</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $user['name'] ?></td>
                        <td><?= $user['

email'] ?></td>
                        <td><?= $user['parent_id'] ?></td>
                        <td><?= $user['level'] ?></td>
                        <td><?= $user['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
```

#### `sales.php`

```php
<?php
require 'db_connection.php';

$stmt = $pdo->query("SELECT sales.id, users.name, sales.amount, sales.created_at FROM sales JOIN users ON sales.user_id = users.id");
$sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Sales</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Sales</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= $sale['id'] ?></td>
                        <td><?= $sale['name'] ?></td>
                        <td>$<?= number_format($sale['amount'], 2) ?></td>
                        <td><?= $sale['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
```

### 4. Running the Application

1. **Set Up Database**: Execute the SQL commands to set up the database and tables.
2. **Add Files**: Create the PHP files as described above.
3. **Start Web Server**: Make sure your web server is running.
4. **Access the Application**: Open your browser and go to `http://localhost/affiliate_system/index.php`.

### Summary

This application provides:

- **User Management**: Add users with a hierarchy.
- **Sales Recording**: Record sales and view calculated commissions.
- **Display Interfaces**: User and sales tables with Bootstrap for styling.

Feel free to enhance the application further with features like user authentication, editing users, and sales, or adding pagination to the tables. If you have any questions or need more functionality, let me know!