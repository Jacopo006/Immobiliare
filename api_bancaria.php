<?php
// mock_bank_api.php
class MockBankAPI {
    
    /**
     * Simula l'invio di un bonifico SEPA
     */
    public static function sendSEPATransfer($data) {
        // Valida IBAN
        if (!self::validateIBAN($data['iban_destinatario'])) {
            return [
                'success' => false,
                'error_code' => 'INVALID_IBAN',
                'error_message' => 'IBAN destinatario non valido'
            ];
        }
        
        // Simula diversi scenari
        $scenario = rand(1, 10);
        
        if ($scenario <= 8) {
            // 80% successo
            $transfer_id = 'TRF_' . date('Ymd') . '_' . sprintf('%06d', rand(1, 999999));
            
            return [
                'success' => true,
                'transfer_id' => $transfer_id,
                'status' => 'ACCEPTED',
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'EUR',
                'execution_date' => date('Y-m-d', strtotime('+1 business day')),
                'fees' => self::calculateFees($data['amount']),
                'reference' => $data['reference'] ?? '',
                'estimated_arrival' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ];
        } elseif ($scenario == 9) {
            // 10% fondi insufficienti
            return [
                'success' => false,
                'error_code' => 'INSUFFICIENT_FUNDS',
                'error_message' => 'Fondi insufficienti sul conto mittente'
            ];
        } else {
            // 10% errore generico
            return [
                'success' => false,
                'error_code' => 'PROCESSING_ERROR',
                'error_message' => 'Errore temporaneo del sistema bancario'
            ];
        }
    }
    
    /**
     * Verifica lo stato di un bonifico
     */
    public static function checkTransferStatus($transfer_id) {
        // Simula diverse fasi del bonifico
        $statuses = ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED'];
        $random_status = $statuses[array_rand($statuses)];
        
        return [
            'success' => true,
            'transfer_id' => $transfer_id,
            'status' => $random_status,
            'last_updated' => date('Y-m-d H:i:s'),
            'processing_time' => rand(1, 48) . ' ore'
        ];
    }
    
    /**
     * Simula la ricezione di un bonifico (webhook)
     */
    public static function simulateIncomingTransfer($iban_mittente, $amount, $reference) {
        $transaction_id = 'REC_' . date('Ymd') . '_' . sprintf('%06d', rand(1, 999999));
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'type' => 'INCOMING_TRANSFER',
            'amount' => $amount,
            'currency' => 'EUR',
            'sender_iban' => $iban_mittente,
            'reference' => $reference,
            'value_date' => date('Y-m-d'),
            'booking_date' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Valida un IBAN (versione semplificata)
     */
    private static function validateIBAN($iban) {
        $iban = strtoupper(str_replace(' ', '', $iban));
        
        // Controllo lunghezza base
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        
        // Controllo formato italiano (IT + 25 caratteri)
        if (substr($iban, 0, 2) === 'IT' && strlen($iban) !== 27) {
            return false;
        }
        
        // Altri controlli IBAN semplificati
        return preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban);
    }
    
    /**
     * Calcola le commissioni simulate
     */
    private static function calculateFees($amount) {
        if ($amount <= 1000) {
            return 2.50;
        } elseif ($amount <= 10000) {
            return 5.00;
        } else {
            return min(15.00, $amount * 0.001); // Max 15€ o 0.1%
        }
    }
}

// Webhook simulator per bonifici in entrata
class BankWebhookSimulator {
    
    public static function processIncomingPayment($reference) {
        // Cerca transazioni in attesa con questa causale
        include 'config.php';
        
        $stmt = $conn->prepare("
            SELECT * FROM acquisti 
            WHERE stato_pagamento = 'in attesa' 
            AND (note LIKE ? OR CONCAT('Acconto Immobile ID: ', id_immobile) = ?)
            LIMIT 1
        ");
        
        $like_reference = "%$reference%";
        $stmt->bind_param("ss", $like_reference, $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Simula ricezione bonifico
            $transfer_data = MockBankAPI::simulateIncomingTransfer(
                'IT60X0542811101000000654321', // IBAN cliente simulato
                $row['acconto'],
                $reference
            );
            
            if ($transfer_data['success']) {
                // Aggiorna lo stato del pagamento
                $update_stmt = $conn->prepare("
                    UPDATE acquisti 
                    SET stato_pagamento = 'pagato',
                        transaction_id = ?,
                        data_pagamento = NOW()
                    WHERE id = ?
                ");
                
                $update_stmt->bind_param("si", 
                    $transfer_data['transaction_id'], 
                    $row['id']
                );
                
                if ($update_stmt->execute()) {
                    // Aggiorna stato immobile a "venduto"
                    $immobile_stmt = $conn->prepare("
                        UPDATE immobili 
                        SET stato = 'venduto' 
                        WHERE id = ?
                    ");
                    $immobile_stmt->bind_param("i", $row['id_immobile']);
                    $immobile_stmt->execute();
                    
                    return [
                        'success' => true,
                        'message' => 'Pagamento elaborato con successo',
                        'acquisto_id' => $row['id']
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'message' => 'Nessuna transazione trovata per questa causale'
        ];
    }
}

// Endpoint per simulare webhook bancario (da chiamare manualmente per test)
if (isset($_GET['simulate_webhook'])) {
    $reference = $_GET['reference'] ?? '';
    if ($reference) {
        $result = BankWebhookSimulator::processIncomingPayment($reference);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Causale mancante']);
    }
}

// Esempio di utilizzo nell'acquisto
if (isset($_POST['process_bank_transfer'])) {
    $transfer_data = [
        'iban_destinatario' => 'IT60X0542811101000000123456',
        'amount' => (float)$_POST['acconto'],
        'currency' => 'EUR',
        'reference' => 'Acconto Immobile ID: ' . $_POST['id_immobile']
    ];
    
    $result = MockBankAPI::sendSEPATransfer($transfer_data);
    
    if ($result['success']) {
        // Salva nel database
        include 'config.php';
        $stmt = $conn->prepare("
            INSERT INTO bonifici_simulati 
            (id_immobile, id_utente, transfer_id, amount, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iisds", 
            $_POST['id_immobile'],
            $_SESSION['user_id'],
            $result['transfer_id'],
            $result['amount'],
            $result['status']
        );
        
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'transfer_id' => $result['transfer_id'],
            'execution_date' => $result['execution_date'],
            'fees' => $result['fees']
        ]);
    } else {
        echo json_encode($result);
    }
}
?>