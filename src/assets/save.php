<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "oriaction";
$table = "formations";
$maxRowsPerFile = 20000; // Définir le nombre maximum de lignes par fichier

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupérer les données de la table
$sql = "SELECT * FROM $table";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $fileIndex = 1;
    $rowCount = 0;
    $headerWritten = false;
    $file = null;

    while ($row = $result->fetch_assoc()) {
        // Créer un nouveau fichier si nécessaire
        if ($rowCount % $maxRowsPerFile === 0) {
            if ($file) {
                fclose($file); // Fermer l'ancien fichier
            }
            $filename = "orication_backup_" . date('Ymd_His') . "_part$fileIndex.csv";
            $file = fopen($filename, 'w');
            $fileIndex++;

            // Écrire l'en-tête uniquement lors de la création d'un nouveau fichier
            if (!$headerWritten) {
                $headers = $result->fetch_fields();
                $headerRow = [];
                foreach ($headers as $header) {
                    $headerRow[] = $header->name;
                }
                fputcsv($file, $headerRow);
                $headerWritten = true;
            }
        }

        // Écrire les données de la ligne dans le fichier CSV
        fputcsv($file, $row);
        $rowCount++;
    }

    // Fermer le dernier fichier
    if ($file) {
        fclose($file);
    }

    echo "Backup completed. $fileIndex files created.";
} else {
    echo "No records found in the table.";
}

$conn->close();

// Fonction pour envoyer un fichier à Discord
function sendToDiscord($filename, $webhookUrl) {
    $filePath = realpath($filename);
    $fileData = file_get_contents($filePath);
    $boundary = uniqid();

    $headers = [
        "Content-Type: multipart/form-data; boundary=$boundary",
    ];

    $body = "--$boundary\r\n" .
        "Content-Disposition: form-data; name=\"file\"; filename=\"$filename\"\r\n" .
        "Content-Type: text/csv\r\n\r\n" .
        $fileData . "\r\n" .
        "--$boundary--";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        echo "File $filename sent to Discord successfully.\n";
    }
    curl_close($ch);
}

// URL du webhook Discord
$webhookUrl = "https://discord.com/api/webhooks/1297672116635701369/sSx_V8-xlL-xVXNs5tS_mBisH2LTGQ9MnzS7zwQ-B5sJYcxhdwbLfam-wFCSL6o4yDgJ";

// Envoyer tous les fichiers créés à Discord
for ($i = 1; $i < $fileIndex; $i++) {
    $filename = "orication_backup_" . date('Ymd_His') . "_part$i.csv";
    sendToDiscord($filename, $webhookUrl);
}
?>
