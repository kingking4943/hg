# Guida Installazione Rapida - Colitalia Real Estate Manager

## 🚀 Installazione in 5 Minuti

### Passo 1: Installazione Plugin
1. Scarica il file `colitalia-real-estate.zip`
2. Nel tuo WordPress vai su **Plugin > Aggiungi nuovo**
3. Clicca **Carica plugin** e seleziona il file ZIP
4. Clicca **Installa ora** e poi **Attiva**

### Passo 2: Configurazione Guidata
1. Vai su **Colitalia RE > Impostazioni**
2. Segui la configurazione guidata passo passo
3. Configura almeno:
   - Nome azienda e dettagli
   - Email mittente per le notifiche
   - Valuta e formato prezzi

### Passo 3: PayPal (Modalità Test)
1. Vai su **Colitalia RE > Impostazioni > PayPal**
2. Seleziona "Modalità Sandbox"
3. Inserisci credenziali test PayPal:
   - Client ID: `sb-test-client-id`
   - Client Secret: `sb-test-client-secret`
4. Testa con una prenotazione di prova

### Passo 4: Prima Proprietà
1. Vai su **Proprietà > Aggiungi Nuova**
2. Compila i campi base:
   - Titolo: "Casa Vacanza Test"
   - Indirizzo completo
   - Prezzo base: €100/notte
   - Carica almeno 3 foto
3. Pubblica la proprietà

### Passo 5: Test Frontend
1. Crea una nuova pagina
2. Aggiungi lo shortcode: `[colitalia_grid limit="6"]`
3. Visualizza la pagina per vedere le proprietà
4. Testa il form di prenotazione

## ✅ Checklist Post-Installazione

- [ ] Plugin attivato senza errori
- [ ] Configurazione base completata
- [ ] PayPal in modalità sandbox configurato
- [ ] Prima proprietà creata e pubblicata
- [ ] Test prenotazione effettuato
- [ ] Email di conferma ricevuta

## 🆘 Risoluzione Problemi Comuni

### Plugin non si attiva
- Verifica requisiti PHP 7.4+ e WordPress 5.8+
- Controlla log errori in wp-content/debug.log

### Email non funzionano
- Vai su **Impostazioni > Email**
- Abilita SMTP personalizzato
- Configura provider email (Gmail, SendGrid, etc.)

### PayPal non funziona
- Verifica credenziali corrette
- Controlla che SSL sia attivo
- Verifica webhook URL configurato

## 📞 Supporto

- **Email**: supporto@colitalia.com
- **Documentazione**: [Guida Completa](docs/ADMIN_GUIDE.md)
- **Video Tutorial**: [YouTube Colitalia](https://youtube.com/colitalia)

---
**Tempo di setup**: ~15 minuti  
**Difficoltà**: Facile ⭐⭐⭐☆☆
