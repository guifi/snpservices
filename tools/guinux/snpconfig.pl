#!/usr/bin/perl
#
# set the configuration CNML
#

$oldfile = "/etc/snpservices/config.php.guinux";
$newfile = "/etc/snpservices/config.php";
$oldid = "%SNPId%";
$oldurl = "%SNPUrl%";
$defurl = "http://snpservices.guifi.net";
$defid = "6833";

print "Without ending backslash, Enter the url where the data is. [$defurl]";
$newurl = <STDIN>;
chomp($newurl);
if ($newurl eq "") { $newurl=$defurl; }

print "Enter the number of the Graph Service Id [$defid]: ";
$newid = <STDIN>;
chomp($newid);
if ($newid eq "") { $newid=$defid; }

print "Setting the Graph Server Url to $newurl\n";
print "Setting the Graph Server Id to $newid\n";
open(OF, $oldfile) or die "Can't open $oldfile : $!";
open(NF, ">$newfile") or die "Can't open $newfile : $!";

# read in each line of the file
while ($line = <OF>) {
    $line =~ s/$oldurl/$newurl/;
    $line =~ s/$oldid/$newid/;
    print NF $line;
}

close(OF);
close(NF);
`/usr/sbin/service apache2 start`;
print "Successful: New Graph Server Url ($newurl) and Id ($newid) has been set\n";
print "Check that the graph server is responding to the CNML calls by visiting http://localhost/snpservices\n";
print "Done. Press <Enter> to finalize.";
$end = <STDIN>;
