<?php
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'utente') {
    // Se non è autenticato, salva la destinazione e reindirizza al login
    $redirect_url = 'contatta_agente.php?';
    if (isset($_GET['id'])) $redirect_url .= 'id=' . $_GET['id'] . '&';
    if (isset($_GET['immobile'])) $redirect_url .= 'immobile=' . $_GET['immobile'];
    
    header("Location: login_utente.php?redirect=" . urlencode($redirect_url));
    exit();
}

// Verifica se sono stati forniti i parametri necessari
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['immobile']) || empty($_GET['immobile'])) {
    header('Location: immobili.php');
    exit();
}

// Recupera i parametri
$id_agente = (int)$_GET['id'];
$id_immobile = (int)$_GET['immobile'];
$id_utente = $_SESSION['user_id'];

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

// Verifica che l'agente e l'immobile esistano
$sql_check = "SELECT i.*, a.nome as agente_nome, a.cognome as agente_cognome 
             FROM immobili i 
             JOIN agenti_immobiliari a ON i.agente_id = a.id 
             WHERE i.id = ? AND i.agente_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_immobile, $id_agente);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // Agente o immobile non validi
    header('Location: immobili.php');
    exit();
}

$immobile = $result_check->fetch_assoc();

// Verifica se esiste già una conversazione tra questo utente e questo agente per questo immobile
$sql_check_conv = "SELECT * FROM conversazioni 
                  WHERE id_utente = ? AND id_agente = ? AND id_immobile = ?";
$stmt_check_conv = $conn->prepare($sql_check_conv);
$stmt_check_conv->bind_param("iii", $id_utente, $id_agente, $id_immobile);
$stmt_check_conv->execute();
$result_check_conv = $stmt_check_conv->get_result();

if ($result_check_conv->num_rows > 0) {
    // Conversazione esistente, reindirizza
    $existing_conv = $result_check_conv->fetch_assoc();
    header("Location: chat.php?conv=" . $existing_conv['id']);
    exit();
} else {
    // Crea una nuova conversazione
    $titolo = "Informazioni " . $immobile['nome'];
    $sql_new_conv = "INSERT INTO conversazioni (id_utente, id_agente, id_immobile, titolo, ultimo_messaggio) 
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
    $stmt_new_conv = $conn->prepare($sql_new_conv);
    $stmt_new_conv->bind_param("iiis", $id_utente, $id_agente, $id_immobile, $titolo);
    
    if ($stmt_new_conv->execute()) {
        $new_conv_id = $conn->insert_id;
        
        // Aggiungi un messaggio di sistema per iniziare la conversazione
        $messaggio_sistema = "Benvenuto nella chat! Sono interessato all'immobile: " . $immobile['nome'] . ". Potrebbe fornirmi maggiori informazioni?";
        $sql_insert_sistema = "INSERT INTO chat_messaggi (id_mittente_utente, id_destinatario_agente, id_immobile, messaggio, id_conversazione) 
                             VALUES (?, ?, ?, ?, ?)";
        $stmt_insert_sistema = $conn->prepare($sql_insert_sistema);
        $stmt_insert_sistema->bind_param("iiisi", $id_utente, $id_agente, $id_immobile, $messaggio_sistema, $new_conv_id);
        $stmt_insert_sistema->execute();
        
        header("Location: chat.php?conv=" . $new_conv_id);
        exit();
    } else {
        // Errore nella creazione della conversazione
        header('Location: immobile.php?id=' . $id_immobile . '&error=1');
        exit();
    }
}

$conn->close();
?>