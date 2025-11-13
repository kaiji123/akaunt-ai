php -S 127.0.0.1:8000 -t public



needed to add autoload in index.php public folder 
require __DIR__.'/../bootstrap/autoload.php';


Needed to delete del bootstrap\cache\config.php manually to apply the .env changes