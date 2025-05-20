<?php
// Avvio della sessione per gestire l'autenticazione dell'utente
session_start();

// Controlla se l'utente è autenticato, altrimenti reindirizza alla pagina di login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_utente.php");
    exit();
}

// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "immobiliare";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica della connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Recupera le informazioni dell'utente loggato
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM utenti WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Recupera le conversazioni dell'utente
$sql_conversazioni = "SELECT c.*, a.nome as agente_nome, a.cognome as agente_cognome, 
                     i.nome as immobile_nome, i.immagine as immobile_foto,
                     (SELECT COUNT(*) FROM chat_messaggi WHERE id_conversazione = c.id AND id_destinatario_utente = ? AND stato = 'non_letto') as non_letti,
                     (SELECT messaggio FROM chat_messaggi WHERE id_conversazione = c.id ORDER BY data_invio DESC LIMIT 1) as ultimo_messaggio_testo
                     FROM conversazioni c 
                     JOIN agenti_immobiliari a ON c.id_agente = a.id 
                     LEFT JOIN immobili i ON c.id_immobile = i.id 
                     WHERE c.id_utente = ? 
                     ORDER BY c.ultimo_messaggio DESC";
$stmt_conv = $conn->prepare($sql_conversazioni);
$stmt_conv->bind_param("ii", $user_id, $user_id);
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
        $sql_get_conv = "SELECT id_agente, id_immobile FROM conversazioni WHERE id = ? AND id_utente = ?";
        $stmt_get_conv = $conn->prepare($sql_get_conv);
        $stmt_get_conv->bind_param("ii", $id_conversazione, $user_id);
        $stmt_get_conv->execute();
        $result_get_conv = $stmt_get_conv->get_result();
        
        if ($row_conv = $result_get_conv->fetch_assoc()) {
            $id_agente = $row_conv['id_agente'];
            $id_immobile = $row_conv['id_immobile'];
            
            // Inserisci il nuovo messaggio
            $sql_insert = "INSERT INTO chat_messaggi (id_mittente_utente, id_destinatario_agente, id_immobile, messaggio, id_conversazione) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiisi", $user_id, $id_agente, $id_immobile, $messaggio, $id_conversazione);
            
            if ($stmt_insert->execute()) {
                // Aggiorna il timestamp dell'ultimo messaggio nella conversazione
                $sql_update = "UPDATE conversazioni SET ultimo_messaggio = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $id_conversazione);
                $stmt_update->execute();
                
                $success_message = "Messaggio inviato con successo!";
                
                // Reindirizza per evitare il riinvio del form con F5
                header("Location: chat.php?conv=" . $id_conversazione . "&sent=1"); 
                exit();
            } else {
                $error_message = "Errore nell'invio del messaggio. Riprova.";
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
    
    // Verifica che la conversazione appartenga all'utente loggato
    $sql_check = "SELECT * FROM conversazioni WHERE id = ? AND id_utente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_conversazione, $user_id);
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
                            WHERE id_conversazione = ? AND id_destinatario_utente = ?";
        $stmt_update_stato = $conn->prepare($sql_update_stato);
        $stmt_update_stato->bind_param("ii", $id_conversazione, $user_id);
        $stmt_update_stato->execute();
        
        // Recupera i dettagli dell'agente e dell'immobile
        $sql_details = "SELECT a.nome as agente_nome, a.cognome as agente_cognome, a.email as agente_email, a.telefono as agente_telefono,
                       i.nome as immobile_nome, i.prezzo, i.citta, i.provincia, i.immagine, i.metri_quadri, i.stanze, i.bagni
                       FROM conversazioni c
                       JOIN agenti_immobiliari a ON c.id_agente = a.id
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

// Gestisce la creazione di una nuova conversazione
if (isset($_GET['new']) && isset($_GET['agent']) && isset($_GET['property'])) {
    $id_agente = $_GET['agent'];
    $id_immobile = $_GET['property'];
    
    // Verifica se esiste già una conversazione con lo stesso agente per lo stesso immobile
    $sql_check_existing = "SELECT * FROM conversazioni 
                          WHERE id_utente = ? AND id_agente = ? AND id_immobile = ?";
    $stmt_check_existing = $conn->prepare($sql_check_existing);
    $stmt_check_existing->bind_param("iii", $user_id, $id_agente, $id_immobile);
    $stmt_check_existing->execute();
    $result_check_existing = $stmt_check_existing->get_result();
    
    if ($result_check_existing->num_rows > 0) {
        // Conversazione esistente, reindirizza
        $existing_conv = $result_check_existing->fetch_assoc();
        header("Location: chat.php?conv=" . $existing_conv['id']);
        exit();
    } else {
        // Recupera i dettagli dell'immobile
        $sql_property = "SELECT * FROM immobili WHERE id = ?";
        $stmt_property = $conn->prepare($sql_property);
        $stmt_property->bind_param("i", $id_immobile);
        $stmt_property->execute();
        $property = $stmt_property->get_result()->fetch_assoc();
        
        if ($property) {
            // Crea una nuova conversazione
            $titolo = "Informazioni " . $property['nome'];
            $sql_new_conv = "INSERT INTO conversazioni (id_utente, id_agente, id_immobile, titolo, ultimo_messaggio) 
                            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt_new_conv = $conn->prepare($sql_new_conv);
            $stmt_new_conv->bind_param("iiis", $user_id, $id_agente, $id_immobile, $titolo);
            
            if ($stmt_new_conv->execute()) {
                $new_conv_id = $conn->insert_id;
                
                // Aggiungi un messaggio di sistema per iniziare la conversazione
                $messaggio_sistema = "Benvenuto nella chat! Un agente immobiliare ti risponderà al più presto.";
                $sql_insert_sistema = "INSERT INTO chat_messaggi (id_mittente_agente, id_destinatario_utente, id_immobile, messaggio, id_conversazione) 
                                    VALUES (?, ?, ?, ?, ?)";
                $stmt_insert_sistema = $conn->prepare($sql_insert_sistema);
                $stmt_insert_sistema->bind_param("iiisi", $id_agente, $user_id, $id_immobile, $messaggio_sistema, $new_conv_id);
                $stmt_insert_sistema->execute();
                
                header("Location: chat.php?conv=" . $new_conv_id);
                exit();
            } else {
                $error_message = "Errore nella creazione della conversazione. Riprova.";
            }
        } else {
            $error_message = "Immobile non trovato.";
        }
    }
}

// Gestisce l'invio di un nuovo contatto per immobile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['messaggio_contatto']) && isset($_POST['immobile_id'])) {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : $user['nome'] . ' ' . $user['cognome'];
    $email = isset($_POST['email']) ? trim($_POST['email']) : $user['email'];
    $messaggio = trim($_POST['messaggio_contatto']);
    $immobile_id = $_POST['immobile_id'];
    
    // Ottieni l'agente associato all'immobile
    $sql_get_agent = "SELECT agente_id FROM immobili WHERE id = ?";
    $stmt_get_agent = $conn->prepare($sql_get_agent);
    $stmt_get_agent->bind_param("i", $immobile_id);
    $stmt_get_agent->execute();
    $agent_result = $stmt_get_agent->get_result();
    
    if ($agent_row = $agent_result->fetch_assoc()) {
        $agente_id = $agent_row['agente_id'];
        
        // Inserisci il contatto
        $sql_insert_contatto = "INSERT INTO contatti (nome, id_utente, id_agente, id_immobile, email, messaggio) 
                               VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_contatto = $conn->prepare($sql_insert_contatto);
        $stmt_insert_contatto->bind_param("siiiss", $nome, $user_id, $agente_id, $immobile_id, $email, $messaggio);
        
        if ($stmt_insert_contatto->execute()) {
            // Crea una nuova conversazione
            $sql_new_conv = "INSERT INTO conversazioni (id_utente, id_agente, id_immobile, titolo, ultimo_messaggio) 
                            VALUES (?, ?, ?, 'Richiesta informazioni', CURRENT_TIMESTAMP)";
            $stmt_new_conv = $conn->prepare($sql_new_conv);
            $stmt_new_conv->bind_param("iii", $user_id, $agente_id, $immobile_id);
            
            if ($stmt_new_conv->execute()) {
                $new_conv_id = $conn->insert_id;
                
                // Aggiungi il messaggio iniziale alla conversazione
                $sql_insert_msg = "INSERT INTO chat_messaggi (id_mittente_utente, id_destinatario_agente, id_immobile, messaggio, id_conversazione) 
                                  VALUES (?, ?, ?, ?, ?)";
                $stmt_insert_msg = $conn->prepare($sql_insert_msg);
                $stmt_insert_msg->bind_param("iiisi", $user_id, $agente_id, $immobile_id, $messaggio, $new_conv_id);
                $stmt_insert_msg->execute();
                
                $success_message = "Richiesta inviata con successo! Un agente ti risponderà al più presto.";
                header("Location: chat.php?conv=" . $new_conv_id . "&success=1");
                exit();
            }
        } else {
            $error_message = "Errore nell'invio della richiesta. Riprova.";
        }
    } else {
        $error_message = "Immobile senza agente associato.";
    }
}

