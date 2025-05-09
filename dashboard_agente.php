<?php
session_start();
include 'config.php';

// Verifica che l'utente sia un agente immobiliare
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'agente') {
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
while ($cat = $result_categorie->fetch_assoc()) {
    $categorie[] = $cat;
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
                        <li><a href="profile.php"><i class="fas fa-id-card"></i> Profilo</a></li>
                        <li><a href="gestione_immobili.php" class="active"><i class="fas fa-cogs"></i> Gestione Immobili</a></li>
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
                <li><a href="#nuovo-immobile"><i class="fas fa-plus-circle"></i> Aggiungi Immobile</a></li>
                <li><a href="#statistiche"><i class="fas fa-chart-line"></i> Statistiche</a></li>
                <li><a href="#contatti-ricevuti"><i class="fas fa-envelope"></i> Richieste Ricevute</a></li>
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
                    <a href="#nuovo-immobile" class="btn btn-primary"><i class="fas fa-plus"></i> Nuovo Immobile</a>
                </div>
                
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
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
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <h3>Nessun immobile inserito</h3>
                        <p>Non hai ancora inserito immobili da gestire. Inizia aggiungendo il tuo primo immobile!</p>
                        <a href="#nuovo-immobile" class="btn btn-primary"><i class="fas fa-plus"></i> Aggiungi Immobile</a>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="nuovo-immobile" class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Aggiungi Nuovo Immobile</h2>
                </div>
                
                <div class="card-body">
                    <form action="inserisci_immobile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nome"><i class="fas fa-tag"></i> Nome Immobile <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="categoria"><i class="fas fa-list"></i> Categoria <span class="required">*</span></label>
                                <select id="categoria" name="categoria_id" required>
                                    <option value="">Seleziona una categoria</option>
                                    <?php foreach($categorie as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="prezzo"><i class="fas fa-euro-sign"></i> Prezzo <span class="required">*</span></label>
                                <input type="number" id="prezzo" name="prezzo" min="0" step="1000" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="stato"><i class="fas fa-info-circle"></i> Stato <span class="required">*</span></label>
                                <select id="stato" name="stato" required>
                                    <option value="disponibile">Disponibile</option>
                                    <option value="venduto">Venduto</option>
                                    <option value="affittato">Affittato</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="metri_quadri"><i class="fas fa-vector-square"></i> Metri Quadri <span class="required">*</span></label>
                                <input type="number" id="metri_quadri" name="metri_quadri" min="1" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="stanze"><i class="fas fa-door-open"></i> Stanze <span class="required">*</span></label>
                                <input type="number" id="stanze" name="stanze" min="1" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bagni"><i class="fas fa-bath"></i> Bagni <span class="required">*</span></label>
                                <input type="number" id="bagni" name="bagni" min="1" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="citta"><i class="fas fa-map-marker-alt"></i> Città <span class="required">*</span></label>
                                <input type="text" id="citta" name="citta" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="provincia"><i class="fas fa-map"></i> Provincia <span class="required">*</span></label>
                                <input type="text" id="provincia" name="provincia" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descrizione"><i class="fas fa-file-alt"></i> Descrizione <span class="required">*</span></label>
                            <textarea id="descrizione" name="descrizione" rows="5" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="immagine"><i class="fas fa-image"></i> Immagine Principale <span class="required">*</span></label>
                            <input type="file" id="immagine" name="immagine" accept="image/*" required>
                            <small>Dimensione massima: 5MB. Formati supportati: JPG, PNG, GIF</small>
                        </div>

                        <div class="form-actions">
                            <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salva Immobile</button>
                        </div>
                    </form>
                </div>
            </section>

            <section id="statistiche" class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Statistiche</h2>
                </div>
                
                <div class="card-body">
                    <div class="stats-container">
                        <?php
                        // Statistiche immobili
                        $stats_query = "SELECT 
                                            COUNT(*) as totale,
                                            SUM(CASE WHEN stato = 'disponibile' THEN 1 ELSE 0 END) as disponibili,
                                            SUM(CASE WHEN stato = 'venduto' THEN 1 ELSE 0 END) as venduti,
                                            SUM(CASE WHEN stato = 'affittato' THEN 1 ELSE 0 END) as affittati,
                                            AVG(prezzo) as prezzo_medio
                                        FROM immobili 
                                        WHERE agente_id = $id_agente";
                        $stats_result = $conn->query($stats_query);
                        $stats = $stats_result->fetch_assoc();
                        ?>
                        
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Totale Immobili</h3>
                                <p class="stat-value"><?php echo $stats['totale']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Immobili Disponibili</h3>
                                <p class="stat-value"><?php echo $stats['disponibili']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Immobili Venduti</h3>
                                <p class="stat-value"><?php echo $stats['venduti']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Immobili Affittati</h3>
                                <p class="stat-value"><?php echo $stats['affittati']; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-card wide">
                            <div class="stat-icon purple">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Prezzo Medio</h3>
                                <p class="stat-value"><?php echo number_format($stats['prezzo_medio'], 0, ',', '.'); ?> €</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="contatti-ricevuti" class="card">
                <div class="card-header">
                    <h2><i class="fas fa-envelope"></i> Richieste di Contatto</h2>
                </div>
                
                <div class="card-body">
                    <?php
                    // Recupera i contatti relativi agli immobili dell'agente
                    $contatti_query = "SELECT c.id, c.nome, c.email, c.messaggio, c.data_invio, i.nome AS immobile_nome
                                      FROM contatti c
                                      JOIN immobili i ON c.id_immobile = i.id
                                      WHERE i.agente_id = $id_agente
                                      ORDER BY c.data_invio DESC";
                    $contatti_result = $conn->query($contatti_query);
                    
                    if ($contatti_result && $contatti_result->num_rows > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Immobile</th>
                                    <th>Messaggio</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($contatto = $contatti_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($contatto['data_invio'])); ?></td>
                                    <td><?php echo htmlspecialchars($contatto['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($contatto['email']); ?></td>
                                    <td><?php echo htmlspecialchars($contatto['immobile_nome']); ?></td>
                                    <td><?php echo substr(htmlspecialchars($contatto['messaggio']), 0, 50) . (strlen($contatto['messaggio']) > 50 ? '...' : ''); ?></td>
                                    <td class="actions">
                                        <a href="mailto:<?php echo $contatto['email']; ?>" class="btn-action btn-email" title="Rispondi"><i class="fas fa-reply"></i></a>
                                        <a href="visualizza_contatto.php?id=<?php echo $contatto['id']; ?>" class="btn-action btn-view" title="Visualizza"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <h3>Nessuna richiesta ricevuta</h3>
                        <p>Non hai ancora ricevuto richieste di contatto per i tuoi immobili.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="business-plan" class="card">
                <div class="card-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Business Plan</h2>
                </div>
                
                <div class="card-body">
                    <div class="business-plan-intro">
                        <p>Crea e gestisci il tuo business plan immobiliare personalizzato. Definisci obiettivi, proiezioni e strategie per massimizzare le tue vendite.</p>
                        <div class="business-plan-actions">
                            <a href="business_plan.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> Accedi al Business Plan</a>
                        </div>
                    </div>
                    
                    <div class="business-plan-features">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3>Definisci Obiettivi</h3>
                            <p>Imposta obiettivi di vendita e di guadagno a breve, medio e lungo termine.</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Analisi del Mercato</h3>
                            <p>Monitora l'andamento del mercato immobiliare nella tua zona.</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <h3>Previsioni Finanziarie</h3>
                            <p>Crea proiezioni finanziarie basate sui tuoi immobili e sul mercato.</p>
                        </div>
                    </div>
                </div>
            </section>
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

    <script>
        // Funzione per attivare la scheda corretta in base all'hash URL
        function activateTab() {
            const hash = window.location.hash || '#miei-immobili';
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.classList.remove('active');
                if(link.getAttribute('href') === hash) {
                    link.classList.add('active');
                }
            });
            
            // Nascondi tutte le sezioni tranne quella attiva
            document.querySelectorAll('.card').forEach(section => {
                section.style.display = 'none';
            });
            
            // Mostra la sezione attiva
            const activeSection = document.querySelector(hash);
            if(activeSection) {
                activeSection.style.display = 'block';
            }
        }

        // Esegui all'avvio e al cambio di hash
        window.addEventListener('DOMContentLoaded', activateTab);
        window.addEventListener('hashchange', activateTab);
    </script>
</body>
</html>

<?php
$conn->close(); // Chiudi la connessione
?>