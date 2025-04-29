<?php
// register.php - Pagina di registrazione per i nuovi utenti
session_start();

// Se l'utente è già loggato, reindirizza alla home
if(isset($_SESSION['user_id'])) {
    header("Location: home-page.php");
    exit();
}

include 'config.php'; // Include il file di connessione al database
$error = ''; // Variabile per memorizzare eventuali messaggi di errore
$success = ''; // Variabile per memorizzare messaggi di successo

// Verifica se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ottieni i dati dal form
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validazione basilare
    if(empty($nome) || empty($cognome) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tutti i campi obbligatori devono essere compilati";
    } elseif($password != $confirm_password) {
        $error = "Le password non corrispondono";
    } elseif(strlen($password) < 8) {
        $error = "La password deve contenere almeno 8 caratteri";
    } else {
        // Controlla se l'email esiste già nella tabella utenti
        $sql = "SELECT id FROM utenti WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Questa email è già registrata";
        } else {
            // Controlla se l'email esiste già nella tabella agenti_immobiliari
            $sql = "SELECT id FROM agenti_immobiliari WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Questa email è già registrata";
            } else {
                // Crea un nuovo utente
                // Creiamo prima la tabella utenti se non esiste
                $sql = "CREATE TABLE IF NOT EXISTS utenti (
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    cognome VARCHAR(100) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    telefono VARCHAR(15),
                    password VARCHAR(255) NOT NULL,
                    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($sql) === TRUE) {
                    // Hash della password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Inserisci il nuovo utente
                    $sql = "INSERT INTO utenti (nome, cognome, email, telefono, password) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssss", $nome, $cognome, $email, $telefono, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = "Registrazione completata con successo! Ora puoi accedere.";
                        // Reindirizza alla pagina di login dopo 2 secondi
                        header("refresh:2;url=login.php");
                    } else {
                        $error = "Errore durante la registrazione: " . $stmt->error;
                    }
                } else {
                    $error = "Errore durante la creazione della tabella: " . $conn->error;
                }
            }
        }
    }
}

// Chiudi la connessione
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="registrazione.css">
</head>
<body>

    <!-- Header -->
    <header>
        <nav>
            <div class="logo">
                <img src="logo.png" alt="Logo Immobiliare">
            </div>
            <ul>
                <li><a href="home-page.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="immobili.php"><i class="fas fa-building"></i> Immobili</a></li>
                <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
            </ul>
        </nav>
    </header>

    <!-- Contenuto Registrazione -->
    <div class="register-container">
        <div class="form-header">
            <i class="fas fa-user-plus form-icon"></i>
            <h2>Crea un nuovo account</h2>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome"><i class="fas fa-user"></i> Nome *</label>
                    <input type="text" id="nome" name="nome" required value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="cognome"><i class="fas fa-user"></i> Cognome *</label>
                    <input type="text" id="cognome" name="cognome" required value="<?php echo isset($cognome) ? htmlspecialchars($cognome) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="telefono"><i class="fas fa-phone"></i> Telefono</label>
                <input type="tel" id="telefono" name="telefono" value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">La password deve contenere almeno 8 caratteri</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Conferma Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 30px;">
                <input type="checkbox" id="privacy" name="privacy" required style="width: auto; margin-right: 10px;">
                <label for="privacy" style="display: inline;">Accetto la <a href="privacy.php" style="color: #3498db;">Privacy Policy</a> e i <a href="terms.php" style="color: #3498db;">Termini di Servizio</a> *</label>
            </div>
            
            <button type="submit" class="submit-btn">Registrati</button>
        </form>
        
        <div class="login-link">
            Hai già un account? <a href="login_utente.php">Accedi</a>
        </div>
    </div>

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