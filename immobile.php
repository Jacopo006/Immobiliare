<?php
session_start();

// Includi il file di configurazione
include 'config.php';

// Verifica se l'ID dell'immobile è stato fornito
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Reindirizza alla pagina immobili se l'ID non è valido
    header('Location: immobili.php');
    exit();
}

$id = (int)$_GET['id'];

// Query per ottenere i dettagli dell'immobile
$sql = "SELECT i.*, c.nome AS categoria, a.nome AS agente_nome, a.cognome AS agente_cognome, 
               a.email AS agente_email, a.telefono AS agente_telefono
        FROM immobili i
        JOIN categorie c ON i.categoria_id = c.id
        LEFT JOIN agenti_immobiliari a ON i.agente_id = a.id
        WHERE i.id = $id";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Immobile non trovato, reindirizza alla pagina immobili
    header('Location: immobili.php');
    exit();
}

$immobile = $result->fetch_assoc();

// Verifica se l'immobile è nei preferiti dell'utente (se l'utente è loggato)
$in_preferiti = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente') {
    $user_id = $_SESSION['user_id'];
    $sql_preferiti = "SELECT * FROM preferiti WHERE id_utente = $user_id AND id_immobile = $id";
    $result_preferiti = $conn->query($sql_preferiti);
    $in_preferiti = ($result_preferiti->num_rows > 0);
}

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
    <title><?php echo $immobile['nome']; ?> - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style_immobile_dettaglio.css">
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
                <li><a href="immobili.php" class="active"><i class="fas fa-building"></i> Immobili</a></li>
                <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="user-menu">
                        <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fas fa-id-card"></i> Profilo</a></li>
                            <?php if($_SESSION['user_type'] == 'utente'): ?>
                                <li><a href="preferiti.php"><i class="fas fa-heart"></i> Preferiti</a></li>
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
            <span><?php echo $immobile['nome']; ?></span>
        </div>
    </div>

    <!-- Sezione Dettaglio Immobile -->
    <section id="immobile-dettaglio">
        <div class="container">
            <div class="immobile-header">
                <div class="immobile-title">
                    <h1><?php echo $immobile['nome']; ?></h1>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $immobile['citta']; ?>, <?php echo $immobile['provincia']; ?></p>
                </div>
                <div class="immobile-actions">
                    <p class="price"><?php echo number_format($immobile['prezzo'], 0, ',', '.'); ?> €</p>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente'): ?>
                        <?php if($in_preferiti): ?>
                            <a href="remove_preferito.php?id=<?php echo $immobile['id']; ?>" class="btn-favorite active">
                                <i class="fas fa-heart"></i> Nei preferiti
                            </a>
                        <?php else: ?>
                            <a href="add_preferito.php?id=<?php echo $immobile['id']; ?>" class="btn-favorite">
                                <i class="far fa-heart"></i> Aggiungi ai preferiti
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="immobile-content">
                <div class="immobile-gallery">
                    <div class="main-image">
                        <img src="<?php echo $immobile['immagine']; ?>" alt="<?php echo $immobile['nome']; ?>">
                        <div class="categoria-tag"><?php echo $categoria_display; ?></div>
                    </div>
                    <!-- Qui potresti aggiungere una galleria di immagini se disponibili -->
                </div>

                <div class="immobile-info">
                    <div class="info-section">
                        <h2>Caratteristiche principali</h2>
                        <div class="immobile-features">
                            <div class="feature">
                                <i class="fas fa-vector-square"></i>
                                <span>Superficie</span>
                                <strong><?php echo $immobile['metri_quadri']; ?> m²</strong>
                            </div>
                            <div class="feature">
                                <i class="fas fa-door-open"></i>
                                <span>Stanze</span>
                                <strong><?php echo $immobile['stanze']; ?></strong>
                            </div>
                            <div class="feature">
                                <i class="fas fa-bath"></i>
                                <span>Bagni</span>
                                <strong><?php echo $immobile['bagni']; ?></strong>
                            </div>
                            <div class="feature">
                                <i class="fas fa-building"></i>
                                <span>Categoria</span>
                                <strong><?php echo $categoria_display; ?></strong>
                            </div>
                            <div class="feature">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Data inserimento</span>
                                <strong><?php echo date('d/m/Y', strtotime($immobile['data_inserimento'])); ?></strong>
                            </div>
                            <div class="feature">
                                <i class="fas fa-tag"></i>
                                <span>Stato</span>
                                <strong><?php echo ucfirst($immobile['stato']); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h2>Descrizione</h2>
                        <div class="description">
                            <?php echo nl2br($immobile['descrizione']); ?>
                        </div>
                    </div>

                    <?php if(!empty($immobile['agente_nome'])): ?>
                    <div class="info-section">
                        <h2>Contatta l'agente</h2>
                        <div class="agente-info">
                            <div class="agente-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="agente-details">
                                <h3><?php echo $immobile['agente_nome'] . ' ' . $immobile['agente_cognome']; ?></h3>
                                <p><i class="fas fa-envelope"></i> <?php echo $immobile['agente_email']; ?></p>
                                <?php if(!empty($immobile['agente_telefono'])): ?>
                                <p><i class="fas fa-phone"></i> <?php echo $immobile['agente_telefono']; ?></p>
                                <?php endif; ?>
                                <a href="contatta_agente.php?id=<?php echo $immobile['agente_id']; ?>&immobile=<?php echo $immobile['id']; ?>" class="btn-contact">Contatta l'agente</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-section">
                        <h2>Richiedi informazioni</h2>
                        <form class="contact-form" action="invia_richiesta.php" method="POST">
                            <input type="hidden" name="immobile_id" value="<?php echo $immobile['id']; ?>">
                            <div class="form-group">
                                <label for="nome">Nome e Cognome <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                    value="<?php echo htmlspecialchars($_SESSION['user_name'] . ' ' . $_SESSION['user_cognome']); ?>"
                                    <?php endif; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                    value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>"
                                    <?php endif; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">Telefono</label>
                                <input type="tel" id="telefono" name="telefono"
                                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_telefono'])): ?>
                                    value="<?php echo htmlspecialchars($_SESSION['user_telefono']); ?>"
                                    <?php endif; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label for="messaggio">Messaggio <span class="required">*</span></label>
                                <textarea id="messaggio" name="messaggio" rows="4" required>Salve, sono interessato a questo immobile (<?php echo $immobile['nome']; ?>). Vorrei ricevere maggiori informazioni.</textarea>
                            </div>
                            
                            <div class="form-group privacy-check">
                                <input type="checkbox" id="privacy" name="privacy" required>
                                <label for="privacy">Ho letto e accetto la <a href="privacy.php" target="_blank">Privacy Policy</a> <span class="required">*</span></label>
                            </div>
                            
                            <button type="submit" class="btn-submit">Invia richiesta</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sezione Immobili Simili -->
