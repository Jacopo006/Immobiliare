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
    header('Location: immobili.php');
    exit();
}

include 'config.php'; // Includi il file di connessione

$immobile_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verifica se l'immobile esiste e se è disponibile
$sql_check = "SELECT id FROM immobili WHERE id = ? AND stato = 'disponibile'";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $immobile_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // L'immobile non esiste o non è disponibile
    $_SESSION['error_message'] = "L'immobile selezionato non è disponibile.";
    header('Location: immobili.php');
    exit();
}
$stmt_check->close();

// Verifica se l'immobile è già nei preferiti
$sql_exists = "SELECT id FROM preferiti WHERE id_utente = ? AND id_immobile = ?";
$stmt_exists = $conn->prepare($sql_exists);
$stmt_exists->bind_param("ii", $user_id, $immobile_id);
$stmt_exists->execute();
$result_exists = $stmt_exists->get_result();

if ($result_exists->num_rows > 0) {
    // L'immobile è già nei preferiti, reindirizza alla pagina dei preferiti
    $_SESSION['info_message'] = "Questo immobile è già nei tuoi preferiti.";
    
    // Redirect alla pagina di provenienza o ai preferiti
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: preferiti.php');
    }
    exit();
}
$stmt_exists->close();

// Aggiungi l'immobile ai preferiti
$sql_insert = "INSERT INTO preferiti (id_utente, id_immobile, data_aggiunta) VALUES (?, ?, NOW())";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("ii", $user_id, $immobile_id);

if ($stmt_insert->execute()) {
    $_SESSION['success_message'] = "Immobile aggiunto ai preferiti con successo!";
} else {
    $_SESSION['error_message'] = "Errore durante l'aggiunta ai preferiti: " . $conn->error;
}
$stmt_insert->close();

// Redirect alla pagina di provenienza o alla pagina dell'immobile
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: immobile.php?id=' . $immobile_id);
}

$conn->close();
?>