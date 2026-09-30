<?php
namespace App\Services\Equipment;

interface EquipmentSourceInterface
{
    public function key(): string;
    public function name(): string;
    public function baseUrl(): string;
    /** @return iterable<array<string, mixed>> */
    public function items(): iterable;
}
