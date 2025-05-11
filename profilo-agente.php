<?php
session_start();
// Verifica se l'agente è loggato
if (!isset($_SESSION['agent_id'])) {
    header("Location: login-agente.php");
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

// Recupera i dati dell'agente
$agent_id = $_SESSION['agent_id'];
$sql = "SELECT * FROM agenti_immobiliari WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();

// Recupera gli immobili gestiti dall'agente
$sql_immobili = "SELECT * FROM immobili WHERE agente_id = ? ORDER BY data_inserimento DESC";
$stmt_immobili = $conn->prepare($sql_immobili);
$stmt_immobili->bind_param("i", $agent_id);
$stmt_immobili->execute();
$result_immobili = $stmt_immobili->get_result();

// Conteggio immobili per stato
$sql_count = "SELECT stato, COUNT(*) as count FROM immobili WHERE agente_id = ? GROUP BY stato";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $agent_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();

$stats = [
    'disponibile' => 0,
    'venduto' => 0,
    'affittato' => 0
];

while ($row = $result_count->fetch_assoc()) {
    if (!empty($row['stato'])) {
        $stats[$row['stato']] = $row['count'];
    } else {
        $stats['disponibile'] += $row['count']; // Default a disponibile se lo stato è vuoto
    }
}

// Recupera le transazioni legate agli immobili dell'agente
$sql_transactions = "SELECT t.*, u.nome as nome_utente, u.cognome as cognome_utente, i.nome as nome_immobile 
                    FROM transazioni t 
                    JOIN immobili i ON t.id_immobile = i.id 
                    JOIN utenti u ON t.id_utente = u.id 
                    WHERE i.agente_id = ? 
                    ORDER BY t.data_transazione DESC";
$stmt_transactions = $conn->prepare($sql_transactions);
$stmt_transactions->bind_param("i", $agent_id);
$stmt_transactions->execute();
$result_transactions = $stmt_transactions->get_result();

// Recupera gli acquisti in corso
$sql_acquisti = "SELECT a.*, u.nome as nome_utente, u.cognome as cognome_utente, u.email as email_utente, 
                 u.telefono as telefono_utente, i.nome as nome_immobile, i.prezzo as prezzo_immobile
                 FROM acquisti a
                 JOIN immobili i ON a.id_immobile = i.id
                 JOIN utenti u ON a.id_utente = u.id
                 WHERE i.agente_id = ?
                 ORDER BY a.data_acquisto DESC";
$stmt_acquisti = $conn->prepare($sql_acquisti);
$stmt_acquisti->bind_param("i", $agent_id);
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
    
    // Verifica email unica
    $check_email = "SELECT id FROM agenti_immobiliari WHERE email = ? AND id != ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("si", $email, $agent_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $error_message = "L'email è già in uso";
    } else {
        // Aggiorna il profilo
        $update_sql = "UPDATE agenti_immobiliari SET nome = ?, cognome = ?, email = ?, telefono = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $nome, $cognome, $email, $telefono, $agent_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Profilo aggiornato con successo!";
            // Aggiorna i dati agente
            $agent['nome'] = $nome;
            $agent['cognome'] = $cognome;
            $agent['email'] = $email;
            $agent['telefono'] = $telefono;
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
    $sql_pwd = "SELECT Password FROM agenti_immobiliari WHERE id = ?";
    $stmt_pwd = $conn->prepare($sql_pwd);
    $stmt_pwd->bind_param("i", $agent_id);
    $stmt_pwd->execute();
    $result_pwd = $stmt_pwd->get_result();
    $pwd_data = $result_pwd->fetch_assoc();
    
    // Verifica se la password corrente è corretta
    if ($current_password === $pwd_data['Password']) {
        if ($new_password === $confirm_password) {
            // Aggiorna la password
            $update_pwd = "UPDATE agenti_immobiliari SET Password = ? WHERE id = ?";
            $stmt_update_pwd = $conn->prepare($update_pwd);
            $stmt_update_pwd->bind_param("si", $new_password, $agent_id);
            
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

// Aggiungi nuovo immobile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_property'])) {
    $nome = $_POST['nome'];
    $descrizione = $_POST['descrizione'];
    $prezzo = $_POST['prezzo'];
    $categoria_id = $_POST['categoria_id'];
    $metri_quadri = $_POST['metri_quadri'];
    $stanze = $_POST['stanze'];
    $bagni = $_POST['bagni'];
    $citta = $_POST['citta'];
    $provincia = $_POST['provincia'];
    
    // Gestione dell'immagine
    $immagine = 'default.jpg'; // Immagine predefinita
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['immagine']['tmp_name'];
        $file_name = $_FILES['immagine']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Controllo estensione
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($file_ext, $allowed_ext)) {
            // Genero un nome univoco per il file
            $new_file_name = uniqid('property_') . '.' . $file_ext;
            $upload_path = 'img/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $immagine = $new_file_name;
            }
        }
    }
    
    // Inserimento dell'immobile
    $insert_sql = "INSERT INTO immobili (nome, descrizione, prezzo, immagine, categoria_id, agente_id, metri_quadri, stanze, bagni, citta, provincia) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssdsiiiisss", $nome, $descrizione, $prezzo, $immagine, $categoria_id, $agent_id, $metri_quadri, $stanze, $bagni, $citta, $provincia);
    
    if ($insert_stmt->execute()) {
        $success_message = "Immobile aggiunto con successo!";
        header("Location: profilo-agente.php?tab=immobili&added=true");
        exit();
    } else {
        $error_message = "Errore nell'aggiunta dell'immobile: " . $conn->error;
    }
}

