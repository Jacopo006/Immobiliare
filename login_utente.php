<?php
// login.php - Pagina di accesso per gli utenti
session_start();

// Se l'utente è già loggato, reindirizza alla home
if(isset($_SESSION['user_id'])) {
    header("Location: home-page.php");
    exit();
}

include 'config.php'; // Include il file di connessione al database
$error = ''; // Variabile per memorizzare eventuali messaggi di errore





if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ottieni i dati dal form
    $email = trim($_POST['email']);
    $password = $_POST['password'];




    
    if(empty($email) || empty($password)) {
        $error = "Inserisci email e password";
    } else {
        // Verifica se l'utente esiste nel database 
        $sql = "SELECT id, nome, cognome, email, password FROM utenti WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // Utente trovato
            $user = $result->fetch_assoc();
            
            // Verifica la password
            if (password_verify($password, $user['password'])) {
                // Password corretta, crea la sessione
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'] . ' ' . $user['cognome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = 'utente';
                
                // Reindirizza alla home
                header("Location: home-page.php");
                exit();
            } else {
                $error = "Credenziali errate";
            }
        } else {
            // Verifica se è un agente immobiliare
            
            
            
            
            $sql = "SELECT id, nome, cognome, email, password FROM agenti_immobiliari WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();





            
            if ($result->num_rows == 1) {
                // Agente trovato
                $agent = $result->fetch_assoc();
                
                // Verifica la password (assumendo che sia memorizzata in chiaro, ma questo andrebbe cambiato in produzione)
                if (password_verify($password, $agent['password'])) {
                    // Password corretta, crea la sessione
                    $_SESSION['user_id'] = $agent['id'];
                    $_SESSION['user_name'] = $agent['nome'] . ' ' . $agent['cognome'];
                    $_SESSION['user_email'] = $agent['email'];
                    $_SESSION['user_type'] = 'agente';
                    
                    // Reindirizza alla dashboard agenti
                    header("Location: agent_dashboard.php");
                    exit();
                } else {
                    $error = "Credenziali errate";
                }
            } else {
                $error = "Utente non trovato";
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
    <title>Accedi - Immobiliare</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
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

    <!-- Contenuto Login -->
    <div class="login-container">
        <div class="form-header">
            <i class="fas fa-user-circle form-icon"></i>
            <h2>Accedi al tuo account</h2>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>



        <!---->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>
        <!---->

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="submit-btn">Accedi</button>
        </form>
        
        <div class="register-link">
            Non hai un account? <a href="registrazione_utente.php">Registrati</a>
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
