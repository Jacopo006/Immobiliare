<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    $_SESSION['error_message'] = "Devi effettuare l'accesso come utente per visualizzare questa pagina.";
    header('Location: login_utente.php');
    exit();
}

$id_utente = $_SESSION['user_id'];

// Includi il file di configurazione
include 'config.php';

// Recupera tutti gli acquisti dell'utente
$sql = "SELECT a.*, i.nome AS immobile_nome, i.prezzo, i.immagine, i.citta, i.provincia, 
               c.nome AS categoria_nome
        FROM acquisti a
        JOIN immobili i ON a.id_immobile = i.id
        JOIN categorie c ON i.categoria_id = c.id
        WHERE a.id_utente = ?
        ORDER BY a.data_acquisto DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_utente);
$stmt->execute();
$result = $stmt->get_result();

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

// Funzione per ottenere la percentuale di completamento
function getProgressPercentage($acconto, $prezzo) {
    return round(($acconto / $prezzo) * 100);
}

// Controlla se esiste un messaggio di errore o successo
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

// Pulisci i messaggi dalla sessione dopo averli recuperati
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Acquisti - Immobiliare</title>
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .acquisti-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .acquisto-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .acquisto-card:hover {
            transform: translateY(-5px);
        }
        
        .acquisto-image {
            height: 180px;
            overflow: hidden;
            position: relative;
        }
        
        .acquisto-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .status-pending {
            background-color: #ffc107;
        }
        
        .status-completed {
            background-color: #28a745;
        }
        
        .status-failed {
            background-color: #dc3545;
        }
        
        .progress-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0,0,0,0.5);
            padding: 5px 10px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }
        
        .progress-bar {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 3px;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #3498db;
        }
        
        .acquisto-details {
            padding: 15px;
        }
        
        .acquisto-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .acquisto-category {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .acquisto-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .acquisto-price {
            font-weight: 600;
            color: #333;
        }
        
        .acquisto-date {
            color: #6c757d;
            font-size: 13px;
        }
        
        .acquisto-location {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .acquisto-location i {
            margin-right: 5px;
            color: #3498db;
        }
        
        .acquisto-actions {
            border-top: 1px solid #e9ecef;
            padding: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .acquisto-actions a {
            text-decoration: none;
            color: #3498db;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .acquisto-actions a i {
            margin-right: 5px;
        }
        
        .acquisto-actions a:hover {
            color: #2980b9;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 40px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
            .acquisto-image {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
    }

    .acquisto-image img {
        width: 100%;
        height: auto;
        display: block;
    }

    .categoria-tag {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: rgba(255, 87, 34, 0.9); /* Arancione trasparente */
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
        font-size: 0.9rem;
    }

        
        @media (max-width: 768px) {
            .acquisti-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header h1 {
                margin-bottom: 10px;
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
                            <li><a href="profilo-utente.php"><i class="fas fa-id-card"></i> Profilo</a></li>
                            <?php if($_SESSION['user_type'] == 'utente'): ?>
                               
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
            <span>I miei acquisti</span>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> I miei acquisti</h1>
        </div>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($result->num_rows > 0): ?>
            <div class="acquisti-grid">
                <?php while($acquisto = $result->fetch_assoc()): 
                    // Formatta la data
                    $data_acquisto = new DateTime($acquisto['data_acquisto']);
                    $data_formattata = $data_acquisto->format('d/m/Y');
                    
                    // Calcola la percentuale di completamento
                    $percentuale = getProgressPercentage($acquisto['acconto'], $acquisto['prezzo']);
                    
                    // Formatta la categoria per la visualizzazione
                    $categoria_display = isset($categorie_map[$acquisto['categoria_nome']]) ? 
                                        $categorie_map[$acquisto['categoria_nome']] : 
                                        $acquisto['categoria_nome'];
                ?>
                <div class="acquisto-card">
                    <div class="acquisto-image">
                    <img src="<?php echo htmlspecialchars($acquisto['immagine']); ?>" alt="...">

                        <?php if($acquisto['stato_pagamento'] == 'pending'): ?>
                            <span class="status-badge status-pending">In attesa</span>
                        <?php elseif($acquisto['stato_pagamento'] == 'completed'): ?>
                            <span class="status-badge status-completed">Completato</span>
                        <?php elseif($acquisto['stato_pagamento'] == 'failed'): ?>
                            <span class="status-badge status-failed">Fallito</span>
                        <?php endif; ?>
                        
                        <div class="progress-container">
                            <span>Pagamento: <?php echo $percentuale; ?>%</span>
                            <span><?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?> € / <?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentuale; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="acquisto-details">
                        <h3 class="acquisto-title"><?php echo $acquisto['immobile_nome']; ?></h3>
                        <p class="acquisto-category"><?php echo $categoria_display; ?></p>
                        
                        <div class="acquisto-info">
                            <span class="acquisto-price"><?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</span>
                            <span class="acquisto-date"><?php echo $data_formattata; ?></span>
                        </div>
                        
                        <div class="acquisto-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo $acquisto['citta'] . ', ' . $acquisto['provincia']; ?>
                        </div>
                        
                        <div class="acquisto-payment-info">
                            <span class="payment-method">
                                <?php if($acquisto['metodo_pagamento'] == 'bonifico'): ?>
                                    <i class="fas fa-university"></i> Bonifico Bancario
                                <?php elseif($acquisto['metodo_pagamento'] == 'carta'): ?>
                                    <i class="fas fa-credit-card"></i> Carta di Credito
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="acquisto-actions">
                        <a href="conferma_acquisto.php?id=<?php echo $acquisto['id']; ?>"><i class="fas fa-eye"></i> Dettagli</a>
                        <a href="piano_pagamenti.php?id=<?php echo $acquisto['id']; ?>"><i class="fas fa-money-bill-wave"></i> Piano pagamenti</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>Non hai ancora effettuato acquisti</h3>
                <p>Esplora il nostro catalogo di immobili e trova la casa dei tuoi sogni!</p>
                <a href="immobili.php" class="btn btn-primary">Sfoglia immobili</a>
            </div>
        <?php endif; ?>
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

<?php
$conn->close(); // Chiudi la connessione
?>