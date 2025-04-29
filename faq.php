<?php
session_start();
include 'config.php'; // Includi il file di connessione
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Immobiliare</title>
    <meta name="description" content="Domande frequenti sull'acquisto, vendita e affitto di immobili. Tutto quello che devi sapere sui nostri servizi.">
    <link rel="stylesheet" href="style_home-page.css">
    <link rel="stylesheet" href="faq.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="contatti.php"><i class="fas fa-envelope" aria-hidden="true"></i> Contatti</a></li>
                <li><a href="faq.php" class="active"><i class="fas fa-question-circle" aria-hidden="true"></i> FAQ</a></li>
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
            <h1>Domande Frequenti</h1>
            <p>Tutto quello che devi sapere sui nostri servizi immobiliari</p>
        </div>
    </section>

    <!-- Contenuto principale FAQ -->
    <div class="faq-container">
        <!-- Ricerca FAQ -->
        <div class="faq-search">
            <input type="text" id="faqSearch" placeholder="Cerca nelle FAQ..." aria-label="Cerca nelle domande frequenti">
            <button aria-label="Cerca"><i class="fas fa-search" aria-hidden="true"></i></button>
        </div>

        <!-- Categorie FAQ -->
        <div class="faq-categories">
            <button class="category-btn active" data-category="all">Tutte le FAQ</button>
            <button class="category-btn" data-category="acquisto">Acquisto</button>
            <button class="category-btn" data-category="vendita">Vendita</button>
            <button class="category-btn" data-category="affitto">Affitto</button>
            <button class="category-btn" data-category="servizi">Servizi</button>
        </div>

        <!-- FAQ Accordion -->
        <div class="faq-accordion">
            <!-- Acquisto -->
            <div class="faq-category" data-category="acquisto">
                <h2><i class="fas fa-hand-holding-usd" aria-hidden="true"></i> Acquisto di immobili</h2>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quali sono i documenti necessari per acquistare un immobile?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Per acquistare un immobile sono necessari diversi documenti tra cui:</p>
                        <ul>
                            <li>Documento d'identità e codice fiscale</li>
                            <li>Documentazione relativa alla provenienza del denaro per l'acquisto</li>
                            <li>Documentazione attestante la capacità economica (busta paga, dichiarazioni dei redditi)</li>
                            <li>Eventuali documenti per accedere a mutui o agevolazioni fiscali</li>
                        </ul>
                        <p>Il nostro team è a disposizione per aiutarti a preparare tutta la documentazione necessaria.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Come funziona il mutuo per l'acquisto di un immobile?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Il mutuo è un prestito concesso da una banca per l'acquisto di un immobile. Generalmente copre fino all'80% del valore dell'immobile, mentre il restante 20% deve essere versato come anticipo dall'acquirente.</p>
                        <p>La procedura standard prevede:</p>
                        <ol>
                            <li>Valutazione della capacità di credito del richiedente</li>
                            <li>Perizia dell'immobile da parte della banca</li>
                            <li>Approvazione del mutuo</li>
                            <li>Stipula del contratto di mutuo contestualmente all'atto notarile di compravendita</li>
                        </ol>
                        <p>Offriamo consulenza gratuita per aiutarti a scegliere il mutuo più adatto alle tue esigenze, mettendoti in contatto con i nostri istituti bancari partner.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quali sono le spese aggiuntive nell'acquisto di un immobile?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Oltre al prezzo dell'immobile, è necessario considerare diverse spese accessorie:</p>
                        <ul>
                            <li>Imposte (registro, ipotecaria, catastale, IVA in alcuni casi)</li>
                            <li>Onorario del notaio</li>
                            <li>Provvigione dell'agenzia immobiliare</li>
                            <li>Spese per l'istruttoria del mutuo (se necessario)</li>
                            <li>Spese per perizie e certificazioni energetiche</li>
                        </ul>
                        <p>Mediamente, le spese accessorie possono variare dal 10% al 15% del valore dell'immobile.</p>
                    </div>
                </div>
            </div>

            <!-- Vendita -->
            <div class="faq-category" data-category="vendita">
                <h2><i class="fas fa-home" aria-hidden="true"></i> Vendita di immobili</h2>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Come viene valutato il prezzo di un immobile?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>La valutazione di un immobile tiene conto di diversi fattori:</p>
                        <ul>
                            <li>Ubicazione e zona</li>
                            <li>Metratura e tipologia</li>
                            <li>Stato di conservazione e anno di costruzione</li>
                            <li>Classe energetica</li>
                            <li>Presenza di servizi (ascensore, box, cantina)</li>
                            <li>Andamento del mercato immobiliare nella zona</li>
                        </ul>
                        <p>I nostri esperti effettuano una valutazione gratuita del tuo immobile, tenendo conto di tutti questi fattori per stabilire un prezzo di mercato realistico e competitivo.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quali documenti servono per vendere un immobile?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Per la vendita di un immobile sono necessari i seguenti documenti:</p>
                        <ul>
                            <li>Titolo di proprietà (atto di acquisto o successione)</li>
                            <li>Planimetria catastale aggiornata</li>
                            <li>Visura catastale</li>
                            <li>Attestato di Prestazione Energetica (APE)</li>
                            <li>Certificato di abitabilità/agibilità</li>
                            <li>Documenti relativi a eventuali ristrutturazioni</li>
                            <li>Dichiarazione di conformità degli impianti</li>
                        </ul>
                        <p>Il nostro team ti assisterà nella raccolta e verifica di tutta la documentazione necessaria.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Come funziona il processo di vendita con la vostra agenzia?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Il nostro processo di vendita si articola in diverse fasi:</p>
                        <ol>
                            <li><strong>Valutazione gratuita</strong> dell'immobile</li>
                            <li><strong>Piano marketing</strong> personalizzato (servizio fotografico professionale, virtual tour, annunci sui portali immobiliari)</li>
                            <li><strong>Selezione acquirenti</strong> qualificati</li>
                            <li><strong>Gestione delle visite</strong> all'immobile</li>
                            <li><strong>Negoziazione</strong> delle offerte</li>
                            <li><strong>Assistenza</strong> fino al rogito notarile</li>
                        </ol>
                        <p>Durante tutto il processo, ti sarà assegnato un consulente dedicato che ti terrà costantemente aggiornato sull'andamento delle attività.</p>
                    </div>
                </div>
            </div>

            <!-- Affitto -->
            <div class="faq-category" data-category="affitto">
                <h2><i class="fas fa-key" aria-hidden="true"></i> Affitto di immobili</h2>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quali sono le tipologie di contratti d'affitto disponibili?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Le principali tipologie di contratto d'affitto sono:</p>
                        <ul>
                            <li><strong>Contratto a canone libero (4+4)</strong>: durata di 4 anni rinnovabile automaticamente per altri 4, con canone stabilito liberamente dalle parti</li>
                            <li><strong>Contratto a canone concordato (3+2)</strong>: durata di 3 anni rinnovabile per altri 2, con canone stabilito in base ad accordi territoriali e agevolazioni fiscali per il proprietario</li>
                            <li><strong>Contratto transitorio</strong>: durata da 1 a 18 mesi, per esigenze temporanee documentabili</li>
                            <li><strong>Contratto per studenti universitari</strong>: durata da 6 a 36 mesi, per studenti fuori sede</li>
                        </ul>
                        <p>I nostri consulenti ti aiuteranno a scegliere la tipologia più adatta alle tue esigenze.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quali garanzie vengono richieste per un contratto d'affitto?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Per stipulare un contratto d'affitto, solitamente vengono richieste le seguenti garanzie:</p>
                        <ul>
                            <li>Deposito cauzionale (di solito pari a 2-3 mensilità)</li>
                            <li>Documentazione reddituale (buste paga, CUD, dichiarazione dei redditi)</li>
                            <li>Referenze da precedenti locazioni</li>
                            <li>In alcuni casi, fideiussione bancaria o assicurativa</li>
                        </ul>
                        <p>Offriamo anche servizi di mediazione per trovare soluzioni personalizzate che soddisfino sia il proprietario che l'inquilino.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Chi paga le spese condominiali in un immobile in affitto?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>La ripartizione delle spese condominiali è regolata dall'art. 9 della legge 392/78:</p>
                        <ul>
                            <li><strong>A carico del proprietario</strong>: spese straordinarie, manutenzione strutturale dell'edificio, amministrazione condominiale</li>
                            <li><strong>A carico dell'inquilino</strong>: spese ordinarie, servizi comuni (portineria, ascensore, pulizia, riscaldamento), piccole riparazioni</li>
                        </ul>
                        <p>È importante che la ripartizione delle spese sia chiaramente indicata nel contratto di locazione per evitare controversie.</p>
                    </div>
                </div>
            </div>

            <!-- Servizi -->
            <div class="faq-category" data-category="servizi">
                <h2><i class="fas fa-concierge-bell" aria-hidden="true"></i> I nostri servizi</h2>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quali servizi offre la vostra agenzia immobiliare?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>La nostra agenzia offre una gamma completa di servizi immobiliari:</p>
                        <ul>
                            <li>Compravendita di immobili residenziali, commerciali e industriali</li>
                            <li>Locazione di immobili</li>
                            <li>Valutazioni gratuite</li>
                            <li>Consulenza mutui e finanziamenti</li>
                            <li>Assistenza legale e notarile</li>
                            <li>Consulenza fiscale immobiliare</li>
                            <li>Home staging e servizi fotografici professionali</li>
                            <li>Virtual tour e planimetrie 3D</li>
                        </ul>
                        <p>Per ogni servizio, ti verrà assegnato un consulente specializzato che seguirà personalmente la tua pratica.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Come funziona il servizio di valutazione gratuita?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Il nostro servizio di valutazione gratuita prevede:</p>
                        <ol>
                            <li>Sopralluogo dell'immobile da parte di un nostro esperto</li>
                            <li>Analisi dettagliata del mercato immobiliare nella zona</li>
                            <li>Valutazione tecnica dell'immobile considerando tutti i fattori rilevanti</li>
                            <li>Elaborazione di un report di valutazione dettagliato</li>
                            <li>Presentazione e discussione della valutazione con il proprietario</li>
                        </ol>
                        <p>Per richiedere una valutazione gratuita, compila il form nella sezione <a href="contatti.php">Contatti</a> o chiama direttamente la nostra agenzia.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Qual è la provvigione dell'agenzia per una compravendita?</h3>
                        <span class="toggle-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>La provvigione standard per una compravendita immobiliare varia generalmente dal 2% al 3% del valore dell'immobile per ciascuna delle parti (acquirente e venditore).</p>
                        <p>L'importo esatto dipende da diversi fattori:</p>
                        <ul>
                            <li>Tipologia e valore dell'immobile</li>
                            <li>Complessità della trattativa</li>
                            <li>Servizi aggiuntivi richiesti</li>
                        </ul>
                        <p>Gli accordi relativi alle provvigioni vengono sempre definiti in modo trasparente all'inizio della collaborazione e formalizzati negli appositi incarichi.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Non hai trovato risposta? -->
        <div class="faq-not-found">
            <h3>Non hai trovato la risposta che cercavi?</h3>
            <p>Contattaci direttamente, saremo felici di aiutarti!</p>
            <a href="contatti.php" class="cta-button"><i class="fas fa-envelope" aria-hidden="true"></i> Contattaci ora</a>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione dell'accordion per le FAQ
        const faqQuestions = document.querySelectorAll('.faq-question');
        
        faqQuestions.forEach(question => {
            question.addEventListener('click', function() {
                // Toggle classe active sulla domanda cliccata
                this.classList.toggle('active');
                
                // Toggle icona plus/minus
                const icon = this.querySelector('.toggle-icon i');
                if (icon.classList.contains('fa-plus')) {
                    icon.classList.remove('fa-plus');
                    icon.classList.add('fa-minus');
                } else {
                    icon.classList.remove('fa-minus');
                    icon.classList.add('fa-plus');
                }
                
                // Toggle visibilità della risposta
                const answer = this.nextElementSibling;
                if (answer.style.maxHeight) {
                    answer.style.maxHeight = null;
                } else {
                    answer.style.maxHeight = answer.scrollHeight + "px";
                }
            });
        });
        
        // Filtro per categorie
        const categoryButtons = document.querySelectorAll('.category-btn');
        const faqCategories = document.querySelectorAll('.faq-category');
        
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Aggiorna stato attivo dei pulsanti
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const selectedCategory = this.getAttribute('data-category');
                
                // Mostra/nascondi categorie in base alla selezione
                if (selectedCategory === 'all') {
                    faqCategories.forEach(category => {
                        category.style.display = 'block';
                    });
                } else {
                    faqCategories.forEach(category => {
                        if (category.getAttribute('data-category') === selectedCategory) {
                            category.style.display = 'block';
                        } else {
                            category.style.display = 'none';
                        }
                    });
                }
            });
        });
        
        // Ricerca nelle FAQ
        const searchInput = document.getElementById('faqSearch');
        const faqItems = document.querySelectorAll('.faq-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Se il campo di ricerca è vuoto, resetta la visualizzazione
            if (searchTerm === '') {
                // Ripristina la visualizzazione basata sulla categoria attualmente selezionata
                const activeCategory = document.querySelector('.category-btn.active').getAttribute('data-category');
                
                if (activeCategory === 'all') {
                    faqCategories.forEach(category => {
                        category.style.display = 'block';
                    });
                    faqItems.forEach(item => {
                        item.style.display = 'block';
                    });
                } else {
                    faqCategories.forEach(category => {
                        if (category.getAttribute('data-category') === activeCategory) {
                            category.style.display = 'block';
                            const categoryItems = category.querySelectorAll('.faq-item');
                            categoryItems.forEach(item => {
                                item.style.display = 'block';
                            });
                        } else {
                            category.style.display = 'none';
                        }
                    });
                }
                return;
            }
            
            // Mostra tutte le categorie durante la ricerca
            faqCategories.forEach(category => {
                category.style.display = 'block';
            });
            
            // Filtra gli elementi in base al termine di ricerca
            let foundResults = false;
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question h3').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    foundResults = true;
                    
                    // Mostra la risposta se corrisponde alla ricerca
                    const questionElement = item.querySelector('.faq-question');
                    const answerElement = item.querySelector('.faq-answer');
                    const icon = questionElement.querySelector('.toggle-icon i');
                    
                    if (!questionElement.classList.contains('active')) {
                        questionElement.classList.add('active');
                        icon.classList.remove('fa-plus');
                        icon.classList.add('fa-minus');
                        answerElement.style.maxHeight = answerElement.scrollHeight + "px";
                    }
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Gestione messaggio "nessun risultato"
            const notFoundElement = document.querySelector('.faq-not-found');
            if (!foundResults) {
                notFoundElement.style.display = 'block';
            } else {
                notFoundElement.style.display = 'none';
            }
        });
    });
    </script>

</body>
</html>

<?php
$conn->close(); // Chiudi la connessione
?>