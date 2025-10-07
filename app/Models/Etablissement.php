<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etablissement extends Model
{
    /** @use HasFactory<\Database\Factories\EtablissementFactory> */
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
        'organisation_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
