<?php
// Avvia la sessione
session_start();


// Titolo della pagina
$page_title = "Business Plan - Agenzia Immobiliare Online";
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="business_plan.css">
    <style>
        .business-plan-section {
            margin-bottom: 40px;
        }
        
        .business-plan-section h3 {
            margin-top: 0;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            color: #2c3e50;
        }
        
        .business-plan-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: #3498db;
            margin: 10px 0;
        }
        
        .metric-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .download-btn {
            background-color: #2ecc71;
            margin-top: 20px;
        }

        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .business-plan-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header e navigazione principale andrebbe qui -->
    
    <div class="dashboard-container">
        <!-- Sidebar di navigazione -->
        <div class="sidebar">
            <ul>
                <li><a href="dashboard_agente.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="immobili.php"><i class="fas fa-home"></i> I miei immobili</a></li>
                <li><a href="clienti.php"><i class="fas fa-users"></i> Clienti</a></li>
                <li><a href="appuntamenti.php"><i class="fas fa-calendar-alt"></i> Appuntamenti</a></li>
                <li><a href="messaggi.php"><i class="fas fa-envelope"></i> Messaggi</a></li>
                <li><a href="documenti.php"><i class="fas fa-file-alt"></i> Documenti</a></li>
                <li><a href="business_plan.php" class="active"><i class="fas fa-chart-line"></i> Business Plan</a></li>
                <li><a href="impostazioni.php"><i class="fas fa-cog"></i> Impostazioni</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Contenuto principale -->
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Business Plan - Agenzia Immobiliare Online</h2>
                    <a href="download_business_plan.php" class="btn btn-success download-btn"><i class="fas fa-download"></i> Scarica PDF</a>
                </div>
                <div class="card-body">
                    <!-- Executive Summary -->
                    <div class="business-plan-section">
                        <h3><i class="fas fa-flag"></i> Executive Summary</h3>
                        <p>L'Agenzia Immobiliare Online è un innovativo portale web dedicato all'intermediazione immobiliare che integra tecnologie all'avanguardia per semplificare ogni fase della compravendita e locazione di immobili. Il progetto nasce dall'esigenza di modernizzare il settore immobiliare italiano, offrendo una piattaforma completa che connette proprietari, acquirenti e professionisti del settore in un unico ecosistema digitale.</p>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-bullseye"></i>
                            <div>
                                <strong>Mission:</strong> Rendere le transazioni immobiliari più semplici, trasparenti e accessibili attraverso la digitalizzazione dell'intero processo, dalla ricerca alla finalizzazione del contratto.
                            </div>
                        </div>
                        
                        <div class="alert alert-success" style="background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb;">
                            <i class="fas fa-eye"></i>
                            <div>
                                <strong>Vision:</strong> Diventare il punto di riferimento nel mercato immobiliare italiano per innovazione tecnologica e qualità del servizio, rivoluzionando il modo in cui le persone comprano, vendono e affittano immobili.
                            </div>
                        </div>
                    </div>
                    
                    <!-- KPI e Obiettivi -->
                    <div class="business-plan-section">
                        <h3><i class="fas fa-bullseye"></i> KPI e Obiettivi</h3>
                        
                        <div class="business-plan-metrics">
                            <div class="metric-card">
                                <div class="metric-label">Utenti registrati (Anno 1)</div>
                                <div class="metric-value">25.000</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Agenzie abbonate (Anno 1)</div>
                                <div class="metric-value">150</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Immobili in piattaforma (Anno 1)</div>
                                <div class="metric-value">5.000</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Transazioni concluse (Anno 1)</div>
                                <div class="metric-value">200</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Timeline del progetto -->
                    <div class="business-plan-section">
                        <h3><i class="fas fa-calendar-alt"></i> Timeline del Progetto</h3>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fase</th>
                                    <th>Attività</th>
                                    <th>Data Inizio</th>
                                    <th>Durata (giorni)</th>
                                    <th>Data Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Pianificazione</td>
                                    <td>Ricerca mercato, modello business, costituzione legale</td>
                                    <td>01/06/2025</td>
                                    <td>6</td>
                                    <td>06/06/2025</td>
                                </tr>
                                <tr>
                                    <td>Allestimento Ufficio</td>
                                    <td>Ricerca location, contratto, design, IT</td>
                                    <td>07/06/2025</td>
                                    <td>15</td>
                                    <td>21/06/2025</td>
                                </tr>
                                <tr>
                                    <td>Sviluppo Sito Web</td>
                                    <td>Requisiti, design, frontend, backend, CMS, QA</td>
                                    <td>07/06/2025</td>
                                    <td>22</td>
                                    <td>28/06/2025</td>
                                </tr>
                                <tr>
                                    <td>Acquisizione Immobili</td>
                                    <td>Partnership proprietari, foto, caricamento</td>
                                    <td>07/06/2025</td>
                                    <td>13</td>
                                    <td>19/06/2025</td>
                                </tr>
                                <tr>
                                    <td>Marketing & SEO</td>
                                    <td>SEO, social media, ads, contenuti</td>
                                    <td>25/06/2025</td>
                                    <td>11</td>
                                    <td>05/07/2025</td>
                                </tr>
                                <tr>
                                    <td>Lancio</td>
                                    <td>Soft launch, feedback, regolazioni</td>
                                    <td>29/06/2025</td>
                                    <td>7</td>
                                    <td>05/07/2025</td>
                                </tr>
                                <tr>
                                    <td>Manutenzione</td>
                                    <td>Supporto operativo, analisi prestazioni</td>
                                    <td>06/07/2025</td>
                                    <td>30+</td>
                                    <td>In corso</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Analisi Finanziaria -->
                    <div class="business-plan-section">
                        <h3><i class="fas fa-money-bill-wave"></i> Analisi Finanziaria</h3>
                        
                        <div class="business-plan-metrics">
                            <div class="metric-card">
                                <div class="metric-label">Investimento Iniziale</div>
                                <div class="metric-value">€105.500</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Costi Operativi Mensili</div>
                                <div class="metric-value">€70.500</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Ricavi Anno 1</div>
                                <div class="metric-value">€453.000</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-label">Break-even</div>
                                <div class="metric-value">Mese 18</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="financialProjectionChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Modello di Business -->
                    <div class="business-plan-section">
                        <h3><i class="fas fa-hand-holding-usd"></i> Modello di Business</h3>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fonte di Ricavo</th>
                                    <th>Descrizione</th>
                                    <th>% Ricavi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-user-check"></i> Abbonamenti Premium</td>
                                    <td>
                                        <strong>Piano Basic:</strong> €99/mese (fino a 20 immobili)<br>
                                        <strong>Piano Pro:</strong> €199/mese (fino a 50 immobili + funzionalità avanzate)<br>
                                        <strong>Piano Enterprise:</strong> €399/mese (immobili illimitati + supporto dedicato)
                                    </td>
                                    <td><div class="status-badge" style="background-color: #3498db;">60%</div></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-percentage"></i> Commissioni</td>
                                    <td>
                                        1% sul valore della transazione conclusa tramite la piattaforma<br>
                                        Fee fissa per contratti di affitto (una mensilità)
                                    </td>
                                    <td><div class="status-badge" style="background-color: #9b59b6;">20%</div></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-plus-circle"></i> Servizi Aggiuntivi</td>
                                    <td>
                                        Servizi fotografici professionali<br>
                                        Creazione tour virtuali<br>
                                        Promozione in evidenza degli annunci<br>
                                        Valutazioni immobiliari professionali
                                    </td>
                                    <td><div class="status-badge" style="background-color: #f39c12;">15%</div></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-ad"></i> Pubblicità</td>
                                    <td>
                                        Banner pubblicitari di partner selezionati<br>
                                        Inserzioni native di servizi complementari
                                    </td>
                                    <td><div class="status-badge" style="background-color: #2ecc71;">5%</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Analisi dei Rischi -->
                    <div class="business-plan-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> Analisi dei Rischi</h3>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rischio</th>
                                    <th>Probabilità</th>
                                    <th>Impatto</th>
                                    <th>Strategia di Mitigazione</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Concorrenza aggressiva</td>
                                    <td><div class="status-badge" style="background-color: #e74c3c;">Alta</div></td>
                                    <td><div class="status-badge" style="background-color: #e74c3c;">Alto</div></td>
                                    <td>Differenziazione servizi, innovazione continua, fidelizzazione cliente</td>
                                </tr>
                                <tr>
                                    <td>Calo del mercato immobiliare</td>
                                    <td><div class="status-badge" style="background-color: #f39c12;">Media</div></td>
                                    <td><div class="status-badge" style="background-color: #e74c3c;">Alto</div></td>
                                    <td>Diversificazione geografica, ampliamento servizi</td>
                                </tr>
                                <tr>
                                    <td>Malfunzionamenti piattaforma</td>
                                    <td><div class="status-badge" style="background-color: #f39c12;">Media</div></td>
                                    <td><div class="status-badge" style="background-color: #e74c3c;">Alto</div></td>
                                    <td>Testing approfondito, monitoraggio continuo, sistema di backup</td>
                                </tr>
                                <tr>
                                    <td>Cambiamenti normativi</td>
                                    <td><div class="status-badge" style="background-color: #f39c12;">Media</div></td>
                                    <td><div class="status-badge" style="background-color: #f39c12;">Medio</div></td>
                                    <td>Consulenza legale continua, adeguamento tempestivo</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer andrebbe qui -->
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Grafico delle entrate triennali
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Anno 1', 'Anno 2', 'Anno 3'],
                datasets: [{
                    label: 'Ricavi totali (€)',
                    data: [453000, 1450000, 3100000],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.7)',
                        'rgba(52, 152, 219, 0.7)',
                        'rgba(52, 152, 219, 0.7)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(52, 152, 219, 1)',
                        'rgba(52, 152, 219, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Proiezione Ricavi Triennale'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Grafico proiezione finanziaria
        const financialCtx = document.getElementById('financialProjectionChart').getContext('2d');
        const financialChart = new Chart(financialCtx, {
            type: 'line',
            data: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6', 'Q7', 'Q8', 'Q9', 'Q10', 'Q11', 'Q12'],
                datasets: [{
                    label: 'Ricavi trimestrali',
                    data: [39000, 87000, 135000, 192000, 280000, 350000, 400000, 420000, 500000, 600000, 700000, 800000],
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }, {
                    label: 'Costi trimestrali',
                    data: [211500, 211500, 211500, 211500, 235000, 235000, 240000, 240000, 270000, 270000, 280000, 280000],
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Proiezione Finanziaria (Trimestrale)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    
</body>
</html>