<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    $_SESSION['error_message'] = "Devi effettuare l'accesso come utente per acquistare un immobile.";
    header('Location: login_utente.php');
    exit();
}

// Verifica se l'ID dell'immobile è stato fornito
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Nessun immobile selezionato.";
    header('Location: immobili.php');
    exit();
}

// Includi il file di configurazione
include 'config.php';

$id_immobile = (int)$_GET['id'];
$id_utente = $_SESSION['user_id'];

// Query per ottenere i dettagli dell'immobile
$sql = "SELECT i.*, c.nome AS categoria, a.nome AS agente_nome, a.cognome AS agente_cognome
        FROM immobili i
        JOIN categorie c ON i.categoria_id = c.id
        LEFT JOIN agenti_immobiliari a ON i.agente_id = a.id
        WHERE i.id = ? AND i.stato = 'disponibile'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_immobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "L'immobile selezionato non è disponibile per l'acquisto.";
    header('Location: immobili.php');
    exit();
}
$immobile = $result->fetch_assoc();

// Ottieni dati utente
$sql_utente = "SELECT * FROM utenti WHERE id = ?";
$stmt_utente = $conn->prepare($sql_utente);
$stmt_utente->bind_param("i", $id_utente);
$stmt_utente->execute();
$result_utente = $stmt_utente->get_result();
$utente = $result_utente->fetch_assoc();

// Funzione per calcolare rata mensile
function calcolaRata($importoTotale, $tassoMensile, $numRate) {
    return round($importoTotale * $tassoMensile * pow(1 + $tassoMensile, $numRate) / (pow(1 + $tassoMensile, $numRate) - 1), 2);
}

$tassoInteresse = 5;
$tassoMensile = $tassoInteresse / 100 / 12;
$opzioniRate = [
    12 => calcolaRata($immobile['prezzo'], $tassoMensile, 12),
    24 => calcolaRata($immobile['prezzo'], $tassoMensile, 24),
    36 => calcolaRata($immobile['prezzo'], $tassoMensile, 36),
    48 => calcolaRata($immobile['prezzo'], $tassoMensile, 48),
];

// Calcola acconto (10%)
$acconto_percentage = 10;
$acconto_amount = round(($immobile['prezzo'] * $acconto_percentage) / 100);

