<?php
// Iziešana no sistemas: izdzēš sesiju un pārsūta uz pieslēgšanās lapu
session_start();  //Sesiju jāpalaiž, lai to varētu iznīcināt.
session_destroy(); // Izdzēš visus sesijas datus (user_id, loma...)
header('Location: login.php');
exit;
