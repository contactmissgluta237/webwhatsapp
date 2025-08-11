<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ApiResponse",
 *     description="Réponse standard de l'API",
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object", nullable=true, description="Données retournées par l'API")
 * )
 *
 * @OA\Schema(
 *      schema="ErrorResponse",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *          @OA\Schema(
 *
 *              @OA\Property(property="success", type="boolean", example=false),
 *              @OA\Property(property="message", type="string", example="Une erreur est survenue.")
 *          )
 *      }
 *  )
 *
 * @OA\Schema(
 *     schema="ValidationErrorDetail",
 *     type="array",
 *
 *     @OA\Items(type="string", example="Le champ email est requis.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *         @OA\Schema(
 *
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Erreur de validation."),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 description="Détail des erreurs de validation",
 *                 @OA\Property(property="field_name", ref="#/components/schemas/ValidationErrorDetail")
 *             )
 *         )
 *     }
 * )
 */
class BaseSchemas {}
