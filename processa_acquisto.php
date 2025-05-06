<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    echo json_encode(['success' => false, 'error' => 'Devi effettuare l\'accesso come utente per acquistare un immobile.']);
    exit();
}

if (!isset($_POST['id_immobile'], $_POST['prezzo'], $_POST['acconto'], $_POST['metodo_pagamento'])) {
    echo json_encode(['success' => false, 'error' => 'Dati mancanti per completare l\'acquisto.']);
    exit();
}

require 'config.php';
require_once 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_51RLft5R6k5lJFmZtpczLwpqKMMvNunZlNBF3dERMWtxnsqHRzkIBhmLzlOjTydF9SHrfYsgMZACVbOpbcSnwnH6700iL4DDRIt');

$id_immobile = (int)$_POST['id_immobile'];
$id_utente = $_SESSION['user_id'];
$prezzo = (float)$_POST['prezzo'];
$acconto = (float)$_POST['acconto'];
$metodo_pagamento = $_POST['metodo_pagamento'];
$tipo_acquisto = $_POST['tipo_acquisto'];
$note = $_POST['note'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$indirizzo = $_POST['indirizzo'] ?? '';

$sql = "SELECT * FROM immobili WHERE id = ? AND stato = 'disponibile'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_immobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'L\'immobile selezionato non è disponibile.']);
    exit();
}

$immobile = $result->fetch_assoc();

$payment_id = null;
$payment_status = 'pending';

if ($metodo_pagamento === 'carta') {
    if (!isset($_POST['stripeToken'])) {
        echo json_encode(['success' => false, 'error' => 'Token Stripe mancante.']);
        exit();
    }

    $token = $_POST['stripeToken'];
    $amount_cents = $acconto * 100;

    try {
        $customer = \Stripe\Customer::create([
            'email' => $_SESSION['user_email'],
            'source' => $token,
        ]);

        $charge = \Stripe\Charge::create([
            'amount' => (int)$amount_cents,
            'currency' => 'eur',
            'description' => 'Acconto per immobile ID ' . $id_immobile,
            'customer' => $customer->id,
            'metadata' => [
                'id_immobile' => $id_immobile,
                'id_utente' => $id_utente,
                'tipo' => 'acconto'
            ]
        ]);

        $payment_id = $charge->id;
        $payment_status = $charge->status === 'succeeded' ? 'completed' : 'failed';

        if ($payment_status !== 'completed') {
            echo json_encode(['success' => false, 'error' => 'Pagamento fallito.']);
            exit();
        }
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Errore pagamento: ' . $e->getMessage()]);
        exit();
    }
} elseif ($metodo_pagamento === 'bonifico') {
    $payment_status = 'pending';
    $payment_id = 'bonifico_' . time() . '_' . $id_utente;
}

// Inserimento nel database
$sql_acquisto = "INSERT INTO acquisti (
    id_immobile, 
    id_utente, 
    acconto, 
    metodo_pagamento, 
    tipo_acquisto, 
    stato_pagamento, 
    payment_id, 
    note, 
    data_acquisto
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt_acquisto = $conn->prepare($sql_acquisto);
$stmt_acquisto->bind_param(
    "idssssss",
    $id_immobile,
    $id_utente,
    $acconto,
    $metodo_pagamento,
    $tipo_acquisto,
    $payment_status,
    $payment_id,
    $note
);

if (!$stmt_acquisto->execute()) {
    if ($metodo_pagamento === 'carta' && $payment_status === 'completed') {
        error_log("Errore inserimento acquisto. ID pagamento: $payment_id");
    }
    echo json_encode(['success' => false, 'error' => 'Errore nel salvataggio dell\'acquisto.']);
    exit();
}

$id_acquisto = $conn->insert_id;

// Aggiorna stato immobile
$nuovo_stato = 'in_trattativa';
$sql_update = "UPDATE immobili SET stato = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $nuovo_stato, $id_immobile);
$stmt_update->execute();

// INIZIO MODIFICHE - Registrazione email in file di log invece dell'invio
// Prepara i contenuti delle email ma salvali in un file di log invece di inviarli

$email_utente = $_SESSION['user_email'];
$nome_utente = $_SESSION['user_name'];
$oggetto = "Conferma prenotazione immobile " . $immobile['nome'];

$messaggio = "Gentile $nome_utente,\n\n";
$messaggio .= "Grazie per aver prenotato l'immobile: " . $immobile['nome'] . "\n\n";

if ($metodo_pagamento === 'carta') {
    $messaggio .= "Pagamento completato con successo. Acconto: " . number_format($acconto, 2, ',', '.') . " €.\n";
    $messaggio .= "ID Transazione: $payment_id\n\n";
} else {
    $messaggio .= "Effettua un bonifico di " . number_format($acconto, 2, ',', '.') . " € alle seguenti coordinate:\n";
    $messaggio .= "IBAN: IT12A0123456789000000123456\n";
    $messaggio .= "Intestatario: Immobiliare Srl\n";
    $messaggio .= "Causale: Acconto immobile ID $id_immobile - $payment_id\n\n";
}

$messaggio .= "Verrai contattato da un nostro agente.\nTelefono: $telefono\nIndirizzo: $indirizzo\n\n";
$messaggio .= "Cordiali saluti,\nImmobiliare";

// Email admin
$email_admin = "admin@immobiliare.it";
$oggetto_admin = "Nuova prenotazione immobile ID $id_immobile";
$messaggio_admin = "Prenotazione effettuata:\n\n";
$messaggio_admin .= "Utente: $nome_utente\nEmail: $email_utente\nTelefono: $telefono\n";
$messaggio_admin .= "Metodo: $metodo_pagamento\nStato: $payment_status\nPagamento ID: $payment_id\n";

// Registra le email in un file di log (solo in ambiente di sviluppo)
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    $log_file = 'emails_log.txt';
    $log_content = "=== " . date('Y-m-d H:i:s') . " ===\n";
    $log_content .= "EMAIL UTENTE:\n";
    $log_content .= "A: $email_utente\n";
    $log_content .= "Oggetto: $oggetto\n";
    $log_content .= "Messaggio:\n$messaggio\n\n";
    $log_content .= "EMAIL ADMIN:\n";
    $log_content .= "A: $email_admin\n";
    $log_content .= "Oggetto: $oggetto_admin\n";
    $log_content .= "Messaggio:\n$messaggio_admin\n\n";
    $log_content .= "===============================\n\n";
    
    file_put_contents($log_file, $log_content, FILE_APPEND);
}
// FINE MODIFICHE

// Risposta finale
echo json_encode([
    'success' => true,
    'message' => 'Acquisto registrato.',
    'redirect' => 'miei_acquisti.php',
    'id_acquisto' => $id_acquisto,
    'payment_status' => $payment_status
]);

$conn->close();