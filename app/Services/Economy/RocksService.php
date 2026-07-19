<?php

namespace App\Services\Economy;

/**
 * Canonical product-facing economy service.
 *
 * The inherited Crown* persistence layer remains in place for backwards
 * compatibility with existing app releases and database tables.
 */
class RocksService extends CrownsService
{
}
