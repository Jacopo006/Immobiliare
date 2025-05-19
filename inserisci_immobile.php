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
    <link rel="stylesheet" href="inserisci.css.css">

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
            if (defaultLat && defaultLng) {
                marker = L.marker([defaultLat, defaultLng]).addTo(map);
            } dell'upload dell'immagine
            document.getElementById('immagine').addEventListener('change', function() {
                var fileName = this.files[0]?.name || 'Nessun file selezionato';
                document.getElementById('file-name').textContent = fileName;
            });
            
            // Inizializzazione della mappa
            var map = L.map('map').setView([45.4668, 9.1905], 6); // Centrata sull'Italia
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            var marker;
            
            // Funzione per aggiungere o spostare il marker
            function placeMarker(e) {
                var lat = e.latlng.lat.toFixed(8);
                var lng = e.latlng.lng.toFixed(8);
                
                // Aggiorna i campi del form
                document.getElementById('latitudine').value = lat;
                document.getElementById('longitudine').value = lng;
                
                // Aggiunge o sposta il marker
                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng).addTo(map);
                }
            }
            
            // Gestisci il click sulla mappa
            map.on('click', placeMarker);
            
            // Aggiorna la mappa quando vengono inseriti valori nei campi
            function updateMapFromInputs() {
                var lat = parseFloat(document.getElementById('latitudine').value);
                var lng = parseFloat(document.getElementById('longitudine').value);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    var newLatLng = L.latLng(lat, lng);
                    
                    if (marker) {
                        marker.setLatLng(newLatLng);
                    } else {
                        marker = L.marker(newLatLng).addTo(map);
                    }
                    
                    map.setView(newLatLng, 13);
                }
            }
            
            // Aggiorna la mappa quando i campi vengono modificati
            document.getElementById('latitudine').addEventListener('change', updateMapFromInputs);
            document.getElementById('longitudine').addEventListener('change', updateMapFromInputs);
            
            // Opzionale: geocoding inverso quando si seleziona una città
            document.getElementById('citta').addEventListener('blur', function() {
                var city = this.value.trim();
                if (city) {
                    // Utilizza Nominatim per trovare le coordinate della città
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                var lat = parseFloat(data[0].lat);
                                var lon = parseFloat(data[0].lon);
                                
                                map.setView([lat, lon], 13);
                                
                                // Non aggiornare automaticamente i campi lat/lon, lasciamo che l'utente clicchi sulla posizione esatta
                            }
                        })
                        .catch(error => console.error('Errore nel geocoding:', error));
                }
            });
        });
    </script>
</body>
</html>