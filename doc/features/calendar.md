# Calendrier

GVV utilise un calendrier pour noter les présences. Il permet de signaler une intention de vol ou un autre événement dans un calendrier partagé.

## Implémentation

* L'interface graphique est gérée par la librairie javascript fullcalendar.
* Et la persistence par un calendrier Google. Cela permettrait de visualiser le calendrier sans connexion à GVV mais cette fonctionnalité n'est pas utilisée. De plus l'utilisation de l'API Google calendar complique l'installation de façon significative.
* L'interface avec Google Calendar est gérée par la librairie javascript gcal.

Le formulaire de création redirige lsr la racine du site web par exemple "http://gvv.net"

C'est un peu douteux, cela utilise le fait que c'est la page par défaut.

```
$route['default_controller'] = "calendar";
```

'''
<button type="button">Enregistrer</button>
'''

### calendar.js

La logique du calendrier est gérée en javascript dans ce fichier.

la fonction add_event est appelée quand le bouton "Enregistrer" est cliqué. 

Elle génère une requête ajax sur index.php/presences/ajout/json.

### Email notification

Based on the codebase context provided, I don't see any explicit email notification functionality within the GoogleCal.php file or the other provided code snippets. The GoogleCal class handles interactions with Google Calendar (creating, updating, deleting events), but there's no direct code for sending email notifications.

Google Calendar itself has a built-in notification system that sends emails when events are created or modified. These notifications are controlled by Google Calendar settings rather than within your application code.

To disable these notifications, you have two options:

Google Calendar Settings (External to your code):

Log into the Google Calendar account being used
Go to Settings (gear icon) > Settings
Navigate to the specific calendar settings
Under "Event notifications" or "Other notifications", disable email notifications for new events
