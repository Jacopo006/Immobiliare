<?php
session_start();
header('Content-Type: application/json');

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit();
}

// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "immobiliare";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Errore di connessione al database']);
    exit();
}

// Verifica che sia stato inviato un file
if (!isset($_FILES['foto_profilo']) || $_FILES['foto_profilo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nessun file ricevuto o errore nell\'upload']);
    exit();
}

$file = $_FILES['foto_profilo'];
$user_id = $_SESSION['user_id'];

// Validazioni
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Controllo tipo file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo di file non supportato. Usa JPG, PNG o GIF.']);
    exit();
}

// Controllo dimensione
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Il file è troppo grande. Massimo 5MB.']);
    exit();
}

// Crea la directory se non esiste
$upload_dir = 'uploads/profile_photos/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Errore nella creazione della directory di upload']);
        exit();
    }
}

// Recupera la foto attuale per eliminarla
$sql = "SELECT foto_profilo FROM utenti WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Genera nome file unico
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Sposta il file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Aggiorna il database
    $sql = "UPDATE utenti SET foto_profilo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $upload_path, $user_id);
    
    if ($stmt->execute()) {
        // Elimina la foto precedente se esisteva
        if (!empty($user_data['foto_profilo']) && file_exists($user_data['foto_profilo'])) {
            unlink($user_data['foto_profilo']);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Foto profilo aggiornata con successo!',
            'foto_url' => $upload_path
        ]);
    } else {
        // Elimina il file se il database non è stato aggiornato
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento del database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel caricamento del file']);
}

$stmt->close();
$conn->close();
?>