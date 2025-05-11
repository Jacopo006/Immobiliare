<?php
session_start();
include 'config.php'; // Includi il file di connessione

// Verificare il tipo di utente e assegnarlo alla sessione se non è già definito
if (isset($_SESSION['user_id']) && !isset($_SESSION['user_type'])) {
    // Verifica se è un agente
    $user_id = $_SESSION['user_id'];
    $sql_check_agent = "SELECT id FROM agenti_immobiliari WHERE id = ?";
    $stmt = $conn->prepare($sql_check_agent);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_agent = $stmt->get_result();
    
    if ($result_agent->num_rows > 0) {
        $_SESSION['user_type'] = 'agente';
    } else {
        // Verifica se è un utente normale
        $sql_check_user = "SELECT id FROM utenti WHERE id = ?";
        $stmt = $conn->prepare($sql_check_user);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result_user = $stmt->get_result();
        
        if ($result_user->num_rows > 0) {
            $_SESSION['user_type'] = 'utente';
        }
    }
}

// Query per ottenere i primi 3 immobili in evidenza
$sql_immobili = "SELECT id, nome, descrizione, prezzo, immagine FROM immobili WHERE stato = 'disponibile' LIMIT 3";
$result_immobili = $conn->query($sql_immobili);

// Query per ottenere gli agenti immobiliari
$sql_agenti = "SELECT id, nome, cognome, email, telefono FROM agenti_immobiliari";
$result_agenti = $conn->query($sql_agenti);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Immobiliare - Trova la casa dei tuoi sogni</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header con menu dinamico basato sul login -->
<header>
    <nav>
    <!-- Icona casetta cliccabile intelligente: porta a login_agente.php o profilo-agente.php -->
        <?php 
        // Link intelligente: se è un agente loggato, va al suo profilo, altrimenti alla pagina di login degli agenti
        $agent_link = (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'agente') ? 'profilo-agente.php' : 'login_agente.php';
        ?>
        <a href="<?php echo $agent_link; ?>" style="text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#3498db" viewBox="0 0 24 24">
                <path d="M3 13h18v8H3v-8zm2 2v4h2v-4H5zm4 0v4h2v-4H9zm4 0v4h2v-4h-2zm4 0v4h2v-4h-2zM3 3h18v8H3V3zm2 2v4h2V5H5zm4 0v4h2V5H9zm4 0v4h2V5h-2zm4 0v4h2V5h-2z"/>
            </svg>
        </a>

        <ul>
            <li><a href="home-page.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="immobili.php"><i class="fas fa-building"></i> Immobili</a></li>
            <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
            <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li class="user-menu">
                    <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i></a>
                    <ul class="dropdown-menu">
                        <?php 
                        // Definisci il percorso del profilo in base al tipo di utente
                        $profile_path = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'agente' ? 'profilo-agente.php' : 'profilo-utente.php';
                        ?>
                        <li><a href="<?php echo $profile_path; ?>"><i class="fas fa-id-card"></i> Profilo</a></li>
                        <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'utente'): ?>
                            <li><a href="preferiti.php"><i class="fas fa-heart"></i> Preferiti</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="login_utente.php"><i class="fas fa-sign-in-alt"></i> Accedi</a></li>
                <li><a href="registrazione_utente.php"><i class="fas fa-user-plus"></i> Registrati</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
    <!-- Banner -->
    <section id="banner">
        <div class="banner-content">
            <h1>
                <?php 
                    if(isset($_SESSION['user_id'])) {
                        echo "Benvenuto " . htmlspecialchars($_SESSION['user_name']);
                    } else {
                        echo "Benvenuto Ospite";
                    }
                ?>
            </h1>
            <p>Le migliori opportunità immobiliari in tutta Italia selezionate per te</p>
            <a href="#immobili" class="cta-button">Scopri le Nostre Offerte</a>
        </div>
    </section>
    <!-- Immobili in Evidenza -->
    <section id="immobili">
        <h2>Immobili in Evidenza</h2>

        <div class="immobili-container">
            <?php
            if ($result_immobili->num_rows > 0) {
                while($row = $result_immobili->fetch_assoc()) {
                    echo "<div class='immobile'>";
                    echo "<img src='" . $row['immagine'] . "' alt='" . $row['nome'] . "'>";
                    echo "<div class='immobile-info'>";
                    echo "<h3>" . $row['nome'] . "</h3>";
                    echo "<p>" . substr($row['descrizione'], 0, 100) . "...</p>";
                    echo "<p class='prezzo'>" . number_format($row['prezzo'], 0, ',', '.') . " €</p>";
                    echo "<a href='immobile.php?id=" . $row['id'] . "' class='btn'>Vedi Dettagli</a>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<p>Nessun immobile disponibile al momento.</p>";
            }
            ?>
        </div>
    </section>

    <!-- Agenti Immobiliari -->
    <section id="agenti">
        <h2>I Nostri Esperti Immobiliari</h2>
        <div class="agenti-container">
            <?php
            if ($result_agenti->num_rows > 0) {
                while($row = $result_agenti->fetch_assoc()) {
                    echo "<div class='agente'>";
                    echo "<img src='img/agenti/avatar-" . $row['id'] . ".jpg' alt='" . $row['nome'] . " " . $row['cognome'] . "'>";
                    echo "<h3>" . $row['nome'] . " " . $row['cognome'] . "</h3>";
                    echo "<p><i class='fas fa-envelope'></i> " . $row['email'] . "</p>";
                    if($row['telefono']) {
                        echo "<p><i class='fas fa-phone'></i> " . $row['telefono'] . "</p>";
                    }
                    echo "<a href='agente.php?id=" . $row['id'] . "' class='btn'>Contatta</a>";
                    echo "</div>";
                }
            } else {
                echo "<p>Nessun agente disponibile al momento.</p>";
            }
            ?>
        </div>
    </section>
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Chi Siamo</h3>
                <p>Immobiliare è la tua agenzia di fiducia con oltre 20 anni di esperienza nel settore immobiliare in tutta Italia.</p>
            </div>
            <div class="footer-column">
                <h3>Link Utili</h3>
                <ul>
                    <li><a href="immobili.php">Ricerca Immobili</a></li>
                    <li><a href="servizi.php">I Nostri Servizi</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="contatti.php">Contattaci</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contatti</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-phone"></i> +39 02 1234567</p>
                <p><i class="fas fa-envelope"></i> info@immobiliare.it</p>
                <div class="social-media">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

</body>
</html>

<?php
$conn->close(); // Chiudi la connessione
?>