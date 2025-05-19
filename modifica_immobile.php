<?php
session_start();
include 'config.php';

// Verifica che l'utente sia un agente immobiliare
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'agente') {
    header('Location: login_agente.php');
    exit();
}

$msg = '';
$error = '';
$id_agente = $_SESSION['user_id'];

// Verifica che sia stato fornito un ID immobile
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard_agente.php');
    exit();
}

$id_immobile = $_GET['id'];

// Verifica che l'immobile appartenga all'agente
$check_query = "SELECT * FROM immobili WHERE id = $id_immobile AND agente_id = $id_agente";
$check_result = $conn->query($check_query);

if ($check_result->num_rows == 0) {
    header('Location: dashboard_agente.php?error=unauthorized');
    exit();
}

// Recupera i dati dell'immobile
$immobile = $check_result->fetch_assoc();

// Recupera le categorie per il form
$sql_categorie = "SELECT id, nome FROM categorie";
$result_categorie = $conn->query($sql_categorie);
$categorie = [];
while ($cat = $result_categorie->fetch_assoc()) {
    $categorie[] = $cat;
}

// Gestione dell'eliminazione dell'immobile
if (isset($_POST['action']) && $_POST['action'] == 'elimina') {
    // Prima controlla se ci sono transazioni o preferiti legati a questo immobile
    $check_transazioni = "SELECT COUNT(*) as num FROM transazioni WHERE id_immobile = $id_immobile";
    $result_transazioni = $conn->query($check_transazioni);
    $transazioni = $result_transazioni->fetch_assoc();
    
    $check_preferiti = "SELECT COUNT(*) as num FROM preferiti WHERE id_immobile = $id_immobile";
    $result_preferiti = $conn->query($check_preferiti);
    $preferiti = $result_preferiti->fetch_assoc();
    
    if ($transazioni['num'] > 0) {
        $error = "Impossibile eliminare l'immobile perché esistono delle transazioni associate.";
    } else {
        // Elimina prima i preferiti associati
        $delete_preferiti = "DELETE FROM preferiti WHERE id_immobile = $id_immobile";
        $conn->query($delete_preferiti);
        
        // Poi elimina l'immobile
        $delete_query = "DELETE FROM immobili WHERE id = $id_immobile AND agente_id = $id_agente";
        if ($conn->query($delete_query) === TRUE) {
            // Redirect alla dashboard con messaggio di successo
            header('Location: dashboard_agente.php?msg=eliminato');
            exit();
        } else {
            $error = "Errore durante l'eliminazione: " . $conn->error;
        }
    }
}

// Gestione del cambio stato dell'immobile
if (isset($_POST['action']) && $_POST['action'] == 'cambio_stato') {
    $nuovo_stato = $conn->real_escape_string($_POST['nuovo_stato']);
    $update_stato_query = "UPDATE immobili SET stato = '$nuovo_stato' WHERE id = $id_immobile AND agente_id = $id_agente";
    
    if ($conn->query($update_stato_query) === TRUE) {
        $msg = "Stato dell'immobile aggiornato con successo!";
        // Aggiorna i dati dell'immobile nella variabile
        $check_result = $conn->query($check_query);
        $immobile = $check_result->fetch_assoc();
    } else {
        $error = "Errore durante l'aggiornamento dello stato: " . $conn->error;
    }
}

