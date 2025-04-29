<?php
// Configurazione per la connessione al database
$servername = "localhost";
$username = "root"; // Usa il tuo nome utente MySQL
$password = ""; // La tua password MySQL
$dbname = "immobiliare"; // Il nome del tuo database

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>
