- toutes les requêtes doivent être déplacées dans des repositories, à part les requêtes de base comme le CRUD
- Déplacez les traitements dans les services
- S'assurez que tous les tests existent
- Standardisez la façon dont les vues sont structurées! 
- Rendre modulable le projet
- Aucun commentaire en français dans le code! uniquement en anglais
- Remplacer Messages , Conversations par WhatsappMessages! et WhatsappConversations
- Retirer tout ce qui concerne docker! 



- tu utilises random alors que response time est un enum ? app/Enums/ResponseTime.php

Je veux que tu l'utilises plutôt! et c à l'interieur qu'on a centralisé la logique du temps! donc tu dois juste appeler la fonction soit getDelay ou un truc du genre! et il va te retourner ce qu'il faut! 