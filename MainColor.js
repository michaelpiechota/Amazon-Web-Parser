function MainColor(base64) {
	var img = new Image();
	//Eventually something like var image = JSON.parse()
	img.src = "data:image/false;base64," + base64;
	//img.src = 'pants.jpg'; //Hard-coded for the meanwhile

	//Create canvas and set to 2d
	var canvas = document.getElementById('canvas');
	var context = canvas.getContext('2d');

	img.onload = function() {
		//Get the the image width and height
		canvas.width = img.width;
		canvas.height = img.height;
		context.drawImage(img, 0, 0);
		img.style.display = 'none';

		var imgData = context.getImageData(0,0, canvas.width, canvas.height);
		var data = imgData.data; //Array that stores the rgba values
		//var pixels = [];
		var colors = [];

		for (var i = 0; i < data.length; i+=4) {
			//rgba value of pixel stored at every 4 indexes. Example:
			//R value stored at 0
			//B value stored at 1
			//G value stored at 2
			//A value stored at 3
			var rgb = "rgba(" + data[i] + "," + data[i+1] + "," + data[i+2] + ")"
			var hex = rgb2hex(rgb); //Convert rgb value to hex

			//Do not store white, or colors close to white
			//This is due to an background colors of images
			if(data[i] < 250 && data[i+1] < 250 && data[i+2] < 250) {
				colors[colors.length] = hex;
			}

			//Array not needed but potentially could be used
			/*pixels.push({
				x: (i/4) % canvas.width,
				y: Math.floor((i/4)/canvas.width),
				red: data[i],
				green: data[i+1],
				blue: data[i+2],
				alpha: data[i+3],
				hex: hex
			}); */

		}
		//document.write(JSON.stringify(order(colors)));

		//Order the array of colors to making a
		var ordered = order(colors);

		//Get the average r, b, g of the top 5 frequent pixels
		var r, g, b;
		r = g = b = 0;

		for (var i = 0; i < 5; i++) {
			var rgb = hex2rgb(ordered[i]);
			r = r + rgb.r;
			g = g + rgb.g;
			b = b + rgb.b;
		}

		r = Math.round(r/5);
		g = Math.round(g/5);
		b = Math.round(b/5);

		var avgrgb = "rgba(" + r + "," + g + "," + b + ")"
		var avghex = rgb2hex(avgrgb);
		var avgArr = [r,g,b];
		//document.write(avgArr);

		var rgba = "rgba(" + r + "," + g + "," + b + "," + "1" + ")"
		//document.write(rgba);
		var color = document.getElementById('color');
		color.style.background =  rgba;
	    color.textContent = rgba;

		//document.write(JSON.stringify(avghex));
		return avghex; //OR avgArr to return the main color rbg value array
	};

	function order(array) {
	    var frequency = {};

	    array.forEach(function(value) { frequency[value] = 0; });

	    var uniques = array.filter(function(value) {
	        return ++frequency[value] == 1;
	    });

	    return uniques.sort(function(a, b) {
	        return frequency[b] - frequency[a];
	    });
	}

	function hex2rgb(hex) {
	    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	    return result ? {
	        r: parseInt(result[1], 16),
	        g: parseInt(result[2], 16),
	        b: parseInt(result[3], 16)
	    } : null;
	}

	function rgb2hex(rgb){
	 rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
	 return (rgb && rgb.length === 4) ? "#" +
	  ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
	  ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
	  ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : '';
	}
}
