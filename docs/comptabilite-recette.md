# Recette du module financier TENACOS

Cette recette couvre les fonctions financières livrées sans plan comptable ni écritures en partie double. Les tableaux de marge, valeurs d’immobilisations et prévisions sont des données de gestion ; le cabinet doit confirmer les règles comptables et fiscales avant leur usage comme chiffres validés.

## Avant le démarrage

1. Faire une sauvegarde complète de la base et des justificatifs.
2. Vérifier les règles proposées avec la Direction : seuil renforcé strictement supérieur à 50 000 F, approbation financière puis DG au-delà, séparation demandeur/approbateur, comptes autorisés et droits par boutique.
3. Faire confirmer par la Direction et le cabinet la date de reprise et les soldes d’ouverture de la caisse générale et des boutiques. Saisir chaque ouverture une seule fois avec date, montant, motif et auteur ; comparer le journal et le solde affiché.
4. Vérifier que les migrations sont appliquées sur l’environnement de recette et que le planificateur Laravel tourne chaque minute.
5. Créer des utilisateurs de recette distincts : DG, responsable financier, caissier, responsable boutique et auditeur.

## Parcours à vérifier

- **Sommes à encaisser :** sur une commande non livrée, enregistrer un paiement partiel et vérifier le reste à encaisser ; confirmer qu’une commande livrée, annulée, grossiste/partenaire ou déjà réglée n’apparaît pas dans cette liste. La règle métier est qu’une commande n’est marquée livrée qu’après encaissement complet.
- **Achats et fournisseurs :** créer une réception de stock, lui associer sa facture, contrôler le fournisseur et le montant, enregistrer un règlement partiel puis final, vérifier la dette résiduelle, les références et les mouvements de caisse. Essayer de rattacher une seconde facture active à la même réception et un paiement supérieur au reste dû : les deux doivent être bloqués ou signalés.
- **Remises et dépenses :** déclarer une remise boutique, la faire prendre en charge puis confirmer sa réception par le DG ; vérifier le transit avant confirmation et le solde de caisse après. Contrôler les dépenses à 50 000 F et 50 001 F, la séparation des rôles, le paiement partiel et l’historique.
- **Budgets :** définir un budget central et un budget boutique, payer une dépense dans chaque périmètre, vérifier le réalisé et le disponible ; vérifier l’alerte visuelle lors d’un dépassement.
- **Prévisions 13 semaines :** vérifier le solde d’ouverture affiché, la moyenne des quatre dernières semaines complètes, les dépenses autorisées et factures fournisseurs à échéance. Confirmer que le résultat est marqué comme estimatif et que les créances sans date prévue ne sont pas présentées comme encaissées.
- **Personnel et commissions :** soumettre une avance et un règlement de commission, vérifier les approbations et le débit uniquement après paiement autorisé ; enregistrer un remboursement partiel, vérifier le solde restant et l’entrée en caisse.
- **Immobilisations :** enregistrer un bien avec coût, valeur résiduelle, durée, lieu et pièce jointe ; vérifier la valeur nette estimée, le téléchargement protégé de la pièce et l’export du registre.
- **Droits :** tenter d’ouvrir les fonctions de gestion avancée avec chaque profil. Seuls l’administrateur et le comptable doivent y accéder ; le responsable boutique garde les parcours de caisse/remise autorisés à son rôle.
- **Sauvegarde/restauration :** exécuter `php artisan finance:backup` sur l’environnement de recette ; vérifier les deux archives et leurs droits d’accès. Restaurer la base et les pièces dans un environnement isolé, comparer les nombres de lignes et les totaux, puis ouvrir quelques pièces. Ne pas restaurer par-dessus la production pendant la recette.

## Critère de mise en production

Joindre à la validation les résultats attendus/obtenus, la date, l’environnement, le testeur et les anomalies. La Direction valide les soldes initiaux et la matrice de délégation ; le cabinet confirme les règles comptables, les fournisseurs/achats et les exports. La sauvegarde hors serveur et un essai de restauration réussi doivent être constatés par l’équipe d’exploitation avant la bascule.
