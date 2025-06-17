# WHMCS Telegram Admin Notification Addon

Dieses Addon erweitert WHMCS um die Möglichkeit, Telegram-Benachrichtigungen gezielt an einzelne Admins oder Gruppen zu senden. Dabei können Telegram-Chat-IDs einzelnen WHMCS-Admins oder Telegram-Gruppen (Gruppenchats) zugewiesen werden. Außerdem können Trigger-Gruppen definiert und Admins/Gruppen diesen zugewiesen werden, um Benachrichtigungen je nach Kategorie zu filtern.

---

## Funktionen

- Verwaltung von Admin-Zuweisungen mit Telegram-Chat-IDs
- Verwaltung von Trigger-Gruppen (z.B. Support, Billing, Sales)
- Mehrfachauswahl von Trigger-Gruppen pro Admin/Chat
- Unterstützung von Gruppen-Chats (Telegram-Gruppen)
- Editieren und Löschen von Admin-Zuweisungen und Gruppen
- Dropdown mit wiederkehrenden Trigger-Gruppen für einfache Auswahl

---

## Installation

1. **Addon-Dateien hochladen**  
   Lade den Ordner `telegram_addon` in dein WHMCS-Verzeichnis unter `/modules/addons/` hoch.

2. **Addon aktivieren**  
   - Melde dich im WHMCS Admin-Bereich an.
   - Gehe zu **Setup > Addon Modules**.
   - Aktiviere das Addon `Telegram Admin Notification`.
   - Klicke auf „Aktivieren“. Dabei werden die benötigten Datenbanktabellen automatisch angelegt.

3. **Telegram Notification Modul konfigurieren**  
   - Gehe zu **Setup > Notifications > Manage Notification Modules**.
   - Aktiviere das Telegram Notification Modul.
   - Trage deinen Telegram-Bot-Token und ggf. eine Standard-Chat-ID ein.
   - Speichere die Einstellungen.

---

## Konfiguration des Addons

### Admin-Zuweisungen verwalten

- Öffne im Adminbereich unter **Addon Modules > Telegram Admin Notification** das Addon.
- Hier kannst du:

  - Einen WHMCS-Admin auswählen.
  - Die zugehörige Telegram-Chat-ID (oder Gruppen-Chat-ID) eingeben.
  - Trigger-Gruppen per Mehrfachauswahl zuweisen.

- Klicke auf **Speichern**, um die Zuweisung anzulegen oder zu aktualisieren.

> ![Screenshot: Admin-Zuweisung anlegen](./screenshots/admin-zuweisung.png)

---

### Trigger-Gruppen verwalten

- Unterhalb der Admin-Zuweisungen findest du die Verwaltung der Trigger-Gruppen.
- Du kannst neue Gruppen hinzufügen oder bestehende löschen.
- Die Gruppen dienen als Filter für unterschiedliche Arten von Benachrichtigungen (z.B. Support, Billing).

> ![Screenshot: Trigger-Gruppen Verwaltung](./screenshots/trigger-gruppen.png)

---

## Benutzung

- Beim Anlegen von Notifications in WHMCS kannst du im Feld **Trigger Identifier** einen der definierten Gruppennamen (z.B. „Support“) angeben.
- Das Addon sorgt dann dafür, dass nur Admins oder Gruppen mit dieser Trigger-Gruppe die Telegram-Benachrichtigung erhalten.

---

## Hinweise

- Telegram-Chat-IDs für Benutzer erhältst du, indem du dem Bot eine Nachricht sendest und die ID über Bot-Tools abrufst.
- Gruppen-Chat-IDs kannst du mit entsprechenden Telegram-Bot-Kommandos oder über APIs ermitteln.
- Für Mehrfachauswahl in den Trigger-Gruppen halte Strg (Windows) oder Cmd (Mac) gedrückt.

---

## Support & Entwicklung

Bei Fragen, Fehlern oder Feature-Wünschen kannst du gerne Issues im GitHub-Repository eröffnen.

---

## Lizenz

Dieses Addon steht unter der MIT-Lizenz.

---

*Screenshots folgen noch…*

