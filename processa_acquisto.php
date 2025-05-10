<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    $response = [
        'success' => false,
        'error' => "Devi effettuare l'accesso come utente per acquistare un immobile."
    ];
    echo json_encode($response);
    exit();
}

// Verifica se tutti i dati necessari sono stati forniti
if (!isset($_POST['id_immobile']) || !isset($_POST['metodo_pagamento']) || !isset($_POST['acconto'])) {
    $response = [
        'success' => false,
        'error' => "Dati mancanti per l'acquisto."
    ];
    echo json_encode($response);
    exit();
}

// Includi il file di configurazione
include 'config.php';

// Recupera i dati dal form
$id_immobile = (int)$_POST['id_immobile'];
$id_utente = $_SESSION['user_id'];
$metodo_pagamento = $_POST['metodo_pagamento'];
$tipo_acquisto = $_POST['tipo_acquisto'];
$acconto = (float)$_POST['acconto'];
$note = isset($_POST['note']) ? $_POST['note'] : '';

// Imposta il fuso orario locale
date_default_timezone_set('Europe/Rome');

// Genera un ID univoco per il pagamento
$payment_id = $metodo_pagamento . '_' . time() . '_' . $id_utente;

// Inizio transazione
mysqli_query($conn, "BEGIN");
$transaction_success = true;
$error_message = '';

try {
    // 1. Verifica se l'immobile è ancora disponibile
    $query_check = "SELECT * FROM immobili WHERE id = ? AND stato = 'disponibile' FOR UPDATE";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("i", $id_immobile);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows == 0) {
        throw new Exception("L'immobile selezionato non è più disponibile per l'acquisto.");
    }

    // 2. Inserisci il record di acquisto
    $query_insert = "INSERT INTO acquisti (id_immobile, id_utente, acconto, metodo_pagamento, tipo_acquisto, stato_pagamento, payment_id, note, data_acquisto) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, CURRENT_TIMESTAMP)";
    $stmt_insert = $conn->prepare($query_insert);
    $stmt_insert->bind_param("iidssss", $id_immobile, $id_utente, $acconto, $metodo_pagamento, $tipo_acquisto, $payment_id, $note);
    $stmt_insert->execute();

    // 3. Aggiorna lo stato dell'immobile
    $query_update = "UPDATE immobili SET stato = 'riservato' WHERE id = ?";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("i", $id_immobile);
    $stmt_update->execute();

    // 4. Inserisci una transazione
    $query_trans = "INSERT INTO transazioni (id_utente, id_immobile, data_transazione, importo, tipo) VALUES (?, ?, CURRENT_TIMESTAMP, ?, ?)";
    $stmt_trans = $conn->prepare($query_trans);
    $stmt_trans->bind_param("iids", $id_utente, $id_immobile, $acconto, $tipo_acquisto);
    $stmt_trans->execute();

    // Commit della transazione
    mysqli_query($conn, "COMMIT");

    // Notifica a schermo
    echo "<script>alert('Acquisto registrato con successo!'); window.location.href = 'conferma_acquisto.php?id=" . $stmt_insert->insert_id . "';</script>";

} catch (Exception $e) {
    mysqli_query($conn, "ROLLBACK");
    $transaction_success = false;
    echo "<script>alert('Errore: " . $e->getMessage() . "'); window.history.back();</script>";
}

$conn->close();
?>
