<?php
session_start();
include 'config.php';

if (isset($_POST['id']) && isset($_POST['stato'])) {
    $id = intval($_POST['id']);
    $stato = $_POST['stato'];
    $id_agente = $_SESSION['user_id'];

    // Verifica che l'immobile appartenga all'agente
    $stmt = $conn->prepare("SELECT id FROM immobili WHERE id = ? AND agente_id = ?");
    $stmt->bind_param('ii', $id, $id_agente);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $update = $conn->prepare("UPDATE immobili SET stato = ? WHERE id = ?");
        $update->bind_param('si', $stato, $id);
        if ($update->execute()) {
            echo "Stato aggiornato.";
        } else {
            http_response_code(500);
            echo "Errore nell'aggiornamento.";
        }
    } else {
        http_response_code(403);
        echo "Non autorizzato.";
    }
} else {
    http_response_code(400);
    echo "Dati mancanti.";
}
?>
