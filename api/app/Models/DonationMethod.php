<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Moyen de don affiche dans l'application.
 *
 * En base et non dans le code : un numero Wave change, et on ne republie pas
 * une application sur les magasins pour ca.
 */
class DonationMethod extends Model
{
    protected $fillable = ['provider', 'label', 'phone', 'note', 'position', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
