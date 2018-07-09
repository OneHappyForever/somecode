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

/*write the rest of the file

The below config is obviously significantly lacking. I would recommend creating separate files for the IPs, views, configs, etc.
And then in the general configs, simply including them, like so:

include "/etc/bind/named.conf.options";
include "/etc/bind/named.conf.acl";
include "/etc/bind/named.conf.default-zones";


/etc/bind/named.conf.acl:
acl "us" {
    1.1.1.1; 4.4.4.4; 123.456.789.0; 
};
acl "uk" {
	2.2.2.2; 5.5.5.5; 342.643.1.45;
}


/etc/bind/named.recursion.conf:
allow-recursion { trusted; };
recursion yes;
additional-from-auth yes;
additional-from-cache yes;

/etc/bind/named.conf.options:
options {
        directory "/var/cache/bind";

        forwarders {
            2001:4860:4860::8888;
            2001:4860:4860::8844;
            8.8.8.8;
            8.8.4.4;
        };

        dnssec-validation auto;

        auth-nxdomain no;    # conform to RFC1035
        listen-on-v6 { any; };

        allow-query { trusted; };
        allow-transfer { none; };

        include "/etc/bind/named.recursion.conf";
};

*/
$txt = "acl any {\n    any;\n};";
fwrite($myfile, $txt);
$txt = "view us {\n     match-clients { us; };\n    allow-recursion { us; };\n    include "/etc/bind/zonesus.override";\n    recursion yes;\n    additional-from-auth yes;\n    additional-from-cache yes;\n};";
fwrite($myfile, $txt);
$txt = "view uk {\n     match-clients { uk; };\n    allow-recursion { uk; };\n    include "/etc/bind/zonesuk.override";\n    recursion yes;\n    additional-from-auth yes;\n    additional-from-cache yes;\n};";
fwrite($myfile, $txt);
$txt = "view any {\n     match-clients { any; };\n    allow-recursion { any; };\n    recursion yes;\n    additional-from-auth yes;\n    additional-from-cache yes;\n};";
fwrite($myfile, $txt);
fclose($myfile);




/*
-----------------------
Now that the new config file is created, we want to replace the old one. 
Before doing so, however, we want to compare both files to make sure they are indeed different to prevent unneccessary bind restarts

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
$oldfile = "/etc/bind/named.conf";
$newfile = "newfile.txt";

//overwrite oldfile
if (!compareFiles($oldfile, $newfile)) {
	unlink ($oldfile);
	rename ($newfile, $oldfile);


//restart bind
exec ('systemctl bind restart > bindrestart.log 2>&1');
/*
If a program is started with this function, in order for it to continue running in the background, 
the output of the program must be redirected to a file or another output stream. 
Failing to do so will cause PHP to hang until the execution of the program ends.
*/

}

$conn->close();


	
?>
