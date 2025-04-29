<?php
session_start();
require_once 'config.php';

// Controllo login agente
if (!isset($_SESSION['agente_id'])) {
    header('Location: login_agente.php');
    exit();
}

// Recupera immobili dell'agente
$query = "SELECT * FROM immobili WHERE agente_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['agente_id']);
$stmt->execute();
$result = $stmt->get_result();
$immobili = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Immobili</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="stylesheet" href="style_immobili.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> <!-- JQuery per AJAX -->
</head>
<body>

<header>
    <nav>
        <div class="logo">
            <a href="dashboard_agente.php"><img src="logo.png" alt="Logo"></a>
        </div>
        <ul>
            <li><a href="dashboard_agente.php">Dashboard</a></li>
            <li><a href="gestione_immobili.php">I miei Immobili</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<section>
    <h2>I miei Immobili</h2>

    <div class="immobili-grid">
        <?php if (count($immobili) > 0): ?>
            <?php foreach ($immobili as $immobile): ?>
                <div class="immobile-card">
                    <div class="immobile-img">
                        <img src="uploads/<?php echo htmlspecialchars($immobile['immagine']); ?>" alt="Immagine Immobile">
                        <div class="categoria-tag"><?php echo htmlspecialchars($immobile['categoria']); ?></div>
                    </div>
                    <div class="immobile-details">
                        <h3><?php echo htmlspecialchars($immobile['titolo']); ?></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($immobile['citta']); ?></p>
                        <p class="price">&euro; <?php echo number_format($immobile['prezzo'], 2, ',', '.'); ?></p>

                        <!-- Cambia stato -->
                        <div style="margin: 10px 0;">
                            <label for="stato-<?php echo $immobile['id']; ?>">Stato:</label>
                            <select id="stato-<?php echo $immobile['id']; ?>" onchange="cambiaStato(<?php echo $immobile['id']; ?>)">
                                <option value="Disponibile" <?php if ($immobile['stato'] == 'Disponibile') echo 'selected'; ?>>Disponibile</option>
                                <option value="Affittato" <?php if ($immobile['stato'] == 'Affittato') echo 'selected'; ?>>Affittato</option>
                                <option value="Venduto" <?php if ($immobile['stato'] == 'Venduto') echo 'selected'; ?>>Venduto</option>
                            </select>
                        </div>

                        <div class="immobile-actions">
                            <a href="modifica_immobile.php?id=<?php echo $immobile['id']; ?>" class="btn-details">Modifica</a>
                            <a href="gestione_immobili.php?action=delete&id=<?php echo $immobile['id']; ?>" class="btn-favorite" onclick="return confirm('Sei sicuro di voler eliminare questo immobile?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-home"></i>
                <h3>Nessun immobile trovato</h3>
                <p>Inizia subito ad aggiungere un nuovo immobile!</p>
                <a href="aggiungi_immobile.php" class="reset-btn">Aggiungi Immobile</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer>
    <div class="footer-content">
        <div class="footer-column">
            <h3>Contatti</h3>
            <p>Email: info@agenziaimmobiliare.it</p>
            <p>Telefono: 0123 456789</p>
        </div>
        <div class="footer-column">
            <h3>Seguici</h3>
            <div class="social-media">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </div>
    <div class="copyright">
        &copy; <?php echo date('Y'); ?> Agenzia Immobiliare. Tutti i diritti riservati.
    </div>
</footer>

<script>
function cambiaStato(id) {
    var nuovoStato = document.getElementById('stato-' + id).value;

    $.ajax({
        url: 'aggiorna_stato.php',
        type: 'POST',
        data: { id: id, stato: nuovoStato },
        success: function(response) {
            alert(response);
        },
        error: function() {
            alert('Errore durante l\'aggiornamento.');
        }
    });
}
</script>

</body>
</html>
