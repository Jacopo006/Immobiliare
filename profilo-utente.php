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
</head>
<body>
    
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
                    <img src="https://via.placeholder.com/150" alt="Profile" class="rounded-circle img-fluid mb-3">
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
                <h3><i class="fas fa-lock"></i> Sicurezza account</h3>
                <div class="row">
                    <div class="col-md-8">
                        <h4>Cambia password</h4>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?tab=sicurezza'); ?>">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password attuale</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nuova password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">La password deve contenere almeno 8 caratteri, una lettera maiuscola e un numero.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Conferma nuova password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Aggiorna Password</button>
                        </form>
                        <div class="text-end mt-1">
                                    <a href="recupera-password.php" class="text-decoration-none">Password dimenticata?</a>
                                </div>
                        <hr>
                        
                        <h4>Impostazioni di privacy</h4>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Le impostazioni di privacy ti permettono di controllare quali informazioni possono essere visualizzate dagli altri utenti.
                            <br>Questa funzionalità sarà disponibile prossimamente.
                        </div>
                        
                        <h4>Attività account</h4>
                        <p>Ultimo accesso: <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <div class="security-tips card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-shield-alt"></i> Consigli per la sicurezza
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success"></i> Usa una password unica e complessa</li>
                                    <li><i class="fas fa-check-circle text-success"></i> Cambia regolarmente la tua password</li>
                                    <li><i class="fas fa-check-circle text-success"></i> Non condividere mai le tue credenziali</li>
                                    <li><i class="fas fa-check-circle text-success"></i> Verifica sempre di aver effettuato il logout</li>
                                    <li><i class="fas fa-check-circle text-success"></i> Controlla regolarmente le attività del tuo account</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Immobiliare</h5>
                    <p>La tua soluzione immobiliare di fiducia dal 2010.<br>
                    Trova la casa dei tuoi sogni con noi!</p>
                    <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano<br>
                    <i class="fas fa-phone"></i> +39 02 1234567<br>
                    <i class="fas fa-envelope"></i> info@immobiliare-esempio.it</p>
                </div>
                <div class="col-md-4">
                    <h5>Link utili</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="immobili.php" class="text-white"><i class="fas fa-building"></i> Immobili</a></li>
                        <li><a href="contatti.php" class="text-white"><i class="fas fa-envelope"></i> Contatti</a></li>
                        <li><a href="faq.php" class="text-white"><i class="fas fa-question-circle"></i> FAQ</a></li>
                        <li><a href="privacy-policy.php" class="text-white"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Seguici</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                    <h5 class="mt-3">Newsletter</h5>
                    <form>
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="La tua email" aria-label="Email">
                            <button class="btn btn-primary" type="button">Iscriviti</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date("Y"); ?> Immobiliare. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attiva il tab corretto all'avvio
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const triggerEl = document.querySelector('#profileTabs button[data-bs-target="#' + tab + '"]');
                if (triggerEl) {
                    const tabTrigger = new bootstrap.Tab(triggerEl);
                    tabTrigger.show();
                }
            }
        });
    </script>
</body>
</html>

<?php
// Chiudi le connessioni
if (isset($stmt)) $stmt->close();
if (isset($stmt_preferiti)) $stmt_preferiti->close();
if (isset($stmt_acquisti) && $table_exists) $stmt_acquisti->close();
$conn->close();
?>