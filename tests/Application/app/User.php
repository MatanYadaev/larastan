<?php

namespace App;

use Tests\Features\ReturnTypes\CustomBuilder2;
use Tests\Rules\Data\Foo;
use function get_class;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tests\Application\HasManySyncable;

/**
 * @property string $propertyDefinedOnlyInAnnotation
 *
 * @method Builder<static> scopeSomeScope(Builder $builder)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'meta' => 'array',
        'blocked' => 'boolean',
        'email_verified_at' => 'date',
        'options' => AsArrayObject::class,
        'properties' => AsCollection::class,
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return UserBuilder<User>
     */
    public function newEloquentBuilder($query): UserBuilder
    {
        return new UserBuilder($query);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function getAllCapsName(): string
    {
        return Str::upper($this->name);
    }

    /** @phpstan-return BelongsTo<Group, User> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withTrashed();
    }

    /** @phpstan-return HasMany<Account> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Account::class);
    }

    public function syncableRelation(): HasManySyncable
    {
        return $this->hasManySyncable(Account::class);
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo(null, 'model_type', 'model_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('some_column')
            ->wherePivotIn('some_column', [1, 2, 3]);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(get_class($this));
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function hasManySyncable($related, $foreignKey = null, $localKey = null): HasManySyncable
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManySyncable(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    public function getOnlyAvailableWithAccessorAttribute(): string
    {
        return 'foo';
    }

    public function isActive(): bool
    {
        return $this->active === 1;
    }

    public function setActive(): void
    {
        $this->active = 1;
    }
}

/**
 * @extends Builder<User>
 */
class UserBuilder extends Builder
{
    /**
     * @return UserBuilder<User>
     */
    public function active(): self
    {
        return $this->where('active', 1);
    }

    /**
     * @return UserBuilder<User>
     */
    public function whereActive(): self
    {
        return $this->where('active', 1);
    }
}