// Mostra messaggio di successo dopo il redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Richiesta inviata con successo! Un agente ti risponderà al più presto.";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaggi - Immobiliare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="contatti.css">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-comments me-2"></i> Le mie conversazioni</h1>
            <a href="home-page.php" class="btn btn-outline-primary"><i class="fas fa-home me-2"></i> Torna alla home</a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="chat-container">
            <div class="row g-0">
                <!-- Lista conversazioni -->
                <div class="col-md-4 col-lg-3 conversations-list">
                    <div class="p-3 bg-light border-bottom">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i> Messaggi</h5>
                    </div>
                    
                    <?php if ($result_conv->num_rows > 0): ?>
                        <?php while ($conv = $result_conv->fetch_assoc()): ?>
                            <div class="conversation-item <?php echo (isset($_GET['conv']) && $_GET['conv'] == $conv['id']) ? 'active' : ''; ?>"
                                 onclick="window.location.href='chat.php?conv=<?php echo $conv['id']; ?>'">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($conv['agente_nome'] . ' ' . $conv['agente_cognome']); ?>
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
                        <div class="no-conversations">
                            <i class="fas fa-inbox fa-2x mb-3"></i>
                            <p>Non hai ancora conversazioni attive</p>
                            <a href="immobili.php" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-search me-1"></i> Esplora immobili
                            </a>
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
                                            <i class="fas fa-user-tie me-2"></i>
                                            <?php echo htmlspecialchars($details['agente_nome'] . ' ' . $details['agente_cognome']); ?>
                                        </h5>
                                        <div class="small text-muted">
                                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($details['agente_email']); ?> 
                                            <?php if ($details['agente_telefono']): ?>
                                                <span class="mx-2">|</span>
                                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($details['agente_telefono']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="btn-group btn-group-sm">
                                            <a href="mailto:<?php echo $details['agente_email']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-envelope me-1"></i> Email
                                            </a>
                                            <?php if ($details['agente_telefono']): ?>
                                                <a href="tel:<?php echo $details['agente_telefono']; ?>" class="btn btn-outline-primary">
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
                                                <?php if ($details['immagine']): ?>
                                                    <div class="col-md-4">
                                                        <div class="property-image" style="background-image: url('uploads/<?php echo htmlspecialchars($details['immagine']); ?>');"></div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="col-md-<?php echo $details['immagine'] ? '8' : '12'; ?>">
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
                            <div class="chat-messages" id="chatMessages">
                                <?php if ($messages->num_rows > 0): ?>
    <?php while ($msg = $messages->fetch_assoc()): ?>
        <?php 
        $is_sent = !empty($msg['id_mittente_utente']);
        $message_class = $is_sent ? 'message-sent' : 'message-received';
        $sender_name = $is_sent ? $msg['utente_nome'] . ' ' . $msg['utente_cognome'] : $msg['agente_nome'] . ' ' . $msg['agente_cognome'];
        ?>
        <div class="message <?php echo $message_class; ?>">
            <div class="message-content">
                <?php if (!$is_sent): ?>
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

<!-- Input messaggi -->
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
<!-- Stato vuoto -->
<div class="empty-state">
    <div class="empty-icon">
        <i class="fas fa-comments"></i>
    </div>
    <h4>Seleziona una conversazione</h4>
    <p class="text-muted">Scegli una conversazione dalla lista o inizia una nuova chat da un annuncio immobiliare</p>
    <a href="immobili.php" class="btn btn-primary mt-3">
        <i class="fas fa-search me-2"></i> Cerca immobili
    </a>
</div>
<?php endif; ?>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Scroll alla fine dei messaggi
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
</script>
</body>
</html>