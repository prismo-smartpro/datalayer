<?php

namespace SmartPRO\Technology;

use PDO;
use PDOException;
use PDOStatement;

/**
 * @DataLayerTrait
 * @package SmartPRO\Technology
 */
trait DataLayerTrait
{

    /**
     * @param array $data
     * @return false|int
     */
    private function update(array $data)
    {
        if ($this->timestamp) {
            $data['updated_at'] = (new \DateTime())->format("Y-m-d H:i:s");
        }
        $primary = $data[$this->primary];
        unset($data[$this->primary]);
        $filter = $this->filter($data);

        $list = [];
        foreach ($data as $item => $value) {
            $list["keys"][] = "`{$item}` = :{$item}";
            $list["values"][":{$item}"] = $value;
        }

        try {
            $statement = "UPDATE `{$this->entity}` SET " . implode(", ", $list['keys']) . " WHERE `{$this->primary}` = :id";
            $query = self::getInstance()->prepare($statement);
            foreach ($list['values'] as $item => $value) {
                $query->bindValue($item, $value);
            }
            $query->bindValue(":id", $primary);
            $query->execute();
            return $query->rowCount();
        } catch (PDOException $exception) {
            $this->exception = $exception->getMessage();
            return false;
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    private function register(array $data): ?bool
    {
        if (empty($data)) {
            return false;
        }

        if ($this->timestamp) {
            $data['created_at'] = (new \DateTime())->format("Y-m-d H:i:s");
            $data['updated_at'] = $data['created_at'];
        }

        $values = [];
        foreach ($data as $item => $value) {
            $values['keys']["`{$item}`"] = ":{$item}";
            $values['values'][":{$item}"] = $value;
        }

        try {
            $statement = "INSERT INTO `{$this->entity}` (" . implode(",", array_keys($values['keys'])) . ") VALUES (" . implode(",", array_values($values['keys'])) . ")";
            $query = self::getInstance()->prepare($statement);
            foreach ($values['values'] as $item => $value) {
                $query->bindValue($item, $value);
            }
            $query->execute();
            return $query->rowCount();
        } catch (PDOException $exception) {
            $this->exception = $exception->getMessage();
            return false;
        }
    }

    /**
     * @return bool
     */
    private function delete(): bool
    {
        $primary = $this->primary;
        $id = $this->data->$primary;
        $statement = "DELETE FROM `{$this->entity}` WHERE `{$primary}` = ?";
        return $this->exec($statement, [$id])->rowCount();
    }

    /**
     * @param null $statement
     * @param array|null $parameters
     * @return false|PDOStatement|void
     */
    private function exec($statement = null, ?array $parameters = [])
    {
        if (empty($statement)) {
            $statement = $this->statement;
        }
        try {
            $query = self::getInstance()->prepare($statement);
            $query->execute($parameters);
            return $query;
        } catch (PDOException $exception) {
            die("<h3 style='color: red;'>PDOException: {$exception->getMessage()}</h3>");
        }
    }

    /**
     * @param array $data
     * @param $separator
     * @return string|null
     */
    private function filter(array $data, $separator = ","): ?string
    {
        $list = [];
        foreach ($data as $item => $value) {
            $list[] = "`{$item}` = '" . filter_var($value, FILTER_DEFAULT) . "'";
        }
        return implode("{$separator} ", $list);
    }
}