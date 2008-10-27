var myimage = new Image();
myimage.src = 'loading.gif';
//Browser Support Code
function ajaxFunction(){
	var ajaxRequest;  // The variable that makes Ajax possible!
	
	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Internet Explorer Browsers
		try{
			ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				// Something went wrong
				alert("Your browser broke!");
				return false;
			}
		}
	}
	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function(){
                if(ajaxRequest.readyState == 1){
                        var ajaxDisplay = document.getElementById('ajaxDiv');
                        //ajaxDisplay.innerHTML = "<img src=\"../images/loading.gif\" alt=\"Loading... Please wait\" />";
                }
                if(ajaxRequest.readyState == 4){
			var ajaxDisplay = document.getElementById('ajaxDiv');
			ajaxDisplay.innerHTML = ajaxRequest.responseText;
		}
	}
	//var myid = document.getElementById('myid').value;
	var queryString = "?curseAddonID=";// + myid;
	ajaxRequest.open("GET", "listAddonsQuery.php" + queryString, true);
	ajaxRequest.send(null);
	document.addAddon.elements[0].focus();
}
setInterval(ajaxFunction, 5000);