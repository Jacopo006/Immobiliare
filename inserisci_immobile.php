<?php
session_start();
include 'config.php';

// Debug per vedere le variabili di sessione
error_log("Form Inserisci Immobile - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non impostato'));
error_log("Form Inserisci Immobile - user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'non impostato'));

// Verifica che l'utente sia un agente immobiliare
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'agente') {
    error_log("Accesso non autorizzato a form_inserimento_immobile - reindirizzamento al login");
    header('Location: login_agente.php');
    exit();
}

// Inizializzazione della variabile messaggio
$messaggio = '';
$tipo_messaggio = '';
$immobile_inserito = null;

// Gestione del form sottomesso
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupero dei dati dal form
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $descrizione = mysqli_real_escape_string($conn, $_POST['descrizione']);
    $prezzo = floatval($_POST['prezzo']);
    $categoria_id = intval($_POST['categoria_id']);
    $stato = mysqli_real_escape_string($conn, $_POST['stato']);
    $metri_quadri = intval($_POST['metri_quadri']);
    $stanze = intval($_POST['stanze']);
    $bagni = intval($_POST['bagni']);
    $citta = mysqli_real_escape_string($conn, $_POST['citta']);
    $provincia = mysqli_real_escape_string($conn, strtoupper($_POST['provincia']));
    $latitudine = floatval($_POST['latitudine']);
    $longitudine = floatval($_POST['longitudine']);
    $agente_id = $_SESSION['user_id'];

    // Gestione dell'upload dell'immagine
    $nome_file_immagine = '';
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] == 0) {
        $estensioni_permesse = ['jpg', 'jpeg', 'png', 'gif'];
        $nome_file = $_FILES['immagine']['name'];
        $tmp_name = $_FILES['immagine']['tmp_name'];
        $dimensione_file = $_FILES['immagine']['size'];
        $estensione = strtolower(pathinfo($nome_file, PATHINFO_EXTENSION));

        // Controllo estensione e dimensione
        if (in_array($estensione, $estensioni_permesse) && $dimensione_file <= 5242880) { // 5MB
            // Genera un nome univoco per il file
            $nuovo_nome_file = uniqid() . '.' . $estensione;
            $destinazione = 'img/immobili/' . $nuovo_nome_file;

            // Verifico se la directory esiste, altrimenti la creo
            if (!file_exists('img/immobili/')) {
                mkdir('img/immobili/', 0777, true);
            }

            // Sposto il file caricato nella destinazione finale
            if (move_uploaded_file($tmp_name, $destinazione)) {
                $nome_file_immagine = $nuovo_nome_file;
            } else {
                $messaggio = "Errore durante il caricamento dell'immagine.";
                $tipo_messaggio = "error";
            }
        } else {
            $messaggio = "Formato file non supportato o dimensione troppo grande (max 5MB).";
            $tipo_messaggio = "error";
        }
    } else {
        $messaggio = "Errore nel caricamento dell'immagine.";
        $tipo_messaggio = "error";
    }

    // Se non ci sono errori, procedo con l'inserimento nel database
    if (empty($messaggio)) {
        $sql = "INSERT INTO immobili (nome, descrizione, prezzo, immagine, categoria_id, agente_id, stato, metri_quadri, stanze, bagni, citta, provincia, latitudine, longitudine) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsiisissssdd", $nome, $descrizione, $prezzo, $nome_file_immagine, $categoria_id, $agente_id, $stato, $metri_quadri, $stanze, $bagni, $citta, $provincia, $latitudine, $longitudine);
        
        if ($stmt->execute()) {
            $immobile_id = $conn->insert_id;
            $messaggio = "Immobile inserito con successo!";
            $tipo_messaggio = "success";
            
            // Recupero i dettagli dell'immobile appena inserito per visualizzarli
            $sql_immobile = "SELECT i.*, c.nome AS categoria_nome FROM immobili i 
                             LEFT JOIN categorie c ON i.categoria_id = c.id 
                             WHERE i.id = ?";
            $stmt_immobile = $conn->prepare($sql_immobile);
            $stmt_immobile->bind_param("i", $immobile_id);
            $stmt_immobile->execute();
            $result_immobile = $stmt_immobile->get_result();
            $immobile_inserito = $result_immobile->fetch_assoc();
        } else {
            $messaggio = "Errore durante l'inserimento dell'immobile: " . $stmt->error;
            $tipo_messaggio = "error";
        }
    }
}

// Query per recuperare le categorie di immobili
$categorie = [];
$sql = "SELECT id, nome FROM categorie ORDER BY nome";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categorie[] = $row;
    }
}