// Aggiorna stato immobile
if (isset($_GET['update_status']) && isset($_GET['property_id']) && isset($_GET['new_status'])) {
    $property_id = $_GET['property_id'];
    $new_status = $_GET['new_status'];
    
    // Verifica che l'immobile appartenga all'agente
    $check_sql = "SELECT id FROM immobili WHERE id = ? AND agente_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $property_id, $agent_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $update_status_sql = "UPDATE immobili SET stato = ? WHERE id = ?";
        $update_status_stmt = $conn->prepare($update_status_sql);
        $update_status_stmt->bind_param("si", $new_status, $property_id);
        
        if ($update_status_stmt->execute()) {
            header("Location: profilo-agente.php?tab=immobili&status_updated=true");
            exit();
        }
    }
}

// Recupera le categorie per il form di aggiunta immobile
$sql_categories = "SELECT * FROM categorie";
$result_categories = $conn->query($sql_categories);

// Determina quale tab mostrare
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Agente Immobiliare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-header {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .dashboard-card {
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .property-card {
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s;
        }
        .property-card:hover {
            transform: translateY(-5px);
        }
        .transaction-item {
            background-color: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .tab-content {
            padding-top: 1rem;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .alert {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Immobiliare - Area Agenti</a>
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
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="profilo-agente.php">Profilo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout-agente.php">Logout</a>
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
                    <h1><?php echo htmlspecialchars($agent['nome'] . ' ' . $agent['cognome']); ?></h1>
                    <p>
                        <span class="badge bg-<?php echo $agent['ruolo'] == 'senior' ? 'primary' : 'info'; ?>">
                            Agente <?php echo ucfirst($agent['ruolo']); ?>
                        </span>
                    </p>
                    <p>
                        <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($agent['email']); ?>
                        <?php if (!empty($agent['telefono'])): ?>
                            <br><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($agent['telefono']); ?>
                        <?php endif; ?>
                    </p>
                    <p>Membro dal: <?php echo date('d/m/Y', strtotime($agent['data_assunzione'])); ?></p>
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
        
        <?php if (isset($_GET['added']) && $_GET['added'] == 'true'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Immobile aggiunto con successo!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == 'true'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Stato immobile aggiornato con successo!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="agentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>" 
                        id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'dashboard' ? 'true' : 'false'; ?>">
                    Dashboard
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'immobili' ? 'active' : ''; ?>" 
                        id="properties-tab" data-bs-toggle="tab" data-bs-target="#properties" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'immobili' ? 'true' : 'false'; ?>">
                    I miei Immobili
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'acquisti' ? 'active' : ''; ?>" 
                        id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'acquisti' ? 'true' : 'false'; ?>">
                    Acquisti in Corso
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'transazioni' ? 'active' : ''; ?>" 
                        id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'transazioni' ? 'true' : 'false'; ?>">
                    Transazioni
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'profilo' ? 'active' : ''; ?>" 
                        id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" 
                        type="button" role="tab" aria-selected="<?php echo $active_tab == 'profilo' ? 'true' : 'false'; ?>">
                    Profilo
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
        <div class="tab-content" id="agentTabsContent">
            <!-- Tab Dashboard -->
            <div class="tab-pane fade <?php echo $active_tab == 'dashboard' ? 'show active' : ''; ?>" 
                 id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <h3 class="mb-4">Dashboard</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="dashboard-card bg-primary text-white">
                            <h4>Immobili Gestiti</h4>
                            <p class="display-4"><?php echo $result_immobili->num_rows; ?></p>
                            <a href="#properties" class="btn btn-light" data-bs-toggle="tab" data-bs-target="#properties">Gestisci</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card bg-success text-white">
                            <h4>Immobili Disponibili</h4>
                            <p class="display-4"><?php echo $stats['disponibile']; ?></p>
                            <a href="#properties" class="btn btn-light" data-bs-toggle="tab" data-bs-target="#properties">Visualizza</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card bg-info text-white">
                            <h4>Immobili Venduti</h4>
                            <p class="display-4"><?php echo $stats['venduto']; ?></p>
                            <a href="#properties" class="btn btn-light" data-bs-toggle="tab" data-bs-target="#properties">Dettagli</a>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4>Acquisti Recenti</h4>
                        <?php if ($result_acquisti->num_rows > 0): ?>
                            <?php
                            $count = 0;
                            $result_acquisti->data_seek(0);
                            while ($acquisto = $result_acquisti->fetch_assoc()): 
                                if ($count >= 3) break; // Limita a 3 risultati
                                $count++;
                            ?>
                                <div class="transaction-item">
                                    <h5><?php echo htmlspecialchars($acquisto['nome_immobile']); ?></h5>
                                    <p class="mb-1">
                                        <strong>Cliente:</strong> <?php echo htmlspecialchars($acquisto['nome_utente'] . ' ' . $acquisto['cognome_utente']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Acconto:</strong> €<?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($acquisto['data_acquisto'])); ?></small>
                                        <span class="badge bg-<?php echo $acquisto['stato_pagamento'] == 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo $acquisto['stato_pagamento'] == 'completed' ? 'Completato' : 'In attesa'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <a href="#purchases" data-bs-toggle="tab" data-bs-target="#purchases" class="btn btn-outline-primary">Vedi tutti</a>
                        <?php else: ?>
                            <p>Nessun acquisto recente.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h4>Ultime transazioni</h4>
                        <?php if ($result_transactions->num_rows > 0): ?>
                            <?php
                            $count = 0;
                            while ($transaction = $result_transactions->fetch_assoc()): 
                                if ($count >= 3) break; // Limita a 3 risultati
                                $count++;
                            ?>
                                <div class="transaction-item">
                                    <h5><?php echo htmlspecialchars($transaction['nome_immobile']); ?></h5>
                                    <p class="mb-1">
                                        <strong>Cliente:</strong> <?php echo htmlspecialchars($transaction['nome_utente'] . ' ' . $transaction['cognome_utente']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Importo:</strong> €<?php echo number_format($transaction['importo'], 2, ',', '.'); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($transaction['data_transazione'])); ?></small>
                                        <span class="badge bg-primary"><?php echo ucfirst($transaction['tipo']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <a href="#transactions" data-bs-toggle="tab" data-bs-target="#transactions" class="btn btn-outline-primary">Vedi tutte</a>
                        <?php else: ?>
                            <p>Nessuna transazione recente.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab Immobili -->
            <div class="tab-pane fade <?php echo $active_tab == 'immobili' ? 'show active' : ''; ?>" 
                 id="properties" role="tabpanel" aria-labelledby="properties-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>I miei immobili</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                        <i class="fas fa-plus me-2"></i> Aggiungi Immobile
                    </button>
                </div>
                
                <div class="row">
                    <?php if ($result_immobili->num_rows > 0): ?>
                        <?php 
                        $result_immobili->data_seek(0);
                        while ($immobile = $result_immobili->fetch_assoc()): 
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="property-card">
                                    <div class="position-relative">
                                        <img src="img/<?php echo htmlspecialchars($immobile['immagine']); ?>" 
                                             class="img-fluid" alt="<?php echo htmlspecialchars($immobile['nome']); ?>">
                                        <?php if (!empty($immobile['stato'])): ?>
                                            <span class="status-badge badge bg-<?php
                                                echo $immobile['stato'] == 'disponibile' ? 'success' : 
                                                    ($immobile['stato'] == 'venduto' ? 'danger' : 'info'); ?>">
                                                <?php echo ucfirst($immobile['stato']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge badge bg-success">Disponibile</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($immobile['nome']); ?></h5>
                                        <p class="card-text">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <?php echo htmlspecialchars($immobile['citta']); ?>, <?php echo htmlspecialchars($immobile['provincia']); ?>
                                        </p>
                                        <p class="fw-bold">€ <?php echo number_format($immobile['prezzo'], 2, ',', '.'); ?></p>
                                        <p>
                                            <i class="fas fa-ruler-combined me-2"></i> <?php echo $immobile['metri_quadri']; ?> m²
                                            <i class="fas fa-bed ms-3 me-2"></i> <?php echo $immobile['stanze']; ?> stanze
                                            <i class="fas fa-bath ms-3 me-2"></i> <?php echo $immobile['bagni']; ?> bagni
                                        </p>
                                        <div class="d-flex gap-2">
                                            <a href="immobile.php?id=<?php echo $immobile['id']; ?>" class="btn btn-sm btn-outline-primary">Dettagli</a>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                       id="statusDropdown<?php echo $immobile['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Cambia stato
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $immobile['id']; ?>">
                                                    <li>
                                                        <a class="dropdown-item" href="profilo-agente.php?update_status=1&property_id=<?php echo $immobile['id']; ?>&new_status=disponibile">
                                                            Disponibile
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="profilo-agente.php?update_status=1&property_id=<?php echo $immobile['id']; ?>&new_status=venduto">
                                                            Venduto
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="profilo-agente.php?update_status=1&property_id=<?php echo $immobile['id']; ?>&new_status=affittato">
                                                            Affittato
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <a href="modifica-immobile.php?id=<?php echo $immobile['id']; ?>" class="btn btn-sm btn-outline-warning">Modifica</a>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <p>Non hai ancora immobili assegnati. Aggiungi il tuo primo immobile!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Acquisti in Corso -->
            <div class="tab-pane fade <?php echo $active_tab == 'acquisti' ? 'show active' : ''; ?>" 
                 id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <h3 class="mb-4">Acquisti in Corso</h3>
                <?php if ($result_acquisti->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Immobile</th>
                                    <th>Cliente</th>
                                    <th>Prezzo Immobile</th>
                                    <th>Acconto</th>
                                    <th>Data Acquisto</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $result_acquisti->data_seek(0);
                                while ($acquisto = $result_acquisti->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($acquisto['nome_immobile']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($acquisto['nome_utente'] . ' ' . $acquisto['cognome_utente']); ?>
                                            <br>
                                            <small><?php echo htmlspecialchars($acquisto['email_utente']); ?></small>
                                            <?php if (!empty($acquisto['telefono_utente'])): ?>
                                                <br>
                                                <small><?php echo htmlspecialchars($acquisto['telefono_utente']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>€<?php echo number_format($acquisto['prezzo_immobile'], 2, ',', '.'); ?></td>
                                        <td>€<?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($acquisto['data_acquisto'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $acquisto['stato_pagamento'] == 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo $acquisto['stato_pagamento'] == 'completed' ? 'Completato' : 'In attesa'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="dettagli-acquisto.php?id=<?php echo $acquisto['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Dettagli
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>Non ci sono acquisti in corso al momento.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Transazioni -->
            <div class="tab-pane fade <?php echo $active_tab == 'transazioni' ? 'show active' : ''; ?>" 
                 id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                <h3 class="mb-4">Storico Transazioni</h3>
                <?php if ($result_transactions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Immobile</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Importo</th>
                                    <th>Data</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $result_transactions->data_seek(0);
                                while ($transaction = $result_transactions->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['nome_immobile']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['nome_utente'] . ' ' . $transaction['cognome_utente']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['tipo'] == 'vendita' ? 'success' : 'info'; ?>">
                                                <?php echo ucfirst($transaction['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>€<?php echo number_format($transaction['importo'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($transaction['data_transazione'])); ?></td>
                                        <td>
                                            <a href="dettagli-transazione.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Dettagli
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>Non ci sono transazioni registrate.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Profilo -->
            <div class="tab-pane fade <?php echo $active_tab == 'profilo' ? 'show active' : ''; ?>" 
                 id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <h3 class="mb-4">Modifica Profilo</h3>
                <form method="post" action="profilo-agente.php?tab=profilo">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($agent['nome']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cognome" class="form-label">Cognome</label>
                                <input type="text" class="form-control" id="cognome" name="cognome" value="<?php echo htmlspecialchars($agent['cognome']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($agent['email']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($agent['telefono']); ?>">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Aggiorna Profilo</button>
                </form>
            </div>

            <!-- Tab Sicurezza -->
            <div class="tab-pane fade <?php echo $active_tab == 'sicurezza' ? 'show active' : ''; ?>" 
                 id="security" role="tabpanel" aria-labelledby="security-tab">
                <h3 class="mb-4">Impostazioni di Sicurezza</h3>
                <div class="card">
                    <div class="card-header">
                        <h5>Modifica Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="profilo-agente.php?tab=sicurezza">
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

    <!-- Modal per l'aggiunta di un immobile -->
    <div class="modal fade" id="addPropertyModal" tabindex="-1" aria-labelledby="addPropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPropertyModalLabel">Aggiungi Nuovo Immobile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="profilo-agente.php?tab=immobili" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Immobile</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="categoria_id" class="form-label">Categoria</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <?php while ($categoria = $result_categories->fetch_assoc()): ?>
                                            <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descrizione" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="descrizione" name="descrizione" rows="3" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="prezzo" class="form-label">Prezzo (€)</label>
                                    <input type="number" step="0.01" class="form-control" id="prezzo" name="prezzo" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="metri_quadri" class="form-label">Metri Quadri</label>
                                    <input type="number" class="form-control" id="metri_quadri" name="metri_quadri" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stanze" class="form-label">Numero Stanze</label>
                                    <input type="number" class="form-control" id="stanze" name="stanze" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bagni" class="form-label">Numero Bagni</label>
                                    <input type="number" class="form-control" id="bagni" name="bagni" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="citta" class="form-label">Città</label>
                                    <input type="text" class="form-control" id="citta" name="citta" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="provincia" class="form-label">Provincia</label>
                                    <input type="text" class="form-control" id="provincia" name="provincia" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="immagine" class="form-label">Immagine</label>
                            <input type="file" class="form-control" id="immagine" name="immagine" accept="image/*">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                            <button type="submit" name="add_property" class="btn btn-primary">Aggiungi Immobile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attiva il tab corretto all'apertura della pagina
        document.addEventListener('DOMContentLoaded', function() {
            // Ottieni l'URL corrente
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            
            if (tab) {
                // Trova il tab button corrispondente
                const tabButton = document.querySelector(`#${tab}-tab`);
                if (tabButton) {
                    const bsTab = new bootstrap.Tab(tabButton);
                    bsTab.show();
                }
            }
        });
    </script>
</body>
</html>

<?php
// Chiudi le connessioni
$stmt->close();
$stmt_immobili->close();
$stmt_count->close();
$stmt_transactions->close();
$stmt_acquisti->close();
$conn->close();
?>