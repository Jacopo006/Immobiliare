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

$user_id = $_SESSION['user_id'];

// Recupera la foto attuale
$sql = "SELECT foto_profilo FROM utenti WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Rimuovi la foto dal database
$sql = "UPDATE utenti SET foto_profilo = NULL WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Elimina il file fisico se esiste
    if (!empty($user_data['foto_profilo']) && file_exists($user_data['foto_profilo'])) {
        unlink($user_data['foto_profilo']);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Foto profilo rimossa con successo!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nella rimozione della foto']);
}

$stmt->close();
$conn->close();
?>