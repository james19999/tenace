# FedaPay Sandbox — paiement Mobile Money à la livraison

Cette intégration est limitée au Sandbox. Le code refuse toute configuration autre que `sandbox`; aucun paiement Live ne peut être initialisé.

## Parcours intégré

Depuis `Livreur > Commandes disponibles > Mes commandes en cours`, sur une commande togolaise à payer à la livraison, le livreur peut lancer une demande Mixx by Yas ou Moov Money. Il fournit le numéro et l’e-mail du client. Tenace calcule le solde depuis la commande, crée une transaction XOF, puis envoie la demande à l’opérateur. Le client confirme sur son téléphone avec son code PIN. Le livreur peut valider la livraison seulement après que Tenace a vérifié `approved` auprès de FedaPay.

Le choix Mixx by Yas utilise le mode API FedaPay `togocel`, nommé TOGOCEL T-Money dans la documentation technique. L’interface affiche le nom Mixx by Yas. Vérifier dans le compte Sandbox FedaPay que cette méthode historique est bien proposée pour les essais Mixx actuels.

## Configuration locale

Ajouter au fichier `.env` du serveur (ne pas envoyer les secrets dans le chat et ne pas les committer) :

```dotenv
FEDAPAY_ENVIRONMENT=sandbox
FEDAPAY_SECRET_KEY=sk_sandbox_VALEUR_FOURNIE_PAR_FEDAPAY
FEDAPAY_WEBHOOK_SECRET=wh_sandbox_SECRET_DE_CETTE_URL_WEBHOOK
```

La clé secrète et le secret de signature doivent provenir du Sandbox. Le secret webhook est propre au webhook et à l’environnement.

Après configuration :

```bash
php artisan config:clear
php artisan migrate --force
```

La migration ajoute des colonnes à `order_payments` et un index unique facultatif sur l’identifiant FedaPay. Sur toute base contenant des données, faire une sauvegarde et appliquer d’abord la migration en staging.

## Webhook

Déclarer une URL HTTPS publiquement accessible dans les réglages Webhooks du compte Sandbox :

```text
https://VOTRE-DOMAINE/fedapay/webhook
```

Activer au minimum les événements `transaction.approved`, `transaction.canceled` et les événements d’état transaction utiles. Copier le secret de cette URL Sandbox dans `FEDAPAY_WEBHOOK_SECRET`. La route seule est exemptée de CSRF ; la signature `X-FEDAPAY-SIGNATURE` est vérifiée et le statut est récupéré de nouveau auprès de l’API. L’écran livreur interroge aussi le statut serveur pour permettre les essais en local, où FedaPay ne peut pas joindre `127.0.0.1`.

## Tests

```bash
php artisan test --filter=FedaPay
```

Les tests utilisent des réponses HTTP simulées et ne créent aucune transaction réelle. Utiliser uniquement les scénarios et numéros de test publiés par FedaPay pour le Sandbox. L’adresse locale `127.0.0.1` ne peut pas recevoir de webhook externe; utiliser une URL HTTPS de développement accessible publiquement pour vérifier le webhook de bout en bout.

## Limites / contrôles avant essai

- La configuration actuelle ne contient pas de clés, il faut les ajouter localement au `.env`.
- La disponibilité effective de `togocel` pour Mixx by Yas et de Moov Togo dans Sandbox dépend des méthodes exposées/activées sur le compte.
- Les moyens de paiement Live n’ont pas été activés ou testés. Le code interdit délibérément l’environnement Live.
- En cas d’état toujours `pending`, ne pas relancer une seconde demande ni remettre le colis sans vérifier la transaction dans le tableau de bord FedaPay.
- Si un remboursement est rapporté après approbation, le paiement est marqué `refunded`; un paiement à la livraison non encore livré est retiré du montant payé. Une commande déjà livrée nécessite une réconciliation manuelle.
