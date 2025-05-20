<?php
// Avvio della sessione per gestire l'autenticazione dell'agente
session_start();
include 'config.php'; // Utilizziamo lo stesso file di configurazione della dashboard

// Debug per vedere se le variabili di sessione sono impostate
error_log("Chat Agente - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non impostato'));
error_log("Chat Agente - user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'non impostato'));

// Verifica che l'utente sia un agente immobiliare usando le stesse variabili di sessione di dashboard_agente.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'agente') {
    error_log("Accesso non autorizzato alla chat agente - reindirizzamento al login");
    header('Location: login_agente.php');
    exit();
}

// Utilizziamo user_id come agente_id per mantenere la compatibilità con il resto del codice
$agente_id = $_SESSION['user_id'];

// Verifica della connessione (il database dovrebbe essere già connesso tramite config.php)
if (!isset($conn) || $conn->connect_error) {
    die("Connessione fallita: " . ($conn ? $conn->connect_error : "Variabile conn non impostata"));
}

// Recupera le informazioni dell'agente loggato
$sql_agente = "SELECT * FROM agenti_immobiliari WHERE id = ?";
$stmt_agente = $conn->prepare($sql_agente);
$stmt_agente->bind_param("i", $agente_id);
$stmt_agente->execute();
$result_agente = $stmt_agente->get_result();
$agente = $result_agente->fetch_assoc();

// Recupera le conversazioni dell'agente
$sql_conversazioni = "SELECT c.*, u.nome as utente_nome, u.cognome as utente_cognome, u.telefono as utente_telefono, u.email as utente_email, 
                     i.nome as immobile_nome, i.immagine as immobile_foto,
                     (SELECT COUNT(*) FROM chat_messaggi WHERE id_conversazione = c.id AND id_destinatario_agente = ? AND stato = 'non_letto') as non_letti,
                     (SELECT messaggio FROM chat_messaggi WHERE id_conversazione = c.id ORDER BY data_invio DESC LIMIT 1) as ultimo_messaggio_testo
                     FROM conversazioni c 
                     JOIN utenti u ON c.id_utente = u.id 
                     LEFT JOIN immobili i ON c.id_immobile = i.id 
                     WHERE c.id_agente = ? 
                     ORDER BY c.ultimo_messaggio DESC";
$stmt_conv = $conn->prepare($sql_conversazioni);
$stmt_conv->bind_param("ii", $agente_id, $agente_id);
$stmt_conv->execute();
$result_conv = $stmt_conv->get_result();

// Flag per messaggi di stato
$success_message = '';
$error_message = '';

// Gestisce l'invio di un nuovo messaggio
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['messaggio']) && isset($_POST['id_conversazione'])) {
    $messaggio = trim($_POST['messaggio']);
    $id_conversazione = $_POST['id_conversazione'];
    
    if (!empty($messaggio)) {
        // Recupera i dettagli della conversazione
        $sql_get_conv = "SELECT id_utente, id_immobile FROM conversazioni WHERE id = ? AND id_agente = ?";
        $stmt_get_conv = $conn->prepare($sql_get_conv);
        $stmt_get_conv->bind_param("ii", $id_conversazione, $agente_id);
        $stmt_get_conv->execute();
        $result_get_conv = $stmt_get_conv->get_result();
        
        if ($row_conv = $result_get_conv->fetch_assoc()) {
            $id_utente = $row_conv['id_utente'];
            $id_immobile = $row_conv['id_immobile'];
            
            // Inserisci il nuovo messaggio
            $sql_insert = "INSERT INTO chat_messaggi (id_mittente_agente, id_destinatario_utente, id_immobile, messaggio, id_conversazione) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiisi", $agente_id, $id_utente, $id_immobile, $messaggio, $id_conversazione);
            
            if ($stmt_insert->execute()) {
                // Aggiorna il timestamp dell'ultimo messaggio nella conversazione
                $sql_update = "UPDATE conversazioni SET ultimo_messaggio = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $id_conversazione);
                $stmt_update->execute();
                
                $success_message = "Messaggio inviato con successo!";
                
                // Reindirizza per evitare il riinvio del form con F5
                header("Location: chat_agente.php?conv=" . $id_conversazione . "&sent=1"); 
                exit();
            } else {
                $error_message = "Errore nell'invio del messaggio: " . $conn->error;
            }
        } else {
            $error_message = "Conversazione non trovata o non autorizzata.";
        }
    } else {
        $error_message = "Il messaggio non può essere vuoto.";
    }
}

