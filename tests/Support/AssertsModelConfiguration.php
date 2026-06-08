<?php

namespace Tests\Support;

trait AssertsModelConfiguration
{
    protected function assertModelUsesConnection(string $modelClass, string $connection): void
    {
        $model = new $modelClass;

        $this->assertSame($connection, $model->getConnectionName());
    }

    protected function assertModelUsesTable(string $modelClass, string $table): void
    {
        $model = new $modelClass;

        $this->assertSame($table, $model->getTable());
    }

    protected function assertModelFillable(string $modelClass, array $fillable): void
    {
        $model = new $modelClass;

        $this->assertSame($fillable, $model->getFillable());
    }

    protected function assertModelCastsInclude(string $modelClass, array $casts): void
    {
        $model = new $modelClass;

        foreach ($casts as $attribute => $cast) {
            $this->assertSame(
                $cast,
                $model->getCasts()[$attribute] ?? null,
                sprintf('Cast for %s on %s', $attribute, $modelClass)
            );
        }
    }
}
