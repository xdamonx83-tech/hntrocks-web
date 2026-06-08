# Architektur-Regeln

1. Web-Optik liegt in Blade Views unter resources/views.
2. Business-Logik kommt nicht in Blade-Dateien.
3. Module bekommen eigene Controller, Requests, Services, Policies und Models.
4. Web und spätere Android-App teilen sich dieselbe Kernlogik.
5. API-Endpunkte werden parallel sauber unter routes/api.php geplant.
6. Marketplace bleibt draußen.
7. Gruppen heißen fachlich immer Teams.
8. Datenbankänderungen nur per Migration.
9. Uploads laufen über ein zentrales Media-System.
10. Jede neue ZIP-Lieferung bekommt eine fortlaufende Nummer.
