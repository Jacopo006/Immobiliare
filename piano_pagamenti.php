<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    $_SESSION['error_message'] = "Devi effettuare l'accesso come utente per visualizzare questa pagina.";
    header('Location: login_utente.php');
    exit();
}

// Verifica se è stato fornito un ID acquisto
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Parametro mancante: ID acquisto non specificato.";
    header('Location: miei_acquisti.php');
    exit();
}

$id_acquisto = intval($_GET['id']);
$id_utente = $_SESSION['user_id'];

// Includi il file di configurazione
include 'config.php';

// Recupera i dati dell'acquisto e dell'immobile
$sql = "SELECT a.*, i.nome AS immobile_nome, i.prezzo, i.immagine, i.citta, i.provincia, 
               c.nome AS categoria_nome
        FROM acquisti a
        JOIN immobili i ON a.id_immobile = i.id
        JOIN categorie c ON i.categoria_id = c.id
        WHERE a.id = ? AND a.id_utente = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_acquisto, $id_utente);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se l'acquisto esiste e appartiene all'utente loggato
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Acquisto non trovato o non autorizzato.";
    header('Location: miei_acquisti.php');
    exit();
}

$acquisto = $result->fetch_assoc();

// Recupera le rate di pagamento per questo acquisto
$sql_rate = "SELECT * FROM rate_pagamento 
             WHERE id_acquisto = ? 
             ORDER BY data_scadenza ASC";

$stmt_rate = $conn->prepare($sql_rate);
$stmt_rate->bind_param("i", $id_acquisto);
$stmt_rate->execute();
$result_rate = $stmt_rate->get_result();

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

// Calcolo del totale pagato e del saldo rimanente
$totale_pagato = $acquisto['acconto'];
$saldo_rimanente = $acquisto['prezzo'] - $totale_pagato;
$percentuale_completamento = round(($totale_pagato / $acquisto['prezzo']) * 100);

