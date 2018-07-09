<?php

//mysql server details
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "myDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully";



//create ip list array
$query = mysql_query("SELECT ip FROM users");
$results = array();

while ($row = mysql_fetch_row($query)) { 
   $results[] = $row[0]; 
}



/*
Similar to dns, we need to confirm that there is a change before rewriting the rules

First, create a file where we will input old rules, so that we can compare the new ones against them next time
*/

$oldips = "/etc/oldips.txt";
$oldipsArray = file($oldips, FILE_IGNORE_NEW_LINES);


if ($results !== $oldipsArray) {


	//create ufw rules

	//flush old rules
	exec ('ufw reset > ufwrestart.log');

	//create basic rules to allow access
	exec ('ufw allow 22 > ufwrestart.log');


	//add in custom rules

	foreach ($results as $ip){
		exec ('ufw allow from $ip proto tcp to any port 80 > ufwrestart.log');
		exec ('ufw allow from $ip proto tcp to any port 443 > ufwrestart.log');
	}

	//start ufw again
	exec ('ufw enable > ufwrestart.log');


	//update ip file
	$newfile = fopen("/etc/newips.txt", "w") or die("Unable to open file!");

	foreach ($results as $ip){
			$txt = "$ip";
			fwrite($myfile, $txt);
	}
	
	fclose($myfile);
	unlink ($oldips);
	rename ($newfile, $oldips);

}

$conn->close();

?>
