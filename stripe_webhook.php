<?php
// Questo script gestisce i webhook di Stripe
// Da configurare nel pannello di controllo Stripe: https://dashboard.stripe.com/webhooks

// Carica l'autoloader di Composer
require_once 'vendor/autoload.php';

// Includi il file di configurazione del database
require_once 'config.php';

// Configura la chiave API di Stripe
\Stripe\Stripe::setApiKey('sk_test_51RLft5R6k5lJFmZtpczLwpqKMMvNunZlNBF3dERMWtxnsqHRzkIBhmLzlOjTydF9SHrfYsgMZACVbOpbcSnwnH6700iL4DDRIt');

// Questa dovrebbe essere la chiave webhooks signing secret che trovi nel pannello di controllo di Stripe
$endpoint_secret = 'whsec_...'; // Sostituisci con la tua chiave segreta di webhook

// Ricevi l'evento JSON POST da Stripe
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Payload non valido
    http_response_code(400);
    echo json_encode(['error' => 'Payload non valido']);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Firma non valida
    http_response_code(400);
    echo json_encode(['error' => 'Firma non valida']);
    exit();
}

// Gestisci l'evento
switch ($event->type) {
    case 'charge.succeeded':
        $charge = $event->data->object;
        
        // Estrai i metadati
        $id_immobile = $charge->metadata->id_immobile ?? null;
        $id_utente = $charge->metadata->id_utente ?? null;
        
        if ($id_immobile && $id_utente) {
            // Aggiorna lo stato del pagamento nel database
            $payment_id = $charge->id;
            
            $sql = "UPDATE acquisti SET stato_pagamento = 'completed' WHERE payment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $payment_id);
            $stmt->execute();
            
            // Se il pagamento è associato a un'immobile, aggiorna lo stato dell'immobile
            if ($stmt->affected_rows > 0) {
                $sql_immobile = "UPDATE immobili SET stato = 'in_trattativa' WHERE id = ?";
                $stmt_immobile = $conn->prepare($sql_immobile);
                $stmt_immobile->bind_param("i", $id_immobile);
                $stmt_immobile->execute();
                
                // Invia email di notifica di pagamento riuscito
                $sql_utente = "SELECT email FROM utenti WHERE id = ?";
                $stmt_utente = $conn->prepare($sql_utente);
                $stmt_utente->bind_param("i", $id_utente);
                $stmt_utente->execute();
                $result_utente = $stmt_utente->get_result();
                
                if ($result_utente->num_rows > 0) {
                    $utente = $result_utente->fetch_assoc();
                    $email = $utente['email'];
                    
                    $oggetto = "Pagamento completato per la prenotazione immobile";
                    $messaggio = "Gentile Cliente,\n\n";
                    $messaggio .= "Il pagamento dell'acconto per l'immobile è stato completato con successo.\n";
                    $messaggio .= "ID Transazione: " . $payment_id . "\n\n";
                    $messaggio .= "Un nostro agente ti contatterà al più presto per proseguire con l'acquisto.\n\n";
                    $messaggio .= "Cordiali saluti,\nIl team di Immobiliare";
                    
                    $headers = "From: noreply@immobiliare.it\r\n";
                    mail($email, $oggetto, $messaggio, $headers);
                }
            }
        }
        break;
    
    case 'charge.failed':
        $charge = $event->data->object;
        
        // Estrai i metadati
        $id_immobile = $charge->metadata->id_immobile ?? null;
        $id_utente = $charge->metadata->id_utente ?? null;
        
        if ($id_immobile && $id_utente) {
            // Aggiorna lo stato del pagamento nel database
            $payment_id = $charge->id;
            
            $sql = "UPDATE acquisti SET stato_pagamento = 'failed' WHERE payment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $payment_id);
            $stmt->execute();
            
            // Invia email di notifica di pagamento fallito
            $sql_utente = "SELECT email FROM utenti WHERE id = ?";
            $stmt_utente = $conn->prepare($sql_utente);
            $stmt_utente->bind_param("i", $id_utente);
            $stmt_utente->execute();
            $result_utente = $stmt_utente->get_result();
            
            if ($result_utente->num_rows > 0) {
                $utente = $result_utente->fetch_assoc();
                $email = $utente['email'];
                
                $oggetto = "Problema con il pagamento per la prenotazione immobile";
                $messaggio = "Gentile Cliente,\n\n";
                $messaggio .= "Il pagamento dell'acconto per l'immobile non è andato a buon fine.\n";
                $messaggio .= "ID Transazione: " . $payment_id . "\n\n";
                $messaggio .= "Ti preghiamo di verificare i dati della tua carta e riprovare.\n";
                $messaggio .= "Puoi accedere alla tua area personale per visualizzare la prenotazione e completare il pagamento.\n\n";
                $messaggio .= "Cordiali saluti,\nIl team di Immobiliare";
                
                $headers = "From: noreply@immobiliare.it\r\n";
                mail($email, $oggetto, $messaggio, $headers);
            }
        }
        break;
    
    case 'charge.refunded':
        $charge = $event->data->object;
        
        // Estrai i metadati
        $id_immobile = $charge->metadata->id_immobile ?? null;
        $id_utente = $charge->metadata->id_utente ?? null;
        
        if ($id_immobile && $id_utente) {
            // Aggiorna lo stato del pagamento nel database
            $payment_id = $charge->id;
            
            $sql = "UPDATE acquisti SET stato_pagamento = 'refunded' WHERE payment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $payment_id);
            $stmt->execute();
            
            // Se il rimborso è completo, aggiorna lo stato dell'immobile a "disponibile"
            if ($charge->refunded) {
                $sql_immobile = "UPDATE immobili SET stato = 'disponibile' WHERE id = ?";
                $stmt_immobile = $conn->prepare($sql_immobile);
                $stmt_immobile->bind_param("i", $id_immobile);
                $stmt_immobile->execute();
                
                // Invia email di notifica di rimborso
                $sql_utente = "SELECT email FROM utenti WHERE id = ?";
                $stmt_utente = $conn->prepare($sql_utente);
                $stmt_utente->bind_param("i", $id_utente);
                $stmt_utente->execute();
                $result_utente = $stmt_utente->get_result();
                
                if ($result_utente->num_rows > 0) {
                    $utente = $result_utente->fetch_assoc();
                    $email = $utente['email'];
                    
                    $oggetto = "Rimborso completato per la prenotazione immobile";
                    $messaggio = "Gentile Cliente,\n\n";
                    $messaggio .= "Il rimborso dell'acconto per l'immobile è stato completato con successo.\n";
                    $messaggio .= "ID Transazione: " . $payment_id . "\n\n";
                    $messaggio .= "L'importo verrà riaccreditato sulla tua carta entro pochi giorni lavorativi.\n\n";
                    $messaggio .= "Cordiali saluti,\nIl team di Immobiliare";
                    
                    $headers = "From: noreply@immobiliare.it\r\n";
                    mail($email, $oggetto, $messaggio, $headers);
                }
            }
        }
        break;
    
    default:
        // Evento non gestito
        break;
}

// Restituisci una risposta 200 a Stripe
http_response_code(200);
echo json_encode(['status' => 'success']);