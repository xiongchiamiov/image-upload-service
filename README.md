I wanted a simple image-upload that supported API use, but when I went looking,
everything had way too much stuff for what I wanted, so I decided to build my
own.

It is not complete, nor is it thoroughly tested, but it seems to work, as viewed
by my light testing.

The curl line at the top of the file is an example of how you would use it.

# Install and Setup

1.  Dump the files somewhere your webserver can get to them.
2.  Adjust the constants at the top of the file to point to where you want them.
3.  Make sure the upload directory exists and is writable. Same thing for a
    directory named 's' inside of it (for the small thumbnails).
4.  Run!
5.  Use!

# Use

All responses are in JSON. You'll probably send data using curl, either the
command-line client or a binding for the language of your choice. Pass image
data in an array named "image"; here's an example:

	curl http://localhost/image/upload.php -F "image[]=@ed2_cover.png" -F "image[]=@ed_cover.png"
