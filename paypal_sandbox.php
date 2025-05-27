<?php
// paypal_config.php
class PayPalSandbox {
    private $client_id;
    private $client_secret;
    private $base_url = 'https://api.sandbox.paypal.com'; // Sandbox URL
    
    public function __construct($client_id, $client_secret) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }
    
    /**
     * Ottieni token di accesso PayPal
     */
    private function getAccessToken() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->client_id . ':' . $this->client_secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    /**
     * Crea un pagamento PayPal
     */
    public function createPayment($amount, $description, $return_url, $cancel_url) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return ['success' => false, 'error' => 'Impossibile ottenere token PayPal'];
        }
        
        $payment_data = [
            'intent' => 'sale',
            'payer' => [
                'payment_method' => 'paypal'
            ],
            'transactions' => [[
                'amount' => [
                    'total' => number_format($amount, 2, '.', ''),
                    'currency' => 'EUR'
                ],
                'description' => $description
            ]],
            'redirect_urls' => [
                'return_url' => $return_url,
                'cancel_url' => $cancel_url
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/v1/payments/payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['id'])) {
            // Trova l'URL di approvazione
            $approval_url = null;
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approval_url') {
                    $approval_url = $link['href'];
                    break;
                }
            }
            
            return [
                'success' => true,
                'payment_id' => $data['id'],
                'approval_url' => $approval_url
            ];
        } else {
            return [
                'success' => false,
                'error' => $data['message'] ?? 'Errore sconosciuto PayPal'
            ];
        }
    }
    
    /**
     * Esegui il pagamento dopo l'approvazione
     */
    public function executePayment($payment_id, $payer_id) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return ['success' => false, 'error' => 'Impossibile ottenere token PayPal'];
        }
        
        $execute_data = [
            'payer_id' => $payer_id
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/v1/payments/payment/' . $payment_id . '/execute');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($execute_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['state']) && $data['state'] === 'approved') {
            return [
                'success' => true,
                'payment_id' => $payment_id,
                'transaction_id' => $data['transactions'][0]['related_resources'][0]['sale']['id']
            ];
        } else {
            return [
                'success' => false,
                'error' => $data['message'] ?? 'Pagamento non completato'
            ];
        }
    }
}

// Utilizzo per l'acquisto immobile
$paypal = new PayPalSandbox(
    'your_sandbox_client_id',
    'your_sandbox_client_secret'
);

if (isset($_POST['create_paypal_payment'])) {
    $amount = (float)$_POST['acconto'];
    $id_immobile = (int)$_POST['id_immobile'];
    
    $result = $paypal->createPayment(
        $amount,
        "Acconto per immobile ID: $id_immobile",
        "http://tuosito.com/paypal_success.php?immobile_id=$id_immobile",
        "http://tuosito.com/paypal_cancel.php?immobile_id=$id_immobile"
    );
    
    if ($result['success']) {
        // Salva il payment_id nel database e reindirizza a PayPal
        header('Location: ' . $result['approval_url']);
        exit;
    } else {
        echo "Errore PayPal: " . $result['error'];
    }
}
?>