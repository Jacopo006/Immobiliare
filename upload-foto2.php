<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Foto Profilo</title>
    <style>
        .photo-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }
       
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
            margin-bottom: 20px;
        }
       
        .default-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 3px solid #ddd;
            font-size: 60px;
            color: #999;
        }
       
        .file-input {
            margin: 10px 0;
        }
       
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
       
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
       
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
       
        .btn:hover {
            opacity: 0.8;
        }
       
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
       
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
       
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
       
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
       
        .loading {
            display: none;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="photo-container">
        <h3>Foto Profilo</h3>
       
        <!-- Foto profilo attuale o avatar di default -->
        <div id="photoDisplay">
            <div class="default-avatar">👤</div>
        </div>
       
        <!-- Form per upload -->
        <div class="file-input">
            <input type="file" id="photoInput" accept="image/*" style="display: none;">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('photoInput').click()">
                Scegli Foto
            </button>
        </div>
       
        <!-- Pulsanti azione -->
        <div>
            <button type="button" id="uploadBtn" class="btn btn-primary" onclick="uploadPhoto()" disabled>
                Carica Foto
            </button>
            <button type="button" id="removeBtn" class="btn btn-danger" onclick="removePhoto()" style="display: none;">
                Rimuovi Foto
            </button>
        </div>
       
        <!-- Loading e messaggi -->
        <div id="loading" class="loading">Caricamento...</div>
        <div id="message"></div>
    </div>

    <script>
        let selectedFile = null;
        let currentPhotoPath = null;

        // Gestione selezione file
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                selectedFile = file;
               
                // Validazione file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showMessage('Formato file non supportato. Usa JPG, PNG o GIF', 'error');
                    return;
                }
               
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    showMessage('File troppo grande. Massimo 5MB', 'error');
                    return;
                }
               
                // Preview della foto
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoDisplay').innerHTML =
                        `<img src="${e.target.result}" alt="Preview" class="profile-photo">`;
                };
                reader.readAsDataURL(file);
               
                // Abilita il pulsante di upload
                document.getElementById('uploadBtn').disabled = false;
                showMessage('File selezionato: ' + file.name, 'success');
            }
        });

        // Funzione per caricare la foto
        function uploadPhoto() {
            if (!selectedFile) {
                showMessage('Seleziona prima una foto', 'error');
                return;
            }
           
            const formData = new FormData();
            formData.append('foto_profilo', selectedFile);
            formData.append('action', 'upload');
           
            showLoading(true);
           
            fetch('manage_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
               
                if (data.success) {
                    showMessage(data.message, 'success');
                    currentPhotoPath = data.photo_path;
                    document.getElementById('removeBtn').style.display = 'inline-block';
                    document.getElementById('uploadBtn').disabled = true;
                    selectedFile = null;
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showMessage('Errore di connessione', 'error');
            });
        }

        // Funzione per rimuovere la foto
        function removePhoto() {
            if (!confirm('Sei sicuro di voler rimuovere la foto profilo?')) {
                return;
            }
           
            showLoading(true);
           
            fetch('manage_photo.php?action=remove', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
               
                if (data.success) {
                    showMessage(data.message, 'success');
                    document.getElementById('photoDisplay').innerHTML =
                        '<div class="default-avatar">👤</div>';
                    document.getElementById('removeBtn').style.display = 'none';
                    currentPhotoPath = null;
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showMessage('Errore di connessione', 'error');
            });
        }

        // Funzioni di utilità
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = text;
            messageDiv.className = 'message ' + type;
           
            // Nasconde il messaggio dopo 5 secondi
            setTimeout(() => {
                messageDiv.innerHTML = '';
                messageDiv.className = '';
            }, 5000);
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        // Carica la foto esistente all'avvio (opzionale)
        function loadCurrentPhoto() {
            // Qui potresti fare una chiamata AJAX per recuperare la foto attuale
            // e mostrarla nel photoDisplay
        }

        // Carica la foto esistente quando la pagina si carica
        window.onload = function() {
            loadCurrentPhoto();
        };
    </script>
</body>
</html>