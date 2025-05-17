<?php
session_start();

if (!isset($_SESSION['success_message']) || !isset($_SESSION['last_acquisto_id'])) {
    header('Location: miei_acquisti.php');
    exit();
}

include 'config.php';

$id_acquisto = (int)$_SESSION['last_acquisto_id'];
unset($_SESSION['success_message'], $_SESSION['last_acquisto_id']);

$sql = "SELECT a.*, i.nome AS nome_immobile, i.citta, i.provincia, i.prezzo, i.immagine_principale
        FROM acquisti a
        JOIN immobili i ON a.id_immobile = i.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_acquisto);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: miei_acquisti.php');
    exit();
}

$acquisto = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riepilogo Acquisto - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="stylesheet" href="style_immobile_dettaglio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
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
                <li><a href="miei_acquisti.php"><i class="fas fa-shopping-cart"></i> I miei acquisti</a></li>
            </ul>
        </nav>
    </header>

    <div class="breadcrumb">
        <div class="container">
            <a href="home-page.php">Home</a> &gt;
            <a href="miei_acquisti.php">I miei acquisti</a> &gt;
            <span>Riepilogo Acquisto</span>
        </div>
    </div>

    <div class="container" style="max-width: 1000px; padding: 40px 20px;">
        <h1 style="color: #2c3e50; text-align: center;">Riepilogo Acquisto</h1>

        <div class="info-section" style="margin-top: 30px;">
            <h2>Dettagli Immobile</h2>
            <div style="display: flex; gap: 30px; align-items: center; flex-wrap: wrap;">
                <div>
                    <img src="<?php echo $acquisto['immagine_principale'] ?? 'img/default.jpg'; ?>" alt="Immagine Immobile" style="width: 300px; border-radius: 12px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                </div>
                <div>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($acquisto['nome_immobile']); ?></p>
                    <p><strong>Località:</strong> <?php echo $acquisto['citta'] . ", " . $acquisto['provincia']; ?></p>
                    <p><strong>Prezzo di listino:</strong> <?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?> €</p>
                    <p><strong>Tipo:</strong> <?php echo ucfirst($acquisto['tipo_acquisto']); ?></p>
                    <p><strong>Modalità di pagamento:</strong> <?php echo ucfirst(str_replace('_', ' ', $acquisto['modalita_pagamento'])); ?></p>
                    <?php if ($acquisto['modalita_pagamento'] === 'rate'): ?>
                        <p><strong>Piano rate:</strong> <?php echo $acquisto['piano_rate']; ?> mesi</p>
                    <?php endif; ?>
                    <p><strong>Metodo di pagamento:</strong> <?php echo ucfirst($acquisto['metodo_pagamento']); ?></p>
                    <p><strong>Acconto versato:</strong> <?php echo number_format($acquisto['acconto'], 2, ',', '.'); ?> €</p>
                    <p><strong>Importo totale:</strong> <?php echo number_format($acquisto['importo_totale'], 2, ',', '.'); ?> €</p>
                    <p><strong>Stato pagamento:</strong> <span style="color: <?php echo ($acquisto['stato_pagamento'] === 'pagato') ? 'green' : '#e67e22'; ?>; font-weight: bold;">
                        <?php echo ucfirst($acquisto['stato_pagamento']); ?></span></p>
                </div>
            </div>
        </div>

        <?php if (!empty($acquisto['note'])): ?>
        <div class="info-section">
            <h2>Note Aggiuntive</h2>
            <p><?php echo nl2br(htmlspecialchars($acquisto['note'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="view-all" style="margin-top: 40px;">
            <a href="miei_acquisti.php" class="btn-view-all">Torna ai miei acquisti</a>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Immobiliare</h3>
                <p>La tua soluzione per la ricerca di immobili in Italia.</p>
            </div>
            <div class="footer-column">
                <h3>Contatti</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-envelope"></i> info@immobiliare.it</p>
            </div>
            <div class="footer-column">
                <h3>Seguici</h3>
                <div class="social-media">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>
</body>
</html>
