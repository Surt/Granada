Paris fork for eager loading
Example of use... check https://github.com/Surt/Granada for more information about the behaviour


```php

require_once dirname(__FILE__) . '/idiorm.php';
require_once dirname(__FILE__) . "/../paris.php";
require_once dirname(__FILE__) . "/../eager.php";

ORM::configure('sqlite:C:\www\sqlite\test.sqlite');
ORM::configure('username', 'admin');
ORM::configure('password', '');



class Main extends Model{

    protected static $_table = 'content';

    public function related() {
      return $this->has_many('related');
    }
}

class Related extends Model{

    protected static $_table = 'related';

    public function content() {
      return $this->belongs_to('related');
    }
}

$parent = Model::factory('Main')->with('related')->find_one(1);
var_dump($parent->ignore['related']);

```
