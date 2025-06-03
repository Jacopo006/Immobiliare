<?php
session_start();

// Includi il file di configurazione
include 'config.php';

// Verifica se l'ID dell'immobile è stato fornito
if (!isset($_GET['id']) || empty($_GET['id'])) {
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
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>
    
    <!-- A-Frame VR Library -->
    <script src="https://aframe.io/releases/1.4.0/aframe.min.js"></script>
    
    <style>
       /* Stili di base ereditati dalla home page */
body {
    font-family: 'Montserrat', 'Roboto', sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
    color: #333;
    line-height: 1.6;
}

/* Mappa container con stile coerente */
#map-container {
    height: 400px;
    width: 100%;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    background: white;
}

.address {
    margin-top: 10px;
    font-size: 14px;
    color: #7f8c8d;
    text-align: center;
}

/* Sezione Tour Virtuale Unificato - Design coerente con home page */
.virtual-tour-section {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.virtual-tour-section:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.virtual-tour-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    flex-wrap: wrap;
    border-bottom: 3px solid #3498db;
    padding-bottom: 20px;
}

.virtual-tour-header h2 {
    display: flex;
    align-items: center;
    gap: 15px;
    color: #2c3e50;
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.tour-badge {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 3px 10px rgba(52, 152, 219, 0.3);
    transition: transform 0.2s ease;
}

.tour-badge:hover {
    transform: translateY(-2px);
}

.virtual-tour-container {
    position: relative;
    width: 100%;
    height: 600px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    background: #2c3e50;
    border: 2px solid #ecf0f1;
}

.tour-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    display: flex;
    gap: 10px;
    z-index: 1000;
    flex-wrap: wrap;
}

.tour-btn {
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid #3498db;
    padding: 12px 20px;
    border-radius: 30px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    color: #2c3e50;
    backdrop-filter: blur(10px);
    font-family: 'Montserrat', sans-serif;
}

