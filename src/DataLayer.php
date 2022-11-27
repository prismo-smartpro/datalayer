<?php

namespace SmartPRO\Technology;

use stdClass;
use PDO;
use PDOException;

/**
 * Class DataLayer
 * @package SmartPRO\Technology
 */
class DataLayer extends Connect
{
    use DataLayerTrait;

    /** @var string|null */
    protected ?string $entity;
    /** @var string|null */
    protected ?string $primary;
    /** @var array|mixed|null */
    protected ?array $required;
    /** @var array|mixed|null */
    protected ?array $unique;
    /** @var bool|mixed */
    protected bool $timestamp;
    /** @var string|null */
    protected ?string $statement;
    /** @var object|null */
    protected ?object $data;
    /** @var string|null */
    protected ?string $error;
    /** @var string|null */
    protected ?string $exception;
    /** @var string|null */
    protected ?string $limite = null;
    /** @var string|null */
    protected ?string $order = null;
    /** @var string|null */
    protected ?string $gruop = null;
    /** @var string|null */
    protected ?string $offset = null;

    /**
     * @param $entity
     * @param $primary
     * @param array $required
     * @param array $unique
     * @param bool $timestamp
     */
    public function __construct($entity, $primary, array $required = [], array $unique = [], bool $timestamp = true)
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
     * @param $parameters
     * @param string $coluns
     * @return $this|null
     */
    public function find($parameters = null, string $coluns = "*"): ?DataLayer
    {
        $this->statement = "SELECT {$coluns} FROM `{$this->entity}`";
        if ($parameters) {
            parse_str($parameters, $parse);
            $parse = $this->filter($parse, " AND");
            if (!empty($parse)) {
                $this->statement = $this->statement . " WHERE {$parse}";
            }
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
     * @param $parameters
     * @param string $coluns
     * @return array|false
     */
    public function search($parameters = null, string $coluns = "*")
    {
        parse_str($parameters, $parans);
        if (empty($parans)) {
            return [];
        }

        $likeList = [];
        foreach ($parans as $item => $value) {
            $likeList[] = "`{$item}` LIKE '%" . filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS) . "%'";
        }

        $likeList = implode(" AND ", $likeList);
        $this->statement = "SELECT {$coluns} FROM `{$this->entity}` WHERE {$likeList}";
        return $this->execute()->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    /**
     * @param $id
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function findById($id)
    {
        return $this->find("id={$id}")->fetch();
    }

    /**
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function fetchAll()
    {
        return $this->find()->fetch(true);
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
     * @param $parameters
     * @return int
     */
    public function rowsCount($parameters = null): int
    {
        return $this->execute()->rowCount();
    }

    /**
     * @param bool $all
     * @return array|false|mixed|object|DataLayer|stdClass|null
     */
    public function fetch(bool $all = false)
    {
        try {
            $query = self::getInstance()->prepare(
                $this->statement . $this->gruop . $this->order . $this->limite . $this->offset
            );
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
            var_dump($this->exception, $this->statement);
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
            $this->error = "O campo {$listDuplicate[0]} já esta cadastrado!";
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
     * @param string|null $gruop
     * @return DataLayer
     */
    public function gruopBy(?string $gruop): DataLayer
    {
        $this->gruop = " GROUP BY " . $gruop;
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
     * @return string|null
     */
    public function error(): ?string
    {
        return $this->error;
    }
}