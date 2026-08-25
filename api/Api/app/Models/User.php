<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_MEMBER = 'member';
    public const ROLE_PASTOR = 'pastor';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
    ];

    /**
     * Valeurs par defaut portees par le MODELE, en plus de celles de la base.
     *
     * Pourquoi les deux : une valeur par defaut declaree uniquement en base
     * n'existe pas dans l'objet PHP juste apres un create(). L'instance
     * renvoyee ne contient que ce qui a ete envoye dans l'INSERT — d'ou un
     * points_total et un reading_cycle a null tant qu'on n'a pas relu la
     * ligne. Concretement, la reponse JSON d'une inscription renvoyait
     * "points_total": null au lieu de 0.
     *
     * Les declarer ici garantit qu'une instance fraiche est toujours coherente.
     */
    protected $attributes = [
        'role' => self::ROLE_MEMBER,
        'points_total' => 0,
        'reading_cycle' => 1,
    ];

    /**
     * points_total et reading_cycle sont volontairement absents de $fillable :
     * ils ne doivent jamais etre modifies par une requete entrante, seulement
     * par la logique metier du serveur (ProgressService).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points_total' => 'integer',
            'reading_cycle' => 'integer',
            'last_reading_reset_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    public function meditationCompletions(): HasMany
    {
        return $this->hasMany(MeditationCompletion::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function activityDays(): HasMany
    {
        return $this->hasMany(ActivityDay::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /** Questions dont cet utilisateur est le repondant (role pastor). */
    public function assignedQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'assigned_to');
    }

    /** Reponses redigees par cet utilisateur. */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'author_id');
    }

    // ------------------------------------------------------------------- roles

    public function isPastor(): bool
    {
        return in_array($this->role, [self::ROLE_PASTOR, self::ROLE_ADMIN], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // ---------------------------------------------------------------- lectures

    /** Lectures du cycle en cours uniquement — c'est ce que l'app affiche. */
    public function currentCycleReadings(): HasMany
    {
        return $this->readings()->where('cycle', $this->reading_cycle);
    }

    /** Nombre de lectures integrales achevees. */
    public function completedBibleCount(): int
    {
        return $this->reading_cycle - 1;
    }
}
