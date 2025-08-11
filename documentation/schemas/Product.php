<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CommonProductProperties",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", description="ID de la catégorie de produit", example=1),
 *     @OA\Property(property="type", type="string", enum={"bottle", "accessory"}, description="Type de produit", example="bottle"),
 *     @OA\Property(property="name", type="string", description="Nom du produit", example="Bouteille de 6Kg"),
 *     @OA\Property(property="description", type="string", description="Description du produit", example="Une bouteille de gaz de 6 kilogrammes."),
 *     @OA\Property(property="quantity", type="integer", description="Quantité en stock", example=40)
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/CommonProductProperties"),
 *         @OA\Schema(
 *             oneOf={
 *                 @OA\Schema(ref="#/components/schemas/BottleProduct"),
 *                 @OA\Schema(ref="#/components/schemas/AccessoryProduct")
 *             }
 *         )
 *     }
 * )
 * @OA\Schema(
 *     schema="BottleProduct",
 *     type="object",
 *
 *     @OA\Property(property="capacity", type="number", format="float", description="Capacité de la bouteille en litres", example=6),
 *     @OA\Property(property="height", type="number", format="float", description="Hauteur de la bouteille en cm", example=45.5),
 *     @OA\Property(property="weight", type="number", format="float", description="Poids de la bouteille vide en kg", example=5.2),
 *     @OA\Property(property="radius", type="number", format="float", description="Rayon de la bouteille en cm", example=15.2),
 *     @OA\Property(property="content_price", type="number", format="float", description="Prix du gaz seul", example=6500),
 *     @OA\Property(property="bottle_with_content_price", type="number", format="float", description="Prix de la consigne (bouteille + gaz)", example=18500)
 * )
 *
 * @OA\Schema(
 *     schema="AccessoryProduct",
 *     type="object",
 *
 *     @OA\Property(property="price", type="number", format="float", description="Prix de l'accessoire", example=2500)
 * )
 */
class Product {}
