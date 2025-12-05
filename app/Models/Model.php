<?php
/**
 * TCT-YMS Base Model Class
 *
 * Provides Active Record-style database operations for all models.
 */

namespace App\Models;

use App\Database;
use App\QueryBuilder;

abstract class Model
{
    /**
     * Table name - must be defined in child classes
     */
    protected static string $table;

    /**
     * Primary key column
     */
    protected static string $primaryKey = 'id';

    /**
     * Columns that can be mass-assigned
     */
    protected static array $fillable = [];

    /**
     * Columns that should be hidden from arrays
     */
    protected static array $hidden = [];

    /**
     * Auto-managed timestamps
     */
    protected static bool $timestamps = true;

    /**
     * Model attributes
     */
    protected array $attributes = [];

    /**
     * Original attributes (for dirty checking)
     */
    protected array $original = [];

    /**
     * Construct model with attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Get attribute value
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if attribute exists
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get changed attributes
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Check if model has changes
     */
    public function isDirty(): bool
    {
        return !empty($this->getDirty());
    }

    /**
     * Get primary key value
     */
    public function getId(): mixed
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    /**
     * Check if model exists in database
     */
    public function exists(): bool
    {
        return $this->getId() !== null;
    }

    /**
     * Convert to array (excluding hidden)
     */
    public function toArray(): array
    {
        $array = $this->attributes;
        foreach (static::$hidden as $key) {
            unset($array[$key]);
        }
        return $array;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Save model to database
     */
    public function save(): bool
    {
        if ($this->exists()) {
            return $this->update();
        }
        return $this->insert();
    }

    /**
     * Insert new record
     */
    protected function insert(): bool
    {
        $data = $this->attributes;
        unset($data[static::$primaryKey]);

        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        $id = Database::insert(static::$table, $data);

        if ($id) {
            $this->attributes[static::$primaryKey] = $id;
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Update existing record
     */
    protected function update(): bool
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        if (static::$timestamps) {
            $dirty['updated_at'] = date('Y-m-d H:i:s');
        }

        $result = Database::update(
            static::$table,
            $dirty,
            static::$primaryKey . ' = ?',
            [$this->getId()]
        );

        if ($result !== false) {
            $this->attributes = array_merge($this->attributes, $dirty);
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Delete model from database
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $result = Database::delete(
            static::$table,
            static::$primaryKey . ' = ?',
            [$this->getId()]
        );

        return $result > 0;
    }

    /**
     * Soft delete (if supported)
     */
    public function softDelete(): bool
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * Restore soft deleted record
     */
    public function restore(): bool
    {
        $this->deleted_at = null;
        return $this->save();
    }

    /**
     * Refresh model from database
     */
    public function refresh(): self
    {
        if ($this->exists()) {
            $fresh = static::find($this->getId());
            if ($fresh) {
                $this->attributes = $fresh->attributes;
                $this->original = $this->attributes;
            }
        }
        return $this;
    }

    // ========== Static Query Methods ==========

    /**
     * Get query builder for table
     */
    public static function query(): QueryBuilder
    {
        return QueryBuilder::table(static::$table);
    }

    /**
     * Find by primary key
     */
    public static function find(mixed $id): ?static
    {
        $row = static::query()
            ->where(static::$primaryKey, $id)
            ->first();

        return $row ? new static($row) : null;
    }

    /**
     * Find by primary key or fail
     */
    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);

        if (!$model) {
            abort(404, 'Record not found');
        }

        return $model;
    }

    /**
     * Get all records
     */
    public static function all(): array
    {
        return array_map(
            fn($row) => new static($row),
            static::query()->get()
        );
    }

    /**
     * Get first record matching conditions
     */
    public static function first(array $conditions = []): ?static
    {
        $query = static::query();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        $row = $query->first();
        return $row ? new static($row) : null;
    }

    /**
     * Get records matching conditions
     */
    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * Get records with IN clause
     */
    public static function whereIn(string $column, array $values): QueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    /**
     * Count records
     */
    public static function count(array $conditions = []): int
    {
        $query = static::query();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        return $query->count();
    }

    /**
     * Create new model and save to database
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Update records matching conditions
     */
    public static function updateWhere(array $data, string $where, array $params = []): int
    {
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return Database::update(static::$table, $data, $where, $params);
    }

    /**
     * Delete records matching conditions
     */
    public static function deleteWhere(string $where, array $params = []): int
    {
        return Database::delete(static::$table, $where, $params);
    }

    /**
     * Find or create model
     */
    public static function firstOrCreate(array $conditions, array $attributes = []): static
    {
        $model = static::first($conditions);

        if (!$model) {
            $model = static::create(array_merge($conditions, $attributes));
        }

        return $model;
    }

    /**
     * Update or create model
     */
    public static function updateOrCreate(array $conditions, array $attributes): static
    {
        $model = static::first($conditions);

        if ($model) {
            $model->fill($attributes);
            $model->save();
        } else {
            $model = static::create(array_merge($conditions, $attributes));
        }

        return $model;
    }

    /**
     * Get paginated results
     */
    public static function paginate(int $page = 1, int $perPage = 25, array $conditions = []): array
    {
        $query = static::query();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        $total = $query->count();
        $items = array_map(
            fn($row) => new static($row),
            $query->paginate($page, $perPage)->get()
        );

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    /**
     * Convert array of rows to models
     */
    public static function hydrate(array $rows): array
    {
        return array_map(fn($row) => new static($row), $rows);
    }
}
