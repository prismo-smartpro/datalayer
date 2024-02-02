<?php

namespace SmartPRO\Technology;

use PDO;
use PDOException;
use stdClass;

/**
 * Class DataLayer
 * @package SmartPRO\Technology
 */
class DataLayer extends Connect
{
    use DataLayerTrait;


    /**
     * @var string|null
     */
    private ?string $entity;
    /**
     * @var string|null
     */
    private ?string $primary;
    /**
     * @var array|null
     */
    private ?array $required = array();
    /**
     * @var array|null
     */
    private ?array $unique = array();
    /**
     * @var bool
     */
    private bool $timestamp;
    /**
     * @var string|null
     */
    private ?string $statement = null;
    /**
     * @var object|null
     */
    private ?object $data = null;
    /**
     * @var string|null
     */
    private ?string $error = null;
    /**
     * @var string|null
     */
    private ?string $exception = null;
    /**
     * @var string|null
     */
    private ?string $limite = null;
    /**
     * @var string|null
     */
    private ?string $order = null;
    /**
     * @var string|null
     */
    private ?string $group = null;
    /**
     * @var string|null
     */
    private ?string $offset = null;
    /**
     * @var array|null
     */
    private ?array $parameters = [];

    /**
     * @param $entity
     * @param string $primary
     * @param array $required
     * @param array $unique
     * @param bool $timestamp
     */
    public function __construct($entity, string $primary = "id", array $required = [], array $unique = [], bool $timestamp = true)
    {
        $this->entity = $entity;
        $this->primary = $primary;
        $this->required = $required;
        $this->unique = $unique;
        $this->timestamp = $timestamp;
        if (empty($this->order)) {
            $this->order($this->primary . " DESC");
        }
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value)
    {
        if (empty($this->data)) {
            $this->data = new stdClass();
        }
        $this->data->$name = $value;
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return ($this->data->$name ?? null);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data->$name);
    }

    /**
     * @param string|null $terms
     * @param string|null $parameters
     * @param string $columns
     * @return $this|null
     */
    public function find(string $terms = null, string $parameters = null, string $columns = "*"): ?DataLayer
    {
        $this->statement = "SELECT {$columns} FROM `{$this->entity}`";
        if (!empty($terms)) {
            $this->statement = $this->statement . " WHERE {$terms}";
        }

        if (!empty($parameters)) {
            parse_str($parameters, $parameterArray);
        }

        if (!empty($parameterArray)) {
            $this->parameters = $parameterArray;
        }
        return $this;
    }

    /**
     * @return bool|int
     */
    public function save()
    {
        $primary = $this->primary;
        if (!$this->required()) {
            return false;
        } elseif (!$this->unique()) {
            return false;
        }

        if (empty($this->data->$primary)) {
            return $this->register((array)$this->data);
        }
        return $this->update((array)$this->data);
    }

    /**
     * @param string|null $terms
     * @param string|null $parameters
     * @param string $columns
     * @return array|false
     */
    public function search(string $terms = null, string $parameters = null, string $columns = "*")
    {
        try {
            $this->statement = "SELECT {$columns} FROM `{$this->entity}` WHERE {$terms}";
            $query = self::getInstance()->prepare($this->statement . $this->group . $this->order . $this->limite . $this->offset);
            if ($parameters) {
                parse_str($parameters, $parametersArray);
            } else {
                $parametersArray = array();
            }
            foreach ($parametersArray as $item => $value) {
                $query->bindValue($item, "%{$value}%");
            }
            $query->execute();
            if (empty($query->rowCount())) {
                return [];
            }
            return $query->fetchAll(PDO::FETCH_CLASS, static::class);
        } catch (PDOException $exception) {
            $this->error = $exception->getMessage();
            return [];
        }
    }

    /**
     * @param $id
     * @param string $columns
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function findById($id, string $columns = "*")
    {
        return $this->find("id = :id", ":id={$id}", $columns)->fetch();
    }

    /**
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function last()
    {
        return $this->find()->limite(1)->fetch();
    }

    /**
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function first()
    {
        return $this->find()->limite(1)->order("id ASC")->fetch();
    }

    /**
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function fetchAll($terms = null, $parameters = null, $columns = "*")
    {
        return $this->find($terms, $parameters, $columns)->fetch(true);
    }

    /**
     * @return bool
     */
    public function destroy(): bool
    {
        $primary = $this->primary;
        if (empty($this->data->$primary)) {
            return false;
        }
        return $this->delete();
    }

    /**
     * @param $terms
     * @param $parameters
     * @return int|null
     */
    public function rowsCount($terms = null, $parameters = null): ?int
    {
        try {
            $this->statement = "select `{$this->primary}` from `{$this->entity}`";
            if ($terms) {
                $this->statement .= " where {$terms}";
            }

            $query = self::getInstance()->prepare($this->statement);
            if ($parameters) {
                parse_str($parameters, $parametersArray);
                foreach ($parametersArray as $item => $value) {
                    if (strpos($this->statement, "like")) {
                        $query->bindValue($item, "%{$value}%");
                    } else {
                        $query->bindValue($item, $value);
                    }
                }
            }

            $query->execute();
            return $query->rowCount();
        } catch (PDOException $exception) {
            return null;
        }
    }

    /**
     * @param bool $all
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function fetch(bool $all = false)
    {
        try {
            if (!$all) {
                $this->limite(1);
            }
            $query = self::getInstance()->prepare(
                $this->statement . $this->group . $this->order . $this->limite . $this->offset
            );
            foreach ($this->parameters as $item => $value) {
                $query->bindValue($item, $value);
            }
            $query->execute();

            if (!$query->rowCount()) {
                return [];
            }

            if ($all) {
                return $query->fetchAll(PDO::FETCH_CLASS, static::class);
            }

            return $query->fetchObject(static::class);
        } catch (PDOException $exception) {
            $this->exception = $exception->getMessage();
            return null;
        }
    }

    /**
     * @return bool
     */
    private function unique(): bool
    {
        $list = [];
        foreach ($this->unique as $item) {
            $list[$item] = $this->data->$item ?? null;
        }

        $listDuplicate = [];
        foreach ($list as $item => $value) {
            $value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
            $statement = "SELECT `{$this->primary}` FROM `{$this->entity}` WHERE `{$item}` = '{$value}'";
            $query = self::getInstance()->query($statement);
            if ($query->rowCount()) {
                $listDuplicate[] = $item;
            }
        }

        if (empty($listDuplicate)) {
            return true;
        } elseif (count($listDuplicate) == 1) {
            $this->error = "Este(a) {$listDuplicate[0]} já existe!";
            return false;
        } else {
            $this->error = "Os campos (" . implode(", ", $listDuplicate) . ") já existem com os valores informados!";
            return false;
        }
    }

    /**
     * @return bool
     */
    private function required(): bool
    {
        $list = [];
        foreach ($this->required as $item) {
            if (empty($this->data->$item)) {
                $list[] = $item;
            }
        }
        if (!empty($list)) {
            $this->error = "Preencha os campos necessários";
            return false;
        }
        return true;
    }

    /**
     * @param string|null $offset
     * @return DataLayer
     */
    public function offset(?string $offset): DataLayer
    {
        $this->offset = " OFFSET " . $offset;
        return $this;
    }

    /**
     * @param string|null $group
     * @return DataLayer
     */
    public function group(?string $group): DataLayer
    {
        $this->group = " GROUP BY " . $group;
        return $this;
    }

    /**
     * @param int|null $limite
     * @return DataLayer
     */
    public function limite(?int $limite): DataLayer
    {
        $this->limite = " LIMIT " . $limite;
        return $this;
    }

    /**
     * @param string|null $order
     * @return DataLayer
     */
    public function order(?string $order): DataLayer
    {
        $this->order = " ORDER BY " . $order;
        return $this;
    }

    /**
     * @return string|null
     */
    public function exception(): ?string
    {
        return $this->exception;
    }

    /**
     * @return object|null
     */
    public function getData(): ?object
    {
        return $this->data;
    }

    /**
     * @return string|null
     */
    public function error(): ?string
    {
        return $this->error;
    }
}