// Gestione del form di modifica
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    // Recupera i dati dal form
    $nome = $conn->real_escape_string($_POST['nome']);
    $categoria_id = intval($_POST['categoria_id']);
    $prezzo = intval($_POST['prezzo']);
    $stato = $conn->real_escape_string($_POST['stato']);
    $metri_quadri = intval($_POST['metri_quadri']);
    $stanze = intval($_POST['stanze']);
    $bagni = intval($_POST['bagni']);
    $citta = $conn->real_escape_string($_POST['citta']);
    $provincia = $conn->real_escape_string($_POST['provincia']);
    $descrizione = $conn->real_escape_string($_POST['descrizione']);
    
    // Prepara la query di aggiornamento
    $update_query = "UPDATE immobili SET 
                    nome = '$nome',
                    categoria_id = $categoria_id,
                    prezzo = $prezzo,
                    stato = '$stato',
                    metri_quadri = $metri_quadri,
                    stanze = $stanze,
                    bagni = $bagni,
                    citta = '$citta',
                    provincia = '$provincia',
                    descrizione = '$descrizione'";
    
    // Gestione upload nuova immagine
    if (isset($_FILES['immagine']) && $_FILES['immagine']['size'] > 0) {
        $target_dir = "uploads/immobili/";
        $file_extension = strtolower(pathinfo($_FILES["immagine"]["name"], PATHINFO_EXTENSION));
        $new_filename = "immobile_" . $id_immobile . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Controlla estensione
        $allowed_extensions = array("jpg", "jpeg", "png", "gif");
        if (!in_array($file_extension, $allowed_extensions)) {
            $error = "Solo file JPG, JPEG, PNG e GIF sono consentiti.";
        } 
        // Controlla dimensione (max 5MB)
        else if ($_FILES["immagine"]["size"] > 5000000) {
            $error = "Il file è troppo grande. La dimensione massima è 5MB.";
        } 
        // Se tutto è ok, carica il file
        else if (move_uploaded_file($_FILES["immagine"]["tmp_name"], $target_file)) {
            // Aggiorna il campo immagine nella query
            $update_query .= ", immagine = '$new_filename'";
        } else {
            $error = "Si è verificato un errore durante il caricamento dell'immagine.";
        }
    }
    
    // Completa la query con la condizione WHERE
    $update_query .= " WHERE id = $id_immobile AND agente_id = $id_agente";
    
    // Esegui la query se non ci sono errori
    if (empty($error)) {
        if ($conn->query($update_query) === TRUE) {
            $msg = "Immobile aggiornato con successo!";
            // Aggiorna i dati dell'immobile nella variabile
            $check_result = $conn->query($check_query);
            $immobile = $check_result->fetch_assoc();
        } else {
            $error = "Errore durante l'aggiornamento: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Immobile - Area Agenti</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="modifica.css">
    <style>
        /* Stili aggiuntivi per la gestione stato e eliminazione */
        .card-actions {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        .card-actions h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.2rem;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background-color: #d35400;
        }
        .state-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }
        .state-form select {
            flex: 1;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #e74c3c;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-disponibile {
            background-color: #2ecc71;
            color: white;
        }
        .status-venduto {
            background-color: #e74c3c;
            color: white;
        }
        .status-affittato {
            background-color: #3498db;
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
                <li><a href="immobili.php"><i class="fas fa-building"></i> Immobili</a></li>
                <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                <li class="user-menu">
                    <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="dashboard_agente.php" class="active"><i class="fas fa-cogs"></i> Gestione Immobili</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Banner Principale -->
    <section id="banner" class="banner-small">
        <div class="banner-content">
            <h1>Gestione Immobile</h1>
            <p>Area riservata agli agenti immobiliari</p>
        </div>
    </section>

    <!-- Contenuto Principale -->
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard_agente.php"><i class="fas fa-home"></i> Dashboard</a> &gt; 
            <span>Gestione Immobile</span>
        </div>
        
        <?php if($msg): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
        </div>
        <?php endif; ?>
        
        <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Card per gestire lo stato e l'eliminazione -->
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-tasks"></i> Gestione Immobile: 
                    <?php echo htmlspecialchars($immobile['nome']); ?>
                    <span class="status-badge status-<?php echo $immobile['stato']; ?>">
                        <?php 
                        switch($immobile['stato']) {
                            case 'disponibile':
                                echo 'Disponibile';
                                break;
                            case 'venduto':
                                echo 'Venduto';
                                break;
                            case 'affittato':
                                echo 'Affittato';
                                break;
                        }
                        ?>
                    </span>
                </h2>
                <a href="dashboard_agente.php#miei-immobili" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Torna alla Lista</a>
            </div>
            
            <div class="card-body">
                <!-- Sezione cambio stato -->
                <div class="card-actions">
                    <h3><i class="fas fa-exchange-alt"></i> Cambia Stato dell'Immobile</h3>
                    <p>Lo stato attuale dell'immobile è: <strong><?php echo ucfirst($immobile['stato']); ?></strong></p>
                    
                    <form action="modifica_immobile.php?id=<?php echo $id_immobile; ?>" method="POST" class="state-form">
                        <input type="hidden" name="action" value="cambio_stato">
                        <select name="nuovo_stato" class="form-control">
                            <option value="disponibile" <?php if($immobile['stato'] == 'disponibile') echo 'selected'; ?>>Disponibile</option>
                            <option value="venduto" <?php if($immobile['stato'] == 'venduto') echo 'selected'; ?>>Venduto</option>
                            <option value="affittato" <?php if($immobile['stato'] == 'affittato') echo 'selected'; ?>>Affittato</option>
                        </select>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Aggiorna Stato</button>
                    </form>
                </div>
                
                <!-- Sezione eliminazione -->
                <div class="card-actions">
                    <h3><i class="fas fa-trash-alt"></i> Elimina Immobile</h3>
                    <p>Attenzione: questa operazione non può essere annullata. L'immobile verrà rimosso permanentemente dal database.</p>
                    
                    <div class="action-buttons">
                        <button id="btnElimina" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Elimina Immobile</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal di conferma eliminazione -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3><i class="fas fa-exclamation-triangle"></i> Conferma eliminazione</h3>
                <p>Sei sicuro di voler eliminare l'immobile "<?php echo htmlspecialchars($immobile['nome']); ?>"?</p>
                <p>Questa operazione è irreversibile.</p>
                
                <div class="modal-buttons">
                    <button id="btnAnnulla" class="btn btn-secondary"><i class="fas fa-times"></i> Annulla</button>
                    <form action="modifica_immobile.php?id=<?php echo $id_immobile; ?>" method="POST">
                        <input type="hidden" name="action" value="elimina">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Conferma Eliminazione</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card per modificare i dettagli dell'immobile -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-edit"></i> Modifica Dettagli Immobile</h2>
            </div>
            
            <div class="card-body">
                <form action="modifica_immobile.php?id=<?php echo $id_immobile; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nome"><i class="fas fa-tag"></i> Nome Immobile <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($immobile['nome']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="categoria"><i class="fas fa-list"></i> Categoria <span class="required">*</span></label>
                            <select id="categoria" name="categoria_id" required>
                                <?php foreach($categorie as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" <?php if($categoria['id'] == $immobile['categoria_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="prezzo"><i class="fas fa-euro-sign"></i> Prezzo <span class="required">*</span></label>
                            <input type="number" id="prezzo" name="prezzo" min="0" step="1000" value="<?php echo $immobile['prezzo']; ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="stato"><i class="fas fa-info-circle"></i> Stato <span class="required">*</span></label>
                            <select id="stato" name="stato" required>
                                <option value="disponibile" <?php if($immobile['stato'] == 'disponibile') echo 'selected'; ?>>Disponibile</option>
                                <option value="venduto" <?php if($immobile['stato'] == 'venduto') echo 'selected'; ?>>Venduto</option>
                                <option value="affittato" <?php if($immobile['stato'] == 'affittato') echo 'selected'; ?>>Affittato</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="metri_quadri"><i class="fas fa-vector-square"></i> Metri Quadri <span class="required">*</span></label>
                            <input type="number" id="metri_quadri" name="metri_quadri" min="1" value="<?php echo $immobile['metri_quadri']; ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="stanze"><i class="fas fa-door-open"></i> Stanze <span class="required">*</span></label>
                            <input type="number" id="stanze" name="stanze" min="1" value="<?php echo $immobile['stanze']; ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="bagni"><i class="fas fa-bath"></i> Bagni <span class="required">*</span></label>
                            <input type="number" id="bagni" name="bagni" min="1" value="<?php echo $immobile['bagni']; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="citta"><i class="fas fa-map-marker-alt"></i> Città <span class="required">*</span></label>
                            <input type="text" id="citta" name="citta" value="<?php echo htmlspecialchars($immobile['citta']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="provincia"><i class="fas fa-map"></i> Provincia <span class="required">*</span></label>
                            <input type="text" id="provincia" name="provincia" value="<?php echo htmlspecialchars($immobile['provincia']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descrizione"><i class="fas fa-file-alt"></i> Descrizione <span class="required">*</span></label>
                        <textarea id="descrizione" name="descrizione" rows="5" required><?php echo htmlspecialchars($immobile['descrizione']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="immagine"><i class="fas fa-image"></i> Immagine Principale</label>
                        <?php if(!empty($immobile['immagine'])): ?>
                            <div class="current-image">
                                <img src="uploads/immobili/<?php echo $immobile['immagine']; ?>" alt="<?php echo htmlspecialchars($immobile['nome']); ?>" class="thumbnail">
                                <p>Immagine attuale: <?php echo $immobile['immagine']; ?></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="immagine" name="immagine" accept="image/*">
                        <small>Lascia vuoto per mantenere l'immagine attuale. Dimensione massima: 5MB. Formati supportati: JPG, PNG, GIF</small>
                    </div>

                    <div class="form-actions">
                        <a href="dashboard_agente.php#miei-immobili" class="btn btn-secondary"><i class="fas fa-times"></i> Annulla</a>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Aggiorna Immobile</button>
                    </div>
                </form>
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
        // Anteprima immagine quando viene selezionata
        document.getElementById('immagine').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Rimuovi l'anteprima precedente se esiste
                    const existingPreview = document.querySelector('.image-preview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Crea un nuovo elemento per l'anteprima
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.innerHTML = `
                        <h4>Anteprima nuova immagine:</h4>
                        <img src="${event.target.result}" alt="Anteprima" class="thumbnail">
                    `;
                    
                    // Inserisci l'anteprima dopo il campo di input
                    const inputContainer = document.getElementById('immagine').parentNode;
                    inputContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });

        // Gestione del modal per la conferma dell'eliminazione
        const modal = document.getElementById("deleteModal");
        const btnElimina = document.getElementById("btnElimina");
        const btnAnnulla = document.getElementById("btnAnnulla");
        const span = document.getElementsByClassName("close")[0];

        // Apri il modal quando si clicca sul pulsante elimina
        btnElimina.onclick = function() {
            modal.style.display = "block";
        }

        // Chiudi il modal quando si clicca sulla X
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Chiudi il modal quando si clicca su Annulla
        btnAnnulla.onclick = function() {
            modal.style.display = "none";
        }

        // Chiudi il modal quando si clicca al di fuori di esso
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php
$conn->close(); // Chiudi la connessione
?>