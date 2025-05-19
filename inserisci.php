<style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 25px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #3498db;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 0;
        }
        
        .form-column {
            flex: 1;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .required-field::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-note {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        .form-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 30px;
        }
        
        /* Stile per il pulsante di upload file */
        .file-upload {
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: pointer;
            display: block;
        }
        
        .file-upload-label {
            display: inline-block;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        
        .file-upload-label:hover {
            background-color: #2980b9;
        }
        
        .file-name {
            display: inline-block;
            margin-left: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Coordinate */
        .coordinates-container {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .coordinates-title {
            font-weight: 600;
            color: #3498db;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        #map {
            height: 300px;
            width: 100%;
            border-radius: 5px;
            margin-bottom: 15px;
        }