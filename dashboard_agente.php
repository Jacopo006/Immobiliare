<?php
session_start();
include 'config.php';

// Debug per vedere se le variabili di sessione sono impostate
error_log("Dashboard - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non impostato'));
error_log("Dashboard - user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'non impostato'));

// Verifica che l'utente sia un agente immobiliare
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'agente') {
    error_log("Accesso non autorizzato alla dashboard agente - reindirizzamento al login");
    header('Location: login_agente.php');
    exit();
}

$msg = '';
$error = '';

// Gestione eliminazione immobile
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_immobile = $_GET['delete'];
    $id_agente = $_SESSION['user_id'];
    
    // Verifica che l'immobile appartenga all'agente
    $check_query = "SELECT * FROM immobili WHERE id = $id_immobile AND agente_id = $id_agente";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        // Controlla se ci sono transazioni legate all'immobile
        $check_trans = "SELECT * FROM transazioni WHERE id_immobile = $id_immobile";
        $trans_result = $conn->query($check_trans);
        
        if ($trans_result->num_rows > 0) {
            $error = "Non è possibile eliminare l'immobile perché esistono transazioni associate.";
        } else {
            // Procedi con l'eliminazione
            $delete_query = "DELETE FROM immobili WHERE id = $id_immobile AND agente_id = $id_agente";
            if ($conn->query($delete_query) === TRUE) {
                $msg = "Immobile eliminato con successo.";
            } else {
                $error = "Errore durante l'eliminazione: " . $conn->error;
            }
        }
    } else {
        $error = "Non hai i permessi per eliminare questo immobile.";
    }
}

// Gestione aggiornamento stato immobile
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id_immobile = $_GET['toggle_status'];
    $id_agente = $_SESSION['user_id'];
    
    // Verifica che l'immobile appartenga all'agente
    $check_query = "SELECT stato FROM immobili WHERE id = $id_immobile AND agente_id = $id_agente";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $nuovo_stato = ($row['stato'] == 'disponibile') ? 'venduto' : 'disponibile';
        
        $update_query = "UPDATE immobili SET stato = '$nuovo_stato' WHERE id = $id_immobile AND agente_id = $id_agente";
        if ($conn->query($update_query) === TRUE) {
            $msg = "Stato dell'immobile aggiornato a '$nuovo_stato'.";
        } else {
            $error = "Errore durante l'aggiornamento dello stato: " . $conn->error;
        }
    } else {
        $error = "Non hai i permessi per modificare questo immobile.";
    }
}

// Recupera gli immobili dell'agente
$id_agente = $_SESSION['user_id'];
$sql = "SELECT i.id, i.nome, i.prezzo, i.metri_quadri, i.stanze, i.bagni, i.citta, 
               i.stato, i.data_inserimento, c.nome AS categoria 
        FROM immobili i
        LEFT JOIN categorie c ON i.categoria_id = c.id
        WHERE i.agente_id = $id_agente
        ORDER BY i.data_inserimento DESC";
$result = $conn->query($sql);

// Recupera le categorie per il form di creazione
$sql_categorie = "SELECT id, nome FROM categorie";
$result_categorie = $conn->query($sql_categorie);
$categorie = [];
if ($result_categorie) {
    while ($cat = $result_categorie->fetch_assoc()) {
        $categorie[] = $cat;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Immobili - Area Agenti</title>
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard_agente.css">
</head>
<body>
    <!-- Header con menu dinamico basato sul login -->
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
                <li><a href="contatti.php"><i class="fas fa-envelope"></i> Contatti</a></li>
                <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                <li class="user-menu">
                    <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="dashboard_agente.php" class="active"><i class="fas fa-cogs"></i> Gestione Immobili</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Banner Principale -->
    <section id="banner" class="banner-small">
        <div class="banner-content">
            <h1>Gestione Immobili</h1>
            <p>Area riservata agli agenti immobiliari</p>
        </div>
    </section>

    <!-- Contenuto Principale -->
    <div class="dashboard-container">
        <div class="sidebar">
            <ul>
                <li><a href="#miei-immobili" class="active"><i class="fas fa-building"></i> I Miei Immobili</a></li>
                <li><a href="inserisci_immobile.php"></i> Nuovo Immobile</a>
                <li><a href="chat_agente.php"><i class="fas fa-envelope"></i> Richieste Ricevute</a></li>
                <li><a href="business_plan.php"><i class="fas fa-file-invoice-dollar"></i> Business Plan</a></li>
            </ul>
        </div>

        <div class="main-content">
            <?php if($msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <section id="miei-immobili" class="card">
                <div class="card-header">
                    <h2><i class="fas fa-building"></i> I Miei Immobili</h2>
                    <a href="inserisci_immobile.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuovo Immobile</a>
                </div>
                
                <div class="card-body">
                    <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Città</th>
                                    <th>Prezzo (€)</th>
                                    <th>Stato</th>
                                    <th>Data Inserimento</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                                    <td><?php echo htmlspecialchars($row['citta']); ?></td>
                                    <td><?php echo number_format($row['prezzo'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['stato']; ?>">
                                            <?php echo ucfirst($row['stato']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_inserimento'])); ?></td>
                                    <td class="actions">
                                        <a href="immobile.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view" title="Visualizza"><i class="fas fa-eye"></i></a>
                                        <a href="modifica_immobile.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit" title="Modifica"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

                        </div>
                    </form>
                </div>
            </section>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section about">
                <h3>Immobiliare XYZ</h3>
                <p>La tua agenzia immobiliare di fiducia. Trova la casa dei tuoi sogni con noi!</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-section links">
                <h3>Link Utili</h3>
                <ul>
                    <li><a href="home-page.php">Home</a></li>
                    <li><a href="immobili.php">Immobili</a></li>
                    <li><a href="contatti.php">Contatti</a></li>
                    <li><a href="privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contattaci</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</p>
                <p><i class="fas fa-phone"></i> +39 02 1234567</p>
                <p><i class="fas fa-envelope"></i> info@immobiliarexyz.it</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Immobiliare XYZ. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <!-- JavaScript per la gestione dei modali e altre funzionalità -->
    <script>
        // Gestione dei modali
        document.addEventListener('DOMContentLoaded', function() {
            // Ottieni tutti i bottoni di apertura modale
            var modalButtons = document.querySelectorAll('[data-toggle="modal"]');
            
            // Aggiungi l'evento click a ciascun bottone
            modalButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var targetModal = document.querySelector(this.getAttribute('data-target'));
                    if (targetModal) {
                        targetModal.style.display = 'block';
                    }
                });
            });
            
            // Ottieni tutti i bottoni di chiusura modale
            var closeButtons = document.querySelectorAll('.close, .close-modal');
            
            // Aggiungi l'evento click a ciascun bottone di chiusura
            closeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var modal = this.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                });
            });
            
            // Chiudi il modale quando si fa clic all'esterno
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });
            
            // Gestione del menu mobile
            var userMenu = document.querySelector('.user-menu');
            if (userMenu) {
                userMenu.addEventListener('click', function(e) {
                    this.classList.toggle('active');
                    e.stopPropagation();
                });
            }
            
            // Chiudi il menu quando si fa clic all'esterno
            document.addEventListener('click', function() {
                if (userMenu) {
                    userMenu.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>