// Mostra messaggio di successo dopo il redirect
if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $success_message = "Messaggio inviato con successo!";
}

// Gestisce la visualizzazione di una conversazione specifica
$active_conversation = null;
$messages = [];
$details = [];

if (isset($_GET['conv'])) {
    $id_conversazione = $_GET['conv'];
    
    // Verifica che la conversazione appartenga all'agente loggato
    $sql_check = "SELECT * FROM conversazioni WHERE id = ? AND id_agente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_conversazione, $agente_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $active_conversation = $result_check->fetch_assoc();
        
        // Recupera i messaggi della conversazione
        $sql_messages = "SELECT m.*, 
                        u.nome as utente_nome, u.cognome as utente_cognome,
                        a.nome as agente_nome, a.cognome as agente_cognome
                        FROM chat_messaggi m
                        LEFT JOIN utenti u ON m.id_mittente_utente = u.id
                        LEFT JOIN agenti_immobiliari a ON m.id_mittente_agente = a.id
                        WHERE m.id_conversazione = ?
                        ORDER BY m.data_invio ASC";
        $stmt_messages = $conn->prepare($sql_messages);
        $stmt_messages->bind_param("i", $id_conversazione);
        $stmt_messages->execute();
        $messages = $stmt_messages->get_result();
        
        // Aggiorna lo stato dei messaggi a 'letto'
        $sql_update_stato = "UPDATE chat_messaggi SET stato = 'letto' 
                            WHERE id_conversazione = ? AND id_destinatario_agente = ?";
        $stmt_update_stato = $conn->prepare($sql_update_stato);
        $stmt_update_stato->bind_param("ii", $id_conversazione, $agente_id);
        $stmt_update_stato->execute();
        
        // Recupera i dettagli dell'utente e dell'immobile
        $sql_details = "SELECT u.nome as utente_nome, u.cognome as utente_cognome, u.email as utente_email, u.telefono as utente_telefono,
                       i.nome as immobile_nome, i.prezzo, i.citta, i.provincia, i.immagine, i.metri_quadri, i.stanze, i.bagni
                       FROM conversazioni c
                       JOIN utenti u ON c.id_utente = u.id
                       LEFT JOIN immobili i ON c.id_immobile = i.id
                       WHERE c.id = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $id_conversazione);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_assoc();
    } else {
        $error_message = "Conversazione non trovata o non autorizzata.";
    }
}

// Visualizza i contatti non ancora riscontrati
$sql_contatti = "SELECT co.*, u.email as utente_email, u.nome as utente_nome, u.cognome as utente_cognome, u.telefono as utente_telefono,
                i.nome as immobile_nome, i.prezzo, i.citta, i.provincia
                FROM contatti co
                LEFT JOIN utenti u ON co.id_utente = u.id
                LEFT JOIN immobili i ON co.id_immobile = i.id
                WHERE co.id_agente = ? AND co.stato = 'non_letto'
                ORDER BY co.data_invio DESC";
$stmt_contatti = $conn->prepare($sql_contatti);
$stmt_contatti->bind_param("i", $agente_id);
$stmt_contatti->execute();
$result_contatti = $stmt_contatti->get_result();

