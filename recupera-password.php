<?php
session_start();

// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "immobiliare";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Inizializza variabili
$email = "";
$success = false;
$error = "";
$form_submitted = false;
$token_form_submitted = false;
$new_password_form_submitted = false;
$email_sent = false;
$token_valid = false;
$password_reset = false;

// Verifica se la tabella reset_password esiste, se no la crea
$check_table = $conn->query("SHOW TABLES LIKE 'reset_password'");
if ($check_table->num_rows == 0) {
    $sql_create_table = "CREATE TABLE reset_password (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        used TINYINT(1) DEFAULT 0
    )";
    
    if (!$conn->query($sql_create_table)) {
        die("Errore nella creazione della tabella: " . $conn->error);
    }
}

// Fase 1: L'utente richiede il reset della password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reset'])) {
    $form_submitted = true;
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido";
    } else {
        // Verifica se l'email esiste nel database
        $sql = "SELECT id FROM utenti WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Genera un token casuale
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Elimina eventuali token precedenti per questa email
            $sql_delete = "DELETE FROM reset_password WHERE email = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("s", $email);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // Inserisci il nuovo token
            $sql_insert = "INSERT INTO reset_password (email, token, expires_at) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $email, $token, $expires_at);
            
            if ($stmt_insert->execute()) {
                // Invia email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?token=" . $token;
                $subject = "Recupero Password - Immobiliare";
                $message = "Ciao,\n\n";
                $message .= "Hai richiesto il reset della password. Clicca sul link seguente per reimpostare la tua password:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "Il link è valido per un'ora.\n\n";
                $message .= "Se non hai richiesto questo reset, ignora questa email.\n\n";
                $message .= "Cordiali saluti,\nTeam Immobiliare";
                
                // Headers per migliorare la consegna dell'email
                $headers = "From: noreply@immobiliare-esempio.it\r\n";
                $headers .= "Reply-To: noreply@immobiliare-esempio.it\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                if (mail($email, $subject, $message, $headers)) {
                    $email_sent = true;
                    $success = true;
                } else {
                    // In ambiente di sviluppo locale, mail() potrebbe non funzionare
                    // Simuliamo l'invio riuscito per scopi di test
                    $email_sent = true;
                    $success = true;
                    
                    // Solo per debugging - Mostra il link (rimuovere in produzione)
                    $_SESSION['debug_reset_link'] = $reset_link;
                }
                
                $stmt_insert->close();
            } else {
                $error = "Errore nel sistema. Riprova più tardi.";
            }
        } else {
            // Non rivelare se l'email esiste o meno per motivi di sicurezza
            $email_sent = true;
            $success = true;
        }
        
        $stmt->close();
    }
}

// Fase 2: L'utente ha cliccato sul link di reset (ha il token)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $current_time = date('Y-m-d H:i:s');
    
    // Verifica se il token è valido e non scaduto
    $sql_check_token = "SELECT email, expires_at FROM reset_password 
                        WHERE token = ? AND used = 0 AND expires_at > ?";
    $stmt_check = $conn->prepare($sql_check_token);
    $stmt_check->bind_param("ss", $token, $current_time);
    $stmt_check->execute();
    $result_token = $stmt_check->get_result();
    
    if ($result_token->num_rows > 0) {
        $token_valid = true;
        $token_row = $result_token->fetch_assoc();
        $email = $token_row['email'];
    }
    
    $stmt_check->close();
}

