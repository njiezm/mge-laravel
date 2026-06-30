<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dossier extends Model
{
    protected $table = 'dossiers';

    protected $fillable = [
        'code_dossier',
        'nom_client',
        'deadline',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected $casts = [
        'deadline' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'dossier_id');
    }
}
