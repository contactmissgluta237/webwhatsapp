## il faut qu'on trouve un moyen de définir de façon globale les gains en tant que parrain quand on référence une personne! puis on doit pouvoir définir ça sur une personne en particulier! super important, les grands ambassadeurs pourront gagner bcp, jusqu'à 50% (MEDIUM)

c mm déjà implémenté! il faut qu'on teste juste et on optimise! en gros ladmin peut configurer le pourcentage de gain dune personne! par défaut lors de la création ça doit utiliser la valeur constante défini dans les configs! Et maintenant si on veut afficher nimporte quoi dans la devise du pays de celui qui est connectée, il nous faut un helper pour convertir pour laffichage! ça c important!

## voir comment intégrer la fonctionnalité de retrait. de façon globale, et spécifiquement à mycoolpay! préciser lors des recharges ou retrait que chaque transaction pourrait avoir des frais liés à lopérateur, donc il est possible que le montant net qui vous sera débité est supérieur au montant attendu (pour la recharge) , et il est possible que le montant net retiré soit inférieur à celui attendu (pour le retrait) (LOW)

il faut qu'on fasse en sorte que le retrait passe!

## penser comment gérer le système de recharge hein, qd quelqu'un recharge on doit tjrs afficher en xaf, ou bien mm si on affiche en sa monnaie, toutes nos opérations doivent se faire en XAF! (HIGH)

De façon globale, on doit afficher les montants en Dollar et en (la monnaie du pays de lutilisateur!). Super important! Mais dans la bd, on fait les calculs et tout en XAF! le CFA ou XAF ne doit pas se balader partout, on doit utiliser le fichier de config où on definiera la devise par défaut, c plus propre et on appele ça partt! 

## revoir tout le design côté Admin! puis Testez toutes les fonctionnalités côté admin (HIGH)

Depuis là on ne teste mm pas le volet admin! les fonctionnalités admin on ne les teste mm pas! genre l'admin doit pouvoir créer les utilisateurs, modifier les packages, configurer les éléments, visualiser tout ce qu'il veut! c super important ! il faut qu'on le fasse! 

## Se rassurer que les requêtes qui sortent du CRUD soient dans un repository! c ça le code propre! (MEDIUM)

c important davoir un code clean, propre! les requêtes qui ne sont pas CRUD doivent être dans un repository!

## S'assurer que chaque fois qu'on débite le compte , il y a une internaltransaction qui est créée! (HIGH)

## Le calcul du nombre de messages à débiter a été mal fait car il y avait des produits et médias mais ça n'a coupé qu'un seul message sur mon nombre de messages disponibles. (HIGH)

## Se rassurer qu'on a la traduction dans toutes les pages, et tous les messages. (HIGH)

## Se rassurer qu'on utilise des exceptions personnalisées quand il le faut! (LOW)

## Se rassurer qu'en cas d'absence de prompt, on ait un prompt dagent commercial par défaut (MEDIUM)

ça doit mm exister, mais il faut verifier et confirmer! 

## Mettre à jour les tests (MEDIUM)
- commencer par fixer tous les tests qui échouent
- rassurer qu'on a les tests partout
- verifier le taux de couverture des tests
- verifier que tous les tests E2E fonctionnent! et penser dautres cas

## Dès que l'ia a besoin de vérifier une information, il faut qu'il signale pour qu'on puisse notifier le propriétaire du besoin, en précisant l'info , le numéro qui en a besoin! (HIGH)

en cours, il faut bien tester!

## penser si on peut optimiser le système de réponse de l'ia pour consommer moins de tokens (LOW)

## - Faire en sorte que si un être humain réponde, l'ia se désactive (MEDIUM)

## Supprimer tous les fichiers inutiles (LOW)

- genre les fichiers pas utilisés, pas necessaires

## Se rassurer que toutes les pages sont bien traduites, et ont le mm standard, que tout soit uniforme (LOW)

## Les permissions, surtout pr la partie finance, et conversations (HIGH)


## Faire en sorte que certains listeners utilisent les queues, super important! sinon ça va ralentir(HIGH)

il faudra faire le tri et update certains listeners!

## Mettre à jour la traduction partout! (LOW)

Que ce soit dans les form requests, la base de donnée, les vues, les mails, tt doit être traduit!


## Verifiez qu'en cas de déconnexion, lutilisateur peut rafraichir sa session!(HIGH) 

si il est déconnecté, alors lutilisateur doit pouvoir raffraichir en scannatn un nouveau qrcode! sinon il ne peut plus rien recevoir ni configuré! 

on doit dabord lancer la suppression côté nodejs, avant de supprimer laravel! je crois que nodejs a déjà un endpoint pour ça! 

## Lors de la génération de Qrcode on doit faire expirer le qrcode après 1min, puis permettre de regénérer! Ensuite on va optimiser le fetching du qrcode pr que ce soit plus rapide (si necessaire) (MEDIUM)


## Implémenter le fait que si le client veut fixer un rendez vous l'ia doit notifier le propriétaire du compte (MEDIUM)