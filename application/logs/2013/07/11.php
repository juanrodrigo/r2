<?php defined('SYSPATH') or die('No direct script access.'); ?>

2013-07-11 17:20:14 --- ERROR: Kohana_Exception [ 0 ]: Invalid method listaGeneral called in Model_Users ~ MODPATH\orm\classes\kohana\orm.php [ 606 ]
2013-07-11 17:47:17 --- ERROR: Database_Exception [ 0 ]: [1064] You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near ') GROUP BY u.id' at line 4 ( SELECT u.id, u.nombre,u.cargo,COUNT(*) as  pendientes FROM users u
    INNER JOIN seguimiento s ON s.derivado_a=u.id
    WHERE s.estado='2'
    AND s.derivado_a IN  ) GROUP BY u.id ) ~ MODPATH\database\classes\kohana\database\mysql.php [ 181 ]