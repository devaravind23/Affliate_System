<?php
require 'db_connection.php';


function getUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, name FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addUser($name, $email, $parentId = null) {
    global $pdo;

    // Check if parentId exists
    if ($parentId) {
        $stmt = $pdo->prepare("SELECT level FROM users WHERE id = ?");
        $stmt->execute([$parentId]);
        $parentLevel = $stmt->fetchColumn();

        if ($parentLevel === false) {
            echo "Parent ID $parentId does not exist.";
            return;
        }

        $level = $parentLevel + 1;

        if ($level > 5) {
            echo "Cannot add user beyond level 5.";
            return;
        }
    } else {
        $level = 1; // Top level user
    }

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, parent_id, level) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $parentId, $level])) {
        exit(); // Ensure no further code is executed
    } else {
        echo "Error adding user.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    addUser($name, $email, $parentId);
}

$users = getUsers(); // Fetch existing users for the dropdown
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
                <label for="parent_id">Parent User:</label>
                <select class="form-control" id="parent_id" name="parent_id">
                    <option value="">Select Parent User (optional)</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= $user['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
        </form>

        <br>
                <a href="index.php" class="btn btn-primary">Back</a> 

    </div>

</body>
</html>