// Gestisce la risposta a un contatto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['risposta_contatto']) && isset($_POST['contatto_id'])) {
    $risposta = trim($_POST['risposta_contatto']);
    $contatto_id = $_POST['contatto_id'];
    
    // Recupera i dettagli del contatto
    $sql_get_contatto = "SELECT * FROM contatti WHERE id = ? AND id_agente = ?";
    $stmt_get_contatto = $conn->prepare($sql_get_contatto);
    $stmt_get_contatto->bind_param("ii", $contatto_id, $agente_id);
    $stmt_get_contatto->execute();
    $result_get_contatto = $stmt_get_contatto->get_result();
    
    if ($row_contatto = $result_get_contatto->fetch_assoc()) {
        $id_utente = $row_contatto['id_utente'];
        $id_immobile = $row_contatto['id_immobile'];
        
        // Controlla se esiste già una conversazione
        $sql_check_conv = "SELECT * FROM conversazioni WHERE id_utente = ? AND id_agente = ? AND id_immobile = ?";
        $stmt_check_conv = $conn->prepare($sql_check_conv);
        $stmt_check_conv->bind_param("iii", $id_utente, $agente_id, $id_immobile);
        $stmt_check_conv->execute();
        $result_check_conv = $stmt_check_conv->get_result();
        
        $id_conversazione = null;
        
        if ($result_check_conv->num_rows > 0) {
            // Usa conversazione esistente
            $conv_row = $result_check_conv->fetch_assoc();
            $id_conversazione = $conv_row['id'];
        } else {
            // Crea nuova conversazione
            $titolo = "Risposta a richiesta informazioni";
            $sql_new_conv = "INSERT INTO conversazioni (id_utente, id_agente, id_immobile, titolo, ultimo_messaggio) 
                            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt_new_conv = $conn->prepare($sql_new_conv);
            $stmt_new_conv->bind_param("iiis", $id_utente, $agente_id, $id_immobile, $titolo);
            
            if ($stmt_new_conv->execute()) {
                $id_conversazione = $conn->insert_id;
            } else {
                $error_message = "Errore nella creazione della conversazione: " . $conn->error;
            }
        }
        
        if ($id_conversazione) {
            // Inserisci il messaggio di risposta
            $sql_insert_msg = "INSERT INTO chat_messaggi (id_mittente_agente, id_destinatario_utente, id_immobile, messaggio, id_conversazione) 
                              VALUES (?, ?, ?, ?, ?)";
            $stmt_insert_msg = $conn->prepare($sql_insert_msg);
            $stmt_insert_msg->bind_param("iiisi", $agente_id, $id_utente, $id_immobile, $risposta, $id_conversazione);
            
            if ($stmt_insert_msg->execute()) {
                // Aggiorna lo stato del contatto
                $sql_update_contatto = "UPDATE contatti SET stato = 'risposto' WHERE id = ?";
                $stmt_update_contatto = $conn->prepare($sql_update_contatto);
                $stmt_update_contatto->bind_param("i", $contatto_id);
                $stmt_update_contatto->execute();
                
                $success_message = "Risposta inviata con successo!";
                header("Location: chat_agente.php?conv=" . $id_conversazione . "&responded=1");
                exit();
            } else {
                $error_message = "Errore nell'invio della risposta: " . $conn->error;
            }
        }
    } else {
        $error_message = "Contatto non trovato o non autorizzato.";
    }
}

// Gestisce la marcatura di un contatto come letto
if (isset($_GET['mark_read']) && isset($_GET['contatto_id'])) {
    $contatto_id = $_GET['contatto_id'];
    
    $sql_update_contatto = "UPDATE contatti SET stato = 'letto' WHERE id = ? AND id_agente = ?";
    $stmt_update_contatto = $conn->prepare($sql_update_contatto);
    $stmt_update_contatto->bind_param("ii", $contatto_id, $agente_id);
    
    if ($stmt_update_contatto->execute()) {
        $success_message = "Contatto segnato come letto.";
        header("Location: chat_agente.php?marked=1");
        exit();
    } else {
        $error_message = "Errore nell'aggiornamento dello stato del contatto: " . $conn->error;
    }
}

// Mostra messaggio di successo dopo il redirect per risposta inviata
if (isset($_GET['responded']) && $_GET['responded'] == 1) {
    $success_message = "Risposta inviata con successo!";
}

