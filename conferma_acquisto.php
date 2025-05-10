<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    $_SESSION['error_message'] = "Devi effettuare l'accesso come utente per visualizzare questa pagina.";
    header('Location: login_utente.php');
    exit();
}

// Verifica se c'è un ID acquisto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Nessun acquisto specificato.";
    header('Location: miei_acquisti.php');
    exit();
}

$id_acquisto = (int)$_GET['id'];
$id_utente = $_SESSION['user_id'];

// Includi il file di configurazione
include 'config.php';

// Query per ottenere i dettagli dell'acquisto
$sql = "SELECT a.*, i.nome AS immobile_nome, i.prezzo, i.immagine, i.citta, i.provincia, i.metri_quadri, i.stanze, i.bagni, 
               c.nome AS categoria_nome, ag.nome AS agente_nome, ag.cognome AS agente_cognome
        FROM acquisti a
        JOIN immobili i ON a.id_immobile = i.id
        JOIN categorie c ON i.categoria_id = c.id
        LEFT JOIN agenti_immobiliari ag ON i.agente_id = ag.id
        WHERE a.id = ? AND a.id_utente = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_acquisto, $id_utente);
$stmt->execute();
$result = $stmt->get_result();

// Se l'acquisto non esiste o non appartiene all'utente
if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "L'acquisto richiesto non è disponibile o non appartiene al tuo account.";
    header('Location: miei_acquisti.php');
    exit();
}

$acquisto = $result->fetch_assoc();

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
$categoria_display = isset($categorie_map[$acquisto['categoria_nome']]) ? $categorie_map[$acquisto['categoria_nome']] : $acquisto['categoria_nome'];

// Formatta la data
$data_acquisto = new DateTime($acquisto['data_acquisto']);
$data_formattata = $data_acquisto->format('d/m/Y H:i');