// Formatta la data dell'acquisto
$data_acquisto = new DateTime($acquisto['data_acquisto']);
$data_acquisto_formattata = $data_acquisto->format('d/m/Y');

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
    <title>Piano Pagamenti - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="piano_pagamenti.css">

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
            <a href="miei_acquisti.php">I miei acquisti</a> &gt; 
            <span>Piano pagamenti</span>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-money-bill-wave"></i> Piano pagamenti</h1>
            <div class="header-actions">
                <a href="miei_acquisti.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Torna agli acquisti</a>
            </div>
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
        
        <!-- Riepilogo dell'acquisto -->
        <div class="acquisto-summary">
        <div class="acquisto-image">
        <img src="<?php echo $acquisto['immagine']; ?>" alt="<?php echo $acquisto['immobile_nome']; ?>">
                        <div class="categoria-tag"><?php echo isset($categorie_map[$acquisto['categoria_nome']]) ? 
                                $categorie_map[$acquisto['categoria_nome']] : 
                                $acquisto['categoria_nome']; ?></div>
                    </div>
            <div class="acquisto-details">
                <h2 class="acquisto-title"><?php echo $acquisto['immobile_nome']; ?></h2>
                <span class="acquisto-category">
                    <?php echo isset($categorie_map[$acquisto['categoria_nome']]) ? 
                                $categorie_map[$acquisto['categoria_nome']] : 
                                $acquisto['categoria_nome']; ?>
                </span>
                
                <div class="acquisto-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo $acquisto['citta'] . ', ' . $acquisto['provincia']; ?>
                </div>
                
                <div class="acquisto-info">
                    <div class="info-item">
                        <div class="info-label">Prezzo totale</div>
                        <div class="info-value"><?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Data acquisto</div>
                        <div class="info-value"><?php echo $data_acquisto_formattata; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Metodo di pagamento</div>
                        <div class="info-value">
                            <?php if($acquisto['metodo_pagamento'] == 'bonifico'): ?>
                                <i class="fas fa-university"></i> Bonifico Bancario
                            <?php elseif($acquisto['metodo_pagamento'] == 'carta'): ?>
                                <i class="fas fa-credit-card"></i> Carta di Credito
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Stato pagamento</div>
                        <div class="info-value">
                            <?php if($acquisto['stato_pagamento'] == 'pending'): ?>
                                <span style="color: #ffc107;"><i class="fas fa-clock"></i> In attesa</span>
                            <?php elseif($acquisto['stato_pagamento'] == 'completed'): ?>
                                <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Completato</span>
                            <?php elseif($acquisto['stato_pagamento'] == 'failed'): ?>
                                <span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Fallito</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span>Pagamento completato: <?php echo $percentuale_completamento; ?>%</span>
                        <span><?php echo number_format($totale_pagato, 2, ',', '.'); ?> € / <?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentuale_completamento; ?>%"></div>
                    </div>
                </div>
                
                <div class="summary-actions">
                    <a href="conferma_acquisto.php?id=<?php echo $acquisto['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> Dettagli acquisto</a>
                    <a href="#" class="btn btn-outline download-btn" onclick="window.print()"><i class="fas fa-download"></i> Scarica riepilogo</a>
                </div>
            </div>
        </div>
        
        <!-- Riepilogo pagamenti -->
        <div class="payment-summary">
            <h3 class="section-title">Riepilogo pagamenti</h3>
            <div class="summary-row">
                <span>Prezzo totale</span>
                <span><?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</span>
            </div>
            <div class="summary-row">
                <span>Acconto versato</span>
                <span><?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?> €</span>
            </div>
            <div class="summary-row">
                <span>Saldo rimanente</span>
                <span><?php echo number_format($saldo_rimanente, 2, ',', '.'); ?> €</span>
            </div>
        </div>
        
        <?php 
        // Controlla se ci sono rate di pagamento future
        $next_payment = null;
        $today = new DateTime();
        
        if ($result_rate->num_rows > 0) {
            $result_rate->data_seek(0); // Reset del puntatore
            while ($rata = $result_rate->fetch_assoc()) {
                $data_scadenza = new DateTime($rata['data_scadenza']);
                if ($rata['stato'] == 'pending' && $data_scadenza >= $today) {
                    $next_payment = $rata;
                    break;
                }
            }
        }
        
        // Se esiste una prossima rata da pagare
        if ($next_payment):
            $data_scadenza = new DateTime($next_payment['data_scadenza']);
            $giorni_rimanenti = $today->diff($data_scadenza)->days;
        ?>
        <!-- Prossimo pagamento -->
        <div class="next-payment">
            <div class="next-payment-header">
                <h3 class="next-payment-title">Prossimo pagamento</h3>
                <span class="next-payment-status">
                    <?php if ($giorni_rimanenti > 0): ?>
                        <i class="fas fa-clock"></i> <?php echo $giorni_rimanenti; ?> giorni rimanenti
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle"></i> Scaduto oggi
                    <?php endif; ?>
                </span>
            </div>
            <div class="next-payment-details">
                <div class="next-payment-amount"><?php echo number_format($next_payment['importo'], 2, ',', '.'); ?> €</div>
                <div class="next-payment-date">Scadenza: <?php echo $data_scadenza->format('d/m/Y'); ?></div>
            </div>
            <form action="processa_pagamento.php" method="post">
                <input type="hidden" name="id_rata" value="<?php echo $next_payment['id']; ?>">
                <input type="hidden" name="id_acquisto" value="<?php echo $id_acquisto; ?>">
                
                <div class="payment-method-radio">
                    <label class="radio-option <?php echo ($acquisto['metodo_pagamento'] == 'carta') ? 'selected' : ''; ?>">
                        <input type="radio" name="metodo_pagamento" value="carta" <?php echo ($acquisto['metodo_pagamento'] == 'carta') ? 'checked' : ''; ?>>
                        <i class="fas fa-credit-card"></i> Carta di credito
                    </label>
                    <label class="radio-option <?php echo ($acquisto['metodo_pagamento'] == 'bonifico') ? 'selected' : ''; ?>">
                        <input type="radio" name="metodo_pagamento" value="bonifico" <?php echo ($acquisto['metodo_pagamento'] == 'bonifico') ? 'checked' : ''; ?>>
                        <i class="fas fa-university"></i> Bonifico bancario
                    </label>
                </div>
                
                <button type="submit" class="payment-button">Procedi al pagamento</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Piano di pagamento -->
        <div class="payment-plan">
            <h3 class="section-title">Piano di pagamento</h3>
            
            <?php if($result_rate->num_rows > 0): ?>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Rata</th>
                            <th>Importo</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                            <th>Data pagamento</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $result_rate->data_seek(0); // Reset del puntatore
                        $count = 1;
                        while($rata = $result_rate->fetch_assoc()): 
                            $data_scadenza = new DateTime($rata['data_scadenza']);
                            $data_scadenza_formatted = $data_scadenza->format('d/m/Y');
                            
                            $data_pagamento = !empty($rata['data_pagamento']) ? 
                                (new DateTime($rata['data_pagamento']))->format('d/m/Y') : 
                                '-';
                            
                            // Determina lo stato della rata
                            $status_class = '';
                            $status_text = '';
                            
                            if ($rata['stato'] == 'paid') {
                                $status_class = 'status-paid';
                                $status_text = 'Pagato';
                            } elseif ($rata['stato'] == 'pending') {
                                $today = new DateTime();
                                if ($data_scadenza < $today) {
                                    $status_class = 'status-missed';
                                    $status_text = 'Scaduto';
                                } else {
                                    $status_class = 'status-pending';
                                    $status_text = 'In attesa';
                                }
                            } else {
                                $status_class = 'status-upcoming';
                                $status_text = 'Programmato';
                            }
                            
                            // Formattazione della data di pagamento
                            if($rata['data_pagamento']) {
                                $data_pagamento_obj = new DateTime($rata['data_pagamento']);
                                $data_pagamento = $data_pagamento_obj->format('d/m/Y');
                            } else {
                                $data_pagamento = '-';
                            }
                            
                            // Calcolo della data di scadenza
                            $data_scadenza_obj = new DateTime($rata['data_scadenza']);
                            $data_scadenza_formatted = $data_scadenza_obj->format('d/m/Y');
                        ?>
                        <tr>
                            <td>Rata <?php echo $count; ?></td>
                            <td><?php echo number_format($rata['importo'], 2, ',', '.'); ?> €</td>
                            <td><?php echo $data_scadenza_formatted; ?></td>
                            <td><span class="payment-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo $data_pagamento; ?></td>
                            <td class="payment-actions">
                                <?php if($rata['stato'] == 'pending'): ?>
                                    <a href="processa_pagamento.php?id_rata=<?php echo $rata['id']; ?>&id_acquisto=<?php echo $id_acquisto; ?>" class="action-btn pay-now">
                                        <i class="fas fa-credit-card"></i> Paga ora
                                    </a>
                                <?php elseif($rata['stato'] == 'paid'): ?>
                                    <a href="ricevuta_pagamento.php?id_rata=<?php echo $rata['id']; ?>" class="action-btn">
                                        <i class="fas fa-file-invoice"></i> Ricevuta
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $count++; endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-payments">
                    <i class="fas fa-info-circle"></i>
                    <p>Non sono presenti rate di pagamento per questo acquisto.</p>
                    <p>Il pagamento è stato effettuato in un'unica soluzione.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Documenti e fatture -->
        <div class="payment-documents">
            <h3 class="section-title">Documenti e fatture</h3>
            <div class="summary-actions">
                <a href="conferma_acquisto.php?id=<?php echo $acquisto['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-file-alt"></i> Contratto di acquisto
                </a>
                <a href="fattura_acquisto.php?id=<?php echo $acquisto['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-file-invoice"></i> Fattura acconto
                </a>
                <?php if($acquisto['stato_pagamento'] == 'completed'): ?>
                <a href="fattura_saldo.php?id=<?php echo $acquisto['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-file-invoice-dollar"></i> Fattura saldo
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Immobiliare</h3>
                <p>La tua agenzia immobiliare di fiducia dal 1995.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Collegamenti rapidi</h4>
                <ul>
                    <li><a href="home-page.php">Home</a></li>
                    <li><a href="immobili.php">Immobili</a></li>
                    <li><a href="contatti.php">Contatti</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="privacy_policy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contatti</h4>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano, IT</p>
                <p><i class="fas fa-phone"></i> +39 02 1234567</p>
                <p><i class="fas fa-envelope"></i> info@immobiliare.it</p>
            </div>
            <div class="footer-section">
                <h4>Newsletter</h4>
                <p>Iscriviti per ricevere aggiornamenti sui nuovi immobili</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="La tua email" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <!-- Script JavaScript -->
    <script>
        // Gestione del menu dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userMenu = document.querySelector('.user-menu');
            if(userMenu) {
                userMenu.addEventListener('click', function(e) {
                    const dropdown = this.querySelector('.dropdown-menu');
                    dropdown.classList.toggle('active');
                    e.stopPropagation();
                });
                
                document.addEventListener('click', function() {
                    const dropdown = document.querySelector('.dropdown-menu');
                    if(dropdown) {
                        dropdown.classList.remove('active');
                    }
                });
            }
            
            // Selezione del metodo di pagamento
            const radioOptions = document.querySelectorAll('.radio-option');
            radioOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Rimuovi la classe selected da tutte le opzioni
                    radioOptions.forEach(opt => opt.classList.remove('selected'));
                    // Aggiungi la classe selected all'opzione cliccata
                    this.classList.add('selected');
                    // Seleziona il radio button all'interno dell'opzione
                    const radioInput = this.querySelector('input[type="radio"]');
                    radioInput.checked = true;
                });
            });
        });
    </script>
</body>
</html>