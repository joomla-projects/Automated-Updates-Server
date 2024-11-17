<?php

namespace App\TUF;

use App\Models\TufMetadata;
use Tuf\Metadata\StorageBase;

class EloquentModelStorage extends StorageBase
{
    public const METADATA_COLUMNS = ['root', 'targets', 'snapshot', 'timestamp', 'mirrors'];

    protected TufMetadata $model;

    /**
     * @var array<string, string>
     */
    protected array $container = [];

    public function __construct(TufMetadata $model)
    {
        $this->model = $model;

        foreach (self::METADATA_COLUMNS as $column) {
            if ($this->model->$column === null) {
                continue;
            }

            $this->write($column, $this->model->$column);
        }
    }

    public function read(string $name): ?string
    {
        return $this->container[$name] ?? null;
    }

    public function write(string $name, string $data): void
    {
        $this->container[$name] = $data;
    }

    public function delete(string $name): void
    {
        unset($this->container[$name]);
    }

    public function persist(): bool
    {
        foreach (self::METADATA_COLUMNS as $column) {
            if (!\array_key_exists($column, $this->container)) {
                continue;
            }

            $this->model->$column = $this->container[$column];
        }

        return $this->model->save();
    }
}
