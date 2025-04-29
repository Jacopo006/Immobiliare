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

// Se l'immobile non esiste o non è disponibile
if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "L'immobile selezionato non è disponibile per l'acquisto.";
    header('Location: immobili.php');
    exit();
}

$immobile = $result->fetch_assoc();

// Ottieni i dati dell'utente
$sql_utente = "SELECT * FROM utenti WHERE id = ?";
$stmt_utente = $conn->prepare($sql_utente);
$stmt_utente->bind_param("i", $id_utente);
$stmt_utente->execute();
$result_utente = $stmt_utente->get_result();
$utente = $result_utente->fetch_assoc();

// Mappa delle categorie per la visualizzazione
$categorie_map = [
    'Appartamenti' => 'Appartamento',
    'Ville' => 'Villa',
    'Monolocali' => 'Monolocale',
    'appartamento' => 'Appartamento',
    'villa' => 'Villa',
    'attico' => 'Attico',
    'casa_indipendente' => 'Casa Indipendente',
    'terreno' => 'Terreno',
    'ufficio' => 'Ufficio',
    'negozio' => 'Negozio'
];

// Formatta la categoria per la visualizzazione
$categoria_display = isset($categorie_map[$immobile['categoria']]) ? $categorie_map[$immobile['categoria']] : $immobile['categoria'];
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
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .breadcrumb {
            background-color: #f8f9fa;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb span {
            color: #6c757d;
        }
        
        .purchase-container {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 50px;
        }
        
        .immobile-summary {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-right: 20px;
        }
        
        .purchase-form {
            flex: 2;
            min-width: 500px;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .immobile-summary h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .immobile-info {
            margin-bottom: 15px;
        }
        
        .immobile-info strong {
            display: inline-block;
            min-width: 120px;
            color: #6c757d;
        }
        
        .price-tag {
            font-size: 24px;
            color: #28a745;
            font-weight: 700;
            margin: 15px 0;
        }
        
        .purchase-form h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .payment-methods {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            flex: 1;
            min-width: 120px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #3498db;
        }
        
        .payment-method.selected {
            border-color: #3498db;
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .payment-method i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #3498db;
        }
        
        .terms-check {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .terms-check input {
            margin-top: 5px;
            margin-right: 10px;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            display: block;
            text-decoration: none;
            margin-top: 10px;
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
        }

        @media (max-width: 992px) {
            .purchase-container {
                flex-direction: column;
            }
            
            .immobile-summary,
            .purchase-form {
                margin-right: 0;
                margin-bottom: 20px;
                min-width: auto;
            }
        }

        .note {
            margin-top: 15px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
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
            </div>
            
            <div class="purchase-form">
                <h2>Dati per l'Acquisto</h2>
                <form action="processa_acquisto.php" method="POST">
                    <input type="hidden" name="id_immobile" value="<?php echo $immobile['id']; ?>">
                    <input type="hidden" name="prezzo" value="<?php echo $immobile['prezzo']; ?>">
                    
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
                    
                    <div class="form-group">
                        <label>Metodo di Pagamento</label>
                        <div class="payment-methods">
                            <div class="payment-method" onclick="selectPayment(this, 'bonifico')">
                                <i class="fas fa-university"></i>
                                <div>Bonifico Bancario</div>
                            </div>
                            <div class="payment-method" onclick="selectPayment(this, 'carta')">
                                <i class="fas fa-credit-card"></i>
                                <div>Carta di Credito</div>
                            </div>
                            <div class="payment-method" onclick="selectPayment(this, 'contanti')">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>Contanti</div>
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
                        <label for="terms">Dichiaro di aver letto e accettato i <a href="termini.php" target="_blank">Termini e Condizioni</a> e la <a href="privacy.php" target="_blank">Privacy Policy</a>. Sono consapevole che questa è una richiesta di acquisto e che sarò contattato da un agente immobiliare per finalizzare la transazione.</label>
                    </div>
                    
                    <button type="submit" class="btn-submit">Conferma Acquisto</button>
                    <a href="immobile.php?id=<?php echo $immobile['id']; ?>" class="btn-cancel">Annulla</a>
                </form>
                
                <p class="note">
                    <i class="fas fa-info-circle"></i> Nota: Questo è il primo passo per l'acquisto dell'immobile. Una volta confermato, sarai contattato da un nostro agente per procedere con la firma del contratto e il pagamento.
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Chi Siamo</h3>
                <p>Immobiliare è la tua agenzia di fiducia con oltre 20 anni di esperienza nel settore immobiliare in tutta Italia.</p>
            </div>
            <div class="footer-column">
                <h3>Link Utili</h3>
                <ul>
                    <li><a href="immobili.php">Ricerca Immobili</a></li>
                    <li><a href="servizi.php">I Nostri Servizi</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="contatti.php">Contattaci</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contatti</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-phone"></i> +39 02 1234567</p>
                <p><i class="fas fa-envelope"></i> info@immobiliare.it</p>
                <div class="social-media">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <script>
        function selectPayment(element, method) {
            // Rimuovi la classe 'selected' da tutti gli elementi
            const paymentMethods = document.querySelectorAll('.payment-method');
            paymentMethods.forEach(el => {
                el.classList.remove('selected');
            });
            
            // Aggiungi la classe 'selected' all'elemento cliccato
            element.classList.add('selected');
            
            // Imposta il valore del metodo di pagamento
            document.getElementById('metodo_pagamento').value = method;
        }
    </script>
</body>
</html>

<?php
$conn->close(); // Chiudi la connessione
?>