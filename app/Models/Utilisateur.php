<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Utilisateur extends Model
{
    protected $table = 'utilisateurs';

    protected $fillable = [
        'code_utilisateur',
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'role',
        'actif',
        'premiere_connexion',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'premiere_connexion' => 'boolean',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'utilisateur_id');
    }
}
