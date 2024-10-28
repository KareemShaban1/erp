<?php

namespace App\Traits;

trait CheckQuantityTrait {
          /**
 * Check if the variation has sufficient quantity at the specified location.
 *
 * @param \Illuminate\Database\Eloquent\Collection $locationDetails
 * @param int $locationId
 * @param int $quantity
 * @return bool
 */
public function checkSufficientQuantity($locationDetails, $locationId, $quantity)
{
    foreach ($locationDetails as $locationDetail) {
        if ($locationDetail->location->id === $locationId && $locationDetail->qty_available >= $quantity) {
            return true; // Sufficient quantity available
        }
    }
    return false; // Insufficient quantity
}

/**
 * Determine if multiple locations are required for the order due to insufficient stock at the primary location.
 *
 * @param \Illuminate\Database\Eloquent\Collection $locationDetails
 * @param int $primaryLocationId
 * @param int $quantity
 * @return bool
 */
public function isMultiLocationAvailable($locationDetails, $primaryLocationId, $quantity)
{
    foreach ($locationDetails as $locationDetail) {
        if ($locationDetail->location->id === $primaryLocationId && $locationDetail->qty_available >= $quantity) {
            return false; // All items are available at the primary location
        }
    }
    return true; // Multiple locations needed due to insufficient quantity at primary location
}

}