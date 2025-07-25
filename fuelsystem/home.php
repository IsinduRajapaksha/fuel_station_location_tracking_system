<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Lanka Fuel Stations - Advanced Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #1f2937;
            --accent-color: #ef4444;
            --dark-bg: #111827;
            --card-bg: #1f2937;
            --text-light: #ffffff;
            --text-muted: #d1d5db;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --shadow: 0 10px 40px rgba(0,0,0,0.5);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--dark-bg);
            color: #ccd6f6;
            overflow-x: hidden;
        }

        .hero-section {
            background: linear-gradient(135deg, #dc2626 0%, #1f2937 100%);
            padding: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><path d="M0,0 C150,100 350,0 500,50 C650,100 850,0 1000,50 L1000,100 L0,100 Z"/></svg>');
            background-size: 100% 100%;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 0 2rem;
        }

        .logo-container {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 10;
        }

        .imag-logo{
          width: 170px;
          height: 160px;
        }

        .logo {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border: 3px solid #dc2626;
        }

        .logo svg {
            width: 60px;
            height: 60px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .stats-container {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(252, 252, 252, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            min-width: 150px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            color: var(--success);
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .controls-panel {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(220, 38, 38, 0.2);
            position: relative;
            overflow: hidden;
        }

        .controls-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .controls-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.3);
            border-radius: 50px;
            color: #ffffff;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.3);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--gradient);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.5);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .filter-group {
            position: relative;
        }

        .filter-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .filter-select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 10px;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.3);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
            border: 2px solid rgba(220, 38, 38, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(220, 38, 38, 0.2);
            transform: translateY(-2px);
        }

        .map-container {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        #map {
            height: 70vh;
            width: 100%;
            border-radius: 20px;
        }

        .map-overlay {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .map-control {
            background: rgba(22, 33, 62, 0.9);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 50px;
            padding: 12px 20px;
            color: #ccd6f6;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .map-control:hover {
            background: rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }

        .map-control.active {
            background: var(--primary-color);
            color: white;
        }

        .info-panel {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
        }

        .info-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
        }

        .info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .info-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ccd6f6;
        }

        .view-toggle {
            display: flex;
            background: rgba(255,255,255,0.05);
            border-radius: 50px;
            padding: 4px;
        }

        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn.active {
            background: var(--gradient);
            color: white;
        }

        .stations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stations-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .station-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .station-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .station-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
            border-color: var(--primary-color);
        }

        .station-card:hover::before {
            transform: scaleX(1);
        }

        .station-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .station-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ccd6f6;
            margin-bottom: 0.5rem;
        }

        .station-brand {
            background: var(--gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .station-info {
            margin-bottom: 1rem;
        }

        .station-info div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .info-icon {
            width: 16px;
            color: var(--primary-color);
        }

        .fuel-types {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .fuel-type {
            background: rgba(100, 255, 218, 0.1);
            color: var(--success);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid rgba(100, 255, 218, 0.3);
        }

        .station-distance {
            color: var(--success);
            font-weight: 600;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .station-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            flex: 1;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .action-btn.primary {
            background: var(--gradient);
            color: white;
        }

        .action-btn.secondary {
            background: rgba(255,255,255,0.1);
            color: var(--text-muted);
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .floating-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .floating-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }

        .popup-fuel-types {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 10px 0;
        }

        .popup-fuel-type {
            background: rgba(100, 255, 218, 0.2);
            color: var(--success);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .stats-container {
                gap: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .stations-grid {
                grid-template-columns: 1fr;
            }
            
            .controls-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .map-overlay {
                right: 10px;
                top: 10px;
            }

            .map-control {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--dark-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 4px;
        }

        .leaflet-popup-content-wrapper {
            background: var(--card-bg) !important;
            color: #ccd6f6 !important;
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
        }

        .leaflet-popup-tip {
            background: var(--card-bg) !important;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="logo-container">
            <div class="logo">
                <img src="image/03.png" class="imag-logo" alt="logo">
            </div>
        </div>
        
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-gas-pump"></i>
                Fuel Finder LK
            </h1>
            <p class="hero-subtitle">
                Discover fuel stations across Sri Lanka with real-time availability and smart filtering
            </p>
            <div class="stats-container">
                <div class="stat-item">
                    <span class="stat-number" id="total-stations">6</span>
                    <span class="stat-label">Total Stations</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="active-districts">25</span>
                    <span class="stat-label">Districts Covered</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="fuel-brands">4</span>
                    <span class="stat-label">Fuel Brands</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="controls-panel">
            <div class="controls-header">
                <h3 class="controls-title">
                    <i class="fas fa-filter"></i>
                    Smart Filters
                </h3>
                <button class="btn btn-secondary" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i>
                    Clear All
                </button>
            </div>

            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Search by station name, address, or district...">
                <button class="search-btn" onclick="searchStations()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div class="filter-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-map-marker-alt"></i>
                        District
                    </label>
                    <select id="district" class="filter-select">
                        <option value="">All Districts</option>
                        <option value="Colombo">Colombo</option>
                        <option value="Gampaha">Gampaha</option>
                        <option value="Kalutara">Kalutara</option>
                        <option value="Kandy">Kandy</option>
                        <option value="Matale">Matale</option>
                        <option value="Nuwara Eliya">Nuwara Eliya</option>
                        <option value="Galle">Galle</option>
                        <option value="Matara">Matara</option>
                        <option value="Hambantota">Hambantota</option>
                        <option value="Jaffna">Jaffna</option>
                        <option value="Kilinochchi">Kilinochchi</option>
                        <option value="Mannar">Mannar</option>
                        <option value="Mullaitivu">Mullaitivu</option>
                        <option value="Vavuniya">Vavuniya</option>
                        <option value="Puttalam">Puttalam</option>
                        <option value="Kurunegala">Kurunegala</option>
                        <option value="Anuradhapura">Anuradhapura</option>
                        <option value="Polonnaruwa">Polonnaruwa</option>
                        <option value="Badulla">Badulla</option>
                        <option value="Monaragala">Monaragala</option>
                        <option value="Ratnapura">Ratnapura</option>
                        <option value="Kegalle">Kegalle</option>
                        <option value="Ampara">Ampara</option>
                        <option value="Batticaloa">Batticaloa</option>
                        <option value="Trincomalee">Trincomalee</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-tint"></i>
                        Fuel Type
                    </label>
                    <select id="fuel-type" class="filter-select">
                        <option value="">All Fuel Types</option>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Super Petrol">Super Petrol</option>
                        <option value="Auto Diesel">Auto Diesel</option>
                        <option value="Octane 95">Octane 95</option>
                        <option value="Super Diesel">Super Diesel</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-building"></i>
                        Brand
                    </label>
                    <select id="brand" class="filter-select">
                        <option value="">All Brands</option>
                        <option value="Ceylon Petroleum Corporation">CPC</option>
                        <option value="Lanka IOC">Lanka IOC</option>
                        <option value="Rpml">RPML</option>
                        <option value="Sinopec">Sinopec</option>
                        <option value="IOC">IOC</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-clock"></i>
                        Operating Hours
                    </label>
                    <select id="hours" class="filter-select">
                        <option value="">All Hours</option>
                        <option value="24">24 Hours</option>
                        <option value="day">Day Time Only</option>
                    </select>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i>
                    Apply Filters
                </button>
                <button class="btn btn-secondary" onclick="loadAllStations()">
                    <i class="fas fa-list"></i>
                    Show All Stations
                </button>
                <button class="btn btn-secondary" onclick="findNearestStation()">
                    <i class="fas fa-location-arrow"></i>
                    Find Nearest
                </button>
            </div>
        </div>

        <div class="map-container">
            <div class="map-overlay">
                <div class="map-control" id="satellite-toggle" onclick="toggleSatelliteView()">
                    <i class="fas fa-satellite"></i>
                    <span id="view-text">Satellite</span>
                </div>
                <div class="map-control" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                    <span>Fullscreen</span>
                </div>
                <div class="map-control" onclick="locateUser()">
                    <i class="fas fa-location-arrow"></i>
                    <span>My Location</span>
                </div>
            </div>
            <div id="map"></div>
        </div>

        <div class="info-panel">
            <div class="info-header">
                <h3 class="info-title">
                    <i class="fas fa-list"></i>
                    Fuel Stations
                </h3>
                <div class="view-toggle">
                    <button class="view-btn active" onclick="toggleView('grid')">
                        <i class="fas fa-th"></i>
                        Grid
                    </button>
                    <button class="view-btn" onclick="toggleView('list')">
                        <i class="fas fa-list"></i>
                        List
                    </button>
                </div>
            </div>

            <div id="loading" class="loading-spinner" style="display: none;">
                <div class="spinner"></div>
            </div>

            <div id="empty-state" class="empty-state" style="display: none;">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>No stations found</h3>
                <p>Try adjusting your filters or search terms</p>
            </div>

            <div id="stations-container" class="stations-grid"></div>
        </div>
    </div>

    <button class="floating-fab" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Global variables
        let map;
        let markersGroup;
        let allStations = [];
        let filteredStations = [];
        let userLocation = null;
        let satelliteView = false;
        let currentView = 'grid';
        let osmLayer, satelliteLayer;

        // Sample data - replace with your actual data source
        const sampleStations = [
            {
                id: 1,
                name: "Colombo Main Station",
                brand: "Ceylon Petroleum Corporation",
                latitude: 6.9271,
                longitude: 79.8612,
                address: "No. 123, Galle Road, Colombo 03",
                district: "Colombo",
                fuel_types: "Petrol, Diesel, Octane 95",
                phone: "+94 11 2345678",
                status: "Open",
                hours: "24"
            },
            {
                id: 2,
                name: "Kandy Junction",
                brand: "Lanka IOC",
                latitude: 7.2906,
                longitude: 80.6337,
                address: "Kandy Road, Kandy",
                district: "Kandy",
                fuel_types: "Petrol, Diesel, Super Diesel",
                phone: "+94 81 2234567",
                status: "Open",
                hours: "day"
            },
            {
                id: 3,
                name: "Galle Express",
                brand: "Rpml",
                latitude: 6.0535,
                longitude: 80.2210,
                address: "Matara Road, Galle",
                district: "Galle",
                fuel_types: "Petrol, Diesel",
                phone: "+94 91 2345678",
                status: "Open",
                hours: "24"
            },
            {
                id: 4,
                name: "Negombo Beach Station",
                brand: "Sinopec",
                latitude: 7.2088,
                longitude: 79.8358,
                address: "Beach Road, Negombo",
                district: "Gampaha",
                fuel_types: "Petrol, Diesel, Octane 95",
                phone: "+94 31 2234567",
                status: "Open",
                hours: "day"
            },
            {
                id: 5,
                name: "Anuradhapura Central",
                brand: "Ceylon Petroleum Corporation",
                latitude: 8.3114,
                longitude: 80.4037,
                address: "Main Street, Anuradhapura",
                district: "Anuradhapura",
                fuel_types: "Petrol, Diesel, Super Diesel",
                phone: "+94 25 2345678",
                status: "Open",
                hours: "24"
            },
            {
                id: 6,
                name: "Trincomalee IOC",
                brand: "IOC",
                latitude: 8.5778,
                longitude: 81.2336,
                address: "Main Street, Trincomalee",
                district: "Trincomalee",
                fuel_types: "Petrol, Diesel, Super Diesel",
                phone: "+94 26 2345678",
                status: "Open",
                hours: "day"
            }
        ];

        // Initialize map
        function initializeMap() {
            map = L.map('map').setView([7.8731, 80.7718], 8);
            
            // Add tile layers
            osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            });
            
            satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '© Esri, Maxar, GeoEye, Earthstar Geographics, CNES/Airbus DS, USDA, USGS, AeroGRID, IGN, and the GIS User Community'
            });
            
            osmLayer.addTo(map);
            markersGroup = L.layerGroup().addTo(map);
            
            // Get user location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Add user location marker
                    L.marker([userLocation.lat, userLocation.lng], {
                        icon: L.divIcon({
                            className: 'user-location-marker',
                            html: '<div style="background: #e74c3c; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-user"></i></div>',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).addTo(map).bindPopup('Your Location');
                }, function(error) {
                    console.log('Geolocation error:', error);
                });
            }
        }

        // Custom markers with different colors for different brands
        function createMarkerIcon(brand) {
            const colors = {
                'Ceylon Petroleum Corporation': '#e74c3c',
                'Lanka IOC': '#3498db',
                'Rpml': '#f39c12',
                'Sinopec': '#27ae60',
                'IOC': '#9b59b6',
                'default': '#34495e'
            };
            
            const color = colors[brand] || colors.default;
            
            return L.divIcon({
                className: 'custom-marker',
                html: `
                    <div style="
                        background: ${color};
                        color: white;
                        width: 40px;
                        height: 40px;
                        border-radius: 50% 50% 50% 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        font-weight: bold;
                        border: 3px solid white;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                        transform: rotate(-45deg);
                        position: relative;
                    ">
                        <i class="fas fa-gas-pump" style="transform: rotate(45deg);"></i>
                    </div>
                `,
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });
        }

        // Enhanced popup content
        function createPopupContent(station) {
            const fuelTypes = station.fuel_types.split(',').map(type => 
                `<span class="popup-fuel-type">${type.trim()}</span>`
            ).join('');

            const distance = userLocation ? 
                calculateDistance(userLocation.lat, userLocation.lng, station.latitude, station.longitude) : 
                null;

            return `
                <div style="min-width: 300px;">
                    <div class="popup-header">
                        <h3 style="margin: 0; font-size: 1.2rem; color: #ccd6f6;">${station.name}</h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9; color: #10b981;">${station.brand}</p>
                    </div>
                    <div class="popup-body">
                        <p style="margin: 10px 0; color: #d1d5db;"><i class="fas fa-map-marker-alt" style="color: #dc2626; margin-right: 8px;"></i> ${station.address}</p>
                        <p style="margin: 10px 0; color: #d1d5db;"><i class="fas fa-phone" style="color: #dc2626; margin-right: 8px;"></i> ${station.phone}</p>
                        ${distance ? `<p style="margin: 10px 0; color: #27ae60; font-weight: 600;"><i class="fas fa-route" style="margin-right: 8px;"></i> ${distance.toFixed(1)} km away</p>` : ''}
                        <div class="popup-fuel-types" style="margin: 10px 0;">
                            ${fuelTypes}
                        </div>
                        <p style="margin: 10px 0 0 0; color: #27ae60; font-weight: 600;"><i class="fas fa-clock" style="margin-right: 8px;"></i> Status: ${station.status}</p>
                    </div>
                </div>
            `;
        }

        // Calculate distance between two points
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the Earth in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Load stations data
        function loadStations() {
            showLoading();
            
            // Simulate API call
            setTimeout(() => {
                allStations = sampleStations;
                filteredStations = [...allStations];
                updateDisplay();
                updateStats();
                hideLoading();
            }, 1000);
        }

        // Update statistics
        function updateStats() {
            document.getElementById('total-stations').textContent = allStations.length;
        }

        // Update map markers
        function updateMapMarkers() {
            markersGroup.clearLayers();
            
            filteredStations.forEach(station => {
                const marker = L.marker([station.latitude, station.longitude], {
                    icon: createMarkerIcon(station.brand)
                }).bindPopup(createPopupContent(station));
                
                markersGroup.addLayer(marker);
            });

            // Fit map to show all markers if there are any
            if (filteredStations.length > 0) {
                const group = new L.featureGroup(markersGroup.getLayers());
                if (group.getBounds().isValid()) {
                    map.fitBounds(group.getBounds(), { padding: [20, 20] });
                }
            }
        }

        // Update station list
        function updateStationList() {
            const container = document.getElementById('stations-container');
            
            if (filteredStations.length === 0) {
                showEmptyState();
                return;
            }
            
            hideEmptyState();
            
            container.innerHTML = filteredStations.map(station => {
                const distance = userLocation ? 
                    calculateDistance(userLocation.lat, userLocation.lng, station.latitude, station.longitude) : 
                    null;
                    
                const fuelTypes = station.fuel_types.split(',').map(type => 
                    `<span class="fuel-type">${type.trim()}</span>`
                ).join('');

                return `
                    <div class="station-card" onclick="focusOnStation(${station.id})">
                        <div class="station-header">
                            <div>
                                <div class="station-name">${station.name}</div>
                                <div class="station-brand">${station.brand}</div>
                            </div>
                        </div>
                        <div class="station-info">
                            <div><i class="fas fa-map-marker-alt info-icon"></i> ${station.address}</div>
                            <div><i class="fas fa-phone info-icon"></i> ${station.phone}</div>
                            <div><i class="fas fa-clock info-icon"></i> ${station.hours === '24' ? '24 Hours' : 'Day Time Only'}</div>
                        </div>
                        <div class="fuel-types">${fuelTypes}</div>
                        ${distance ? `<div class="station-distance"><i class="fas fa-route"></i> ${distance.toFixed(1)} km away</div>` : ''}
                    </div>
                `;
            }).join('');
        }

        // Focus on station
        function focusOnStation(stationId) {
            const station = allStations.find(s => s.id === stationId);
            if (station) {
                map.setView([station.latitude, station.longitude], 15);
                
                // Find and open the popup for this station
                markersGroup.eachLayer(layer => {
                    if (layer.getLatLng().lat === station.latitude && layer.getLatLng().lng === station.longitude) {
                        layer.openPopup();
                    }
                });
            }
        }

        // Search stations
        function searchStations() {
            applyFilters();
        }

        // Apply filters
        function applyFilters() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const districtFilter = document.getElementById('district').value;
            const fuelFilter = document.getElementById('fuel-type').value;
            const brandFilter = document.getElementById('brand').value;
            const hoursFilter = document.getElementById('hours').value;
            
            filteredStations = allStations.filter(station => {
                const matchesSearch = !searchTerm || 
                    station.name.toLowerCase().includes(searchTerm) ||
                    station.address.toLowerCase().includes(searchTerm) ||
                    station.district.toLowerCase().includes(searchTerm);
                
                const matchesDistrict = !districtFilter || station.district === districtFilter;
                const matchesFuel = !fuelFilter || station.fuel_types.includes(fuelFilter);
                const matchesBrand = !brandFilter || station.brand === brandFilter;
                const matchesHours = !hoursFilter || station.hours === hoursFilter;
                
                return matchesSearch && matchesDistrict && matchesFuel && matchesBrand && matchesHours;
            });
            
            updateDisplay();
        }

        // Clear all filters
        function clearAllFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('district').value = '';
            document.getElementById('fuel-type').value = '';
            document.getElementById('brand').value = '';
            document.getElementById('hours').value = '';
            
            filteredStations = [...allStations];
            updateDisplay();
        }

        // Load all stations
        function loadAllStations() {
            clearAllFilters();
        }

        // Find nearest station
        function findNearestStation() {
            if (!userLocation) {
                alert('Location access is required to find the nearest station. Please enable location services and refresh the page.');
                return;
            }

            let nearestStation = null;
            let minDistance = Infinity;

            allStations.forEach(station => {
                const distance = calculateDistance(
                    userLocation.lat, userLocation.lng,
                    station.latitude, station.longitude
                );
                
                if (distance < minDistance) {
                    minDistance = distance;
                    nearestStation = station;
                }
            });

            if (nearestStation) {
                filteredStations = [nearestStation];
                updateDisplay();
                focusOnStation(nearestStation.id);
            }
        }

        // Toggle satellite view
        function toggleSatelliteView() {
            satelliteView = !satelliteView;
            const toggleBtn = document.getElementById('satellite-toggle');
            const viewText = document.getElementById('view-text');
            
            if (satelliteView) {
                map.removeLayer(osmLayer);
                map.addLayer(satelliteLayer);
                toggleBtn.classList.add('active');
                viewText.textContent = 'Street';
            } else {
                map.removeLayer(satelliteLayer);
                map.addLayer(osmLayer);
                toggleBtn.classList.remove('active');
                viewText.textContent = 'Satellite';
            }
        }

        // Toggle fullscreen
        function toggleFullscreen() {
            const mapContainer = document.getElementById('map');
            
            if (!document.fullscreenElement) {
                mapContainer.requestFullscreen().then(() => {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                });
            } else {
                document.exitFullscreen().then(() => {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                });
            }
        }

        // Locate user
        function locateUser() {
            if (!userLocation) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        
                        map.setView([userLocation.lat, userLocation.lng], 15);
                        
                        // Add or update user location marker
                        L.marker([userLocation.lat, userLocation.lng], {
                            icon: L.divIcon({
                                className: 'user-location-marker',
                                html: '<div style="background: #e74c3c; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-user"></i></div>',
                                iconSize: [26, 26],
                                iconAnchor: [13, 13]
                            })
                        }).addTo(map).bindPopup('Your Location');
                    }, function(error) {
                        alert('Unable to access your location. Please check your browser settings.');
                    });
                } else {
                    alert('Geolocation is not supported by this browser.');
                }
            } else {
                map.setView([userLocation.lat, userLocation.lng], 15);
            }
        }

        // Toggle view
        function toggleView(view) {
            currentView = view;
            const container = document.getElementById('stations-container');
            const gridBtn = document.querySelector('.view-btn:first-child');
            const listBtn = document.querySelector('.view-btn:last-child');
            
            if (view === 'grid') {
                container.className = 'stations-grid';
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            } else {
                container.className = 'stations-list';
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            }
        }

        // Utility functions
        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
            document.getElementById('stations-container').style.display = 'none';
            document.getElementById('empty-state').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('stations-container').style.display = currentView === 'grid' ? 'grid' : 'flex';
        }

        function showEmptyState() {
            document.getElementById('empty-state').style.display = 'block';
            document.getElementById('stations-container').style.display = 'none';
        }

        function hideEmptyState() {
            document.getElementById('empty-state').style.display = 'none';
            document.getElementById('stations-container').style.display = currentView === 'grid' ? 'grid' : 'flex';
        }

        function updateDisplay() {
            updateMapMarkers();
            updateStationList();
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            loadStations();
            
            // Search input with debounce
            let searchTimeout;
            document.getElementById('search-input').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 300);
            });
            
            // Filter selects
            document.getElementById('district').addEventListener('change', applyFilters);
            document.getElementById('fuel-type').addEventListener('change', applyFilters);
            document.getElementById('brand').addEventListener('change', applyFilters);
            document.getElementById('hours').addEventListener('change', applyFilters);
            
            // Handle fullscreen changes
            document.addEventListener('fullscreenchange', function() {
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
            });
        });
    </script>
</body>
</html>