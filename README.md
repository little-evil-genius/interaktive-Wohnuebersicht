# interaktive Wohnübersicht
Dieses Plugin erweitert das Board um eine manuelle Liste über die Wohnorte der Charaktere. Ausgewählte Gruppen können Straßen und Wohnorte hinzufügen, welche vom Team freigeschaltet werden müssen. Wohnorte vom Team werden direkt freigeschalt. Die Benachrichtigung erfolgt über eine MyAlert Benachrichtigung.<br>
Den Straßen können Beschreibungen und Bewertungen hinzugefügt werden. User können nur in einen einzigen Wohnort einziehen und die Adresse (Straße Nummer) wird dann über eine Variable im Profil angezeigt. Genau wie alle Mitbewohner von dem Charakter.

# Voraussetzungen
- Eingebundene Icons von Fontawesome

# Datenbank-Änderungen
Hinzugefügte Tabellen:
- PRÄFIX_residences_streets
- PRÄFIX_residences_home
- PRÄFIX_residences_user

# Neue Templates
- residences
- residences_home	
- residences_home_add	
- residences_home_edit
- residences_memberprofile_address
- residences_memberprofile_roomates
- residences_modcp_home
- residences_modcp_home_bit
- residences_modcp_nav	
- residences_modcp_streets
- residences_modcp_streets_bit
- residences_streets
- residences_streets_add	
- residences_streets_edit
- residences_user

# Template Änderungen - neue Variablen
- header - {$new_street_alert} {$new_home_alert}
- modcp_nav_users - {$nav_residences}
- member_profile - {$residences_address} {$residences_roommate}

# ACP-Einstellungen - Wohnübersicht
- Erlaubte Gruppen für die Wohnorte
- Erlaubte Gruppen für die Straßen
- Straßen löschen User
- Wohnorte löschen User

# Links
- https://euerforum.de/misc.php?action=residences
- https://euerforum.de/modcp.php?action=residences_streets
- https://euerforum.de/modcp.php?action=residences_home

# CSS Erweiterung
<blockquote>
  :root {
  --street-rate: red;
  }
  </blockquote>

# Demo
<img src="https://stormborn.at/plugins/wohnuebersicht_add.png">
<img src="https://stormborn.at/plugins/wohnuebersicht_streets.png">
<img src="https://stormborn.at/plugins/wohnuebersicht_streets2.png">
<img src="https://stormborn.at/plugins/wohnuebersicht_profil.png">
<img src="https://stormborn.at/plugins/wohnuebersicht_alerts.png">
<img src="https://stormborn.at/plugins/wohnuebersicht_modcp.png">
<img src="https://stormborn.at/plugins/wohnuebersicht_modcp2.png">