// Mostra istruzioni diverse in base al metodo di pagamento
$istruzioni_pagamento = '';
if ($acquisto['metodo_pagamento'] == 'bonifico') {
    $istruzioni_pagamento = '
    <div class="payment-instructions">
        <h3>Istruzioni per il Pagamento</h3>
        <p>Per completare l\'acquisto, effettua un bonifico con i seguenti dati:</p>
        <ul>
            <li><strong>Intestatario:</strong> Agenzia Immobiliare Srl</li>
            <li><strong>IBAN:</strong> IT60X0542811101000000123456</li>
            <li><strong>Causale:</strong> Acconto immobile #' . $acquisto['id_immobile'] . ' - Rif. ' . $acquisto['payment_id'] . '</li>
            <li><strong>Importo:</strong> ' . number_format($acquisto['acconto'], 2, ',', '.') . ' €</li>
        </ul>
        <p class="note">Il pagamento deve essere effettuato entro 3 giorni lavorativi. Dopo aver effettuato il bonifico, invia una copia della ricevuta all\'indirizzo email: amministrazione@immobiliare.it</p>
    </div>';
} else if ($acquisto['metodo_pagamento'] == 'carta') {
    if ($acquisto['stato_pagamento'] == 'pending') {
        $istruzioni_pagamento = '
        <div class="payment-instructions">
            <h3>Pagamento con Carta</h3>
            <p>Il tuo pagamento è in fase di elaborazione. Riceverai una conferma non appena sarà completato.</p>
            <p>Reference ID: ' . $acquisto['payment_id'] . '</p>
        </div>';
    } else if ($acquisto['stato_pagamento'] == 'completed') {
        $istruzioni_pagamento = '
        <div class="payment-instructions success">
            <h3>Pagamento Completato</h3>
            <p>Il tuo pagamento è stato elaborato con successo!</p>
            <p>Reference ID: ' . $acquisto['payment_id'] . '</p>
        </div>';
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Acquisto - Immobiliare</title>
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
        
        .confirmation-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-header i {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .confirmation-header h2 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        
        .confirmation-info {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .confirmation-col {
            flex: 1;
            min-width: 250px;
            padding: 0 15px;
            margin-bottom: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-group h3 {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-group p {
            font-size: 18px;
            color: #333;
            margin: 0;
        }
        
        .payment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .payment-details h3 {
            color: #333;
            margin-top: 0;
        }
        
        .payment-amount {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .payment-amount:last-child {
            border-bottom: none;
            font-weight: 700;
        }
        
        .payment-instructions {
            background-color: #e9f7ef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #28a745;
        }
        
        .payment-instructions.success {
            background-color: #d4edda;
        }
        
        .payment-instructions h3 {
            color: #28a745;
            margin-top: 0;
        }
        
        .payment-instructions ul {
            padding-left: 20px;
        }
        
        .payment-instructions li {
            margin-bottom: 10px;
        }
        
        .note {
            font-size: 14px;
            color: #6c757d;
            font-style: italic;
            margin-top: 10px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background-color: #3498db;
            color: white;
        }

        .immobile-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .confirmation-col {
                flex: 100%;
            }
            .action-buttons {
                flex-direction: column;
            }
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
            <a href="miei_acquisti.php">I miei acquisti</a> &gt; 
            <span>Conferma Acquisto</span>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h2>Acquisto Confermato!</h2>
                <p>Il tuo acquisto è stato registrato con successo.</p>
            </div>
            
            <div class="confirmation-info">
                <div class="confirmation-col">
                    <img src="images/<?php echo $acquisto['immagine']; ?>" alt="<?php echo $acquisto['immobile_nome']; ?>" class="immobile-image">
                    <div class="info-group">
                        <h3>Immobile</h3>
                        <p><?php echo $acquisto['immobile_nome']; ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Categoria</h3>
                        <p><?php echo $categoria_display; ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Ubicazione</h3>
                        <p><?php echo $acquisto['citta'] . ', ' . $acquisto['provincia']; ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Caratteristiche</h3>
                        <p><?php echo $acquisto['metri_quadri']; ?> m² - <?php echo $acquisto['stanze']; ?> stanze - <?php echo $acquisto['bagni']; ?> bagni</p>
                    </div>
                </div>
                
                <div class="confirmation-col">
                    <div class="info-group">
                        <h3>Riferimento Acquisto</h3>
                        <p>#<?php echo $acquisto['id']; ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Data</h3>
                        <p><?php echo $data_formattata; ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Tipo Acquisto</h3>
                        <p><?php echo $acquisto['tipo_acquisto'] == 'acquisto' ? 'Acquisto' : 'Affitto'; ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Metodo di Pagamento</h3>
                        <p>
                            <?php if($acquisto['metodo_pagamento'] == 'bonifico'): ?>
                                <i class="fas fa-university"></i> Bonifico Bancario
                            <?php elseif($acquisto['metodo_pagamento'] == 'carta'): ?>
                                <i class="fas fa-credit-card"></i> Carta di Credito
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="info-group">
                        <h3>Stato Pagamento</h3>
                        <p>
                            <?php if($acquisto['stato_pagamento'] == 'pending'): ?>
                                <span style="color: #ffc107;"><i class="fas fa-clock"></i> In attesa</span>
                            <?php elseif($acquisto['stato_pagamento'] == 'completed'): ?>
                                <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Completato</span>
                            <?php elseif($acquisto['stato_pagamento'] == 'failed'): ?>
                                <span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Fallito</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if($acquisto['payment_id']): ?>
                    <div class="info-group">
                        <h3>ID Pagamento</h3>
                        <p><?php echo $acquisto['payment_id']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="payment-details">
                <h3>Dettagli Pagamento</h3>
                <div class="payment-amount">
                    <span>Prezzo totale</span>
                    <span><?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</span>
                </div>
                <div class="payment-amount">
                    <span>Acconto versato (10%)</span>
                    <span><?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?> €</span>
                </div>
                <div class="payment-amount">
                    <span>Saldo rimanente</span>
                    <span><?php echo number_format($acquisto['prezzo'] - $acquisto['acconto'], 2, ',', '.'); ?> €</span>
                </div>
            </div>
            
            <?php echo $istruzioni_pagamento; ?>
            
            <div class="action-buttons">
                <a href="miei_acquisti.php" class="btn btn-primary"><i class="fas fa-list"></i> I miei acquisti</a>
                <a href="immobili.php" class="btn btn-outline"><i class="fas fa-search"></i> Scopri altri immobili</a>
                <a href="contatti.php" class="btn btn-secondary"><i class="fas fa-envelope"></i> Contattaci</a>
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
            </div>
            <div class="footer-column">
                <h3>Seguici</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <script>
    // Script per il dropdown menu
    document.addEventListener('DOMContentLoaded', function() {
        const userMenu = document.querySelector('.user-menu');
        if (userMenu) {
            userMenu.addEventListener('click', function(e) {
                this.querySelector('.dropdown-menu').classList.toggle('show');
                e.stopPropagation();
            });
            
            document.addEventListener('click', function() {
                document.querySelector('.dropdown-menu').classList.remove('show');
            });
        }
    });
    </script>
</body>
</html>