// Fase 3: L'utente invia il form con la nuova password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token_form_submitted = true;
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $current_time = date('Y-m-d H:i:s');
    
    // Validazione della password più robusta
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
    
    if (!preg_match($password_pattern, $new_password)) {
        $error = "La password deve contenere almeno 8 caratteri, una lettera maiuscola e un numero";
    } elseif ($new_password !== $confirm_password) {
        $error = "Le password non corrispondono";
    } else {
        // Verifica se il token è valido e non scaduto
        $sql_check_token = "SELECT email FROM reset_password 
                           WHERE token = ? AND used = 0 AND expires_at > ?";
        $stmt_check = $conn->prepare($sql_check_token);
        $stmt_check->bind_param("ss", $token, $current_time);
        $stmt_check->execute();
        $token_result = $stmt_check->get_result();
        
        if ($token_result->num_rows > 0) {
            $token_row = $token_result->fetch_assoc();
            $email = $token_row['email'];
            
            // Aggiorna la password dell'utente
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update_pwd = "UPDATE utenti SET password = ? WHERE email = ?";
            $stmt_update = $conn->prepare($sql_update_pwd);
            $stmt_update->bind_param("ss", $hashed_password, $email);
            
            if ($stmt_update->execute()) {
                // Segna il token come utilizzato
                $sql_mark_used = "UPDATE reset_password SET used = 1 WHERE token = ?";
                $stmt_mark = $conn->prepare($sql_mark_used);
                $stmt_mark->bind_param("s", $token);
                $stmt_mark->execute();
                $stmt_mark->close();
                
                // Pulisci tutte le sessioni attive per questo utente (opzionale)
                // Qui puoi inserire il codice per invalidare le sessioni esistenti
                
                $password_reset = true;
                $success = true;
                $stmt_update->close();
            } else {
                $error = "Errore nell'aggiornamento della password";
            }
        } else {
            $error = "Token non valido o scaduto";
        }
        
        $stmt_check->close();
    }
}

