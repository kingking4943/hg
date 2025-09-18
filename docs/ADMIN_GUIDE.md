# Guida Amministratore - Colitalia Real Estate Manager

## Indice
1. [Dashboard Principale](#dashboard-principale)
2. [Gestione Proprietà](#gestione-proprietà)
3. [Configurazione Prezzi Stagionali](#configurazione-prezzi-stagionali)
4. [Gestione Prenotazioni](#gestione-prenotazioni)
5. [Gestione Clienti](#gestione-clienti)
6. [Reportistica e Statistiche](#reportistica-e-statistiche)
7. [Manutenzione Database](#manutenzione-database)
8. [Backup e Restore](#backup-e-restore)

## Dashboard Principale

### Panoramica
Il dashboard fornisce una vista completa dello stato delle tue proprietà immobiliari.

#### Widgets Disponibili
- **Statistiche Rapide**: Prenotazioni oggi, settimana, mese
- **Entrate**: Fatturato giornaliero, mensile, annuale
- **Proprietà**: Totale proprietà, occupazione media
- **Clienti**: Nuovi clienti, clienti ricorrenti
- **Attività Recente**: Ultime prenotazioni e modifiche

#### Personalizzazione Dashboard
1. Clicca su **Opzioni Schermo** (angolo alto destro)
2. Seleziona/deseleziona i widget desiderati
3. Trascina i widget per riorganizzare il layout
4. Le modifiche vengono salvate automaticamente

## Gestione Proprietà

### Creazione Nuova Proprietà

#### 1. Informazioni Base
```
Titolo: Nome della proprietà
Descrizione: Descrizione dettagliata (supporta HTML)
Indirizzo: Indirizzo completo con CAP
Categoria: Scegli tra quelle create
Tipologia: Casa, Appartamento, Villa, ecc.
```

#### 2. Caratteristiche
```
Camere da letto: Numero
Bagni: Numero  
Ospiti massimi: Numero
Metri quadrati: Dimensione
Piano: Numero piano
Ascensore: Sì/No
```

#### 3. Prezzi Base
```
Prezzo Notte (Bassa Stagione): €
Prezzo Notte (Alta Stagione): €
Pulizia Finale: €
Caparra: € o %
Tassa Soggiorno: € per persona/notte
```

#### 4. Regole Prenotazione
```
Soggiorno Minimo: Notti
Soggiorno Massimo: Notti
Anticipo Prenotazione: Giorni
Check-in: Orario
Check-out: Orario
Cambio Giorno: Qualsiasi/Solo weekend/ecc.
```

#### 5. Gallery Immagini
- **Minimo 5 foto** per proprietà
- **Risoluzione raccomandata**: 1200x800px
- **Formati supportati**: JPG, PNG, WebP
- **Dimensione massima**: 2MB per foto
- **Ordine**: Trascina per riordinare

#### 6. Servizi e Amenities
Seleziona tutti i servizi disponibili:
- Connessione Internet
- Parcheggio  
- Aria Condizionata
- Riscaldamento
- Cucina Attrezzata
- TV
- Asciugacapelli
- Biancheria
- Pulizia

### Modifica Proprietà Esistenti

#### Modifica Rapida
1. Vai su **Proprietà > Tutte le Proprietà**
2. Clicca su **Modifica Rapida** sotto il titolo
3. Modifica i campi necessari
4. Clicca **Aggiorna**

#### Modifica Completa
1. Clicca sul titolo della proprietà
2. Modifica le sezioni necessarie
3. **Salva bozza** per modifiche incomplete
4. **Pubblica** per rendere effettive le modifiche

#### Duplica Proprietà
1. Seleziona la proprietà da duplicare
2. Scegli **Azioni in massa > Duplica**
3. Modifica titolo e dettagli specifici
4. Pubblica la nuova proprietà

### Eliminazione Proprietà

⚠️ **ATTENZIONE**: L'eliminazione è permanente e rimuove:
- Tutti i dati della proprietà
- Cronologia prenotazioni associate
- Gallery immagini
- Recensioni clienti

#### Procedura Sicura
1. **Backup** database prima dell'eliminazione
2. **Verifica** nessuna prenotazione attiva
3. **Sposta nel cestino** invece di eliminare definitivamente
4. **Svuota cestino** solo dopo conferma

## Configurazione Prezzi Stagionali

### Creazione Periodi Stagionali

#### 1. Definire le Stagioni

**Alta Stagione** (Esempio: Giugno-Settembre)
```
Nome Periodo: Alta Stagione Estate
Data Inizio: 01/06/2025
Data Fine: 30/09/2025
Prezzo Notte: €150
Soggiorno Minimo: 7 notti
Cambio Giorno: Solo Sabato
```

**Media Stagione** (Esempio: Aprile-Maggio, Ottobre)
```
Nome Periodo: Media Stagione
Data Inizio: 01/04/2025
Data Fine: 31/05/2025
Prezzo Notte: €100
Soggiorno Minimo: 3 notti
Cambio Giorno: Weekend
```

**Bassa Stagione** (Esempio: Novembre-Marzo)
```
Nome Periodo: Bassa Stagione
Data Inizio: 01/11/2025
Data Fine: 31/03/2026
Prezzo Notte: €70
Soggiorno Minimo: 2 notti
Cambio Giorno: Qualsiasi
```

#### 2. Periodi Speciali

**Festività/Eventi**
```
Capodanno: 29/12 - 02/01 (+50% sul prezzo base)
Pasqua: Date variabili (+30% sul prezzo base)
Ferragosto: 10/08 - 20/08 (+40% sul prezzo base)
Eventi Locali: Date specifiche (prezzo personalizzato)
```

#### 3. Priorità Periodi
I periodi vengono applicati in ordine di priorità:
1. **Eventi Speciali** (massima priorità)
2. **Festività**
3. **Periodi Stagionali**
4. **Prezzo Base** (fallback)

### Gestione Avanzata Prezzi

#### Sconti e Maggiorazioni

**Sconto Soggiorno Lungo**
```
7-13 notti: -10%
14-20 notti: -15%
21+ notti: -20%
```

**Last Minute (< 7 giorni)**
```
Sconto: -15%
Condizioni: Solo bassa stagione
```

**Early Booking (> 60 giorni)**
```
Sconto: -10%
Condizioni: Tutte le stagioni
```

#### Gestione Occupazione
```
Doppia: Prezzo base
Singola: -20%
Ospite Extra: +€20/notte per persona
```

## Gestione Prenotazioni

### Stati Prenotazione

#### Ciclo di Vita Prenotazione
1. **Pendente**: Prenotazione creata, in attesa pagamento
2. **Confermata**: Pagamento ricevuto, prenotazione attiva
3. **Check-in**: Cliente arrivato
4. **Check-out**: Soggiorno completato
5. **Annullata**: Prenotazione cancellata

### Creazione Prenotazione Manuale

1. **Proprietà > Calendario**
2. Seleziona le date desiderate
3. Clicca **Nuova Prenotazione**
4. Compila i dati cliente:
```
Nome: Nome completo
Email: Email valida
Telefono: Numero di contatto
Documento: Tipo e numero
Indirizzo: Indirizzo completo
Ospiti: Numero totale
```

5. **Calcolo Automatico**:
   - Notti totali
   - Prezzo per notte (secondo stagionalità)
   - Tasse e commissioni
   - Totale finale

6. **Modalità Pagamento**:
   - Contanti
   - Bonifico
   - PayPal (link inviato al cliente)
   - Pagamento in struttura

### Modifica Prenotazioni

#### Cambio Date
1. Verifica disponibilità nuove date
2. Calcola differenza prezzo
3. Invia notifica modifica al cliente
4. Aggiorna calendario automaticamente

#### Modifica Ospiti
1. Verifica capacità massima
2. Ricalcola prezzo se necessario
3. Aggiorna dati prenotazione

#### Aggiunta Servizi Extra
```
Colazione: €10/persona/giorno
Pulizia Extra: €50
Biancheria Extra: €15/set
Animali Domestici: €20/notte
Parcheggio: €10/giorno
```

### Annullamenti

#### Politiche Annullamento

**Flessibile**
```
>24h prima: Rimborso 100%
<24h prima: Rimborso 50%
No-show: Nessun rimborso
```

**Moderata**
```
>7 giorni: Rimborso 100%
2-7 giorni: Rimborso 50%
<2 giorni: Nessun rimborso
```

**Rigida**
```
>30 giorni: Rimborso 100%
15-30 giorni: Rimborso 50%
<15 giorni: Nessun rimborso
```

## Gestione Clienti

### Database Clienti

#### Informazioni Salvate
```
Dati Anagrafici:
- Nome e Cognome
- Data di Nascita
- Codice Fiscale
- Documento Identità

Contatti:
- Email
- Telefono
- Indirizzo

Storico:
- Prenotazioni passate
- Spesa totale
- Preferenze
- Note private
```

#### Classificazione Clienti
```
Nuovo: Prima prenotazione
Fedele: 2-4 prenotazioni
VIP: 5+ prenotazioni
Corporate: Clienti aziendali
```

### Comunicazioni Automatiche

#### Email Template

**Conferma Prenotazione**
```
Oggetto: Conferma prenotazione #{booking_id}
Contenuto:
- Dettagli prenotazione
- Istruzioni check-in
- Contatti struttura
- Link annullamento
```

**Promemoria Check-in**
```
Oggetto: Il tuo soggiorno inizia domani
Invio: 1 giorno prima arrivo
Contenuto:
- Orari check-in
- Indirizzo e indicazioni
- Contatto emergenza
```

**Richiesta Recensione**
```
Oggetto: Come è andato il soggiorno?
Invio: 3 giorni dopo check-out
Contenuto:
- Link recensione
- Sconto prossima prenotazione
- Offerte speciali
```

## Reportistica e Statistiche

### Dashboard Analytics

#### Metriche Principali
```
Occupancy Rate: % camere occupate
ADR (Average Daily Rate): Prezzo medio notte
RevPAR: Ricavo per camera disponibile
Booking Lead Time: Giorni anticipo prenotazione
```

#### Report Finanziari

**Report Mensile**
- Entrate totali
- Prenotazioni per proprietà
- Confronto anno precedente
- Trend stagionale

**Report Annuale**
- Performance per proprietà
- Clienti top spender
- Stagionalità ricavi
- Proiezioni future

### Export Dati

#### Formati Disponibili
- **CSV**: Per Excel/Google Sheets
- **PDF**: Report stampabili
- **JSON**: Per integrazioni

#### Dati Esportabili
```
Prenotazioni:
- Periodo selezionabile
- Filtri per stato/proprietà
- Campi personalizzabili

Clienti:
- Lista completa
- Segmentazione
- GDPR compliant

Proprietà:
- Performance metrics
- Occupancy rates
- Pricing analysis
```

## Manutenzione Database

### Pulizia Automatica

Il plugin include sistema di pulizia automatica:

#### Dati Rimossi Automaticamente
```
Prenotazioni Annullate: Dopo 12 mesi
Log Sistema: Dopo 6 mesi
Sessioni Scadute: Dopo 24 ore
Cache Temporanee: Dopo 7 giorni
```

#### Configurazione Pulizia
1. **Impostazioni > Manutenzione**
2. Configura periodi di retention
3. Abilita/disabilita pulizia automatica
4. Programma orario esecuzione

### Ottimizzazione Performance

#### Indici Database
```sql
-- Indici automatici creati dal plugin
CREATE INDEX idx_booking_dates ON bookings(check_in, check_out);
CREATE INDEX idx_property_status ON properties(status);
CREATE INDEX idx_customer_email ON customers(email);
```

#### Cache Sistema
```
Query Cache: 1 ora
Property Data: 6 ore
Pricing Rules: 24 ore
Settings: 1 settimana
```

### Monitoraggio Salute

#### Controlli Automatici
- **Integrità referenziale**: Verifica relazioni tabelle
- **Duplicati**: Identifica record duplicati
- **Performance**: Monitora query lente
- **Spazio**: Controlla utilizzo storage

## Backup e Restore

### Estrategia Backup

#### Backup Automatico
```
Frequenza: Giornaliero (3:00 AM)
Ritention: 30 giorni
Compressione: Sì (gzip)
Crittografia: AES-256
```

#### Cosa Viene Salvato
```
Dati Plugin:
- Tabelle proprietà
- Prenotazioni
- Clienti
- Impostazioni

File Media:
- Gallery proprietà
- Template personalizzati
- Log sistema
```

### Backup Manuale

1. **Impostazioni > Backup**
2. Clicca **Crea Backup Ora**
3. Attendi completamento
4. Download file backup

### Procedura Restore

⚠️ **ATTENZIONE**: Il restore sovrascrive tutti i dati esistenti.

#### Pre-Restore
1. **Crea backup** stato attuale
2. **Metti sito in manutenzione**
3. **Disattiva plugin** temporaneamente

#### Restore
1. **Impostazioni > Backup**
2. **Carica file** backup
3. Seleziona **componenti da ripristinare**:
   - Solo dati
   - Solo impostazioni
   - Tutto
4. Clicca **Avvia Restore**
5. **Riattiva plugin**
6. **Verifica funzionamento**

#### Post-Restore
1. Controlla integrità dati
2. Testa funzioni principali
3. Svuota cache
4. Rimuovi modalità manutenzione

### Migrazione Dati

#### Da Altri Plugin
Supporto importazione da:
- WP Hotel Booking
- BookingWP
- WooCommerce Bookings
- Hotel Booking Lite

#### Formato Import
```csv
property_title,address,price,category,description
"Villa Mare","Via Roma 1, 12345 Città",150,"villa","Bella villa vista mare"
```

---

## Risoluzione Problemi Comuni

### Performance Lente
```
1. Svuota cache plugin
2. Ottimizza database
3. Controlla conflitti plugin
4. Verifica risorse hosting
```

### Email Non Inviate
```
1. Testa configurazione SMTP
2. Controlla spam folder
3. Verifica DNS/SPF record
4. Prova provider diverso
```

### Errori PayPal
```
1. Verifica credenziali API
2. Controlla modalità (Sandbox/Live)
3. Testa connessione internet
4. Controlla webhook configuration
```

---

**Versione Guida**: 1.0  
**Ultimo Aggiornamento**: Settembre 2025