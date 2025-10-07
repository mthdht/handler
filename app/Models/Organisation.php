<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    /** @use HasFactory<\Database\Factories\OrganisationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'settings',
        'email',
        'phone',
        'website',
        'address',
        'owner_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function etablissements()
    {
        return $this->hasMany(Etablissement::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

}