// Pulizia dei token scaduti (buona pratica per la manutenzione del database)
$cleanup_query = "DELETE FROM reset_password WHERE expires_at < NOW() OR (used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY))";
$conn->query($cleanup_query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera Password - Immobiliare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .password-recovery-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .password-recovery-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .password-recovery-header i {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .success-message {
            text-align: center;
            padding: 2rem;
        }
        .success-message i {
            font-size: 3rem;
            color: #198754;
            margin-bottom: 1rem;
        }
        .password-strength {
            margin-top: 0.5rem;
            height: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .password-feedback {
            margin-top: 0.5rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-recovery-container">
            <!-- Header -->
            <div class="password-recovery-header">
                <i class="fas fa-key"></i>
                <h2>Recupero Password</h2>
                <?php if (!$token_valid && !$password_reset): ?>
                    <p class="text-muted">Inserisci l'email associata al tuo account per ricevere un link di reset password</p>
                <?php elseif ($token_valid && !$password_reset): ?>
                    <p class="text-muted">Inserisci la tua nuova password</p>
                <?php endif; ?>
            </div>
            
            <!-- Messaggi di errore/successo -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Form per richiedere il reset -->
            <?php if (!$token_valid && !$email_sent && !$password_reset): ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="request_reset" class="btn btn-primary">Invia link di reset</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Messaggio di conferma invio email -->
            <?php if ($email_sent && !$token_valid && !$password_reset): ?>
                <div class="success-message">
                    <i class="fas fa-envelope"></i>
                    <h3>Email inviata!</h3>
                    <p>Se esiste un account con questa email, riceverai un link per reimpostare la tua password.</p>
                    <p>Controlla la tua casella di posta (e la cartella spam).</p>
                    
                    <?php 
                    // Solo per debugging - Mostra il link (rimuovere in produzione)
                    if (isset($_SESSION['debug_reset_link'])): 
                    ?>
                        <div class="alert alert-info mt-3">
                            <p><strong>Debug (solo per sviluppo):</strong></p>
                            <p>Link di reset: <a href="<?php echo $_SESSION['debug_reset_link']; ?>"><?php echo $_SESSION['debug_reset_link']; ?></a></p>
                        </div>
                    <?php 
                        unset($_SESSION['debug_reset_link']);
                    endif; 
                    ?>
                    
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-outline-secondary">Torna al login</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Form per impostare la nuova password -->
            <?php if ($token_valid && !$password_reset): ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nuova password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required autocomplete="new-password">
                        <div class="password-strength bg-light mt-2"></div>
                        <div class="password-feedback">La password deve contenere almeno 8 caratteri, una lettera maiuscola e un numero.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Conferma password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <div id="passwordMatch" class="form-text"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="showPassword">
                        <label class="form-check-label" for="showPassword">Mostra password</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="reset_password" class="btn btn-primary" id="submitBtn">Reimposta password</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Messaggio di conferma reset password -->
            <?php if ($password_reset): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Password reimpostata!</h3>
                    <p>La tua password è stata aggiornata con successo.</p>
                    <p>Ora puoi accedere al tuo account con la nuova password.</p>
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-primary">Vai al login</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 text-center">
                <p>
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Torna al login
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mostra/nascondi password
        const showPasswordCheckbox = document.getElementById('showPassword');
        const passwordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (showPasswordCheckbox && passwordInput && confirmPasswordInput) {
            showPasswordCheckbox.addEventListener('change', function() {
                const type = this.checked ? 'text' : 'password';
                passwordInput.type = type;
                confirmPasswordInput.type = type;
            });
        }
        
        // Controllo password in tempo reale
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
        }
        
        // Controllo conferma password
        if (confirmPasswordInput && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const matchDiv = document.getElementById('passwordMatch');
                if (this.value === '') {
                    matchDiv.textContent = '';
                    matchDiv.className = 'form-text';
                } else if (this.value === passwordInput.value) {
                    matchDiv.textContent = 'Le password corrispondono';
                    matchDiv.className = 'form-text text-success';
                } else {
                    matchDiv.textContent = 'Le password non corrispondono';
                    matchDiv.className = 'form-text text-danger';
                }
            });
        }
        
        // Funzione per controllare la robustezza della password
        function checkPasswordStrength(password) {
            const strengthBar = document.querySelector('.password-strength');
            const feedback = document.querySelector('.password-feedback');
            
            if (!strengthBar || !feedback) return;
            
            // Regole di validazione
            const hasLowerCase = /[a-z]/.test(password);
            const hasUpperCase = /[A-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const isLongEnough = password.length >= 8;
            
            // Calcola il punteggio
            let strength = 0;
            if (hasLowerCase) strength += 1;
            if (hasUpperCase) strength += 1;
            if (hasNumber) strength += 1;
            if (hasSpecialChar) strength += 1;
            if (isLongEnough) strength += 1;
            
            // Aggiorna la UI
            let feedbackText = '';
            let barColor = '';
            
            switch(strength) {
                case 0:
                case 1:
                    feedbackText = 'Password molto debole';
                    barColor = '#dc3545'; // rosso
                    strengthBar.style.width = '20%';
                    break;
                case 2:
                    feedbackText = 'Password debole';
                    barColor = '#ffc107'; // giallo
                    strengthBar.style.width = '40%';
                    break;
                case 3:
                    feedbackText = 'Password media';
                    barColor = '#fd7e14'; // arancione
                    strengthBar.style.width = '60%';
                    break;
                case 4:
                    feedbackText = 'Password buona';
                    barColor = '#20c997'; // teal
                    strengthBar.style.width = '80%';
                    break;
                case 5:
                    feedbackText = 'Password ottima';
                    barColor = '#198754'; // verde
                    strengthBar.style.width = '100%';
                    break;
            }
            
            strengthBar.style.backgroundColor = barColor;
            
            // Aggiungi consigli
            let requiredChanges = [];
            if (!isLongEnough) requiredChanges.push('almeno 8 caratteri');
            if (!hasLowerCase) requiredChanges.push('almeno una lettera minuscola');
            if (!hasUpperCase) requiredChanges.push('almeno una lettera maiuscola');
            if (!hasNumber) requiredChanges.push('almeno un numero');
            if (!hasSpecialChar) requiredChanges.push('almeno un carattere speciale (!@#$%^&*(),.?":{}|<>)');
            
            if (requiredChanges.length > 0) {
                feedbackText += ': aggiungi ' + requiredChanges.join(', ');
            }
            
            feedback.textContent = feedbackText;
        }
    });
    </script>
</body>
</html>

<?php
// Chiudi la connessione
$conn->close();
?>