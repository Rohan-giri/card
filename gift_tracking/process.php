<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = 'root'; // Default for MAMP
$dbname = 'Cards';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for adding vouchers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucher_number = $_POST['voucher_number'];
    $amount = $_POST['amount'];
    $sale_date = $_POST['sale_date'];
    $customer_name = $_POST['customer_name'];
    $notes = $_POST['notes'];
    $payment_type = $_POST['payment_type'];

    // Insert data into the vouchers table
    $sql = "INSERT INTO vouchers (voucher_number, amount, current_value, sale_date, customer_name, notes, payment_type)
            VALUES ('$voucher_number', $amount, $amount, '$sale_date', '$customer_name', '$notes', '$payment_type')";

    if ($conn->query($sql) === TRUE) {
        header("Location: index.html?success=true");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
