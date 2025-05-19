<?php
session_start();


// Procedi con l'inclusione del file di configurazione e il resto della logica
include 'config.php'; // Includi il file di connessione

// Inizializzazione delle variabili di filtro
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$prezzo_min = isset($_GET['prezzo_min']) ? (int)$_GET['prezzo_min'] : 0;
$prezzo_max = isset($_GET['prezzo_max']) ? (int)$_GET['prezzo_max'] : 1000000;
$citta = isset($_GET['citta']) ? $_GET['citta'] : '';
$stanze = isset($_GET['stanze']) ? (int)$_GET['stanze'] : 0;

$sql = "SELECT i.id, i.nome, i.descrizione, i.prezzo, i.metri_quadri, i.stanze, i.bagni, c.nome AS categoria, 
                i.citta, i.provincia, i.immagine 
        FROM immobili i
        JOIN categorie c ON i.categoria_id = c.id
        WHERE i.stato = 'disponibile'";

// Aggiungi filtri alla query se specificati
if (!empty($categoria)) {
    $sql .= " AND c.nome = '$categoria'";
}
if ($prezzo_min > 0) {
    $sql .= " AND i.prezzo >= $prezzo_min";
}
if ($prezzo_max < 1000000) {
    $sql .= " AND i.prezzo <= $prezzo_max";
}
if (!empty($citta)) {
    $sql .= " AND i.citta LIKE '%$citta%'";
}
if ($stanze > 0) {
    $sql .= " AND i.stanze >= $stanze";
}

// Esecuzione della query
$result = $conn->query($sql);

// Query per ottenere le categorie disponibili per il filtro
$sql_categorie = "SELECT DISTINCT c.nome AS categoria
                  FROM immobili i
                  JOIN categorie c ON i.categoria_id = c.id
                  WHERE i.stato = 'disponibile'";
$result_categorie = $conn->query($sql_categorie);

// Query per ottenere le città disponibili per il filtro
$sql_citta = "SELECT DISTINCT citta FROM immobili WHERE stato = 'disponibile'";
$result_citta = $conn->query($sql_citta);

// Se l'utente è loggato come utente, ottiene l'elenco dei suoi immobili preferiti per confronto
$preferiti = [];
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente') {
    $user_id = $_SESSION['user_id'];
    $sql_preferiti = "SELECT id_immobile FROM preferiti WHERE id_utente = ?";
    $stmt_preferiti = $conn->prepare($sql_preferiti);
    $stmt_preferiti->bind_param("i", $user_id);
    $stmt_preferiti->execute();
    $result_preferiti = $stmt_preferiti->get_result();
    
    while ($row_preferito = $result_preferiti->fetch_assoc()) {
        $preferiti[] = $row_preferito['id_immobile'];
    }
    $stmt_preferiti->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Immobili Disponibili - Immobiliare</title>
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
        .btn-favorite.active {
            background-color: #e74c3c;
            color: white;
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
                <li><a href="immobili.php" class="active"><i class="fas fa-building"></i> Immobili</a></li>
                <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="user-menu">
                        <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="profilo-utente.php"><i class="fas fa-id-card"></i> Profilo</a></li>
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

    <!-- Banner Principale -->
    <section id="banner" class="banner-small">
        <div class="banner-content">
            <h1>Immobili Disponibili</h1>
            <p>Trova la casa dei tuoi sogni tra le nostre proposte selezionate</p>
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

    <!-- Sezione Ricerca e Filtri -->
    <section id="filtri">
        <div class="container">
            <h2>Filtra la Tua Ricerca</h2>
            <form action="immobili.php" method="GET" class="filtri-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="categoria"><i class="fas fa-tag"></i> Categoria</label>
                        <select id="categoria" name="categoria">
                            <option value="">Tutte le categorie</option>
                            <?php
                            $categorie_map = [
                                'appartamento' => 'Appartamento',
                                'villa' => 'Villa',
                                'attico' => 'Attico',
                                'casa_indipendente' => 'Casa Indipendente',
                                'terreno' => 'Terreno',
                                'ufficio' => 'Ufficio',
                                'negozio' => 'Negozio'
                            ];
                            
                            if ($result_categorie->num_rows > 0) {
                                while($row = $result_categorie->fetch_assoc()) {
                                    $selected = ($categoria == $row['categoria']) ? 'selected' : '';
                                    $label = isset($categorie_map[$row['categoria']]) ? $categorie_map[$row['categoria']] : $row['categoria'];
                                    echo "<option value='" . $row['categoria'] . "' $selected>" . $label . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="citta"><i class="fas fa-map-marker-alt"></i> Città</label>
                        <select id="citta" name="citta">
                            <option value="">Tutte le città</option>
                            <?php
                            if ($result_citta->num_rows > 0) {
                                while($row = $result_citta->fetch_assoc()) {
                                    $selected = ($citta == $row['citta']) ? 'selected' : '';
                                    echo "<option value='" . $row['citta'] . "' $selected>" . $row['citta'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stanze"><i class="fas fa-door-open"></i> Stanze (min)</label>
                        <select id="stanze" name="stanze">
                            <option value="0" <?php echo ($stanze == 0) ? 'selected' : ''; ?>>Qualsiasi</option>
                            <option value="1" <?php echo ($stanze == 1) ? 'selected' : ''; ?>>1+</option>
                            <option value="2" <?php echo ($stanze == 2) ? 'selected' : ''; ?>>2+</option>
                            <option value="3" <?php echo ($stanze == 3) ? 'selected' : ''; ?>>3+</option>
                            <option value="4" <?php echo ($stanze == 4) ? 'selected' : ''; ?>>4+</option>
                            <option value="5" <?php echo ($stanze == 5) ? 'selected' : ''; ?>>5+</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group prezzo-range">
                        <label><i class="fas fa-euro-sign"></i> Prezzo</label>
                        <div class="range-inputs">
                            <div>
                                <input type="number" id="prezzo_min" name="prezzo_min" min="0" step="10000" value="<?php echo $prezzo_min; ?>" placeholder="Min €">
                                <span>Min €</span>
                            </div>
                            <div>
                                <input type="number" id="prezzo_max" name="prezzo_max" min="0" step="10000" value="<?php echo $prezzo_max; ?>" placeholder="Max €">
                                <span>Max €</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Cerca</button>
                <a href="immobili.php" class="reset-btn"><i class="fas fa-undo"></i> Reset Filtri</a>
            </form>
        </div>
    </section>

    <!-- Sezione Risultati -->
    <section id="immobili-results">
        <div class="container">
            <?php if ($result->num_rows > 0): ?>
                <h2><?php echo $result->num_rows; ?> immobili trovati</h2>
                <div class="immobili-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php $is_preferito = isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente' && in_array($row['id'], $preferiti); ?>
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
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente'): ?>
                                        <?php if($is_preferito): ?>
                                            <a href="remove_preferito.php?id=<?php echo $row['id']; ?>" 
                                               class="btn-favorite active" 
                                               title="Rimuovi dai preferiti">
                                                <i class="fas fa-heart"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="add_preferito.php?id=<?php echo $row['id']; ?>" 
                                               class="btn-favorite" 
                                               title="Aggiungi ai preferiti">
                                                <i class="far fa-heart"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Nessun immobile trovato</h3>
                    <p>Prova a modificare i filtri di ricerca per trovare l'immobile che fa per te.</p>
                    <a href="immobili.php" class="reset-btn"><i class="fas fa-undo"></i> Reset Filtri</a>
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
$conn->close(); // Chiudi la connessione
?>