.tour-btn:hover {
    background: #3498db;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

.tour-btn.active {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    border-color: #2980b9;
}

.tour-btn.fullscreen {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    border-color: #c0392b;
}

.tour-btn.fullscreen:hover {
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
}

.room-navigation {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 15px;
    z-index: 1000;
    flex-wrap: wrap;
    justify-content: center;
}

.room-btn {
    background: rgba(44, 62, 80, 0.8);
    color: white;
    border: 2px solid rgba(52, 152, 219, 0.5);
    padding: 12px 20px;
    border-radius: 30px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    font-family: 'Montserrat', sans-serif;
}

.room-btn:hover {
    background: rgba(52, 152, 219, 0.9);
    border-color: #3498db;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

.room-btn.active {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border-color: white;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

.tour-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 25px;
    padding: 25px;
    background: linear-gradient(135deg, #ecf0f1 0%, #d5dbdb 100%);
    border-radius: 8px;
    flex-wrap: wrap;
    gap: 20px;
    border-left: 4px solid #3498db;
}

.tour-features {
    display: flex;
    gap: 30px;
    font-size: 14px;
    color: #2c3e50;
    flex-wrap: wrap;
    font-weight: 500;
}

.tour-feature {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tour-feature i {
    color: #3498db;
    font-size: 16px;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(44, 62, 80, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    border-radius: 8px;
}

.loading-content {
    text-align: center;
    color: white;
    font-family: 'Montserrat', sans-serif;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.vr-scene {
    width: 100%;
    height: 100%;
    border-radius: 8px;
}

.instructions {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(44, 62, 80, 0.9);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    font-size: 14px;
    max-width: 280px;
    z-index: 999;
    backdrop-filter: blur(10px);
    border-left: 4px solid #3498db;
    font-family: 'Montserrat', sans-serif;
    line-height: 1.5;
}

.no-tour-message {
    text-align: center;
    padding: 50px;
    color: #7f8c8d;
    background: linear-gradient(135deg, #ecf0f1 0%, #d5dbdb 100%);
    border-radius: 8px;
    border: 2px dashed #bdc3c7;
    font-family: 'Montserrat', sans-serif;
}

.no-tour-message i {
    font-size: 64px;
    color: #bdc3c7;
    margin-bottom: 20px;
    display: block;
}

.no-tour-message h3 {
    color: #2c3e50;
    font-size: 24px;
    margin-bottom: 15px;
}

.request-tour-btn {
    margin-top: 20px;
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
    box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3);
}

.request-tour-btn:hover {
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
}

/* Responsive Design - Mobile First */
@media screen and (max-width: 768px) {
    .virtual-tour-section {
        padding: 20px;
        margin: 15px;
    }
    
    .virtual-tour-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .virtual-tour-header h2 {
        font-size: 24px;
    }
    
    .virtual-tour-container {
        height: 400px;
    }
    
    .tour-controls {
        top: 15px;
        right: 15px;
        flex-direction: column;
    }
    
    .tour-btn {
        padding: 10px 15px;
        font-size: 12px;
        border-radius: 20px;
    }
    
    .room-navigation {
        bottom: 15px;
        left: 15px;
        right: 15px;
        transform: none;
        justify-content: center;
    }
    
    .room-btn {
        padding: 10px 15px;
        font-size: 12px;
        border-radius: 20px;
    }
    
    .tour-info {
        flex-direction: column;
        text-align: center;
    }
    
    .tour-features {
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    
    .instructions {
        display: none;
    }
    
    .no-tour-message {
        padding: 30px 20px;
    }
    
    .no-tour-message i {
        font-size: 48px;
    }
    
    .no-tour-message h3 {
        font-size: 20px;
    }
}

@media screen and (max-width: 480px) {
    .virtual-tour-header h2 {
        font-size: 20px;
    }
    
    .virtual-tour-container {
        height: 300px;
    }
    
    .tour-controls {
        gap: 5px;
    }
    
    .room-navigation {
        gap: 8px;
    }
    
    .tour-btn, .room-btn {
        padding: 8px 12px;
        font-size: 11px;
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
                    <div class="action-buttons">
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
                       
                        <?php if($immobile['stato'] == 'disponibile'): ?>
                            <a href="<?php echo isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente' ? 'acquista.php?id=' . $immobile['id'] : 'login_utente.php?redirect=acquista.php?id=' . $immobile['id']; ?>" class="btn-purchase">
                                <i class="fas fa-shopping-cart"></i> Acquista
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="immobile-content">
                <div class="immobile-gallery">
                    <div class="main-image">
                        <img src="<?php echo $immobile['immagine']; ?>" alt="<?php echo $immobile['nome']; ?>">
                        <div class="categoria-tag"><?php echo $categoria_display; ?></div>
                    </div>
                </div>

                <!-- Sezione Tour Virtuale Unificata -->
                <?php if($immobile['ha_tour_virtuale'] == 1): ?>
                <div class="virtual-tour-section">
                    <div class="virtual-tour-header">
                        <h2>
                            <i class="fas fa-vr-cardboard"></i>
                            Tour Virtuale 360°
                        </h2>
                        <span class="tour-badge">Interattivo</span>
                    </div>
                   
                    <div class="virtual-tour-container" id="tourContainer">
                        <!-- Loading Overlay -->
                        <div class="loading-overlay" id="loadingOverlay">
                            <div class="loading-content">
                                <div class="spinner"></div>
                                <p>Caricamento tour virtuale...</p>
                            </div>
                        </div>
                        
                      
                        <!-- Tour Controls -->
                        <div class="tour-controls">
                            <button class="tour-btn fullscreen" onclick="toggleFullscreen()" title="Schermo intero" id="fullscreenBtn">
                                <i class="fas fa-expand"></i> Fullscreen
                            </button>
                        </div>
                       
                        <!-- Room Navigation -->
                        <div class="room-navigation">
                            <button class="room-btn active" onclick="changeRoom('living')" data-room="living">
                                <i class="fas fa-couch"></i> Soggiorno
                            </button>
                            <button class="room-btn" onclick="changeRoom('kitchen')" data-room="kitchen">
                                <i class="fas fa-utensils"></i> Cucina
                            </button>
                            <button class="room-btn" onclick="changeRoom('bedroom')" data-room="bedroom">
                                <i class="fas fa-bed"></i> Camera
                            </button>
                            <button class="room-btn" onclick="changeRoom('bathroom')" data-room="bathroom">
                                <i class="fas fa-bath"></i> Bagno
                            </button>
                            <button class="room-btn" onclick="changeRoom('balcony')" data-room="balcony">
                                <i class="fas fa-leaf"></i> Balcone
                            </button>
                        </div>
                       
                       <!-- A-Frame VR Scene -->
<a-scene 
    class="vr-scene"
    id="vrScene"
    embedded 
    style="height: 100%; width: 100%;"
    vr-mode-ui="enabled: true"
    device-orientation-permission-ui="enabled: true"
    background="color: #000">
    
    <!-- Assets con immagini alternative -->
    <a-assets>
    <img id="living-sky" src="SALOTTO.jpg">
    <img id="kitchen-sky" src="img/360/kitchen.jpg">
    <img id="bedroom-sky" src="CAMERALETTO.jpg">
    <img id="bathroom-sky" src="BAGNO.jpg">
    <img id="balcony-sky" src="img/360/balcony.jpg">
</a-assets>

    
    <!-- Camera con controlli migliorati -->
    <a-camera 
        id="camera"
        look-controls="pointerLockEnabled: false; touchEnabled: true; magicWindowTrackingEnabled: true"
        wasd-controls="enabled: false"
        position="0 1.6 0"
        rotation="0 0 0">
        
        <!-- Cursore per l'interazione -->
        <a-cursor
            animation__click="property: scale; startEvents: click; from: 0.1 0.1 0.1; to: 1 1 1; dur: 150"
            animation__fusing="property: scale; startEvents: fusing; from: 1 1 1; to: 0.1 0.1 0.1; dur: 1500"
            geometry="primitive: ring; radiusInner: 0.02; radiusOuter: 0.03"
            material="color: #667eea; shader: flat"
            raycaster="objects: .clickable">
        </a-cursor>
    </a-camera>
    
    <!-- Sky iniziale -->
    <a-sky 
        id="panorama" 
        src="#living-sky" 
        rotation="0 -130 0"
        radius="500">
    </a-sky>
    
    <!-- Illuminazione -->
    <a-light type="ambient" color="#404040" intensity="0.6"></a-light>
    <a-light type="point" position="0 5 0" color="#ffffff" intensity="0.4"></a-light>
    
    <!-- Pannello informazioni -->
    <a-plane
        id="infoPanel"
        position="0 2 -3"
        width="3"
        height="1"
        color="#000000"
        opacity="0.8"
        visible="false">
        <a-text
            id="infoText"
            value=""
            position="0 0 0.01"
            align="center"
            color="#ffffff"
            width="6">
        </a-text>
    </a-plane>
</a-scene>
                   
                    <div class="tour-info">
                        <div class="tour-features">
                            <div class="tour-feature">
                                <i class="fas fa-eye"></i>
                                <span>Vista 360° completa</span>
                            </div>
                            <div class="tour-feature">
                                <i class="fas fa-mouse"></i>
                                <span>Navigazione con mouse/touch</span>
                            </div>
                            <div class="tour-feature">
                                <i class="fas fa-vr-cardboard"></i>
                                <span>Compatibile VR</span>
                            </div>
                            <div class="tour-feature">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Sensori smartphone</span>
                            </div>
                            <div class="tour-feature">
                                <i class="fas fa-home"></i>
                                <span>5 stanze esplorabili</span>
                            </div>
                            <div class="tour-feature">
                                <i class="fas fa-info-circle"></i>
                                <span>Punti informativi interattivi</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="virtual-tour-section">
                    <div class="virtual-tour-header">
                        <h2>
                            <i class="fas fa-vr-cardboard"></i>
                            Tour Virtuale 360°
                        </h2>
                    </div>
                   
                    <div class="no-tour-message">
                        <i class="fas fa-vr-cardboard"></i>
                        <h3>Tour virtuale non disponibile</h3>
                        <p>Il tour virtuale per questo immobile non è ancora disponibile.<br>
                        Contatta l'agente per richiederne la creazione o per organizzare una visita.</p>
                        <button class="request-tour-btn" onclick="requestTour()">
                            <i class="fas fa-camera"></i> Richiedi Tour Virtuale
                        </button>
                    </div>
                </div>
                <?php endif; ?>

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

                    <div class="info-section">
                        <h2>Posizione</h2>
                        <div id="map-container"></div>
                        <p class="address">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo $immobile['citta'] . ', ' . $immobile['provincia']; ?>
                        </p>
                    </div>

                    <?php if(!empty($immobile['agente_nome'])): ?>
                    <div class="info-section">
                        <h2>Contatta l'agente</h2>
                        <a href="contatta_agente.php?id=<?php echo $immobile['agente_id']; ?>&immobile=<?php echo $immobile['id']; ?>" class="btn-contact">Contatta l'agente</a>
                    </div>
                    <?php endif; ?>
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
                                    <?php endif; ?>
                                   
                                    <?php if($row['stato'] == 'disponibile'): ?>
                                        <a href="<?php echo isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'utente' ? 'acquista.php?id=' . $row['id'] : 'login_utente.php?redirect=acquista.php?id=' . $row['id']; ?>" class="btn-purchase">
                                            <i class="fas fa-shopping-cart"></i> Acquista
                                        </a>
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

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
   
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Coordinate dell'immobile dal database
            const lat = <?php echo $immobile['latitudine'] ?: 'null'; ?>;
            const lng = <?php echo $immobile['longitudine'] ?: 'null'; ?>;
           
            // Controlla se le coordinate sono disponibili
            if (lat !== null && lng !== null) {
                // Inizializza la mappa
                const map = L.map('map-container').setView([lat, lng], 15);
               
                // Aggiungi il layer di OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
               
                // Aggiungi un marker nella posizione dell'immobile
                const marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup("<strong><?php echo htmlspecialchars($immobile['nome']); ?></strong><br><?php echo htmlspecialchars($immobile['citta'] . ', ' . $immobile['provincia']); ?>").openPopup();
            } else {
                // Se le coordinate non sono disponibili, nascondi il div della mappa
                document.getElementById('map-container').innerHTML = '<div class="no-map-message">La posizione esatta non è disponibile per questo immobile.</div>';
            }
        });

        // Tour virtuale state
        let currentRoom = 'living';
        let autoRotateEnabled = false;
        let autoRotateInterval;
        let scene, camera, sky;
        
        // Room configurations
        const rooms = {
            living: {
                name: 'Soggiorno',
                skyId: '#living-sky',
                rotation: '0 -130 0',
                hotspots: [
                    { position: '3 2 -2', info: 'Ampia vetrata con vista panoramica' },
                    { position: '-3 1.5 -1', info: 'Zona pranzo elegante per 6 persone' }
                ]
            },
            kitchen: {
                name: 'Cucina',
                skyId: '#kitchen-sky',
                rotation: '0 0 0',
                hotspots: [
                    { position: '2 1.5 -2', info: 'Cucina moderna con elettrodomestici di alta qualità' },
                    { position: '-2 1.2 -1.5', info: 'Isola centrale per la preparazione' }
                ]
            },
            bedroom: {
                name: 'Camera da Letto',
                skyId: '#bedroom-sky',
                rotation: '0 90 0',
                hotspots: [
                    { position: '2.5 1.5 -1', info: 'Camera matrimoniale spaziosa e luminosa' },
                    { position: '-2 1.2 -2', info: 'Ampia cabina armadio' }
                ]
            },
            bathroom: {
                name: 'Bagno',
                skyId: '#bathroom-sky',
                rotation: '0 180 0',
                hotspots: [
                    { position: '1.5 1.5 -1.5', info: 'Bagno moderno con vasca e doccia separate' },
                    { position: '-1.5 1.2 -1', info: 'Doppio lavabo in marmo' }
                ]
            },
            balcony: {
                name: 'Balcone',
                skyId: '#balcony-sky',
                rotation: '0 -90 0',
                hotspots: [
                    { position: '2 1.5 -2', info: 'Terrazza con vista mozzafiato sulla città' },
                    { position: '-1 1.2 -2.5', info: 'Spazio perfetto per pranzi all\'aperto' }
                ]
            }
        };
        
        // Initialize tour when scene is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                initializeTour();
            }, 1000);
        });
        
        function initializeTour() {
            scene = document.querySelector('#vrScene');
            camera = document.querySelector('#camera');
            sky = document.querySelector('#panorama');
            
            // Hide loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            // Setup scene loaded event
            if (scene) {
                scene.addEventListener('loaded', () => {
                    console.log('A-Frame scene loaded successfully');
                    updateHotspots();
                });
            }
            
            // Add click handlers for room navigation
            setupRoomNavigation();
        }
        
        function changeRoom(roomId) {
            if (currentRoom === roomId) return;
            
            currentRoom = roomId;
            const room = rooms[roomId];
            
            if (!room) return;
            
            // Update active button
            document.querySelectorAll('.room-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            const activeBtn = document.querySelector(`[data-room="${roomId}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
            
            // Change sky texture
            if (sky) {
                sky.setAttribute('src', room.skyId);
                sky.setAttribute('rotation', room.rotation);
            }
            
            // Update hotspots
            setTimeout(() => {
                updateHotspots();
            }, 500);
            
            // Reset camera position
            if (camera) {
                camera.setAttribute('rotation', '0 0 0');
            }
        }
        
        function updateHotspots() {
            const room = rooms[currentRoom];
            if (!room || !scene) return;
            
            // Remove existing hotspots
            const existingHotspots = scene.querySelectorAll('.hotspot');
            existingHotspots.forEach(hotspot => hotspot.remove());
            
            // Add new hotspots
            room.hotspots.forEach((hotspot, index) => {
                const hotspotElement = document.createElement('a-sphere');
                hotspotElement.setAttribute('class', 'hotspot clickable');
                hotspotElement.setAttribute('position', hotspot.position);
                hotspotElement.setAttribute('radius', '0.1');
                hotspotElement.setAttribute('color', index % 2 === 0 ? '#667eea' : '#e74c3c');
                hotspotElement.setAttribute('opacity', '0.8');
                hotspotElement.setAttribute('animation', 'property: rotation; to: 0 360 0; loop: true; dur: 4000');
                
                hotspotElement.addEventListener('click', () => {
                    showInfo(hotspot.info);
                });
                
                scene.appendChild(hotspotElement);
            });
        }
        
        function showInfo(message) {
            const infoPanel = document.querySelector('#infoPanel');
            const infoText = document.querySelector('#infoText');
            
            if (infoPanel && infoText) {
                infoText.setAttribute('value', message);
                infoPanel.setAttribute('visible', true);
                
                // Hide after 3 seconds
                setTimeout(() => {
                    infoPanel.setAttribute('visible', false);
                }, 3000);
            }
        }
        
        function resetView() {
            if (camera) {
                camera.setAttribute('rotation', '0 0 0');
                camera.setAttribute('position', '0 1.6 0');
            }
        }
        
        function toggleVR() {
            if (scene) {
                scene.enterVR();
            }
        }
        
        function toggleFullscreen() {
            const container = document.getElementById('tourContainer');
            const btn = document.getElementById('fullscreenBtn');
            
            if (!document.fullscreenElement) {
                container.requestFullscreen().then(() => {
                    btn.innerHTML = '<i class="fas fa-compress"></i> Esci';
                }).catch(err => {
                    console.log('Errore fullscreen:', err);
                });
            } else {
                document.exitFullscreen().then(() => {
                    btn.innerHTML = '<i class="fas fa-expand"></i> Fullscreen';
                });
            }
        }
        
        function toggleAutoRotate() {
            const btn = document.getElementById('autoRotateBtn');
            
            if (autoRotateEnabled) {
                // Stop auto rotate
                clearInterval(autoRotateInterval);
                autoRotateEnabled = false;
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Auto';
            } else {
                // Start auto rotate
                autoRotateEnabled = true;
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-pause"></i> Stop';
                
                autoRotateInterval = setInterval(() => {
                    if (camera) {
                        const currentRotation = camera.getAttribute('rotation');
                        const newY = (parseFloat(currentRotation.y) + 0.5) % 360;
                        camera.setAttribute('rotation', `${currentRotation.x} ${newY} ${currentRotation.z}`);
                    }
                }, 50);
            }
        }
        
        function setupRoomNavigation() {
            // Add keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                switch(e.key) {
                    case '1':
                        changeRoom('living');
                        break;
                    case '2':
                        changeRoom('kitchen');
                        break;
                    case '3':
                        changeRoom('bedroom');
                        break;
                    case '4':
                        changeRoom('bathroom');
                        break;
                    case '5':
                        changeRoom('balcony');
                        break;
                    case 'r':
                        resetView();
                        break;
                    case 'f':
                        toggleFullscreen();
                        break;
                }
            });
        }
        
        // Funzione per richiedere il tour virtuale
        function requestTour() {
            alert('Richiesta inviata! L\'agente ti contatterà presto per organizzare il tour virtuale.');
            // Qui potresti implementare una chiamata AJAX per inviare la richiesta
        }
        
        // Handle fullscreen changes
        document.addEventListener('fullscreenchange', function() {
            const btn = document.getElementById('fullscreenBtn');
            if (!document.fullscreenElement && btn) {
                btn.innerHTML = '<i class="fas fa-expand"></i> Fullscreen';
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (scene) {
                scene.resize();
            }
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (autoRotateInterval) {
                clearInterval(autoRotateInterval);
            }
        });

        // Gestione responsive del menu dropdown
        document.addEventListener('click', function(e) {
            const userMenu = document.querySelector('.user-menu');
            if (userMenu && !userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Attiva/disattiva il menu dropdown dell'utente
        const userMenuLink = document.querySelector('.user-menu > a');
        if (userMenuLink) {
            userMenuLink.addEventListener('click', function(e) {
                e.preventDefault();
                this.parentElement.classList.toggle('active');
            });
        }
    </script>
</body>
</html>

