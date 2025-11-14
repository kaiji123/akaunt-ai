php -S 127.0.0.1:8000 -t public



needed to add autoload in index.php public folder 
require __DIR__.'/../bootstrap/autoload.php';


Needed to delete del bootstrap\cache\config.php manually to apply the .env changes


kernel shows the priority and structure of middlewares

you need to use module:make to create a module and after that , use module:install 1 to set permissions and allow you to access that module

do not forget to add
    <x-slot name="title">AI RECEIPT READER</x-slot>

    to your view otherwise you will get and internal server error




after you have updated a module please use module:update