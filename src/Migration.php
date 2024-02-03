<?php

namespace Inilim\Phinx;

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

abstract class Migration extends AbstractMigration
{
    protected string $name;
    /**
     * @var array{id:bool,primary_key:string[],collation:string,encoding:string}
     */
    protected array $settingTable = [];
    protected array $columns = [];
    /**
     * https://book.cakephp.org/phinx/0/en/migrations.html#working-with-indexes
     * @var array{columns:string[],options:array{unique:bool,order:<string,string>,name:string,type:string,limit:mixed,include:string[]}}
     */
    protected array $my_indices = [];

    protected function createTable(): void
    {
        if (!$this->columns) return;

        $table = $this->table($this->name, $this->settingTable);

        foreach ($this->columns as $column) {
            $table->addColumn($column['name'], $column['type'], $column['options']);
        }
        $table->create();
    }

    protected function addIndicesTable(): void
    {
        if (!$this->my_indices) return;

        $table = $this->table($this->name);
        foreach ($this->my_indices as $index) {
            if (!$index['columns']) continue;

            if (!$table->hasIndex($index['columns'])) {
                $table->addIndex($index['columns'], $index['options']);
            }
        }
        $table->update();
    }

    protected function updateTable(): void
    {
        if (!$this->columns) return;

        $table = $this->table($this->name);
        foreach ($this->columns as $column) {
            if ($table->hasColumn($column['name'])) {
                $table->changeColumn($column['name'], $column['type'], $column['options']);
            } else {
                $table->addColumn($column['name'], $column['type'], $column['options']);
            }
        }
        $table->update();
    }

    protected function removeUnnecessaryColumns(): void
    {
        if (!$this->columns) return;

        $table = $this->table($this->name);
        $columns = $table->getColumns();
        $needColumns = \array_column($this->columns, 'name');
        foreach ($columns as $column) {
            if (!\in_array($column->getName(), $needColumns)) {
                $table->removeColumn($column->getName());
            }
        }
        $table->update();
    }

    private function aliasNameTypeColumn(string $type): string
    {
        switch ($type) {
            case 'tinytext':
                return 'text';
            case 'mediumtext':
                return 'text';
            case 'longtext':
                return 'text';
            case 'varchar':
                return 'string';
            case 'str':
                return 'string';
            case 'int':
                return 'integer';
            case 'tinyint':
                return 'integer';
            case 'tinyinteger':
                return 'integer';
            case 'mediumint':
                return 'integer';
            case 'mediuminteger':
                return 'integer';
            case 'smallint':
                return 'smallinteger';
            case 'bigint':
                return 'biginteger';
        }
        return $type;
    }

    private function handleOption(string $type, array $options): array
    {
        if ($type === 'tinytext')
            $options['limit'] = MysqlAdapter::TEXT_TINY;
        if ($type === 'mediumtext')
            $options['limit'] = MysqlAdapter::TEXT_MEDIUM;
        if ($type === 'longtext')
            $options['limit'] = MysqlAdapter::TEXT_LONG;

        if ($type === 'tinyint')
            $options['limit'] = MysqlAdapter::INT_TINY;
        if ($type === 'tinyinteger')
            $options['limit'] = MysqlAdapter::INT_TINY;

        if ($type === 'smallint')
            $options['limit'] = MysqlAdapter::INT_SMALL;
        if ($type === 'smallinteger')
            $options['limit'] = MysqlAdapter::INT_SMALL;

        if ($type === 'mediumint')
            $options['limit'] = MysqlAdapter::INT_MEDIUM;
        if ($type === 'mediuminteger')
            $options['limit'] = MysqlAdapter::INT_MEDIUM;

        if ($type === 'bigint')
            $options['limit'] = MysqlAdapter::INT_BIG;
        if ($type === 'biginteger')
            $options['limit'] = MysqlAdapter::INT_BIG;

        return $options;
    }

    /**
     * DOCS: https://book.cakephp.org/phinx/0/en/migrations.htm
     * @param string $name column
     * @param string $type binary, boolean, char, date, datetime, decimal, float, double, tinyint, varchar, smallint, int, bigint, str, text, tinytext, mediumtext, longtext time, timestamp, uuid
     * @param array{null:bool,identity:bool,signed:bool,comment:string,limit:int,default:int|string,update:string} $options Keys: limit | default (CURRENT_TIMESTAMP|Any) | null | after | comment | precision (целое) | scale (после запятой) | signed | values | identity | update (CURRENT_TIMESTAMP) | timezone | collation | encoding | delete | constraint
     */
    protected function setColumn(string $name, string $type, array $options): void
    {
        $type = \strtolower($type);
        $options = $this->handleOption($type, $options);
        if ($this->columns) {
            $last = \end($this->columns);
            $options['after'] = $last['name'];
        }
        $this->columns[] = [
            'name' => $name,
            'type' => $this->aliasNameTypeColumn($type),
            'options' => $options,
        ];
    }

    protected function createdAt(string $input_name = 'created_at'): void
    {
        $this->setColumn($input_name, 'timestamp', [
            'null'    => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);
    }

    protected function updatedAt(string $input_name = 'updated_at'): void
    {
        $this->setColumn($input_name, 'timestamp', [
            'null'    => false,
            'default' => 'CURRENT_TIMESTAMP',
            'update'  => 'CURRENT_TIMESTAMP'
        ]);
    }

    protected function deletedAt(string $input_name = 'deleated_at'): void
    {
        $this->setColumn($input_name, 'timestamp', [
            'null'    => true,
        ]);
    }

    protected function createView(string $sql): void
    {
        $sql = 'CREATE OR REPLACE VIEW ' . $this->name . ' AS ' . $sql;
        $this->execute($sql);
    }
}
