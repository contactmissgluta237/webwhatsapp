## Les permissions, surtout pr la partie finance, et conversations (HIGH)

## revoir tout le design côté Admin! puis Testez toutes les fonctionnalités côté admin (HIGH)

Depuis là on ne teste mm pas le volet admin! les fonctionnalités admin on ne les teste mm pas! genre l'admin doit pouvoir créer les utilisateurs, modifier les packages, configurer les éléments, visualiser tout ce qu'il veut! c super important ! il faut qu'on le fasse! 

## il faut qu'on trouve un moyen de définir de façon globale les gains en tant que parrain quand on référence une personne! puis on doit pouvoir définir ça sur une personne en particulier! super important, les grands ambassadeurs pourront gagner bcp, jusqu'à 50% (MEDIUM)

c mm déjà implémenté! il faut qu'on teste juste et on optimise! en gros ladmin peut configurer le pourcentage de gain dune personne! par défaut lors de la création ça doit utiliser la valeur constante défini dans les configs! Et maintenant si on veut afficher nimporte quoi dans la devise du pays de celui qui est connectée, il nous faut un helper pour convertir pour laffichage! ça c important!

## Se rassurer que les requêtes qui sortent du CRUD soient dans un repository! c ça le code propre! (MEDIUM)

c important davoir un code clean, propre! les requêtes qui ne sont pas CRUD doivent être dans un repository!

## Mettre à jour les tests (MEDIUM)
- commencer par fixer tous les tests qui échouent
- rassurer qu'on a les tests partout
- verifier le taux de couverture des tests
- verifier que tous les tests E2E fonctionnent! et penser dautres cas

## - Faire en sorte que si un être humain réponde, l'ia se désactive (MEDIUM)

## Lors de la génération de Qrcode on doit faire expirer le qrcode après 1min, puis permettre de regénérer! Ensuite on va optimiser le fetching du qrcode pr que ce soit plus rapide (si necessaire) (MEDIUM)


## Implémenter le fait que si le client veut fixer un rendez vous l'ia doit notifier le propriétaire du compte (MEDIUM)

## Faire en sorte que la vue de création et la vue de rafraichissement ait exactement le mm design! que le loader marche! et que les fonctionnalités dans http://localhost:8000/customer/whatsapp/create genre: Connexion en cours... (Tentative 26/60) - Temps restant: ~1min! soit aussi là bas!  (MEDIUM)
bref on doit sassurer davoir un seul composant pr le qrcode, un seul composant pr les instructions, et que ceux qui les appellent les utilise de la mm façon!

## voir comment intégrer la fonctionnalité de retrait. de façon globale, et spécifiquement à mycoolpay! préciser lors des recharges ou retrait que chaque transaction pourrait avoir des frais liés à lopérateur, donc il est possible que le montant net qui vous sera débité est supérieur au montant attendu (pour la recharge) , et il est possible que le montant net retiré soit inférieur à celui attendu (pour le retrait) (LOW)

il faut qu'on fasse en sorte que le retrait passe!

## Se rassurer qu'on utilise des exceptions personnalisées quand il le faut! (LOW)

## penser si on peut optimiser le système de réponse de l'ia pour consommer moins de tokens (LOW)

## Supprimer tous les fichiers inutiles (LOW)

- genre les fichiers pas utilisés, pas necessaires

## Se rassurer que toutes les pages sont bien traduites, et ont le mm standard, que tout soit uniforme (LOW)

Que ce soit dans les form requests, la base de donnée, les vues, les mails, tt doit être traduit!


## Retouchez la page de rafraichissement (LOW)

Juste le rendre plus agréable, plus beau! et de façon générale on dirait que rien ne saligne sur la verticale avec les breadcrumbs, tt déborde de la gauche

## Implémenter un ssytème côté nodejs pr supprimer les sessisons qrcode ready qui ont plus de 15 min (LOW)

## Permettre qu'on puisse désactiver lia sur une conversation. Ou plus loin, le désactiver pr un temps. (LOW)
il faudra se rassurer qu'avant de répondre à une converstaion existante, l'ia verifie que agent_enabled sur la conversation! 