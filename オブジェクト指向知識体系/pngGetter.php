<?php
$matches = [];

//rawurldecode()
preg_match("/^[^,]+,(.*)$/", $_POST['dataurl'], $matches);


file_put_contents($_POST['filename'], base64_decode($matches[1]));

print $_POST['filename'];
?>