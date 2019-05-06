# MyStuff2PDF
Webpage for converting MyStuff Export Files into PDF Files

It is recommended to run the script locally. With Windows you can use XAMPP (Just check Apache), in Mac/Linux systems a PHP solution is usually implemented. Just google how to run a php script as a local server.

Set the following values in php.ini:
```
post-max-size=0
upload-max-filesize=2000M
```

The Reset button resets all already rendered PDF files and returns you to the selection screen.

With the Generate Zip button all categories generated at that time can be packed and downloaded in one.

IMPORTANT:
Once the file is loaded, only one category should/can be rendered at a time. As long as the page is still loaded, the PDF will be processed. A small info will appear next to the category when it is finished.
