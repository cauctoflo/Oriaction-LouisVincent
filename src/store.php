<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "oriaction";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define the number of formations dynamically
$formation_count = 3; // Par exemple, 3 formations

// Create table if not exists with dynamic formation fields
$sql = "CREATE TABLE IF NOT EXISTS Formations (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(50) UNIQUE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

for ($i = 1; $i <= $formation_count; $i++) {
    $sql .= ", formation$i VARCHAR(50), interest$i INT(2)";
}
$sql .= ")";

$conn->query($sql);

// Insert data into table
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $formations = $_POST['formation'];
    $interests = $_POST['interest-rating'];

    // Check for duplicate email
    $email_check_query = $conn->prepare("SELECT email FROM Formations WHERE email = ?");
    $email_check_query->bind_param("s", $email);
    $email_check_query->execute();
    $email_check_query->store_result();

    if ($email_check_query->num_rows > 0) {
        $email_check_query->close();
        $conn->close();
        exit(); // Fin de script si email existe déjà
    }

    $email_check_query->close();

    // Prepare the dynamic SQL for insertion
    $columns = "name, firstname, phone, email";
    $placeholders = "?, ?, ?, ?";
    $values = [$name, $firstname, $phone, $email];

    for ($i = 1; $i <= $formation_count; $i++) {
        $formation = isset($formations[$i - 1]) ? $formations[$i - 1] : null;
        $interest = isset($interests[$i - 1]) ? $interests[$i - 1] : null;
        $columns .= ", formation$i, interest$i";
        $placeholders .= ", ?, ?";
        array_push($values, $formation, $interest);
    }

    $stmt = $conn->prepare("INSERT INTO Formations ($columns) VALUES ($placeholders)");
    $stmt->bind_param(str_repeat("s", count($values)), ...$values);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
header("Location: saved.html");

?>


