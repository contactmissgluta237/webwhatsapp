<?php

namespace Database\Factories;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\CustomerDeliveryAddress;
use App\Models\DeliveryPerson;
use App\Models\DistributionCenter;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $customer = Customer::inRandomOrder()->first();
        $distributionCenter = DistributionCenter::inRandomOrder()->first();
        $deliveryAddress = CustomerDeliveryAddress::where('customer_id', $customer->id)
            ->inRandomOrder()->first() ?? CustomerDeliveryAddress::factory()->create(['customer_id' => $customer->id]);

        $deliveryType = $this->faker->randomElement([DeliveryType::NORMAL(), DeliveryType::FAST()]);
        $orderDate = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'customer_id' => $customer->id,
            'distribution_center_id' => $distributionCenter->id,
            'delivery_address_id' => $deliveryAddress->id,
            'delivery_person_id' => null,
            'order_number' => 'ORD-'.$this->faker->unique()->bothify('######'),
            'delivery_type' => $deliveryType,
            'status' => OrderStatus::CONFIRMED(),
            'subtotal' => $this->faker->randomFloat(2, 1000, 10000),
            'delivery_fee' => $deliveryType->fee(),
            'total_amount' => 0,
            'order_date' => $orderDate,
            'delivery_date' => null,
            'comments' => $this->faker->optional(0.3)->sentence(),
            'center_comments' => null,
            'rating' => null,
            'confirmed_at' => $orderDate,
            'processing_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Order $order) {
            $order->total_amount = $order->subtotal + $order->delivery_fee;
        });
    }

    public function confirmed(): static
    {
        return $this->state(function () {
            $deliveryType = $this->faker->randomElement([DeliveryType::NORMAL(), DeliveryType::FAST()]);
            $orderDate = $this->faker->dateTimeBetween('-30 days', 'now');

            return [
                'delivery_type' => $deliveryType,
                'status' => OrderStatus::CONFIRMED(),
                'order_date' => $orderDate,
                'delivery_date' => null,
                'delivery_person_id' => null,
                'comments' => $this->faker->optional(0.3)->sentence(),
                'center_comments' => null,
                'rating' => null,
                'confirmed_at' => $orderDate,
                'processing_at' => null,
                'delivered_at' => null,
                'cancelled_at' => null,
                'delivery_fee' => $deliveryType->fee(),
            ];
        });
    }

    public function processing(): static
    {
        return $this->state(function () {
            $deliveryPerson = DeliveryPerson::inRandomOrder()->first();
            $deliveryType = $this->faker->randomElement([DeliveryType::NORMAL(), DeliveryType::FAST()]);

            $orderDate = $this->faker->dateTimeBetween('-7 days', '-1 hours');
            $processingAt = Carbon::instance($orderDate)->addMinutes(rand(30, 240));

            $maxProcessingTime = match ($deliveryType) {
                DeliveryType::FAST() => Carbon::instance($orderDate)->addHours(24),
                DeliveryType::NORMAL() => Carbon::instance($orderDate)->addHours(48),
            };

            if ($processingAt->greaterThan($maxProcessingTime)) {
                $processingAt = $maxProcessingTime->subHours(rand(1, 4));
            }

            return [
                'delivery_type' => $deliveryType,
                'status' => OrderStatus::PROCESSING(),
                'order_date' => $orderDate,
                'delivery_date' => null,
                'delivery_person_id' => $deliveryPerson?->id,
                'comments' => $this->faker->optional(0.3)->sentence(),
                'center_comments' => $this->faker->optional(0.4)->sentence(),
                'rating' => null,
                'confirmed_at' => $orderDate,
                'processing_at' => $processingAt,
                'delivered_at' => null,
                'cancelled_at' => null,
                'delivery_fee' => $deliveryType->fee(),
            ];
        });
    }

    public function delivered(): static
    {
        return $this->state(function () {
            $deliveryPerson = DeliveryPerson::inRandomOrder()->first();
            $deliveryType = $this->faker->randomElement([DeliveryType::NORMAL(), DeliveryType::FAST()]);

            $orderDate = $this->faker->dateTimeBetween('-30 days', '-2 hours');
            $processingAt = Carbon::instance($orderDate)->addMinutes(rand(30, 240));

            [$maxDeliveryTime, $deliveryTimeRange] = match ($deliveryType) {
                DeliveryType::FAST() => [
                    Carbon::instance($orderDate)->addHours(24),
                    [30, 360],
                ],
                DeliveryType::NORMAL() => [
                    Carbon::instance($orderDate)->addHours(48),
                    [60, 720],
                ],
            };

            $deliveredAt = $processingAt->copy()->addMinutes(rand(...$deliveryTimeRange));

            if ($deliveredAt->greaterThan($maxDeliveryTime)) {
                $deliveredAt = $maxDeliveryTime->subMinutes(rand(30, 120));
            }

            return [
                'delivery_type' => $deliveryType,
                'status' => OrderStatus::DELIVERED(),
                'order_date' => $orderDate,
                'delivery_date' => $deliveredAt,
                'delivery_person_id' => $deliveryPerson?->id,
                'comments' => $this->faker->optional(0.4)->sentence(),
                'center_comments' => $this->faker->optional(0.3)->sentence(),
                'rating' => $this->faker->optional(0.7)->randomFloat(1, 1, 5),
                'confirmed_at' => $orderDate,
                'processing_at' => $processingAt,
                'delivered_at' => $deliveredAt,
                'cancelled_at' => null,
                'delivery_fee' => $deliveryType->fee(),
            ];
        });
    }

    public function cancelledFromConfirmed(): static
    {
        return $this->state(function () {
            $deliveryType = $this->faker->randomElement([DeliveryType::NORMAL(), DeliveryType::FAST()]);
            $orderDate = $this->faker->dateTimeBetween('-30 days', '-1 hours');

            $maxCancelTime = match ($deliveryType) {
                DeliveryType::FAST() => Carbon::instance($orderDate)->addHours(20),
                DeliveryType::NORMAL() => Carbon::instance($orderDate)->addHours(40),
            };

            $cancelledAt = Carbon::instance($orderDate)->addMinutes(rand(60, $maxCancelTime->diffInMinutes(Carbon::instance($orderDate))));

            return [
                'delivery_type' => $deliveryType,
                'status' => OrderStatus::CANCELLED(),
                'order_date' => $orderDate,
                'delivery_date' => null,
                'delivery_person_id' => null,
                'comments' => $this->faker->optional(0.6)->sentence(),
                'center_comments' => $this->faker->randomElement([
                    'Stock insuffisant',
                    'Problème de livraison',
                    'Annulation client',
                    'Adresse introuvable',
                ]),
                'rating' => null,
                'confirmed_at' => $orderDate,
                'processing_at' => null,
                'delivered_at' => null,
                'cancelled_at' => $cancelledAt,
                'delivery_fee' => $deliveryType->fee(),
            ];
        });
    }

    public function cancelledFromProcessing(): static
    {
        return $this->state(function () {
            $deliveryPerson = DeliveryPerson::inRandomOrder()->first();
            $deliveryType = $this->faker->randomElement([DeliveryType::NORMAL(), DeliveryType::FAST()]);

            $orderDate = $this->faker->dateTimeBetween('-30 days', '-2 hours');
            $processingAt = Carbon::instance($orderDate)->addMinutes(rand(30, 240));

            $maxCancelTime = match ($deliveryType) {
                DeliveryType::FAST() => Carbon::instance($orderDate)->addHours(22),
                DeliveryType::NORMAL() => Carbon::instance($orderDate)->addHours(46),
            };

            $cancelledAt = $processingAt->copy()->addMinutes(rand(30, $maxCancelTime->diffInMinutes($processingAt)));

            return [
                'delivery_type' => $deliveryType,
                'status' => OrderStatus::CANCELLED(),
                'order_date' => $orderDate,
                'delivery_date' => null,
                'delivery_person_id' => $deliveryPerson?->id,
                'comments' => $this->faker->optional(0.6)->sentence(),
                'center_comments' => $this->faker->randomElement([
                    'Problème lors de la livraison',
                    'Client injoignable',
                    'Adresse incorrecte',
                    'Refus de livraison',
                ]),
                'rating' => null,
                'confirmed_at' => $orderDate,
                'processing_at' => $processingAt,
                'delivered_at' => null,
                'cancelled_at' => $cancelledAt,
                'delivery_fee' => $deliveryType->fee(),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->faker->boolean(60) ?
            $this->cancelledFromConfirmed() :
            $this->cancelledFromProcessing();
    }

    public function realistic(): static
    {
        $statusWeights = [
            'confirmed' => 20,
            'processing' => 15,
            'delivered' => 60,
            'cancelled' => 5,
        ];

        $randomValue = $this->faker->numberBetween(1, 100);
        $cumulativeWeight = 0;

        foreach ($statusWeights as $status => $weight) {
            $cumulativeWeight += $weight;
            if ($randomValue <= $cumulativeWeight) {
                return $this->{$status}();
            }
        }

        return $this->delivered();
    }
}
