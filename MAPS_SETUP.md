# 🗺️ ISTRUZIONI SETUP MAPPE - Colitalia Real Estate

## ⚡ Setup Veloce (2 minuti)

### 1. Ottieni API Key Google Maps
1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un progetto o seleziona uno esistente
3. Abilita "Maps JavaScript API" e "Places API"
4. Crea una API Key
5. Aggiungi restrizioni dominio per sicurezza

### 2. Configura l'API Key
Sostituisci `INSERISCI_QUI_LA_TUA_GOOGLE_MAPS_API_KEY` con la tua API key nei file:

- `includes/Admin/PropertyMaps.php` (riga 86)
- `includes/Core/MapShortcodes.php` (riga 76)

**OPPURE** aggiungi questa riga in `wp-config.php`:
```php
define('GOOGLE_MAPS_API_KEY', 'la_tua_api_key_qui');
```

E cambia nei file PHP:
```php
'https://maps.googleapis.com/maps/api/js?key=' . GOOGLE_MAPS_API_KEY . '&libraries=places'
```

### 3. Attiva Plugin
1. Carica e attiva il plugin
2. Vai a modificare una proprietà
3. Troverai la nuova sezione "Mappa Proprietà"
4. Inserisci l'indirizzo e clicca "Trova su Mappa"

### 4. Test Frontend
Aggiungi questo shortcode in una pagina:
```
[colitalia_map property_id="123"]
```

## ✨ Funzionalità Incluse

### Admin:
- 🎯 Metabox mappa interattiva nell'editor proprietà
- 📍 Autocomplete indirizzi Google Places
- 🔄 Coordinate automatiche trascinando marker
- 🔍 Geocoding bidirezionale (indirizzo ↔ coordinate)

### Frontend:
- 🗺️ Mappe responsive per le proprietà
- 🖼️ Controllo fullscreen
- 🧭 Link indicazioni stradali
- 📱 Ottimizzate mobile

### Shortcodes:
- `[colitalia_map property_id="123"]` - Mappa singola proprietà
- `[colitalia_map]` - Mappa proprietà corrente

## 🔧 Personalizzazioni

### Stili CSS
Modifica `templates/property-map.php` per personalizzare l'aspetto.

### Marker Personalizzato
Sostituisci `assets/images/marker.png` con la tua icona (40x40px).

### Configurazione Mappa
Modifica le opzioni in `assets/js/frontend-map.js`:
- Zoom default
- Stili mappa
- Controlli disponibili

## 📞 Supporto
- **Email**: supporto@colitalia.com
- **Documentazione**: docs/MAPS_GUIDE.md
