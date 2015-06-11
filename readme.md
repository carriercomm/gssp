GSSP
----

Google Spreadsheet Proxy

Takes GSS public data and transforms them to more JS-friendly format, i.e. as JSON, with callback or variable assignment, with columns renamed, with forced cache (or forced nocache) etc.

Needs cURL

Licensed under no-whining MIT

Example:

&lt;script src="http://example.com/gssp/filter.php?key=abcdefgh8765432mInUbYvTr&sheet=1&cols=1&forcecache=1&cb=process"></script>

means:

 - take data from spreadsheet with given key. It has to be publish. (not "public for everyone", but "published on web")
 - take sheet 1 (default)
 - ignore column names in row 1, name them as "c0", c1", "c2", "c3"...
 - force cache (default is "renown cache after 600 sec"). You can use "nocache" too
 - enclose data in callback - process({... data ...});

 Second file, bigfilter.php, can takes data from multiple columns
