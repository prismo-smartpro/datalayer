#### INSTALAÇÃO

run
```bash
composer require prismo-smartpro/datalayer
```

#### MYSQL CONNECT

```php
<?php
const MYSQL = [
    "host" => "",
    "username" => "",
    "password" => "",
    "db" => ""
];
```

#### DOCUMENTAÇÃO

##### PHP Class

```php
<?php

namespace SmartPRO\Technology;

class Settings extends DataLayer
{
    public function __construct()
    {
        parent::__construct("settings", "id", $required = [], $unique = [], true);
    }
}
```

#### Usage examples

```php
<?php

require "Mysql_Config.php";
require "vendor/autoload.php";

use SmartPRO\Technology\Settings;

// Returns all data from the table
$results = (new Settings())->fetchAll();

// Returns data starting from a specific id
$byId = (new Settings())->findById(17);

// Edit the data that returns and saves the data
if (!empty($byId)) {
    $byId->empresa = "My Busines";
    $byId->save();
}

// Deletes the returned query from the database
if (!empty($byId)) {
    $byId->destroy();
}

// Makes a search query using the LIKE parameter
$serch = (new Settings())->search("empresa=My Busines");

// Query with custom data
$results = (new Settings())->find("empresa=My Busines", "*")
    ->limite(5)
    ->order("id DESC")
    ->gruopBy("site")
    ->offset(0)
    ->fetch(true);

foreach ($results as $result){
    //...
}
```

#### PERSONALIZED

```php
<?php

namespace SmartPRO\Technology;

class Settings extends DataLayer
{
    public function __construct()
    {
        parent::__construct("settings", "id", $required = [], $unique = [], true);
    }
    
    public function fidByEmail($email){
        return $this->find("email={$email}")->fetch();
    }
}
```