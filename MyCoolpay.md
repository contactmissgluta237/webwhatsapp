Voici comment mycoolpay fonctionne: 
1- il y a la transaction 

curl --location -g 'https://my-coolpay.com/api/{public_key}/payin' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--data-raw '{
    "transaction_amount": 100,
    "transaction_currency": "XAF",
    "transaction_reason": "Bic pen",
    "app_transaction_ref": "order_124",
    "customer_phone_number": "699009900",
    "customer_name": "Bob MARLEY",
    "customer_email": "bob@mail.com",
    "customer_lang": "en"
}'
la plupart des infos là on les a pour un client donné! il faut que l'app_transaction_ref soit unique, je pense que tu sauras comment gérer, peut être utilisé l'id de la recharge, tu sauras géré! La clé publique on peut lavoir via config/services.php! 
une fois la transaction lancée, nous aurons ça comme réponse de la part de coolpay: 
{
  "status": "success",
  "transaction_ref": "17178321-c6d229f2-5c12-43a7-85d3-2c687425e2fe",
  "action": "PENDING",
  "ussd": "#150*50#"
}
ça va donc permettre dattendre la validation cliente, qu'il entre son code et tout... 1 min environ après, nous aurons le callback (webhook) de coolpay.
2- il y a le callback (webhook). After each transaction, My-CoolPay notifies your system by making a POST request to the callback URL of your application with the following body:
Exemple: 
{
    "application": "{public_key}",
    "app_transaction_ref": "order_123",
    "operator_transaction_ref": "MP200618.1634.A34527",
    "transaction_ref": "18ac6335-2bdd-4b95-944e-ef029c49c5b5",
    "transaction_type": "PAYIN",
    "transaction_amount": 100,
    "transaction_fees": 2,
    "transaction_currency": "XAF",
    "transaction_operator": "CM_OM",
    "transaction_status": "SUCCESS",
    "transaction_reason": "Bic pen",
    "transaction_message": "Your transaction has been successfully completed",
    "customer_phone_number": "699009900",
    "signature": "d41d8cd98f00b204e9800998ecf8427e"
}

7.3. Securing the callback URL
In order to reduce the risk of attack, it is also recommended to choose a complex callback URL (difficult to remember or imagine).
e.g. https://mywebsite.com/callback/jkdKo0Lp8lsdfjk4j0HJhskfak93d






maintennat comment ça doit fonctioner côté customer! quand le client a choisi le pays (mycoolpay ne fonctionne qu'au cameroun), il est sur la page de recharge, il a choisi le montant, le pays, et tout ce qui est necessaire , il clique sur recharger, le loader doit s'activer durant tout le processus, donc il verra que le bouton recharger est en mode "loader" et dès qu'on reçoit le premier response de myccolpay: 
{
  "status": "success",
  "transaction_ref": "17178321-c6d229f2-5c12-43a7-85d3-2c687425e2fe",
  "action": "PENDING",
  "ussd": "#150*50#"
}
on lui affiche un message du genre: veuilez soit valider la transaction sur votre mobile, soit taper #150*50#, vous devriez avoir reçu le message de retrait Orange/Mtn provenant de DIgital House International! 

pendant ce temps on reste en mode loader, et on attend le callback de mycoolpay, si le callback nous dit que la transaction est un succès, on crédite le wallet du client et on lui affiche un message de succès, si le callback nous dit que la transaction a échoué, on lui affiche un message d'erreur!