
##BaseApp Framework / Railsy

For those smaller, non-Ruby based apps, that have to be in PHP.  

---

###This vs. Master

- Removed/Updated any PHP 5.3 deprecations
- switched up the folder structure a bit and moved the config file out to a really obvious place `/config`
- Separated off the Routes to their own file `/config/routes.php`
- added in [ PHP-ActiveRecord ](http://github.com/kla/php-activerecord) to mimic Rails ActiveRecord functionality (no migrations though) via BaseApp's `/app/app_model.php` option

---

###Install

Requires PHP 5.3+ for PHP-ActiveRecord use.

---

###To-Do

- Make it a bit more CakePHP compliant, since the original is supposedly 80% Cake like
- Whatever else that comes my way

---

###Bugs

Let me know.

---

###Credits

Based off the original BaseApp Framework  
[http://code.google.com/p/baseappframework/](http://code.google.com/p/baseappframework/)  
Git mirror: [https://github.com/nowk/BaseApp-Framework/](https://github.com/nowk/BaseApp-Framework/) (master)

---

License: MIT License
