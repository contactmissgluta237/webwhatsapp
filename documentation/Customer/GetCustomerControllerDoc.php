<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CustomerResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *         @OA\Schema(
 *
 *             @OA\Property(
 *                 property="data",
 *                 ref="#/components/schemas/CustomerData"
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Client récupéré avec succès"
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Get(
 *     path="/api/customers/{customerId}",
 *     summary="Récupérer un client spécifique",
 *     description="Récupérer les informations d'un client spécifique par son ID avec ses adresses de livraison.",
 *     operationId="api.customers.show",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="customerId",
 *         in="path",
 *         required=true,
 *         description="ID du client à récupérer",
 *
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Client récupéré avec succès",
 *
 *         @OA\JsonContent(ref="#/components/schemas/CustomerResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Client non trouvé",
 *
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Erreur interne du serveur",
 *
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class GetCustomerControllerDoc {}
