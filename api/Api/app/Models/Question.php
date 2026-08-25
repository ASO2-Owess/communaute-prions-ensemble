<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Question posee a l'equipe pastorale.
 *
 * DONNEES SENSIBLES : `body` peut etre intime. Ne jamais le journaliser,
 * ne jamais l'exposer a un autre membre, ne jamais l'envoyer a un tiers.
 */
class Question extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['user_id', 'body', 'topic', 'status', 'assigned_to'];

    /**
     * `body` est masque par defaut dans les reponses JSON et les journaux.
     * Il faut le demander explicitement (makeVisible) pour l'exposer.
     */
    protected $hidden = ['body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Le repondant : un utilisateur de role pastor, jamais une personne codee en dur. */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(Answer::class);
    }

    /** File d'attente du pasteur : ce qui reste a traiter, du plus ancien au plus recent. */
    public function scopeAwaiting($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_ASSIGNED])
                     ->orderBy('created_at');
    }
}
