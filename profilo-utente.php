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

// Recupera gli immobili preferiti dell'utente
$sql_preferiti = "SELECT i.* FROM immobili i 
                 JOIN preferiti p ON i.id = p.id_immobile 
                 WHERE p.id_utente = ?";
$stmt_preferiti = $conn->prepare($sql_preferiti);
$stmt_preferiti->bind_param("i", $user_id);
$stmt_preferiti->execute();
$result_preferiti = $stmt_preferiti->get_result();

// Recupera gli acquisti dell'utente
$sql_acquisti = "SELECT a.*, i.nome as nome_immobile, i.prezzo 
                FROM acquisti a 
                JOIN immobili i ON a.id_immobile = i.id 
                WHERE a.id_utente = ?";
$stmt_acquisti = $conn->prepare($sql_acquisti);
$stmt_acquisti->bind_param("i", $user_id);
$stmt_acquisti->execute();
$result_acquisti = $stmt_acquisti->get_result();

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
        header("Location: profilo-utente.php?tab=preferiti&removed=true");
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
    <title>Profilo Utente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-stats {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1rem;
        }
        .favorite-property, .purchase-item {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s;
        }
        .favorite-property:hover, .purchase-item:hover {
            transform: translateY(-5px);
        }
        .tab-content {
            padding-top: 1rem;
        }
        .alert {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Immobiliare</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="immobili.php">Immobili</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contatti.php">Contatti</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="profilo-utente.php">Profilo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
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
        
        <?php if (isset($_GET['removed']) && $_GET['removed'] == 'true'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Immobile rimosso dai preferiti!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'profilo' ? 'active' : ''; ?>" 
                        id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'profilo' ? 'true' : 'false'; ?>">
                    Profilo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'preferiti' ? 'active' : ''; ?>" 
                        id="favorites-tab" data-bs-toggle="tab" data-bs-target="#favorites" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'preferiti' ? 'true' : 'false'; ?>">
                    Preferiti
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'acquisti' ? 'active' : ''; ?>" 
                        id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'acquisti' ? 'true' : 'false'; ?>">
                    Acquisti
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'sicurezza' ? 'active' : ''; ?>" 
                        id="security-tab" data-bs-toggle="tab" data-bs-target="#security" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'sicurezza' ? 'true' : 'false'; ?>">
                    Sicurezza
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
                            <p><strong>Acquisti:</strong> <?php echo $result_acquisti->num_rows; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Preferiti -->
            <div class="tab-pane fade <?php echo $active_tab == 'preferiti' ? 'show active' : ''; ?>" 
                 id="favorites" role="tabpanel" aria-labelledby="favorites-tab">
                <h3>I tuoi immobili preferiti</h3>
                <div class="row">
                    <?php if ($result_preferiti->num_rows > 0): ?>
                        <?php while ($immobile = $result_preferiti->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="favorite-property">
                                    <div class="position-relative">
                                        <img src="img/<?php echo htmlspecialchars($immobile['immagine']); ?>" 
                                             class="img-fluid mb-2" alt="<?php echo htmlspecialchars($immobile['nome']); ?>">
                                        <a href="profilo-utente.php?remove_favorite=<?php echo $immobile['id']; ?>" 
                                           class="position-absolute top-0 end-0 btn btn-sm btn-danger m-2" 
                                           onclick="return confirm('Sei sicuro di voler rimuovere questo immobile dai preferiti?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    <h5><?php echo htmlspecialchars($immobile['nome']); ?></h5>
                                    <p><?php echo htmlspecialchars($immobile['citta']); ?>, <?php echo htmlspecialchars($immobile['provincia']); ?></p>
                                    <p class="fw-bold">€ <?php echo number_format($immobile['prezzo'], 2, ',', '.'); ?></p>
                                    <p>
                                        <i class="fas fa-ruler-combined me-2"></i> <?php echo $immobile['metri_quadri']; ?> m²
                                        <i class="fas fa-bed ms-3 me-2"></i> <?php echo $immobile['stanze']; ?> stanze
                                        <i class="fas fa-bath ms-3 me-2"></i> <?php echo $immobile['bagni']; ?> bagni
                                    </p>
                                    <a href="immobile.php?id=<?php echo $immobile['id']; ?>" class="btn btn-primary">Dettagli</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p>Non hai ancora aggiunto immobili ai preferiti.</p>
                            <a href="immobili.php" class="btn btn-primary">Esplora immobili</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Acquisti -->
            <div class="tab-pane fade <?php echo $active_tab == 'acquisti' ? 'show active' : ''; ?>" 
                 id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <h3>I tuoi acquisti</h3>
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
                                    <a href="immobile.php?id=<?php echo $acquisto['id_immobile']; ?>" class="btn btn-sm btn-primary">Vedi immobile</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p>Non hai ancora effettuato acquisti.</p>
                            <a href="immobili.php" class="btn btn-primary">Esplora immobili</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Sicurezza -->
            <div class="tab-pane fade <?php echo $active_tab == 'sicurezza' ? 'show active' : ''; ?>" 
                 id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="row">
                    <div class="col-md-8">
                        <h3>Modifica Password</h3>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=sicurezza">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Attuale</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nuova Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Cambia Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Immobiliare</h5>
                    <p>La tua agenzia immobiliare di fiducia</p>
                </div>
                <div class="col-md-4">
                    <h5>Contatti</h5>
                    <p>
                        <i class="fas fa-map-marker-alt me-2"></i> Via Roma, 123, Milano<br>
                        <i class="fas fa-phone me-2"></i> +39 02 1234567<br>
                        <i class="fas fa-envelope me-2"></i> info@immobiliare.it
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Seguici</h5>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Immobiliare. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attiva la scheda corretta in base all'URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const triggerEl = document.querySelector(`button[data-bs-target="#${tab}"]`);
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
// Chiudi le connessioni al database
$stmt->close();
$stmt_preferiti->close();
$stmt_acquisti->close();
$conn->close();
?>