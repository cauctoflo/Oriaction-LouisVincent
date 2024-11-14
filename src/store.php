<?php

// Désactiver l'affichage des erreurs pour la production


    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "oriaction";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Define the number of formations dynamically
    $formation_count = 3; // Par exemple, 3 formations

    // Create table if not exists with dynamic formation fields
    $sql = "CREATE TABLE IF NOT EXISTS Formations (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        firstname VARCHAR(50) NOT NULL UNIQUE,
        phone VARCHAR(20),
        email VARCHAR(50) UNIQUE,
        is_active BOOLEAN DEFAULT NULL,
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    for ($i = 1; $i <= $formation_count; $i++) {
        $sql .= ", formation$i VARCHAR(50), interest$i INT(2)";
    }
    $sql .= ")";

    $conn->query($sql);

    // Initialize the success message
    $insert_success = false;
    $error_message = "";  // Variable to hold error message

    // Insert data into table
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = isset($_POST['lastname']) ? trim($_POST['lastname']) : null;
        $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : null;
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;
        $is_active = isset($_POST['accept']) ? 1 : 0;

        if (empty($email)) {
            $insert_success = false;
            $error_message = "Problème : L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $insert_success = false;
            $error_message = "Problème : L'adresse email n'est pas valide.";
        }
        
        $formations = isset($_POST['formation']) ? $_POST['formation'] : [];
        $interests = isset($_POST['interest-rating']) ? $_POST['interest-rating'] : [];

        // Check if name, firstname, email are provided and not empty
        if (empty($name) || empty($firstname) || empty($email)) {
            $insert_success = false;
            $error_message = "Problème : Veuillez remplir tous les champs requis.";
        } else {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $insert_success = false;
                $error_message = "Problème : L'adresse email n'est pas valide.";
            } else {
                // Check for duplicate email
                $email_check_query = $conn->prepare("SELECT email FROM Formations WHERE email = ?");
                $email_check_query->bind_param("s", $email);
                $email_check_query->execute();
                $email_check_query->store_result();

                if ($email_check_query->num_rows > 0) {
                    $email_check_query->close();
                    $insert_success = false;
                    $error_message = "Problème : Cet email est déjà utilisé.";
                } else {
                    // Prepare the dynamic SQL for insertion
                    $columns = "name, firstname, phone, email, is_active";
                    $placeholders = "?, ?, ?, ?, ?";
                    $values = [$name, $firstname, $phone, $email, $is_active];

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
                        $insert_success = true;
                    } else {
                        $error_message = "Erreur : " . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }

        $conn->close();
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci de votre réponse</title>
    <link rel="stylesheet" href="../src/output.css">
</head>
<body class="relative">

    <!-- Image de fond fixe en pleine largeur et hauteur de l'écran -->
    <div class="fixed inset-0 w-screen h-screen bg-cover bg-center" style="background-image: url('../src/images/blank-792125_1920.jpg');"></div>

    <!-- Conteneur principal pour le formulaire avec défilement par-dessus l'image -->
    <div class="relative flex items-center justify-center min-h-screen">
        <div class="bg-white bg-opacity-90 shadow-lg mx-auto w-full max-w-[700px] p-12 rounded-2xl">
            <div class="text-center mb-8">
                <img src="../src/images/logo.png" alt="Lycée Louis Vincent" class="mx-auto mb-5 w-32 h-32">
                
                <!-- Message en fonction de l'insertion -->
                <?php if ($insert_success): ?>
                    <h1 class="text-3xl font-bold text-[#07074D] mb-5">À très bientôt !</h1>
                    <p class="mb-5 text-base text-[#6B7280]">
                        Merci pour vos réponses ! Nous sommes ravis d’avoir pu vous accompagner lors de cet événement d’orientation. Vos retours sont précieux pour nous aider à améliorer chaque édition. Nous avons hâte de vous retrouver lors de nos prochains événements ! <br> Pour plus d'informations rendez-vous sur notre <a href="https://www.lycee-louis-vincent.fr/"> <span class="underline">site</span></a> !
                    </p>
                <?php else: ?>
                    <a href="/src/formu.html"
                        class="text-sm text-[#780b10] hover:text-[#780b10]/75 font-medium transition duration-300">
                        ← Retour
                    </a>
                    <h1 class="text-3xl font-bold text-[#07074D] mb-5">Erreur d'inscription</h1>
                    <p class="mb-5 text-base text-[#6B7280]">
                        <?php echo $error_message; ?>
                    </p>
                <?php endif; ?>
                
                
            </div>
        </div>
    </div>
</body>
</html>
