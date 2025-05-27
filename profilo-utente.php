<?php
session_start();
// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "immobiliare";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Recupera i dati dell'utente
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM utenti WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Recupera gli immobili preferiti dell'utente con tutte le informazioni necessarie
$sql_preferiti = "SELECT i.id, i.nome, i.descrizione, i.prezzo, i.metri_quadri, i.stanze, i.bagni, 
               c.nome AS categoria, i.citta, i.provincia, i.immagine, p.data_aggiunta
        FROM preferiti p
        JOIN immobili i ON p.id_immobile = i.id
        JOIN categorie c ON i.categoria_id = c.id
        WHERE p.id_utente = ?
        ORDER BY p.data_aggiunta DESC";
$stmt_preferiti = $conn->prepare($sql_preferiti);
$stmt_preferiti->bind_param("i", $user_id);
$stmt_preferiti->execute();
$result_preferiti = $stmt_preferiti->get_result();

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

// Verifica se la tabella acquisti esiste
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'acquisti'");
if ($check_table->num_rows > 0) {
    $table_exists = true;
}

// Recupera gli acquisti dell'utente se la tabella esiste
$result_acquisti = null;
if ($table_exists) {
    $sql_acquisti = "SELECT a.*, i.nome as nome_immobile, i.prezzo 
                    FROM acquisti a 
                    JOIN immobili i ON a.id_immobile = i.id 
                    WHERE a.id_utente = ?";
    $stmt_acquisti = $conn->prepare($sql_acquisti);
    $stmt_acquisti->bind_param("i", $user_id);
    $stmt_acquisti->execute();
    $result_acquisti = $stmt_acquisti->get_result();
}

// Gestione dell'aggiornamento del profilo
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $indirizzo = $_POST['indirizzo'];
    
    // Verifica email unica
    $check_email = "SELECT id FROM utenti WHERE email = ? AND id != ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("si", $email, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $error_message = "L'email è già in uso";
    } else {
        // Aggiorna il profilo
        $update_sql = "UPDATE utenti SET nome = ?, cognome = ?, email = ?, telefono = ?, indirizzo = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssi", $nome, $cognome, $email, $telefono, $indirizzo, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Profilo aggiornato con successo!";
            // Aggiorna i dati utente
            $user['nome'] = $nome;
            $user['cognome'] = $cognome;
            $user['email'] = $email;
            $user['telefono'] = $telefono;
            $user['indirizzo'] = $indirizzo;
        } else {
            $error_message = "Errore nell'aggiornamento del profilo: " . $conn->error;
        }
    }
}

// Gestione cambio password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verifica password corrente
    $sql_pwd = "SELECT password FROM utenti WHERE id = ?";
    $stmt_pwd = $conn->prepare($sql_pwd);
    $stmt_pwd->bind_param("i", $user_id);
    $stmt_pwd->execute();
    $result_pwd = $stmt_pwd->get_result();
    $pwd_data = $result_pwd->fetch_assoc();
    
    // Verifica se la password corrente è corretta
    if (password_verify($current_password, $pwd_data['password']) || $current_password === $pwd_data['password']) {
        if ($new_password === $confirm_password) {
            // Hash della nuova password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Aggiorna la password
            $update_pwd = "UPDATE utenti SET password = ? WHERE id = ?";
            $stmt_update_pwd = $conn->prepare($update_pwd);
            $stmt_update_pwd->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt_update_pwd->execute()) {
                $success_message = "Password aggiornata con successo!";
            } else {
                $error_message = "Errore nell'aggiornamento della password: " . $conn->error;
            }
        } else {
            $error_message = "Le nuove password non corrispondono!";
        }
    } else {
        $error_message = "Password corrente errata!";
    }
}

// Gestione rimozione preferito
if (isset($_GET['remove_favorite']) && is_numeric($_GET['remove_favorite'])) {
    $immobile_id = $_GET['remove_favorite'];
    
    $remove_sql = "DELETE FROM preferiti WHERE id_utente = ? AND id_immobile = ?";
    $remove_stmt = $conn->prepare($remove_sql);
    $remove_stmt->bind_param("ii", $user_id, $immobile_id);
    
    if ($remove_stmt->execute()) {
        $_SESSION['success_message'] = "Immobile rimosso dai preferiti con successo!";
        header("Location: profilo-utente.php?tab=preferiti");
        exit();
    }
}

