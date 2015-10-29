function ShowAdminActions(tablename, tableid)
{	if (document.getElementById("aaModalInner"))
	{	$url = "ajax_aahistory.php?tablename=" + tablename + "&tableid=" + tableid;
		if (window.XMLHttpRequest) // code for IE7+, Firefox, Chrome, Opera, Safari
		{	xmlhttp=new XMLHttpRequest();
		} else // code for IE6, IE5
		{	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.open("GET", $url, false);
		xmlhttp.send(null);
		if (xmlhttp.responseText.substring(0, 16) == "<!--aahistory-->")
		{	document.getElementById("aaModalInner").innerHTML = xmlhttp.responseText;
		} else document.getElementById("aaModalInner").innerHTML = xmlhttp.responseText;
	} //else alert("not found div");
} // end of fn ShowAdminActions

function ShowAdminActionsDeleted(tablename)
{	if (document.getElementById("aaModalInner"))
	{	$url = "ajax_aadeleted.php?tablename=" + tablename;
		if (window.XMLHttpRequest) // code for IE7+, Firefox, Chrome, Opera, Safari
		{	xmlhttp=new XMLHttpRequest();
		} else // code for IE6, IE5
		{	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.open("GET", $url, false);
		xmlhttp.send(null);
		if (xmlhttp.responseText.substring(0, 16) == "<!--aahistory-->")
		{	document.getElementById("aaModalInner").innerHTML = xmlhttp.responseText;
		} //else aaModalInner.innerHTML = xmlhttp.responseText;
	} //else alert("not found div");
} // end of fn ShowAdminActionsDeleted

function getConfirmation(url, msg){
	var msgTxt = msg;
	msgTxt = (typeof(msgTxt)!=='undefined')?msgTxt:'Deleting this record will delete all its \nassoicated records such as bookings/orders. \n Do you still want to continue?';
   	var retVal = confirm(msgTxt);
   	if(retVal == true){
	  	if(typeof(url)!=='undefined'){
			location.href = url;
		}
   	}else{
	 	return false;
   	}
}