<section id="immobili-simili">
    <div class="container">
        <h2>Immobili simili che potrebbero interessarti</h2>
        
        <?php
        // Query per trovare immobili simili (stessa categoria e città)
        $sql_simili = "SELECT i.id, i.nome, i.descrizione, i.prezzo, i.metri_quadri, i.stanze, i.bagni, 
                              c.nome AS categoria, i.citta, i.provincia, i.immagine, i.stato
                      FROM immobili i
                      JOIN categorie c ON i.categoria_id = c.id
                      WHERE i.stato = 'disponibile' 
                      AND i.id != $id 
                      AND (i.categoria_id = {$immobile['categoria_id']} OR i.citta = '{$immobile['citta']}')
                      LIMIT 3";
        
        $result_simili = $conn->query($sql_simili);
        
        if ($result_simili->num_rows > 0):
        ?>
        <div class="immobili-grid">
            <?php while($row = $result_simili->fetch_assoc()): 
                // Verifica se l'immobile è nei preferiti
                $sim_in_preferiti = false;
                if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente') {
                    $sim_id = $row['id'];
                    $user_id = $_SESSION['user_id'];
                    $sql_sim_preferiti = "SELECT * FROM preferiti WHERE id_utente = $user_id AND id_immobile = $sim_id";
                    $result_sim_preferiti = $conn->query($sql_sim_preferiti);
                    $sim_in_preferiti = ($result_sim_preferiti->num_rows > 0);
                }
            ?>
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
                        <div class="immobile-actions">
                            <div class="action-buttons">
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente'): ?>
                                    <?php if($sim_in_preferiti): ?>
                                        <a href="remove_preferito.php?id=<?php echo $row['id']; ?>" class="btn-favorite active">
                                            <i class="fas fa-heart"></i> Nei preferiti
                                        </a>
                                    <?php else: ?>
                                        <a href="add_preferito.php?id=<?php echo $row['id']; ?>" class="btn-favorite">
                                            <i class="far fa-heart"></i> Aggiungi ai preferiti
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if($row['stato'] == 'disponibile'): ?>
                                        <a href="acquista.php?id=<?php echo $row['id']; ?>" class="btn-purchase">
                                            <i class="fas fa-shopping-cart"></i> Acquista
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="view-details">
                            <a href="immobile_dettaglio.php?id=<?php echo $row['id']; ?>" class="btn-details">Visualizza dettagli</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <p class="no-similar">Non ci sono immobili simili disponibili al momento.</p>
        <?php endif; ?>
        
        <div class="view-all">
            <a href="immobili.php" class="btn-view-all">Visualizza tutti gli immobili</a>
        </div>
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
$conn->close(); // Chiudi la connessione
?>