<?php
require "flyway.php";

$link = mysqli_connect("localhost","root","root","tienda") or die("Error " . mysqli_error($link)); 

$flyway = new Flyway($link);
$flyway->migrate();
    

