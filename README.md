# orbisius-dot-env
Orbisius Dot Env is a free php class that allows you to read .env project files.
It is just one file and doesn't have any dependencies.

If the file is not passed the class will try to look for .env starting from Document Root, then checking if WordPress' const ABSPATH is defined and try one level up.

The class is very efficient and doesn't currently use regular expressions at all.
When it parses the key names are automatically uppercases them.
It can define the values in environment, $_ENV, $_SERVER etc. and also define php constants if they haven't been defined just yet.

This class was created by https://orbisius.com

Usage Notes
-----------
Check the example.php file.

```php
$dot_env = Orbisius_Dot_Env::getInstance();
$data = $dot_env->read();
$dot_env->updateEnv($data);
$dot_env->defineConsts($data);
```


### .env Example

You can comment your `.env` file using the `#` character. E.g.

```shell
# This is a comment
VAR1="value" # comment
VAR2=value # comment
```

Customizations / Support
--------------
We do offer paid support & customizations.
Contact us at https://orbisius.com/contact for more info.


License
-------

MIT
