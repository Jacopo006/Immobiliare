<?php
session_start();

// Controlla se l'utente è loggato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    // Se l'utente non è loggato, reindirizzalo alla pagina di login
    header('Location: login_utente.php');
    exit();
}

include 'config.php'; // Includi il file di connessione

$user_id = $_SESSION['user_id'];

// Query per ottenere i preferiti dell'utente
$sql = "SELECT i.id, i.nome, i.descrizione, i.prezzo, i.metri_quadri, i.stanze, i.bagni, 
               c.nome AS categoria, i.citta, i.provincia, i.immagine, p.data_aggiunta
        FROM preferiti p
        JOIN immobili i ON p.id_immobile = i.id
        JOIN categorie c ON i.categoria_id = c.id
        WHERE p.id_utente = ?
        ORDER BY p.data_aggiunta DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Preferiti - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style_immobili.css">
    <style>
        .message-container {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-message {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
                                <li><a href="preferiti.php" class="active"><i class="fas fa-heart"></i> Preferiti</a></li>
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

    <!-- Banner Principale -->
    <section id="banner" class="banner-small">
        <div class="banner-content">
            <h1>I Miei Preferiti</h1>
            <p>Immobili salvati che ti interessano</p>
        </div>
    </section>

    <!-- Messaggi di sistema -->
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="container">
            <div class="message-container success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="container">
            <div class="message-container error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['info_message'])): ?>
        <div class="container">
            <div class="message-container info-message">
                <?php echo $_SESSION['info_message']; ?>
                <?php unset($_SESSION['info_message']); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sezione Risultati -->
    <section id="immobili-results">
        <div class="container">
            <?php if ($result->num_rows > 0): ?>
                <h2><?php echo $result->num_rows; ?> immobili nei preferiti</h2>
                <div class="immobili-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="immobile-card">
                            <div class="immobile-img">
                                <img src="<?php echo $row['immagine']; ?>" alt="<?php echo $row['nome']; ?>">
                                <div class="categoria-tag"><?php echo isset($categorie_map[$row['categoria']]) ? $categorie_map[$row['categoria']] : $row['categoria']; ?></div>
                            </div>
                            <div class="immobile-details">
                                <h3><?php echo $row['nome']; ?></h3>
                                <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $row['citta']; ?>, <?php echo $row['provincia']; ?></p>
                                <p class="price"><?php echo number_format($row['prezzo'], 0, ',', '.'); ?> €</p>
                                <div class="immobile-features">
                                    <span><i class="fas fa-vector-square"></i> <?php echo $row['metri_quadri']; ?> m²</span>
                                    <span><i class="fas fa-door-open"></i> <?php echo $row['stanze']; ?> stanze</span>
                                    <span><i class="fas fa-bath"></i> <?php echo $row['bagni']; ?> bagni</span>
                                </div>
                                <p class="description"><?php echo substr($row['descrizione'], 0, 100); ?>...</p>
                                <div class="immobile-actions">
                                    <a href="immobile.php?id=<?php echo $row['id']; ?>" class="btn-details">Dettagli</a>
                                    <a href="remove_preferito.php?id=<?php echo $row['id']; ?>" class="btn-remove" onclick="return confirm('Sei sicuro di voler rimuovere questo immobile dai preferiti?');"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-heart-broken"></i>
                    <h3>Non hai ancora salvato immobili nei preferiti</h3>
                    <p>Sfoglia il nostro catalogo e aggiungi gli immobili che ti interessano ai preferiti.</p>
                    <a href="immobili.php" class="btn-view-all">Esplora gli immobili</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

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

</body>
</html>

<?php
$stmt->close();
$conn->close(); // Chiudi la connessione
?>