var page = require('webpage').create();
var args = require('system').args;

var url = args[1];
var filename = args[2];

page.open(url, function (status) {

    if ( status === "success" ) {
        setTimeout(function(){
            page.render(filename);
        },500);
    } else {
        console.log("Page failed to load.");
    }
    phantom.exit();
});
