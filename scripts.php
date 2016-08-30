<?php

/**
 * @todo Adapter les informations de connexion selon environnement !!
 */
$StrHost = "localhost";
$StrMyDB = "dwebch_vertic-halle";
$StrUser = "root";
$StrPswd = "root";

//connection: 
$link = mysqli_connect($StrHost, $StrUser, $StrPswd, $StrMyDB) or die("Error " . mysqli_error($link)); 

//execute the query. 
$rs = $link->query('SELECT * FROM clients_has_cours') or die("Error in the select..." . mysqli_error($link));

//display information: 
while($cc = mysqli_fetch_object($rs)) {
	$rs_cours = $link->query('SELECT * FROM cours_date WHERE fk_cours = '.$cc->fk_cours) or die("Error in the select..." . mysqli_error($link));
	while($cours = mysqli_fetch_object($rs_cours)) {
		$rs_part = $link->query('INSERT INTO clients_has_cours_date (fk_personne, fk_cours_date) VALUES ('.$cc->fk_personne.', '.$cours->cours_date_id.')') or die("Error in the insert..." . mysqli_error($link));
	}
} 

echo '<br />end';

?>