// Determina quale tab mostrare
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profilo';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente - Immobiliare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Aggiungi parametro versione per forzare ricaricamento -->
    <link rel="stylesheet" href="style_profilo-utente.css?v=1.1">
    <style>
        /* Stili per la foto profilo */
        .profile-photo-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        
        .profile-photo-container:hover .photo-overlay {
            opacity: 1;
        }
        
        .photo-overlay i {
            color: white;
            font-size: 24px;
        }
        
        .photo-actions {
            margin-top: 15px;
        }
        
        .photo-actions .btn {
            margin: 0 5px;
        }
        
        #file-input {
            display: none;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-spinner {
            color: white;
            font-size: 24px;
        }
        
        /* Alert per messaggi foto */
        .photo-alert {
            margin-top: 15px;
            display: none;
        }
    </style>
</head>
<body>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <span class="ms-2">Caricamento...</span>
        </div>
    </div>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="home-page.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-house-door" viewBox="0 0 16 16">
                    <path d="M8 3L0 9V10.5H2V16H6V12H10V16H14V10.5H16V9L8 3Z" />
                </svg>
                Immobiliare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home-page.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="immobili.php"><i class="fas fa-building"></i> Immobili</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['nome']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profilo-utente.php"><i class="fas fa-id-card"></i> Profilo</a></li>
                            <li><a class="dropdown-item" href="profilo-utente.php?tab=preferiti"><i class="fas fa-heart"></i> Preferiti</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header del profilo -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="profile-photo-container">
                        <img src="<?php echo !empty($user['foto_profilo']) && file_exists($user['foto_profilo']) ? $user['foto_profilo'] : 'https://via.placeholder.com/150'; ?>" 
                             alt="Profile" class="profile-photo" id="profileImage">
                        <div class="photo-overlay" onclick="document.getElementById('file-input').click();">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    
                    <!-- Input file nascosto -->
                    <input type="file" id="file-input" accept="image/*">
                    
                    <!-- Azioni foto -->
                    <div class="photo-actions">
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('file-input').click();">
                            <i class="fas fa-upload"></i> Carica foto
                        </button>
                        <?php if (!empty($user['foto_profilo'])): ?>
                        <button class="btn btn-danger btn-sm" onclick="removePhoto()">
                            <i class="fas fa-trash"></i> Rimuovi
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Alert per messaggi foto -->
                    <div class="alert photo-alert" id="photoAlert" role="alert"></div>
                </div>
                <div class="col-md-9">
                    <h1><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></h1>
                    <p class="text-muted">
                        <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($user['email']); ?>
                        <?php if (!empty($user['telefono'])): ?>
                            <br><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($user['telefono']); ?>
                        <?php endif; ?>
                        <?php if (!empty($user['indirizzo'])): ?>
                            <br><i class="fas fa-map-marker-alt me-2"></i> <?php echo htmlspecialchars($user['indirizzo']); ?>
                        <?php endif; ?>
                    </p>
                    <p>Membro dal: <?php echo date('d/m/Y', strtotime($user['data_registrazione'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="container">
        <!-- Messaggi di errore/successo -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info_message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['info_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['info_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'profilo' ? 'active' : ''; ?>" 
                        id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'profilo' ? 'true' : 'false'; ?>">
                    <i class="fas fa-id-card"></i> Profilo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'preferiti' ? 'active' : ''; ?>" 
                        id="favorites-tab" data-bs-toggle="tab" data-bs-target="#favorites" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'preferiti' ? 'true' : 'false'; ?>">
                    <i class="fas fa-heart"></i> Preferiti
                </button>
            </li>
            <?php if ($table_exists): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'acquisti' ? 'active' : ''; ?>" 
                        id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'acquisti' ? 'true' : 'false'; ?>">
                    <i class="fas fa-shopping-cart"></i> Acquisti
                </button>
            </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'sicurezza' ? 'active' : ''; ?>" 
                        id="security-tab" data-bs-toggle="tab" data-bs-target="#security" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'sicurezza' ? 'true' : 'false'; ?>">
                    <i class="fas fa-lock"></i> Sicurezza
                </button>
            </li>
        </ul>

        <!-- Contenuto dei tabs -->
        <div class="tab-content" id="profileTabsContent">
            <!-- Tab Profilo -->
            <div class="tab-pane fade <?php echo $active_tab == 'profilo' ? 'show active' : ''; ?>" 
                 id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="row">
                    <div class="col-md-8">
                        <h3>Informazioni Personali</h3>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="cognome" class="form-label">Cognome</label>
                                <input type="text" class="form-control" id="cognome" name="cognome" 
                                       value="<?php echo htmlspecialchars($user['cognome']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo htmlspecialchars($user['telefono']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="indirizzo" class="form-label">Indirizzo</label>
                                <input type="text" class="form-control" id="indirizzo" name="indirizzo" 
                                       value="<?php echo htmlspecialchars($user['indirizzo'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Aggiorna Profilo</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-stats">
                            <h4>Attività</h4>
                            <p><strong>Immobili preferiti:</strong> <?php echo $result_preferiti->num_rows; ?></p>
                            <p><strong>Acquisti:</strong> <?php echo $table_exists && $result_acquisti ? $result_acquisti->num_rows : '0'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Preferiti (ora utilizza lo stile di preferiti.php) -->
            <div class="tab-pane fade <?php echo $active_tab == 'preferiti' ? 'show active' : ''; ?>" 
                 id="favorites" role="tabpanel" aria-labelledby="favorites-tab">
                <h3><i class="fas fa-heart"></i> I miei preferiti</h3>
                <p>Immobili salvati che ti interessano</p>
                
                <?php if ($result_preferiti->num_rows > 0): ?>
                    <h4><?php echo $result_preferiti->num_rows; ?> immobili nei preferiti</h4>
                    <div class="immobili-grid">
                        <?php while($row = $result_preferiti->fetch_assoc()): ?>
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
                                        <a href="profilo-utente.php?remove_favorite=<?php echo $row['id']; ?>&tab=preferiti" class="btn-remove" onclick="return confirm('Sei sicuro di voler rimuovere questo immobile dai preferiti?');"><i class="fas fa-trash-alt"></i></a>
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

            <?php if ($table_exists): ?>
            <!-- Tab Acquisti -->
            <div class="tab-pane fade <?php echo $active_tab == 'acquisti' ? 'show active' : ''; ?>" 
                 id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <h3><i class="fas fa-shopping-cart"></i> I tuoi acquisti</h3>
                <div class="row">
                    <?php if ($result_acquisti->num_rows > 0): ?>
                        <?php while ($acquisto = $result_acquisti->fetch_assoc()): ?>
                            <div class="col-md-6">
                                <div class="purchase-item">
                                    <h5><?php echo htmlspecialchars($acquisto['nome_immobile']); ?></h5>
                                    <p><strong>Data acquisto:</strong> <?php echo date('d/m/Y', strtotime($acquisto['data_acquisto'])); ?></p>
                                    <p><strong>Prezzo:</strong> € <?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?></p>
                                    <p><strong>Acconto:</strong> € <?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?></p>
                                    <p><strong>Metodo pagamento:</strong> <?php echo htmlspecialchars($acquisto['metodo_pagamento']); ?></p>
                                    <p><strong>Stato:</strong> 
                                        <span class="badge <?php echo $acquisto['stato_pagamento'] == 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $acquisto['stato_pagamento'] == 'completed' ? 'Completato' : 'In attesa'; ?>
                                        </span>
                                        </p>
                                    <?php if (!empty($acquisto['note'])): ?>
                                        <p><strong>Note:</strong> <?php echo htmlspecialchars($acquisto['note']); ?></p>
                                    <?php endif; ?>
                                    <a href="immobile.php?id=<?php echo $acquisto['id_immobile']; ?>" class="btn btn-sm btn-primary">Vedi immobile</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-results">
                                <i class="fas fa-shopping-bag"></i>
                                <h3>Non hai ancora effettuato acquisti</h3>
                                <p>Quando acquisterai un immobile, potrai trovare qui i dettagli delle tue transazioni.</p>
                                <a href="immobili.php" class="btn-view-all">Esplora gli immobili</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab Sicurezza -->
            <div class="tab-pane fade <?php echo $active_tab == 'sicurezza' ? 'show active' : ''; ?>" 
                 id="security" role="tabpanel" aria-labelledby="security-tab">
                <h3><i class="fas fa-lock"></i> Sicurezza Account</h3>
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Corrente</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nuova Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">La password deve contenere almeno 8 caratteri.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">Cambia Password</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="security-info">
                            <h5><i class="fas fa-shield-alt"></i> Sicurezza Account</h5>
                            <p>La tua password è stata modificata l'ultima volta il: 
                                <strong><?php echo date('d/m/Y', strtotime($user['data_registrazione'])); ?></strong>
                            </p>
                            <div class="alert alert-info">
                                <small>
                                    <strong>Consigli per la sicurezza:</strong><br>
                                    • Usa una password forte<br>
                                    • Non condividere mai la tua password<br>
                                    • Cambia la password regolarmente
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script per gestione foto profilo -->
    <script>
        // Gestione upload foto
        document.getElementById('file-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validazioni lato client
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showPhotoAlert('Tipo file non supportato. Usa JPG, PNG o GIF.', 'danger');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) { // 5MB
                showPhotoAlert('File troppo grande. Massimo 5MB.', 'danger');
                return;
            }
            
            // Mostra loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Crea FormData per upload
            const formData = new FormData();
            formData.append('foto_profilo', file);
            
            // Invia richiesta AJAX
            fetch('upload-foto.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    // Aggiorna immagine profilo
                    document.getElementById('profileImage').src = data.foto_url + '?t=' + new Date().getTime();
                    showPhotoAlert(data.message, 'success');
                    
                    // Ricarica la pagina dopo 2 secondi per aggiornare il pulsante rimuovi
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showPhotoAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                showPhotoAlert('Errore durante l\'upload della foto.', 'danger');
                console.error('Error:', error);
            });
        });
        
        // Funzione per rimuovere foto
        function removePhoto() {
            if (!confirm('Sei sicuro di voler rimuovere la foto profilo?')) {
                return;
            }
            
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            fetch('remove-foto.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    // Ripristina immagine placeholder
                    document.getElementById('profileImage').src = 'https://via.placeholder.com/150';
                    showPhotoAlert(data.message, 'success');
                    
                    // Ricarica la pagina dopo 2 secondi per rimuovere il pulsante
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showPhotoAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                showPhotoAlert('Errore durante la rimozione della foto.', 'danger');
                console.error('Error:', error);
            });
        }
        
        // Mostra alert per foto
        function showPhotoAlert(message, type) {
            const alertDiv = document.getElementById('photoAlert');
            alertDiv.className = `alert photo-alert alert-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            // Nascondi dopo 5 secondi
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }
        
        // Gestione attivazione tab dalla URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            if (activeTab) {
                const tabButton = document.getElementById(activeTab === 'preferiti' ? 'favorites-tab' : 
                                                       activeTab === 'acquisti' ? 'purchases-tab' :
                                                       activeTab === 'sicurezza' ? 'security-tab' : 'profile-tab');
                if (tabButton) {
                    const tab = new bootstrap.Tab(tabButton);
                    tab.show();
                }
            }
        });
        
        // Validazione password in tempo reale
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const feedback = this.nextElementSibling;
            
            if (password.length >= 8) {
                feedback.className = 'form-text text-success';
                feedback.textContent = 'Password valida ✓';
            } else {
                feedback.className = 'form-text text-danger';
                feedback.textContent = 'La password deve contenere almeno 8 caratteri.';
            }
        });
        
        // Verifica corrispondenza password
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Le password non corrispondono');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>