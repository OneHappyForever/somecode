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


/* 
---------------------------
We will first get the mysql database and input it into a multidimensional array.
The array will have the following structure:
Array
(
      [0]
          [ip] => 1.1.1.1
          [region] => us
      [1]
          [ip] => 2.2.2.2
          [region] => uk
)
See https://stackoverflow.com/questions/5053857/php-multi-dimensional-array-from-mysql-result

Then, we will create a new config file "newfile.txt" and build it with data from the multidimensional array 
---------------------------

*/

//create variables and assign them value, as well as create new file
$sql = "SELECT ip, region FROM users";
$results = array();
$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");

//create multidimensional arrays
while($line = mysql_fetch_array($query)){
    $results[] = $line;
}

//beginning of config file writing


//write acl US ips
$txt = "acl us { ";
fwrite($myfile, $txt);

foreach ($results as $row) {
	if ($row[region] == "us") {
		$txt = "$row[ip]; ";
		fwrite($myfile, $txt);
	}
	
}
$txt = "}; \n";
fwrite($myfile, $txt);

//write acl uk ips
$txt = "acl uk { ";
fwrite($myfile, $txt);

foreach ($results as $row) {
	if ($row[region] == "uk") {
		$txt = "$row[ip]; ";
		fwrite($myfile, $txt);
	}
	
}
$txt = "}; \n";
fwrite($myfile, $txt);


/*
-----------------------
Now that the new config file is created, we want to replace the old one. 
Before doing so, however, we want to compare both files to make sure they are indeed different to prevent unnecessary bind restarts

-----------------------
*/

//create function for comparing files before overwriting
function compareFiles($file_a, $file_b)
{
    if (filesize($file_a) == filesize($file_b))
    {
        $fp_a = fopen($file_a, 'rb');
        $fp_b = fopen($file_b, 'rb');

        while (($b = fread($fp_a, 4096)) !== false)
        {
            $b_b = fread($fp_b, 4096);
            if ($b !== $b_b)
            {
                fclose($fp_a);
                fclose($fp_b);
                return false;
            }
        }

        fclose($fp_a);
        fclose($fp_b);

        return true;
    }

    return false;
}

//get new and old file locations
$oldfile = "/etc/bind/named.conf.acl";
$newfile = "newfile.txt";

//overwrite oldfile
if (!compareFiles($oldfile, $newfile)) {
	unlink ($oldfile);
	rename ($newfile, $oldfile);


//restart bind
	exec ('update-rc.d bind9 defaults > bindrestart.log');
	exec ('rndc reconfig > bindrestart.log'); 
/*
If a program is started with this function, in order for it to continue running in the background, 
the output of the program must be redirected to a file or another output stream. 
Failing to do so will cause PHP to hang until the execution of the program ends.
*/

}

$conn->close();


	
?>
