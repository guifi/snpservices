# SNPServices
The **snpservices** package provides graphing services in the context of the [Spontaneous Networking Platform](https://guifi.net/node/5192).
It uses *nodexchange/cnml* as the input format, by using *mrtg/rrdtool* collected historical data, and provides traffic and latency/availability graphs in PNG image format, by means of HTTP URLs.
Therefore, it is easily plugable into any web application.

## Prerequisites
You must have *rrdtool*, *rrdtool-perl*, *rrdtool-php*, *mrtg* and *php-gd* installed.
Optionally, in order to checkout files from the repository, *git*.

## Installation

### 1. Checkout the latest GIT version into a directory accessible by an http server

Example:
```
cd /var/www/html
git clone git://github.com/guifi/snpservices.git
```

To publish the directory, you might want to create an alias into the Apache host configuration for the directory:

```
Alias /snpservices /var/www/html/snpservices
```

### 2. Make the directory snpservices/tmp writable to everybody

```
chmod a+rw snpservices/tmp
```

### 3. Create the service on [Guifi.net's](https://www.guifi.net) database
Provide this URL in the form:
```
http://yourserver/snpservices
```

### 4. Copy the `common/config.php.template` to `config.php` and edit it to set your values
In particular, pay attention to:
 - Setting the Service ID (`SNPGraphServerId`) to match the service created in the database
 - Pointing `MRTGConfigSource`. If you don't have a cached CNML, use your Data server, make sure you specify the zone you are using as the server parameter
 - Pointing CNMLSource. Use your Data server

### IMPORTANT: You can skip steps 5 & 6 if you don't have old files to rename

However, it is a good idea to execute the `wget` command just to make sure that your data server is ready to feed graphs to your server.
Have in mind that it may take a while for it to be ready to export your data.

### 5. If you have the rrd files in an old format (devicename*.rrd)...
If you have old graph files which you would like to keep, rename them into the new format, by creating a script with the following code:
```
wget -O x.sh "http://snpservices.guifi.net/snpservices/graphs/cnml2mrtgcsv.php?cp&server=ServerID#"
```
where the server number is the number of your server.

### 6. If you simply want a list of the files at another server
You can get a list from it with the script:

```
wget -O x.sh "http://snpservices.guifi.net/snpservices/graphs/cnml2mrtgcsv.php?list&server=ServerID#"
```
where the server number is the number of your server.

### 7. Install a cron job to run *mrtg* and refresh your config every 5 minutes

It can be something like this:
```
cd /var/www/snpservices/graphs; php mrtgcsv2mrtgcfg.php;
/usr/bin/mrtg ../data/mrtg.cfg --lock-file /var/lock/mrtg/guifi_l --confcache-file
```
