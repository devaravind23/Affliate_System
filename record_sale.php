<?php
require 'db_connection.php';

// Fetch all users for the dropdown
$stmt = $pdo->query("SELECT id, name FROM users");
$users = $stmt->fetchAll();

function recordSale($userId, $amount) {
    global $pdo;

    // Check if the user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userExists = $stmt->fetchColumn();

    if (!$userExists) {
        throw new Exception("User ID $userId does not exist.");
    }

    // Insert the sale into the database
    $stmt = $pdo->prepare("INSERT INTO sales (user_id, amount) VALUES (?, ?)");
    $stmt->execute([$userId, $amount]);

    // Commission calculation logic
    $commissionRates = [10, 5, 3, 2, 1];
    $currentUserId = $userId;
    $level = 0;
    $commissions = [];

    while ($level < 5) {
        $stmt = $pdo->prepare("SELECT parent_id FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $parentId = $stmt->fetchColumn();

        if (!$parentId) break;

        // Calculate commission
        $commission = ($amount * $commissionRates[$level]) / 100;
        $commissions[] = ["level" => $level + 1, "amount" => number_format($commission, 2)];
        $currentUserId = $parentId;
        $level++;
    }

    return $commissions;
}

$warningMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $userId = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);

        if ($amount <= 0 || $userId <= 0) {
            echo "Invalid user ID or sale amount.";
        } else {
            $commissions = recordSale($userId, $amount);
        }
    } catch (Exception $e) {
        $warningMessage = "Error: " . $e->getMessage();
    }
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
                <label for="user_id">Select User:</label>
                <select class="form-control" id="user_id" name="user_id" required>
                    <option value="">Choose a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= $user['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Sale Amount:</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Record Sale</button>
        </form>

        <?php if ($warningMessage): ?>
            <div class="alert alert-warning mt-4"><?= $warningMessage ?></div>
        <?php endif; ?>

        <?php if (isset($commissions)): ?>
            <h2 class="mt-4">Payouts</h2>
            <ul class="list-group">
                <?php foreach ($commissions as $commission): ?>
                    <li class="list-group-item">Level <?= $commission['level'] ?>: $<?= $commission['amount'] ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <br>

           <a href="index.php" class="btn btn-primary">Back</a> 

    </div>


</body>
</html>
