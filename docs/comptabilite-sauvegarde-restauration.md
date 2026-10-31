# Sauvegarde et essai de restauration TENACOS

## Sauvegarde planifiée

La commande `php artisan finance:backup` crée deux fichiers privés sous `storage/app/finance-backups` : un export compressé de la base et une archive des justificatifs de `storage/app/finance`. Elle est planifiée chaque jour à 01 h 45. Le serveur doit exécuter le planificateur Laravel chaque minute :

```cron
* * * * * cd /chemin/vers/tenace && php artisan schedule:run >> /dev/null 2>&1
```

La rétention locale est de 30 jours par défaut et peut être réglée avec `FINANCE_BACKUP_RETENTION_DAYS`. Les sauvegardes locales doivent ensuite être copiées vers un stockage distinct, protégé et hors du serveur de production.

## Essai de restauration sans toucher à la production

1. Créer une base de test vide, avec un nom distinct de la base utilisée par le site.
2. Pour MariaDB/MySQL ou PostgreSQL, décompresser un fichier `tenacos-finance-*.sql.gz` dans cette base de test avec l’outil du moteur utilisé. Pour SQLite, décompresser le fichier `*.sqlite.gz` dans un nouveau fichier SQLite. Ne lance jamais la restauration sur la base de production pour cet essai.
3. Extraire l’archive `tenacos-finance-*-pieces.zip` dans un répertoire temporaire de test.
4. Comparer le nombre de tables et les principaux totaux financiers avec le rapport de sauvegarde, puis ouvrir quelques justificatifs depuis cet environnement isolé.
5. Consigner la date, le nom de la sauvegarde, l’environnement de test, le résultat et l’identité de la personne qui a réalisé l’essai.

La commande d’application ne restaure volontairement pas de sauvegarde : l’écrasement d’une base active doit suivre la procédure d’exploitation et être précédé d’une sauvegarde distincte de l’état courant.

## Limites à valider

- La commande nécessite `mysqldump` (MySQL/MariaDB), `pg_dump` (PostgreSQL), ou le support `VACUUM INTO` du pilote SQLite, ainsi que l’extension PHP `ZipArchive`.
- Les pièces financières enregistrées en dehors de `storage/app/finance` doivent être ajoutées à la politique de sauvegarde de leur module.
- Vérifier que les sauvegardes sont bien copiées hors machine : une archive conservée uniquement sur le serveur ne protège pas d’une panne de ce serveur.
