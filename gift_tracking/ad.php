<?php
// Reopen the database connection
$host = 'localhost';
$username = 'root';
$password = 'root'; // Default for MAMP
$dbname = 'Cards';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process Redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    $voucher_number = $_POST['voucher_number'];
    $redeem_amount = (float)$_POST['redeem_amount'];

    // Fetch current balance
    $result = $conn->query("SELECT current_value FROM vouchers WHERE voucher_number = '$voucher_number'");
    $row = $result->fetch_assoc();
    $current_value = (float)$row['current_value'];

    if ($redeem_amount > $current_value) {
        $error = "Redeem amount exceeds the remaining balance.";
    } else {
        // Update the balance
        $new_balance = $current_value - $redeem_amount;
        $conn->query("UPDATE vouchers SET current_value = $new_balance WHERE voucher_number = '$voucher_number'");
        $success = "Voucher redeemed successfully.";
    }
}

// Process Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $voucher_number = $_POST['voucher_number'];
    $conn->query("DELETE FROM vouchers WHERE voucher_number = '$voucher_number'");
    $success = "Voucher deleted successfully.";
}

// Fetch all voucher details
$result = $conn->query("SELECT voucher_number, amount AS initial_value, 
    (amount - current_value) AS total_redeemed, 
    current_value AS remaining_balance, 
    CASE 
        WHEN current_value = 0 THEN 'Redeemed' 
        ELSE 'Unredeemed' 
    END AS status, 
    sale_date AS issue_date, 
    customer_name, 
    payment_type 
    FROM vouchers");

// Fetch stats for total redeemed and remaining
$stats = $conn->query("SELECT 
    SUM(amount - current_value) AS total_redeemed, 
    SUM(current_value) AS total_remaining 
    FROM vouchers");
$stats_data = $stats->fetch_assoc();
$total_redeemed = $stats_data['total_redeemed'] ?? 0;
$total_remaining = $stats_data['total_remaining'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="everest.ico">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: url('https://i.ibb.co/QY9zswM/IMG-3371.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
        }

        header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            text-align: center;
            color: white;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin: 20px auto;
            max-width: 90%;
            flex-wrap: wrap;
        }

        .stats div {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 48%;
            margin-bottom: 10px;
        }

        .stats div h3 {
            margin: 0;
            font-size: 16px;
            color: #555;
        }

        .stats div p {
            font-size: 20px;
            font-weight: bold;
            color: #004db3;
        }

        .dashboard {
            max-width: 90%;
            margin: 20px auto;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .grid-item {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .grid-item h3 {
            margin-top: 0;
            font-size: 18px;
        }

        .grid-item p {
            margin: 5px 0;
            font-size: 14px;
        }

        .grid-item .actions {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
        }

        .actions button {
            font-size: 14px;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .redeem-button {
            background-color: #4CAF50;
            color: white;
        }

        .delete-button {
            background-color: #f44336;
            color: white;
        }

        .redeem-button:hover {
            background-color: #45a049;
        }

        .delete-button:hover {
            background-color: #d32f2f;
        }

        .message {
            text-align: center;
            margin: 10px auto;
            color: green;
            font-size: 16px;
        }

        .error {
            text-align: center;
            margin: 10px auto;
            color: red;
            font-size: 16px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: white;
            background-color: #004db3;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            background-color: #003a80;
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
    </header>

    <div class="stats">
        <div>
            <h3>Total Redeemed (£)</h3>
            <p><?php echo number_format($total_redeemed, 2); ?></p>
        </div>
        <div>
            <h3>Total Remaining (£)</h3>
            <p><?php echo number_format($total_remaining, 2); ?></p>
        </div>
    </div>

    <!-- Messages -->
    <?php if (!empty($success)): ?>
        <div class="message"><?php echo $success; ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Grid Layout -->
    <div class="dashboard">
        <div class="grid-container">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='grid-item'>
                            <h3>Voucher: {$row['voucher_number']}</h3>
                            <p>Initial Value: £" . number_format($row['initial_value'], 2) . "</p>
                            <p>Total Redeemed: £" . number_format($row['total_redeemed'], 2) . "</p>
                            <p>Remaining Balance: £" . number_format($row['remaining_balance'], 2) . "</p>
                            <p>Status: {$row['status']}</p>
                            <p>Issue Date: {$row['issue_date']}</p>
                            <p>Customer: {$row['customer_name']}</p>
                            <p>Payment Method: {$row['payment_type']}</p>
                            <div class='actions'>
                                <form method='POST' style='display: inline;'>
                                    <input type='hidden' name='voucher_number' value='{$row['voucher_number']}'>
                                    <input type='number' name='redeem_amount' step='0.01' placeholder='Amount' required>
                                    <button type='submit' name='redeem' class='redeem-button'>Redeem</button>
                                </form>
                                <form method='POST' style='display: inline;'>
                                    <input type='hidden' name='voucher_number' value='{$row['voucher_number']}'>
                                    <button type='submit' name='delete' class='delete-button'>Delete</button>
                                </form>
                            </div>
                          </div>";
                }
            } else {
                echo "<p>No vouchers found.</p>";
            }
            ?>
        </div>
    </div>

    <div class="back-link">
        <a href="index.html">Go Back to Form</a>
    </div>
</body>
</html>