// Definisci i valori predefiniti
$latitudine_default = 45.4642;  // Milano come posizione predefinita
$longitudine_default = 9.1900;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Immobile - Area Agenti</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard_agente.css">
    <link rel="stylesheet" href="inserisci.css">

    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 25px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #3498db;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 0;
        }
        
        .form-column {
            flex: 1;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .required-field::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-note {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        .form-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 30px;
        }
        
        /* Stile per il pulsante di upload file */
        .file-upload {
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: pointer;
            display: block;
        }
        
        .file-upload-label {
            display: inline-block;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        
        .file-upload-label:hover {
            background-color: #2980b9;
        }
        
        .file-name {
            display: inline-block;
            margin-left: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Coordinate */
        .coordinates-container {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .coordinates-title {
            font-weight: 600;
            color: #3498db;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        #map {
            height: 300px;
            width: 100%;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        /* Messaggio di avviso */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 5px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Dettagli immobile inserito */
        .immobile-inserito {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .immobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .immobile-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #3498db;
        }
        
        .immobile-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2ecc71;
        }
        
        .immobile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .immobile-detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .immobile-detail-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .immobile-detail-value {
            font-weight: 500;
        }
        
        .immobile-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
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
            <h1>Inserimento Immobile</h1>
            <p>Area riservata agli agenti immobiliari</p>
        </div>
    </section>
    
    <!-- Contenuto Principale -->
    <div class="container">
        <?php if (!empty($messaggio)): ?>
        <div class="alert alert-<?php echo $tipo_messaggio === 'success' ? 'success' : 'error'; ?>">
            <?php echo $messaggio; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($immobile_inserito): ?>
        <!-- Visualizzazione dei dettagli dell'immobile inserito -->
        <div class="immobile-inserito">
            <div class="immobile-header">
                <div class="immobile-title">
                    <i class="fas fa-check-circle"></i> Immobile inserito con successo
                </div>
                <div class="immobile-price">
                    € <?php echo number_format($immobile_inserito['prezzo'], 2, ',', '.'); ?>
                </div>
            </div>
            
            <div class="immobile-details">
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Nome</div>
                    <div class="immobile-detail-value"><?php echo htmlspecialchars($immobile_inserito['nome']); ?></div>
                </div>
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Categoria</div>
                    <div class="immobile-detail-value"><?php echo htmlspecialchars($immobile_inserito['categoria_nome']); ?></div>
                </div>
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Città</div>
                    <div class="immobile-detail-value"><?php echo htmlspecialchars($immobile_inserito['citta']); ?> (<?php echo htmlspecialchars($immobile_inserito['provincia']); ?>)</div>
                </div>
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Superficie</div>
                    <div class="immobile-detail-value"><?php echo $immobile_inserito['metri_quadri']; ?> m²</div>
                </div>
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Stanze</div>
                    <div class="immobile-detail-value"><?php echo $immobile_inserito['stanze']; ?></div>
                </div>
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Bagni</div>
                    <div class="immobile-detail-value"><?php echo $immobile_inserito['bagni']; ?></div>
                </div>
                <div class="immobile-detail-item">
                    <div class="immobile-detail-label">Stato</div>
                    <div class="immobile-detail-value"><?php echo ucfirst($immobile_inserito['stato']); ?></div>
                </div>
            </div>
            
            <div class="immobile-actions">
                <a href="dashboard_agente.php" class="btn-primary">
                    <i class="fas fa-arrow-left"></i> Torna alla dashboard
                </a>
                <a href="inserisci_immobile.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Inserisci un altro immobile
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="form-container">
            <div class="form-title">Inserisci un nuovo immobile</div>
            <p class="form-text">Completa tutti i campi contrassegnati con * per inserire un nuovo immobile nel database.</p>
            
            <form action="inserisci_immobile.php" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="nome" class="form-label required-field">Nome immobile</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="categoria_id" class="form-label required-field">Categoria</label>
                            <select id="categoria_id" name="categoria_id" class="form-control" required>
                                <option value="">Seleziona categoria</option>
                                <?php foreach($categorie as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descrizione" class="form-label required-field">Descrizione</label>
                    <textarea id="descrizione" name="descrizione" class="form-control" rows="5" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="prezzo" class="form-label required-field">Prezzo (€)</label>
                            <input type="number" id="prezzo" name="prezzo" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="stato" class="form-label required-field">Stato</label>
                            <select id="stato" name="stato" class="form-control" required>
                                <option value="disponibile">Disponibile</option>
                                <option value="venduto">Venduto</option>
                                <option value="affittato">Affittato</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="metri_quadri" class="form-label required-field">Metri quadri</label>
                            <input type="number" id="metri_quadri" name="metri_quadri" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="stanze" class="form-label required-field">Numero stanze</label>
                            <input type="number" id="stanze" name="stanze" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="bagni" class="form-label required-field">Numero bagni</label>
                            <input type="number" id="bagni" name="bagni" class="form-control" min="1" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="citta" class="form-label required-field">Città</label>
                            <input type="text" id="citta" name="citta" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="provincia" class="form-label required-field">Provincia</label>
                            <input type="text" id="provincia" name="provincia" class="form-control" maxlength="2" required>
                            <p class="form-note">Inserire sigla della provincia (es. MI, RM, NA)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione per le coordinate geografiche -->
                <div class="coordinates-container">
                    <div class="coordinates-title">
                        <i class="fas fa-map-marker-alt"></i> Posizione geografica
                    </div>
                    <div id="map"></div>
                    <div class="form-row">
                        <div class="form-column">
                            <div class="form-group">
                                <label for="latitudine" class="form-label">Latitudine</label>
                                <input type="text" id="latitudine" name="latitudine" class="form-control" step="0.00000001" value="<?php echo $latitudine_default; ?>">
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="form-group">
                                <label for="longitudine" class="form-label">Longitudine</label>
                                <input type="text" id="longitudine" name="longitudine" class="form-control" step="0.00000001" value="<?php echo $longitudine_default; ?>">
                            </div>
                        </div>
                    </div>
                    <p class="form-note">Clicca sulla mappa per selezionare la posizione dell'immobile o inserisci manualmente i valori</p>
                </div>
                
                <div class="form-group">
                    <label for="immagine" class="form-label required-field">Immagine principale</label>
                    <div class="file-upload">
                        <label for="immagine" class="file-upload-label">
                            <i class="fas fa-upload"></i> Seleziona file
                        </label>
                        <input type="file" id="immagine" name="immagine" accept="image/*" required>
                        <span class="file-name" id="file-name">Nessun file selezionato</span>
                    </div>
                    <p class="form-note">Formati supportati: JPG, JPEG, PNG, GIF - Max 5MB</p>
                </div>
                
                <div class="form-group" style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Salva immobile
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section about">
                <h3>Immobiliare XYZ</h3>
                <p>La tua agenzia immobiliare di fiducia. Trova la casa dei tuoi sogni con noi!</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-section links">
                <h3>Link Utili</h3>
                <ul>
                    <li><a href="home-page.php">Home</a></li>
                    <li><a href="immobili.php">Immobili</a></li>
                    <li><a href="contatti.php">Contatti</a></li>
                    <li><a href="privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contattaci</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-phone"></i> +39 02 1234567</p>
                <p><i class="fas fa-envelope"></i> info@immobiliarexyz.it</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Immobiliare XYZ. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <!-- Inclusione di Leaflet.js per la mappa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <script>
        // Gestione del menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            var userMenu = document.querySelector('.user-menu');
            if (userMenu) {
                userMenu.addEventListener('click', function(e) {
                    this.classList.toggle('active');
                    e.stopPropagation();
                });
            }
            
            // Chiudi il menu quando si fa clic all'esterno
            document.addEventListener('click', function() {
                if (userMenu) {
                    userMenu.classList.remove('active');
                }
            });
            
            // Gestione dell'upload dell'immagine
            document.getElementById('immagine').addEventListener('change', function() {
                var fileName = this.files[0]?.name || 'Nessun file selezionato';
                document.getElementById('file-name').textContent = fileName;
            });
            
            // Inizializzazione della mappa
            var defaultLat = parseFloat(document.getElementById('latitudine').value) || 45.4668;
            var defaultLng = parseFloat(document.getElementById('longitudine').value) || 9.1905;
            
            var map = L.map('map').setView([defaultLat, defaultLng], 12); // Inizializza con valori predefiniti o Milano
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Crea marker iniziale se ci sono coordinate predefinite
            var marker;
            if (defaultLat && defaultLng) {marker = L.marker([defaultLat, defaultLng]).addTo(map);
            }
            
            // Aggiorna coordinate quando si clicca sulla mappa
            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                
                // Aggiorna i campi input
                document.getElementById('latitudine').value = lat.toFixed(8);
                document.getElementById('longitudine').value = lng.toFixed(8);
                
                // Aggiorna o crea marker
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng]).addTo(map);
                }
            });
            
            // Aggiorna marker quando vengono modificati manualmente i campi input
            document.getElementById('latitudine').addEventListener('change', updateMarkerFromInputs);
            document.getElementById('longitudine').addEventListener('change', updateMarkerFromInputs);
            
            function updateMarkerFromInputs() {
                var lat = parseFloat(document.getElementById('latitudine').value);
                var lng = parseFloat(document.getElementById('longitudine').value);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng]).addTo(map);
                    }
                    map.setView([lat, lng], 12);
                }
            }
            
            // Geocodifica l'indirizzo quando vengono modificati i campi città e provincia
            document.getElementById('citta').addEventListener('change', geocodeAddress);
            document.getElementById('provincia').addEventListener('change', geocodeAddress);
            
            function geocodeAddress() {
                var citta = document.getElementById('citta').value;
                var provincia = document.getElementById('provincia').value;
                
                if (citta && provincia) {
                    var indirizzo = citta + ", " + provincia + ", Italia";
                    
                    // Utilizzo del servizio di geocodifica Nominatim
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(indirizzo)}&limit=1`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                var lat = parseFloat(data[0].lat);
                                var lon = parseFloat(data[0].lon);
                                
                                document.getElementById('latitudine').value = lat.toFixed(8);
                                document.getElementById('longitudine').value = lon.toFixed(8);
                                
                                if (marker) {
                                    marker.setLatLng([lat, lon]);
                                } else {
                                    marker = L.marker([lat, lon]).addTo(map);
                                }
                                
                                map.setView([lat, lon], 13);
                            }
                        })
                        .catch(error => console.error('Errore nella geocodifica:', error));
                }
            }
        });
    </script>
</body>
</html>
                