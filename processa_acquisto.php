<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    $_SESSION['error_message'] = "Devi effettuare l'accesso come utente per acquistare un immobile.";
    header('Location: login_utente.php');
    exit();
}

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Errore: richiesta non valida.";
    header('Location: immobili.php');
    exit();
}

// Verifica che tutti i campi obbligatori siano presenti
$required_fields = ['id_immobile', 'prezzo', 'telefono', 'indirizzo', 'tipo_acquisto', 'metodo_pagamento', 'terms'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $_SESSION['error_message'] = "Tutti i campi obbligatori devono essere compilati.";
        header('Location: acquista.php?id=' . $_POST['id_immobile']);
        exit();
    }
}

// Includi il file di configurazione
include 'config.php';

$id_immobile = (int)$_POST['id_immobile'];
$id_utente = $_SESSION['user_id'];
$prezzo = (float)$_POST['prezzo'];
$telefono = $conn->real_escape_string($_POST['telefono']);
$indirizzo = $conn->real_escape_string($_POST['indirizzo']);
$tipo_acquisto = $conn->real_escape_string($_POST['tipo_acquisto']);
$metodo_pagamento = $conn->real_escape_string($_POST['metodo_pagamento']);
$note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';

// Verifica che l'immobile esista e sia disponibile
$sql_check = "SELECT * FROM immobili WHERE id = ? AND stato = 'disponibile'";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $id_immobile);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    $_SESSION['error_message'] = "L'immobile selezionato non è più disponibile.";
    header('Location: immobili.php');
    exit();
}

// Inizia transazione
$conn->begin_transaction();

try {
    // Aggiorna i dati dell'utente se necessario
    $sql_update_utente = "UPDATE utenti SET telefono = ?, indirizzo = ? WHERE id = ?";
    $stmt_update_utente = $conn->prepare($sql_update_utente);
    $stmt_update_utente->bind_param("ssi", $telefono, $indirizzo, $id_utente);
    $stmt_update_utente->execute();
    
    // Inserisci la transazione nel database
    $sql_transazione = "INSERT INTO transazioni (id_utente, id_immobile, importo, tipo, data_transazione, metodo_pagamento, note, stato_transazione) 
                        VALUES (?, ?, ?, ?, NOW(), ?, ?, 'in_corso')";
    $stmt_transazione = $conn->prepare($sql_transazione);
    $stmt_transazione->bind_param("iidsss", $id_utente, $id_immobile, $prezzo, $tipo_acquisto, $metodo_pagamento, $note);
    $stmt_transazione->execute();
    
    // Ottieni l'ID della transazione inserita
    $id_transazione = $conn->insert_id;
    
    // Aggiorna lo stato dell'immobile
    $nuovo_stato = ($tipo_acquisto == 'acquisto') ? 'venduto' : 'affittato';
    $sql_update_immobile = "UPDATE immobili SET stato = ? WHERE id = ?";
    $stmt_update_immobile = $conn->prepare($sql_update_immobile);
    $stmt_update_immobile->bind_param("si", $nuovo_stato, $id_immobile);
    $stmt_update_immobile->execute();
    
    // Rimuovi l'immobile dai preferiti degli utenti (se presente)
    $sql_delete_preferiti = "DELETE FROM preferiti WHERE id_immobile = ?";
    $stmt_delete_preferiti = $conn->prepare($sql_delete_preferiti);
    $stmt_delete_preferiti->bind_param("i", $id_immobile);
    $stmt_delete_preferiti->execute();
    
    // Commit della transazione
    $conn->commit();
    
    // Ottieni i dettagli dell'immobile per la conferma
    $sql_immobile = "SELECT i.nome, i.prezzo, a.email AS agente_email, a.nome AS agente_nome, a.cognome AS agente_cognome
                    FROM immobili i
                    LEFT JOIN agenti_immobiliari a ON i.agente_id = a.id
                    WHERE i.id = ?";
    $stmt_immobile = $conn->prepare($sql_immobile);
    $stmt_immobile->bind_param("i", $id_immobile);
    $stmt_immobile->execute();
    $result_immobile = $stmt_immobile->get_result();
    $immobile = $result_immobile->fetch_assoc();

    // Invia email di conferma all'utente (questa funzione dovrebbe essere implementata)
    if (function_exists('inviaEmailConfermaAcquisto')) {
        inviaEmailConfermaAcquisto($_SESSION['user_email'], $immobile['nome'], $prezzo, $tipo_acquisto, $id_transazione);
    }
    
    // Invia notifica all'agente (questa funzione dovrebbe essere implementata)
    if (!empty($immobile['agente_email']) && function_exists('inviaNotificaAgente')) {
        inviaNotificaAgente($immobile['agente_email'], $immobile['nome'], $_SESSION['user_name'], $_SESSION['user_email'], $telefono, $tipo_acquisto);
    }
    
    // Imposta messaggio di successo e reindirizza alla pagina di conferma
    $_SESSION['success_message'] = "La tua richiesta di " . ($tipo_acquisto == 'acquisto' ? 'acquisto' : 'affitto') . " è stata inviata con successo. Un nostro agente ti contatterà presto per finalizzare la transazione.";
    header('Location: conferma_acquisto.php?id=' . $id_transazione);
    
} catch (Exception $e) {
    // In caso di errore, esegui il rollback della transazione
    $conn->rollback();
    
    $_SESSION['error_message'] = "Si è verificato un errore durante l'elaborazione della richiesta. Riprova più tardi.";
    header('Location: immobili.php');
    exit();
}

$conn->close(); // Chiudi la connessione
?>