// Gestione form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    $required_fields = ['telefono', 'indirizzo', 'tipo_acquisto', 'modalita_pagamento', 'metodo_pagamento'];
    $valid = true;

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $valid = false;
            $response['message'] = "Tutti i campi obbligatori devono essere compilati.";
            break;
        }
    }

    if ($valid && $_POST['modalita_pagamento'] === 'rate' && empty($_POST['piano_rate'])) {
        $valid = false;
        $response['message'] = "Seleziona un piano di pagamento rateizzato.";
    }

    if ($valid && (!isset($_POST['terms']) || $_POST['terms'] !== 'on')) {
        $valid = false;
        $response['message'] = "Devi accettare i termini e le condizioni.";
    }

    if ($valid) {
        // Recupera dati dal form
        $tipo_acquisto = $_POST['tipo_acquisto'];
        $modalita_pagamento = $_POST['modalita_pagamento'];
        $metodo_pagamento = $_POST['metodo_pagamento'];
        $piano_rate = isset($_POST['piano_rate']) ? (int)$_POST['piano_rate'] : null;
        $note = isset($_POST['note']) ? $_POST['note'] : '';

        // Calcola importo totale
        $importo_totale = $immobile['prezzo'];
        if ($modalita_pagamento === 'rate' && $piano_rate) {
            $importo_totale = $opzioniRate[$piano_rate] * $piano_rate;
        }

        $stato_pagamento = ($metodo_pagamento === 'carta') ? 'pagato' : 'in attesa';

        // Query inserimento acquisto
        $sql_acquisto = "INSERT INTO acquisti 
            (id_immobile, id_utente, tipo_acquisto, modalita_pagamento, metodo_pagamento, 
            piano_rate, importo_totale, acconto, note, data_acquisto, stato_pagamento) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmt_acquisto = $conn->prepare($sql_acquisto);

        // Se piano_rate è NULL, passalo comunque
        $stmt_acquisto->bind_param(
            "iissisdsss",
            $id_immobile,
            $id_utente,
            $tipo_acquisto,
            $modalita_pagamento,
            $metodo_pagamento,
            $piano_rate,
            $importo_totale,
            $acconto_amount,
            $note,
            $stato_pagamento
        );

        if ($stmt_acquisto->execute()) {
            // Aggiorna stato immobile
            $sql_update = "UPDATE immobili SET stato = 'venduto' WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $id_immobile);
            $stmt_update->execute();

            $_SESSION['success_message'] = "Acquisto completato con successo!";
            header('Location: miei_acquisti.php?success=true');
            exit();
        } else {
            $_SESSION['error_message'] = "Errore durante l'elaborazione dell'acquisto.";
            header("Location: acquista.php?id=$id_immobile");
            exit();
        }
    } else {
        $_SESSION['error_message'] = $response['message'];
        header("Location: acquista.php?id=$id_immobile");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acquista Immobile - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="acquista.css">
    <!-- Stripe JS -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <!-- Header con menu dinamico basato sul login -->
    <header>
        <nav>
            <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#3498db" viewBox="0 0 24 24">
                <path d="M12 3l8 7h-3v7h-4v-5h-2v5H7v-7H4l8-7z"/>
            </svg>
            </div>
            <ul>
                <li><a href="home-page.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="immobili.php"><i class="fas fa-building"></i> Immobili</a></li>
                <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="user-menu">
                        <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fas fa-id-card"></i> Profilo</a></li>
                            <?php if($_SESSION['user_type'] == 'utente'): ?>
                                <li><a href="preferiti.php"><i class="fas fa-heart"></i> Preferiti</a></li>
                                <li><a href="miei_acquisti.php"><i class="fas fa-shopping-cart"></i> I miei acquisti</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="login_utente.php"><i class="fas fa-sign-in-alt"></i> Accedi</a></li>
                    <li><a href="registrazione_utente.php"><i class="fas fa-user-plus"></i> Registrati</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="home-page.php">Home</a> &gt; 
            <a href="immobili.php">Immobili</a> &gt; 
            <a href="immobile.php?id=<?php echo $immobile['id']; ?>"><?php echo $immobile['nome']; ?></a> &gt; 
            <span>Acquista</span>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="container">
        <h1>Conferma Acquisto</h1>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="purchase-container">
            <div class="immobile-summary">
                <h2>Dettagli Immobile</h2>
                <div class="immobile-info">
                    <strong>Nome:</strong> <?php echo $immobile['nome']; ?>
                </div>
                <div class="immobile-info">
                    <strong>Categoria:</strong> <?php echo $categoria_display; ?>
                </div>
                <div class="immobile-info">
                    <strong>Indirizzo:</strong> <?php echo $immobile['citta'] . ', ' . $immobile['provincia']; ?>
                </div>
                <div class="immobile-info">
                    <strong>Dimensioni:</strong> <?php echo $immobile['metri_quadri']; ?> m²
                </div>
                <div class="immobile-info">
                    <strong>Stanze:</strong> <?php echo $immobile['stanze']; ?>
                </div>
                <div class="immobile-info">
                    <strong>Bagni:</strong> <?php echo $immobile['bagni']; ?>
                </div>
                <?php if(!empty($immobile['agente_nome'])): ?>
                <div class="immobile-info">
                    <strong>Agente:</strong> <?php echo $immobile['agente_nome'] . ' ' . $immobile['agente_cognome']; ?>
                </div>
                <?php endif; ?>
                
                <div class="price-tag">
                    Prezzo: <?php echo number_format($immobile['prezzo'], 0, ',', '.'); ?> €
                </div>
                
                <div class="acconto-tag">
                    Acconto (<?php echo $acconto_percentage; ?>%): <?php echo number_format($acconto_amount, 0, ',', '.'); ?> €
                </div>
            </div>
            
            <div class="purchase-form">
                <h2>Dati per l'Acquisto</h2>
                <form id="purchase-form" method="POST" action="acquista.php?id=<?php echo $immobile['id']; ?>">
                    <input type="hidden" name="id_immobile" value="<?php echo $immobile['id']; ?>">
                    <input type="hidden" name="prezzo" value="<?php echo $immobile['prezzo']; ?>">
                    <input type="hidden" name="acconto" value="<?php echo $acconto_amount; ?>">

                    <div class="form-group">
                        <label for="nome">Nome e Cognome</label>
                        <input type="text" id="nome" name="nome" value="<?php echo $utente['nome'] . ' ' . $utente['cognome']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $utente['email']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Telefono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?php echo $utente['telefono']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="indirizzo">Indirizzo di Residenza</label>
                        <input type="text" id="indirizzo" name="indirizzo" value="<?php echo $utente['indirizzo']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="tipo_acquisto">Tipo di Acquisto</label>
                        <select id="tipo_acquisto" name="tipo_acquisto" required>
                            <option value="acquisto">Acquisto</option>
                            <option value="affitto">Affitto</option>
                        </select>
                    </div>

                    <!-- Modalità di pagamento -->
                    <div class="form-group">
                        <label>Modalità di Pagamento</label>
                        <div class="payment-options">
                            <div class="payment-option" onclick="selectPaymentOption(this, 'unica_soluzione')">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>Pagamento Completo</div>
                                <div class="option-desc">Paga l'intero importo subito</div>
                            </div>
                            <div class="payment-option" onclick="selectPaymentOption(this, 'rate')">
                                <i class="fas fa-calendar-alt"></i>
                                <div>Pagamento Rateizzato</div>
                                <div class="option-desc">Dividi il pagamento in rate mensili</div>
                            </div>
                        </div>
                        <input type="hidden" id="modalita_pagamento" name="modalita_pagamento" required>
                    </div>
                    
                    <!-- Opzioni per il pagamento a rate (visibili solo se selezionata l'opzione "rate") -->
                    <div id="installment-options-container" class="installment-options" style="display: none;">
                        <label>Scegli il piano di pagamento:</label>
                        
                        <div class="installment-option" onclick="selectInstallmentPlan(this, '12')">
                            <input type="radio" name="piano_rate" value="12">
                            <div class="installment-details">
                                <div class="installment-duration">12 mesi</div>
                                <div class="installment-amount"><?php echo number_format($opzioniRate[12], 2, ',', '.'); ?> € / mese</div>
                                <div class="installment-total">Totale: <?php echo number_format($opzioniRate[12] * 12, 0, ',', '.'); ?> €</div>
                            </div>
                        </div>
                        
                        <div class="installment-option" onclick="selectInstallmentPlan(this, '24')">
                            <input type="radio" name="piano_rate" value="24">
                            <div class="installment-details">
                                <div class="installment-duration">24 mesi</div>
                                <div class="installment-amount"><?php echo number_format($opzioniRate[24], 2, ',', '.'); ?> € / mese</div>
                                <div class="installment-total">Totale: <?php echo number_format($opzioniRate[24] * 24, 0, ',', '.'); ?> €</div>
                            </div>
                        </div>
                        
                        <div class="installment-option" onclick="selectInstallmentPlan(this, '36')">
                            <input type="radio" name="piano_rate" value="36">
                            <div class="installment-details">
                                <div class="installment-duration">36 mesi</div>
                                <div class="installment-amount"><?php echo number_format($opzioniRate[36], 2, ',', '.'); ?> € / mese</div>
                                <div class="installment-total">Totale: <?php echo number_format($opzioniRate[36] * 36, 0, ',', '.'); ?> €</div>
                            </div>
                        </div>
                        
                        <div class="installment-option" onclick="selectInstallmentPlan(this, '48')">
                            <input type="radio" name="piano_rate" value="48">
                            <div class="installment-details">
                                <div class="installment-duration">48 mesi</div>
                                <div class="installment-amount"><?php echo number_format($opzioniRate[48], 2, ',', '.'); ?> € / mese</div>
                                <div class="installment-total">Totale: <?php echo number_format($opzioniRate[48] * 48, 0, ',', '.'); ?> €</div>
                            </div>
                        </div>
                        
                        <div class="payment-details">
                            <p><i class="fas fa-info-circle"></i> Il pagamento rateizzato prevede un tasso di interesse annuo del <?php echo $tassoInteresse; ?>%.</p>
                            <p>È richiesto un acconto iniziale del <?php echo $acconto_percentage; ?>% (<?php echo number_format($acconto_amount, 0, ',', '.'); ?> €).</p>
                        </div>
                    </div>
                    
                    <!-- Riepilogo del pagamento -->
                    <div id="payment-summary" class="payment-summary">
                        <h3>Riepilogo Pagamento</h3>
                        <div class="summary-row">
                            <div>Prezzo immobile:</div>
                            <div><?php echo number_format($immobile['prezzo'], 0, ',', '.'); ?> €</div>
                        </div>
                        <div id="summary-plan" class="summary-row" style="display: none;">
                            <div>Piano scelto:</div>
                            <div id="summary-plan-value">-</div>
                        </div>
                        <div id="summary-interest" class="summary-row" style="display: none;">
                            <div>Interessi totali:</div>
                            <div id="summary-interest-value">-</div>
                        </div>
                        <div class="summary-row">
                            <div>Acconto da versare ora:</div>
                            <div><?php echo number_format($acconto_amount, 0, ',', '.'); ?> €</div>
                        </div>
                        <div class="summary-row" id="summary-total">
                            <div>Importo totale:</div>
                            <div id="summary-total-value"><?php echo number_format($immobile['prezzo'], 0, ',', '.'); ?> €</div>
                        </div>
                    </div>
                    
                    <!-- Metodo di pagamento per l'acconto -->
                    <div class="form-group">
                        <label>Metodo di Pagamento per l'Acconto</label>
                        <div class="payment-methods">
                            <div class="payment-method" onclick="selectPayment(this, 'carta')">
                                <i class="fas fa-credit-card"></i>
                                <div>Carta di Credito</div>
                            </div>
                            <div class="payment-method" onclick="selectPayment(this, 'bonifico')">
                                <i class="fas fa-university"></i>
                                <div>Bonifico Bancario</div>
                            </div>
                        </div>
                        <input type="hidden" id="metodo_pagamento" name="metodo_pagamento" required>
                    </div>

                    <div class="form-group">
                        <label for="note">Note aggiuntive (opzionale)</label>
                        <textarea id="note" name="note" rows="4"></textarea>
                    </div>

                    <div class="terms-check">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            Dichiaro di aver letto e accettato i <a href="termini.php" target="_blank">Termini e Condizioni</a> e la <a href="privacy.php" target="_blank">Privacy Policy</a>.
                        </label>
                    </div>

                    <div id="standard-submit">
                        <button type="submit" class="btn-submit">Procedi con l'Acquisto</button>
                        <a href="immobile.php?id=<?php echo $immobile['id']; ?>" class="btn-cancel">Annulla</a>
                    </div>
                </form>
                
                <!-- Form per pagamento con Stripe (visibile solo quando viene selezionato il metodo carta) -->
                <div id="stripe-payment-form" style="display: none; margin-top: 20px;">
                    <h3>Pagamento con Carta</h3>
                    <form id="stripe-form">
                        <div id="card-element">
                            <!-- Qui sarà inserito il form per la carta -->
                        </div>
                        <div id="card-errors" class="payment-error" role="alert"></div>
                        <button id="stripe-submit" class="btn-submit" style="margin-top: 15px;">Paga Acconto</button>
                    </form>
                </div>
                
                <div id="payment-processing" class="payment-processing" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Elaborazione del pagamento in corso...
                </div>
                
                <div id="payment-success" class="payment-success" style="display: none;">
                    <i class="fas fa-check-circle"></i> Pagamento effettuato con successo! Stai per essere reindirizzato...
                </div>
                
                <!-- Informazioni bonifico bancario (visibili solo quando viene selezionato il metodo bonifico) -->
                <div id="bank-transfer-info" style="display: none; margin-top: 20px;">
                    <h3>Informazioni per il Bonifico Bancario</h3>
                    <div class="bank-info">
                        <p><strong>Intestatario:</strong> Immobiliare S.r.l.</p>
                        <p><strong>IBAN:</strong> IT60X0542811101000000123456</p>
                        <p><strong>Banca:</strong> Banca Nazionale del Lavoro</p>
                        <p><strong>Causale:</strong> Acconto Immobile ID: <?php echo $immobile['id']; ?> - <?php echo $immobile['nome']; ?></p>
                        <p><strong>Importo:</strong> <?php echo number_format($acconto_amount, 2, ',', '.'); ?> €</p>
                    </div>
                    <div class="bank-note">
                        <p><i class="fas fa-info-circle"></i> Dopo aver effettuato il bonifico, inviaci la ricevuta di pagamento all'indirizzo email: <strong>pagamenti@immobiliare.it</strong></p>
                        <p>Elaboreremo la tua richiesta entro 24-48 ore lavorative dalla ricezione del pagamento.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Immobiliare</h3>
                <p>La tua soluzione per la ricerca di immobili in Italia.</p>
            </div>
            <div class="footer-section">
                <h3>Link Utili</h3>
                <ul>
                    <li><a href="home-page.php">Home</a></li>
                    <li><a href="immobili.php">Immobili</a></li>
                    <li><a href="contatti.php">Contatti</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contatti</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-phone"></i> +39 02 1234567</p>
                <p><i class="fas fa-envelope"></i> info@immobiliare.it</p>
            </div>
            <div class="footer-section">
                <h3>Seguici</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <script>
        // Inizializzazione di Stripe
        const stripe = Stripe('your_publishable_key'); // Sostituire con la tua chiave pubblica Stripe
        const elements = stripe.elements();
        
        // Stile del form di carta
        const cardElementStyle = {
            base: {
                color: '#32325d',
                fontFamily: '"Montserrat", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
        
        // Creazione dell'elemento card
        const cardElement = elements.create('card', {style: cardElementStyle});
        cardElement.mount('#card-element');
        
        // Gestione degli errori della carta
        cardElement.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Funzione di selezione modalità di pagamento
        function selectPaymentOption(element, option) {
            // Rimuovi la classe 'selected' da tutte le opzioni
            document.querySelectorAll('.payment-option').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Aggiungi la classe 'selected' all'opzione selezionata
            element.classList.add('selected');
            
            // Imposta il valore nel campo nascosto
            document.getElementById('modalita_pagamento').value = option;
            
            // Mostra/nascondi le opzioni di rate
            if (option === 'rate') {
                document.getElementById('installment-options-container').style.display = 'block';
            } else {
                document.getElementById('installment-options-container').style.display = 'none';
                // Reset dei valori nel riepilogo
                updatePaymentSummary();
            }
        }
        
        // Funzione di selezione piano rate
        function selectInstallmentPlan(element, plan) {
            // Rimuovi la classe 'selected' da tutte le opzioni
            document.querySelectorAll('.installment-option').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Aggiungi la classe 'selected' all'opzione selezionata
            element.classList.add('selected');
            
            // Seleziona il radio button
            element.querySelector('input[type="radio"]').checked = true;
            
            // Aggiorna il riepilogo del pagamento
            updatePaymentSummary(plan);
        }
        
        // Funzione di aggiornamento riepilogo pagamento
        function updatePaymentSummary(plan = null) {
            const prezzo = <?php echo $immobile['prezzo']; ?>;
            const prezzoFormattato = new Intl.NumberFormat('it-IT').format(prezzo);
            
            let totalAmount = prezzo;
            let interestAmount = 0;
            
            // Elementi del riepilogo
            const summaryPlan = document.getElementById('summary-plan');
            const summaryPlanValue = document.getElementById('summary-plan-value');
            const summaryInterest = document.getElementById('summary-interest');
            const summaryInterestValue = document.getElementById('summary-interest-value');
            const summaryTotalValue = document.getElementById('summary-total-value');
            
            // Se è stato selezionato un piano
            if (plan) {
                // Mostra dettagli del piano
                summaryPlan.style.display = 'flex';
                summaryInterest.style.display = 'flex';
                
                // Ottieni la rata mensile dal piano selezionato
                const rataMensile = <?php echo json_encode($opzioniRate); ?>[plan];
                const totaleConInteressi = rataMensile * plan;
                interestAmount = totaleConInteressi - prezzo;
                
                summaryPlanValue.textContent = plan + ' mesi - ' + new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rataMensile) + ' € / mese';
                summaryInterestValue.textContent = new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(interestAmount) + ' €';
                
                totalAmount = totaleConInteressi;
            } else {
                // Nascondi dettagli del piano
                summaryPlan.style.display = 'none';
                summaryInterest.style.display = 'none';
            }
            
            // Aggiorna l'importo totale
            summaryTotalValue.textContent = new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(totalAmount) + ' €';
        }
        
        // Funzione per selezionare il metodo di pagamento
        function selectPayment(element, method) {
            // Rimuovi la classe 'selected' da tutti i metodi
            document.querySelectorAll('.payment-method').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Aggiungi la classe 'selected' al metodo selezionato
            element.classList.add('selected');
            
            // Imposta il valore nel campo nascosto
            document.getElementById('metodo_pagamento').value = method;
            
            // Mostra/nascondi i form di pagamento
            if (method === 'carta') {
                document.getElementById('stripe-payment-form').style.display = 'block';
                document.getElementById('bank-transfer-info').style.display = 'none';
                document.getElementById('standard-submit').style.display = 'none';
            } else if (method === 'bonifico') {
                document.getElementById('stripe-payment-form').style.display = 'none';
                document.getElementById('bank-transfer-info').style.display = 'block';
                document.getElementById('standard-submit').style.display = 'block';
            } else {
                document.getElementById('stripe-payment-form').style.display = 'none';
                document.getElementById('bank-transfer-info').style.display = 'none';
                document.getElementById('standard-submit').style.display = 'block';
            }
        }
        
        // Gestione della sottomissione del form di pagamento con Stripe
        document.getElementById('stripe-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Mostra l'indicatore di elaborazione
            document.getElementById('payment-processing').style.display = 'block';
            document.getElementById('stripe-submit').disabled = true;
            
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    // Gestione errore
                    document.getElementById('card-errors').textContent = result.error.message;
                    document.getElementById('payment-processing').style.display = 'none';
                    document.getElementById('stripe-submit').disabled = false;
                } else {
                    // Token creato correttamente, procediamo con l'invio del form
                    // In un'implementazione reale, qui invieresti il token al server
                    
                    // Aggiungi il token al form principale e invia
                    const tokenInput = document.createElement('input');
                    tokenInput.setAttribute('type', 'hidden');
                    tokenInput.setAttribute('name', 'stripeToken');
                    tokenInput.setAttribute('value', result.token.id);
                    document.getElementById('purchase-form').appendChild(tokenInput);
                    
                    // Simula il pagamento riuscito (in produzione, questa logica dovrebbe essere sul server)
                    setTimeout(function() {
                        document.getElementById('payment-processing').style.display = 'none';
                        document.getElementById('payment-success').style.display = 'block';
                        
                        // Invia il form principale dopo 2 secondi
                        setTimeout(function() {
                            document.getElementById('purchase-form').submit();
                        }, 2000);
                    }, 2000);
                }
            });
        });
        
        // Inizializzazione del form
        document.addEventListener('DOMContentLoaded', function() {
            // Impostazione valori default
            selectPaymentOption(document.querySelector('.payment-option'), 'unica_soluzione');
            selectPayment(document.querySelector('.payment-method'), 'carta');
            
            // Validator per il form
            document.getElementById('purchase-form').addEventListener('submit', function(event) {
                // Evita doppi submit
                if (document.getElementById('metodo_pagamento').value === 'carta') {
                    event.preventDefault();
                    document.getElementById('stripe-form').dispatchEvent(new Event('submit'));
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>