<?php
// stripe_config.php
require_once 'vendor/autoload.php'; // Composer autoload per Stripe PHP SDK

// Configurazione Stripe (Test Mode)
\Stripe\Stripe::setApiKey('sk_test_...'); // Chiave segreta di test

class StripePaymentSimulator {
    
    /**
     * Simula il pagamento dell'acconto per l'acquisto immobile
     */
    public static function processAccontoPayment($amount, $currency = 'EUR', $description = '') {
        try {
            // Crea un Payment Intent per simulare il pagamento
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Stripe usa i centesimi
                'currency' => strtolower($currency),
                'description' => $description,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'type' => 'acconto_immobile',
                    'timestamp' => time()
                ]
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'status' => $paymentIntent->status
            ];

        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error' => 'Carta rifiutata: ' . $e->getError()->message
            ];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return [
                'success' => false,
                'error' => 'Richiesta non valida: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Errore generico: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Simula il bonifico bancario
     */
    public static function simulateBankTransfer($amount, $iban, $description = '') {
        // Simula una risposta di bonifico bancario
        $transfer_id = 'BNFT_' . time() . '_' . rand(1000, 9999);
        
        // In un caso reale, qui integreresti con API bancarie come:
        // - PSD2 APIs
        // - Open Banking APIs
        // - SEPA Credit Transfer APIs
        
        return [
            'success' => true,
            'transfer_id' => $transfer_id,
            'amount' => $amount,
            'iban' => $iban,
            'status' => 'pending', // pending, completed, failed
            'estimated_completion' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'description' => $description
        ];
    }

    /**
     * Verifica lo stato di un pagamento
     */
    public static function checkPaymentStatus($payment_intent_id) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status, // succeeded, processing, requires_payment_method, etc.
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// processo_pagamento_stripe.php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_immobile = (int)$_POST['id_immobile'];
    $acconto = (float)$_POST['acconto'];
    $payment_method = $_POST['metodo_pagamento'];
    
    if ($payment_method === 'carta') {
        // Processo pagamento con carta via Stripe
        $result = StripePaymentSimulator::processAccontoPayment(
            $acconto,
            'EUR',
            "Acconto per immobile ID: $id_immobile"
        );
        
        if ($result['success']) {
            // Salva il pagamento nel database
            include 'config.php';
            
            $stmt = $conn->prepare("
                INSERT INTO pagamenti_stripe 
                (id_immobile, id_utente, payment_intent_id, amount, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("iisds", 
                $id_immobile, 
                $_SESSION['user_id'], 
                $result['payment_intent_id'],
                $acconto,
                $result['status']
            );
            
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'client_secret' => $result['client_secret'],
                'message' => 'Pagamento inizializzato con successo'
            ]);
        } else {
            echo json_encode($result);
        }
        
    } elseif ($payment_method === 'bonifico') {
        // Simula bonifico bancario
        $iban = "IT60X0542811101000000123456"; // IBAN dell'azienda
        $result = StripePaymentSimulator::simulateBankTransfer(
            $acconto,
            $iban,
            "Acconto immobile ID: $id_immobile"
        );
        
        if ($result['success']) {
            // Salva la richiesta di bonifico nel database
            include 'config.php';
            
            $stmt = $conn->prepare("
                INSERT INTO bonifici_bancari 
                (id_immobile, id_utente, transfer_id, amount, status, iban_destinatario, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("iisdss", 
                $id_immobile, 
                $_SESSION['user_id'], 
                $result['transfer_id'],
                $acconto,
                $result['status'],
                $iban
            );
            
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'transfer_id' => $result['transfer_id'],
                'estimated_completion' => $result['estimated_completion'],
                'message' => 'Bonifico registrato con successo'
            ]);
        } else {
            echo json_encode($result);
        }
    }
}

// Carte di test Stripe (sempre funzionanti in test mode)
/*
Carte di successo:
- 4242424242424242 (Visa)
- 5555555555554444 (Mastercard)
- 378282246310005 (American Express)

Carte che falliscono:
- 4000000000000002 (Carta declinata)
- 4000000000009995 (Fondi insufficienti)
- 4000000000000069 (Carta scaduta)

CVC: qualsiasi numero a 3 cifre
Data scadenza: qualsiasi data futura
*/
?>
