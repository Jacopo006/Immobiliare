<?php
session_start();

// Controlla se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    // Se l'utente non è loggato, reindirizzalo alla pagina di login
    header('Location: login_utente.php');
    exit();
}

// Verifica se l'ID dell'immobile è stato fornito
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Reindirizza alla pagina immobili se l'ID non è valido
    header('Location: preferiti.php');
    exit();
}

include 'config.php'; // Includi il file di connessione

$immobile_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verifica se l'immobile è nei preferiti dell'utente
$sql_check = "SELECT id FROM preferiti WHERE id_utente = ? AND id_immobile = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $user_id, $immobile_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // L'immobile non è nei preferiti dell'utente
    $_SESSION['error_message'] = "L'immobile selezionato non è nei tuoi preferiti.";
    
    // Redirect alla pagina di provenienza o ai preferiti
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: preferiti.php');
    }
    exit();
}
$stmt_check->close();

// Rimuovi l'immobile dai preferiti
$sql_delete = "DELETE FROM preferiti WHERE id_utente = ? AND id_immobile = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("ii", $user_id, $immobile_id);

if ($stmt_delete->execute()) {
    $_SESSION['success_message'] = "Immobile rimosso dai preferiti con successo!";
} else {
    $_SESSION['error_message'] = "Errore durante la rimozione dai preferiti: " . $conn->error;
}
$stmt_delete->close();

// Redirect alla pagina di provenienza o alla pagina dell'immobile
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: immobile.php?id=' . $immobile_id);
}

$conn->close();
?>