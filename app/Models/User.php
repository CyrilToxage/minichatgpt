<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_model', // Ajouté pour stocker le modèle préféré
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all conversations for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Conversation>
     */
    public function conversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the custom instructions for the user.
     */
    public function customInstruction(): HasOne
    {
        return $this->hasOne(CustomInstruction::class);
    }

    /**
     * Get the active custom instructions for the user or create default ones.
     */
    public function getActiveInstructions()
    {
        $instructions = $this->customInstruction;

        if (!$instructions) {
            // Créer des instructions par défaut
            $instructions = $this->customInstruction()->create([
                'about_you' => '',
                'assistant_behavior' => '',
                'custom_commands' => [],
                'is_active' => true,
            ]);
        }

        return $instructions;
    }
}
