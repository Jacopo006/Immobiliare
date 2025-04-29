<?php
session_start();
include 'config.php'; // Includi il file di connessione

$message = '';
$error = '';
$nome = $email = $telefono = $oggetto = $messaggio = '';

// Processo il form di contatto quando viene inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccoglie i dati dal form
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $oggetto = trim($_POST['oggetto'] ?? '');
    $messaggio = trim($_POST['messaggio'] ?? '');
    
    // Validazione avanzata
    if (empty($nome) || empty($email) || empty($messaggio)) {
        $error = "I campi Nome, Email e Messaggio sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Inserire un indirizzo email valido.";
    } elseif (strlen($messaggio) < 10) {
        $error = "Il messaggio deve contenere almeno 10 caratteri.";
    } else {
        // Utilizzo di prepared statements per prevenire SQL injection
        $stmt = $conn->prepare("INSERT INTO messaggi (nome, email, telefono, oggetto, messaggio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nome, $email, $telefono, $oggetto, $messaggio);
        
        if ($stmt->execute()) {
            $message = "Il tuo messaggio è stato inviato con successo. Ti contatteremo presto!";
            
            // Reset dei campi del form dopo invio riuscito
            $nome = $email = $telefono = $oggetto = $messaggio = '';
        } else {
            $error = "Si è verificato un errore durante l'invio del messaggio: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contatti - Immobiliare</title>
    <meta name="description" content="Contatta la nostra agenzia immobiliare per informazioni, visite o consulenze sui nostri immobili">
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="contatti.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">
                <a href="home-page.php" aria-label="Vai alla home page">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#3498db" viewBox="0 0 24 24">
                    <path d="M12 3l8 7h-3v7h-4v-5h-2v5H7v-7H4l8-7z"/>
                </svg>
                </a>
            </div>
            <ul>
                <li><a href="home-page.php"><i class="fas fa-home" aria-hidden="true"></i> Home</a></li>
                <li><a href="immobili.php"><i class="fas fa-building" aria-hidden="true"></i> Immobili</a></li>
                <li><a href="contatti.php" class="active"><i class="fas fa-envelope" aria-hidden="true"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle" aria-hidden="true"></i> FAQ</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="user-menu">
                        <a href="#" aria-haspopup="true"><i class="fas fa-user" aria-hidden="true"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down" aria-hidden="true"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fas fa-id-card" aria-hidden="true"></i> Profilo</a></li>
                            <?php if($_SESSION['user_type'] == 'utente'): ?>
                                <li><a href="preferiti.php"><i class="fas fa-heart" aria-hidden="true"></i> Preferiti</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="login_utente.php"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Accedi</a></li>
                    <li><a href="registrazione_utente.php"><i class="fas fa-user-plus" aria-hidden="true"></i> Registrati</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Banner piccolo -->
    <section id="banner" class="banner-small">
        <div class="banner-content">
            <h1>Contattaci</h1>
            <p>Siamo qui per aiutarti a trovare la casa dei tuoi sogni</p>
        </div>
    </section>

    <!-- Contenuto principale -->
    <div class="contatti-container">
        <?php if(!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="contatti-content">
            <!-- Form di contatto -->
            <div class="contatti-form">
                <h2>Inviaci un messaggio</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome"><i class="fas fa-user" aria-hidden="true"></i> Nome e Cognome<span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required 
                                   aria-required="true" placeholder="Es. Mario Rossi">
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope" aria-hidden="true"></i> Email<span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required 
                                   aria-required="true" placeholder="Es. mario.rossi@example.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono"><i class="fas fa-phone" aria-hidden="true"></i> Telefono</label>
                            <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>"
                                   placeholder="Es. 333 1234567">
                        </div>
                        <div class="form-group">
                            <label for="oggetto"><i class="fas fa-tag" aria-hidden="true"></i> Oggetto</label>
                            <select id="oggetto" name="oggetto">
                                <option value="Informazioni generali" <?php echo ($oggetto == 'Informazioni generali') ? 'selected' : ''; ?>>Informazioni generali</option>
                                <option value="Richiesta visita" <?php echo ($oggetto == 'Richiesta visita') ? 'selected' : ''; ?>>Richiesta visita</option>
                                <option value="Vendita immobile" <?php echo ($oggetto == 'Vendita immobile') ? 'selected' : ''; ?>>Vendita immobile</option>
                                <option value="Affitto immobile" <?php echo ($oggetto == 'Affitto immobile') ? 'selected' : ''; ?>>Affitto immobile</option>
                                <option value="Altro" <?php echo ($oggetto == 'Altro') ? 'selected' : ''; ?>>Altro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="messaggio"><i class="fas fa-comment" aria-hidden="true"></i> Messaggio<span class="required">*</span></label>
                        <textarea id="messaggio" name="messaggio" rows="5" required aria-required="true" 
                                  placeholder="Scrivi qui il tuo messaggio..."><?php echo htmlspecialchars($messaggio); ?></textarea>
                        <small class="form-text">Minimo 10 caratteri</small>
                    </div>
                    
                    <div class="form-group form-privacy">
                        <input type="checkbox" id="privacy" name="privacy" required aria-required="true">
                        <label for="privacy">Ho letto e accetto la <a href="privacy.php">Privacy Policy</a><span class="required">*</span></label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="submit-btn"><i class="fas fa-paper-plane" aria-hidden="true"></i> Invia messaggio</button>
                    </div>
                </form>
            </div>
            
            <!-- Informazioni di contatto -->
            <div class="contatti-info">
                <h2>I nostri contatti</h2>
                
                <div class="info-item">
                    <div class="info-title"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Indirizzo</div>
                    <address>
                        Via Roma 123<br>
                        20123 Milano (MI)<br>
                        Italia
                    </address>
                </div>
                
                <div class="info-item">
                    <div class="info-title"><i class="fas fa-phone" aria-hidden="true"></i> Telefono</div>
                    <p><a href="tel:+390212345678">+39 02 1234567</a><br>
                       <a href="tel:+393339876543">+39 333 9876543</a></p>
                </div>
                
                <div class="info-item">
                    <div class="info-title"><i class="fas fa-envelope" aria-hidden="true"></i> Email</div>
                    <p><a href="mailto:info@immobiliare.it">info@immobiliare.it</a><br>
                       <a href="mailto:vendite@immobiliare.it">vendite@immobiliare.it</a></p>
                </div>
                
                <div class="info-item">
                    <div class="info-title"><i class="fas fa-clock" aria-hidden="true"></i> Orari di apertura</div>
                    <p>Lunedì - Venerdì: 9:00 - 18:00<br>
                    Sabato: 9:00 - 12:30<br>
                    Domenica: Chiuso</p>
                </div>
                
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                </div>
                
                <div class="mappa">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2798.2463292465584!2d9.18247231596658!3d45.46529974061027!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4786c6aec34636a1%3A0xab7f4e27101a2e19!2sVia%20Roma%2C%20Milano%20MI!5e0!3m2!1sit!2sit!4v1650029854843!5m2!1sit!2sit" 
                    allowfullscreen="" loading="lazy" title="Mappa della nostra sede"></iframe>
                </div>
            </div>
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
                <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-phone" aria-hidden="true"></i> <a href="tel:+390212345678">+39 02 1234567</a></p>
                <p><i class="fas fa-envelope" aria-hidden="true"></i> <a href="mailto:info@immobiliare.it">info@immobiliare.it</a></p>
                <div class="social-media">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Immobiliare. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <script>
    // Script per validazione client-side
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const nome = document.getElementById('nome');
            const email = document.getElementById('email');
            const messaggio = document.getElementById('messaggio');
            const privacy = document.getElementById('privacy');
            
            // Reset previous error states
            const errorElements = document.querySelectorAll('.error-input');
            errorElements.forEach(el => el.classList.remove('error-input'));
            
            // Validate required fields
            if (!nome.value.trim()) {
                nome.classList.add('error-input');
                isValid = false;
            }
            
            if (!email.value.trim()) {
                email.classList.add('error-input');
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                email.classList.add('error-input');
                isValid = false;
            }
            
            if (!messaggio.value.trim() || messaggio.value.trim().length < 10) {
                messaggio.classList.add('error-input');
                isValid = false;
            }
            
            if (!privacy.checked) {
                privacy.classList.add('error-input');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    });
    </script>

</body>
</html>

<?php
$conn->close(); // Chiudi la connessione
?>