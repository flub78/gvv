# MariaDB strict mode

GVV has been developed long before MariaDB started to enforce strict mode by default. With strict mode some operations generate error like "Incorrect decimal value: '' for column" for example in the "terrains" table.

To mitigate the issue it is possible to disable STRICT_TRANS_TABLES in SQL mode.

First check the current sql_mode value.

```
sudo -i 
mysql -u root -p

MariaDB [(none)]> SELECT @@SQL_MODE, @@GLOBAL.SQL_MODE;
+-------------------------------------------------------------------------------------------+-------------------------------------------------------------------------------------------+
| @@SQL_MODE                                                                                | @@GLOBAL.SQL_MODE                                                                         |
+-------------------------------------------------------------------------------------------+-------------------------------------------------------------------------------------------+
| STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION | STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION |
+-------------------------------------------------------------------------------------------+-------------------------------------------------------------------------------------------+
```

in /etc/mysql/my.cnf

```
[mysqld]
sql_mode="ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"

service mysql restart
```

At some point it should be better to identify all occurences of the issue and change database methods to avoid the error. Right now the situation is not worst than before MariaDB started to activate the control.
