<?php
// Initialisation des variables
$insert_success = false; // Défaut à false
$error_message = "";    // Message d"erreur par défaut

// Désactiver l"affichage des erreurs pour la production

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "oriaction";

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    http_response_code(500); // Envoyer une erreur HTTP 500
    exit;
}

// Définir dynamiquement le nombre de formations
$formation_count = 3; // Exemple : 3 formations

// Créer une table si elle n"existe pas, avec des champs de formation dynamiques
$sql = "CREATE TABLE IF NOT EXISTS Formations (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(50) UNIQUE,
    is_active BOOLEAN DEFAULT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

for ($i = 1; $i <= $formation_count; $i++) {
    $sql .= ", formation$i VARCHAR(50), interest$i INT(2)";
}
$sql .= ")";

if (!$conn->query($sql)) {
    http_response_code(500); // Envoyer une erreur HTTP 500
    exit;
}

// Insérer des données dans la table
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST["lastname"]) ? trim($_POST["lastname"]) : null;
    $firstname = isset($_POST["firstname"]) ? trim($_POST["firstname"]) : null;
    $phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : null;
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : null;
    $is_active = isset($_POST["accept"]) ? 1 : 0;

    // Vérifier si l'email est valide
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Si l'email n'est pas valide, afficher un message d'erreur
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur d\'Email</title>
    <link rel="stylesheet" href="../src/output.css">
</head>
<body class="relative">

    <!-- Image de fond fixe en pleine largeur et hauteur de l\'écran -->
    <div class="fixed inset-0 w-screen h-screen bg-cover bg-center" style="background-image: url(\'../src/images/blank-792125_1920.jpg\');"></div>

    <!-- Conteneur principal pour le formulaire avec défilement par-dessus l\'image -->
    <div class="relative flex items-center justify-center min-h-screen">
        <div class="bg-white bg-opacity-90 shadow-lg mx-auto w-full max-w-[700px] p-12 rounded-2xl">
            <div class="text-center mb-8">
                <img src="../src/images/logo.png" alt="Lycée Louis Vincent" class="mx-auto mb-5 w-32 h-32">
                
                <!-- Message d\'erreur pour l\'email invalide -->
                <h1 class="text-3xl font-bold text-[#07074D] mb-5">Erreur d\'Email</h1>
                <p class="mb-5 text-base text-[#6B7280]">
                    L\'email que vous avez fourni n\'est pas valide. Veuillez fournir un email valide.
                </p>
            </div>
        </div>
    </div>
</body>
</html>';
        exit; // Quitter le script
    }

    // Vérifier si l'email existe déjà dans la base de données
    $email_check_query = $conn->prepare("SELECT id FROM Formations WHERE email = ?");
    $email_check_query->bind_param("s", $email);
    $email_check_query->execute();
    $email_check_query->store_result();

    // Si l'email existe déjà
    if ($email_check_query->num_rows > 0) {
        // Fermer la requête
        $email_check_query->close();
        // Retourner une erreur HTTP 400 pour indiquer que l'email est déjà pris
        http_response_code(400); // Code de statut HTTP 400 pour mauvaise demande
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci de votre réponse</title>
    <link rel="stylesheet" href="../src/output.css">
</head>
<body class="relative">

    <!-- Image de fond fixe en pleine largeur et hauteur de l\'écran -->
    <div class="fixed inset-0 w-screen h-screen bg-cover bg-center" style="background-image: url(\'../src/images/blank-792125_1920.jpg\');"></div>

    <!-- Conteneur principal pour le formulaire avec défilement par-dessus l\'image -->
    <div class="relative flex items-center justify-center min-h-screen">
        <div class="bg-white bg-opacity-90 shadow-lg mx-auto w-full max-w-[700px] p-12 rounded-2xl">
            <div class="text-center mb-8">
                <img src="../src/images/logo.png" alt="Lycée Louis Vincent" class="mx-auto mb-5 w-32 h-32">
                
                <!-- Message d\'erreur si l\'email est déjà pris -->
                <h1 class="text-3xl font-bold text-[#07074D] mb-5">Erreur</h1>
                <p class="mb-5 text-base text-[#6B7280]">
                    L\'email que vous nous avez fourni est déjà utilisé.
                </p>
            </div>
        </div>
    </div>
</body>
</html>';
        exit; // Quitter le script
    }

    // Fermer la requête
    $email_check_query->close();

    // Récupérer les formations et les notes d'intérêt
    $formations = isset($_POST["formation"]) ? $_POST["formation"] : [];
    $interests = isset($_POST["interest-rating"]) ? $_POST["interest-rating"] : [];

    // Préparer la requête d'insertion dynamique
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
    if (!$stmt) {
        http_response_code(500); // Envoyer une erreur HTTP 500
        exit;
    }

    $stmt->bind_param(str_repeat("s", count($values)), ...$values);

    // Si l'insertion réussit, définir $insert_success à true
    if ($stmt->execute()) {
        $insert_success = true;
    } else {
        http_response_code(500); // Envoyer une erreur HTTP 500
        exit;
    }

    $stmt->close();
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

    <!-- Image de fond fixe en pleine largeur et hauteur de l"écran -->
    <div class="fixed inset-0 w-screen h-screen bg-cover bg-center" style="background-image: url('../src/images/blank-792125_1920.jpg');"></div>

    <!-- Conteneur principal pour le formulaire avec défilement par-dessus l"image -->
    <div class="relative flex items-center justify-center min-h-screen">
        <div class="bg-white bg-opacity-90 shadow-lg mx-auto w-full max-w-[700px] p-12 rounded-2xl">
            <div class="text-center mb-8">
                <img src="../src/images/logo.png" alt="Lycée Louis Vincent" class="mx-auto mb-5 w-32 h-32">
                
                <!-- Afficher le message si l"insertion est réussie -->
                <?php if ($insert_success): ?>
                    <h1 class="text-3xl font-bold text-[#07074D] mb-5">À très bientôt !</h1>
                    <p class="mb-5 text-base text-[#6B7280]">
                        Merci pour vos réponses ! Nous sommes ravis d’avoir pu vous accompagner lors de cet événement d’orientation. Vos retours sont précieux pour nous aider à améliorer chaque édition. Nous avons hâte de vous retrouver lors de nos prochains événements ! <br> Pour plus d"informations rendez-vous sur notre <a href="https://www.lycee-louis-vincent.fr/"> <span class="underline">site</span></a> !
                    </p>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</body>
</html>