// Mostra messaggio di successo dopo il redirect per contatto segnato come letto
if (isset($_GET['marked']) && $_GET['marked'] == 1) {
    $success_message = "Contatto segnato come letto.";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Chat - Area Agenti</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="contatti.css">
    <style>
        /* Aggiungiamo alcuni stili per assicurarci che la lista conversazioni sia visibile */
        .conversations-list {
            border-right: 1px solid #dee2e6;
            max-height: 700px;
            overflow-y: auto;
            display: block !important; /* Forziamo la visualizzazione */
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .conversation-item:hover {
            background-color: #f5f5f5;
        }
        
        .conversation-item.active {
            background-color: #e9f5ff;
            border-left: 3px solid #3498db;
        }
        
        .unread-badge {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            font-size: 12px;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            margin-left: 5px;
        }
        
        /* Stili per la visualizzazione corretta dei tab */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
                        <li><a href="dashboard_agente.php"><i class="fas fa-cogs"></i> Gestione Immobili</a></li>
                        <li><a href="chat_agente.php" class="active"><i class="fas fa-comments"></i> Richieste Ricevute</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <div class="container py-4 mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-comments me-2"></i> Gestione Chat</h1>
            <div>
                <span class="me-3">Agente: <?php echo htmlspecialchars($agente['nome'] . ' ' . $agente['cognome']); ?></span>
                <a href="dashboard_agente.php" class="btn btn-outline-primary me-2"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab navigation - Semplificata e corretta -->
        <div class="tab-buttons mb-4">
            <button class="tab-button active" data-tab="chats">
                <i class="fas fa-comments me-2"></i> Chat
                <?php
                $total_unread = 0;
                if ($result_conv->num_rows > 0) {
                    $result_conv->data_seek(0);
                    while ($conv = $result_conv->fetch_assoc()) {
                        $total_unread += $conv['non_letti'];
                    }
                }
                if ($total_unread > 0):
                ?>
                <span class="badge bg-danger"><?php echo $total_unread; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" data-tab="contacts">
                <i class="fas fa-envelope me-2"></i> Nuovi Contatti
                <?php if ($result_contatti->num_rows > 0): ?>
                <span class="badge bg-danger"><?php echo $result_contatti->num_rows; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Tab content -->
        <div class="tab-content active" id="chats-tab">
            <div class="chat-container">
                <div class="row g-0">
                    <!-- Lista conversazioni - Assicuriamoci che sia visibile -->
                    <div class="col-md-4 col-lg-3 conversations-list">
                        <div class="p-3 bg-light border-bottom">
                            <h5 class="mb-0"><i class="fas fa-envelope me-2"></i> Conversazioni</h5>
                        </div>
                        
                        <?php 
                        // Reset del risultato della query per evitare problemi
                        $result_conv->data_seek(0);
                        if ($result_conv->num_rows > 0): 
                        ?>
                            <?php while ($conv = $result_conv->fetch_assoc()): ?>
                                <div class="conversation-item <?php echo (isset($_GET['conv']) && $_GET['conv'] == $conv['id']) ? 'active' : ''; ?>"
                                     onclick="window.location.href='chat_agente.php?conv=<?php echo $conv['id']; ?>'">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($conv['utente_nome'] . ' ' . $conv['utente_cognome']); ?>
                                            <?php if ($conv['non_letti'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['non_letti']; ?></span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php 
                                            if ($conv['ultimo_messaggio']) {
                                                $date = new DateTime($conv['ultimo_messaggio']);
                                                echo $date->format('d/m/Y');
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div class="small text-truncate mb-1">
                                        <?php echo $conv['immobile_nome'] ? htmlspecialchars($conv['immobile_nome']) : 'Conversazione generica'; ?>
                                    </div>
                                    <div class="small text-muted text-truncate">
                                        <?php echo $conv['ultimo_messaggio_testo'] ? htmlspecialchars(substr($conv['ultimo_messaggio_testo'], 0, 50) . '...') : 'Nessun messaggio'; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-conversations p-4 text-center">
                                <i class="fas fa-inbox fa-2x mb-3 text-muted"></i>
                                <p>Non hai ancora conversazioni attive</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Area messaggi -->
<div class="col-md-8 col-lg-9">
    <div class="chat-area">
        <?php if ($active_conversation): ?>
            <!-- Intestazione conversazione -->
            <div class="chat-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($details['utente_nome'] . ' ' . $details['utente_cognome']); ?>
                        </h5>
                        <div class="small text-muted">
                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($details['utente_email']); ?> 
                            <?php if ($details['utente_telefono']): ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($details['utente_telefono']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group btn-group-sm">
                            <a href="mailto:<?php echo $details['utente_email']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-1"></i> Email
                            </a>
                            <?php if ($details['utente_telefono']): ?>
                                <a href="tel:<?php echo $details['utente_telefono']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-phone me-1"></i> Chiama
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($details['immobile_nome']): ?>
                    <div class="mt-3 border-top pt-3">
                        <div class="property-card">
                            <div class="row g-0">
                                <?php if ($details['immobile_foto']): ?>
                                    <div class="col-md-4">
                                        <div class="property-image" style="background-image: url('uploads/<?php echo htmlspecialchars($details['immobile_foto']); ?>');"></div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-<?php echo $details['immobile_foto'] ? '8' : '12'; ?>">
                                    <div class="property-info">
                                        <h6><?php echo htmlspecialchars($details['immobile_nome']); ?></h6>
                                        <div class="property-price">€ <?php echo number_format($details['prezzo'], 0, ',', '.'); ?></div>
                                        <div class="property-address">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($details['citta'] . ' (' . $details['provincia'] . ')'); ?>
                                        </div>
                                        <div class="property-features">
                                            <span><i class="fas fa-ruler-combined me-1"></i> <?php echo $details['metri_quadri']; ?> m²</span>
                                            <span><i class="fas fa-door-open me-1"></i> <?php echo $details['stanze']; ?> locali</span>
                                            <span><i class="fas fa-bath me-1"></i> <?php echo $details['bagni']; ?> bagni</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Messaggi -->
            <div class="chat-messages" id="chat-messages">
                <?php if ($messages->num_rows > 0): ?>
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <?php 
                        $is_received = !empty($msg['id_mittente_utente']);
                        $message_class = $is_received ? 'message-received' : 'message-sent';
                        $sender_name = $is_received ? $msg['utente_nome'] . ' ' . $msg['utente_cognome'] : $msg['agente_nome'] . ' ' . $msg['agente_cognome'];
                        ?>
                        <div class="message <?php echo $message_class; ?>">
                            <div class="message-content">
                                <?php if ($is_received): ?>
                                    <div class="message-sender"><?php echo htmlspecialchars($sender_name); ?></div>
                                <?php endif; ?>
                                <?php echo nl2br(htmlspecialchars($msg['messaggio'])); ?>
                                <div class="message-time">
                                    <?php 
                                    $date = new DateTime($msg['data_invio']);
                                    echo $date->format('d/m/Y H:i'); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <p>Inizia la conversazione inviando un messaggio</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Form invio messaggio -->
            <div class="chat-input">
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="hidden" name="id_conversazione" value="<?php echo $active_conversation['id']; ?>">
                        <input type="text" name="messaggio" class="form-control" placeholder="Scrivi un messaggio..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Invia
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state h-100 d-flex flex-column justify-content-center align-items-center">
                <i class="fas fa-comments fa-4x mb-4 text-muted"></i>
                <p>Seleziona una conversazione per iniziare a chattare.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
                </div>
            </div>
        </div>
        
        <!-- Tab content: Nuovi Contatti - Rimane uguale -->
        <div class="tab-content" id="contacts-tab">
            <!-- Il contenuto rimane uguale -->
        </div>
    </div>
    
    <!-- Modal per la risposta ai contatti - Rimane uguale -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <!-- Il contenuto rimane uguale -->
    </div>
    
    <!-- JavaScript corretto per funzionalità varie -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll all'ultimo messaggio
            const messagesContainer = document.getElementById('chat-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Gestione tab corretta
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Funzione per cambiare tab
            function switchTab(tabId) {
                // Rimuovi la classe active da tutti i pulsanti e contenuti tab
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Aggiungi la classe active al pulsante e al contenuto corrispondente
                document.querySelector(`.tab-button[data-tab="${tabId}"]`).classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            }
            
            // Aggiungi event listener ai pulsanti tab
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });
            
            // Gestione modal di risposta
            const respondButtons = document.querySelectorAll('.respond-button');
            respondButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const contattoId = this.getAttribute('data-contatto-id');
                    const clienteNome = this.getAttribute('data-cliente-nome');
                    const immobileNome = this.getAttribute('data-immobile-nome');
                    
                    document.getElementById('contatto_id').value = contattoId;
                    document.getElementById('clienteNome').value = clienteNome;
                    document.getElementById('immobileNome').value = immobileNome;
                });
            });
            
            // Controlla se c'è un parametro tab nell'URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('tab')) {
                switchTab(urlParams.get('tab'));
            }
        });
    </script>
</body>
</html>