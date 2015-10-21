
function SaveDelivery(orderno)
{	var params, url;
	url = 'ajax_ordersave.php?action=savedel&id=' + String(orderno);
	params = new Array();
	params[params.length] = 'delnotes=' + encodeURIComponent(document.getElementById('delnotes').value);
	params[params.length] = 'delivered=' + (document.getElementById('delDelivered').checked ? '1' : '0');
	
	action_function = function(ajax_response)
	{	var orderDelContainer;
		if (orderDelContainer = document.getElementById('orderDelContainer'))
		{	orderDelContainer.innerHTML = ajax_response;
		}
	};
	AK_Ajax_Post(url, params.join('&'), action_function);
} // end of fn SaveDelivery

function SaveDeliveryChanged()
{	document.getElementById('delSaveSubmit').style.display = 'block';
} // end of fn SaveDeliveryChanged

function SavePayment(orderno)
{	var params, url, paycheck;
	url = 'ajax_ordersave.php?action=savepay&id=' + String(orderno);
	params = new Array();
	params[params.length] = 'pmtnotes=' + encodeURIComponent(document.getElementById('pmtnotes').value);
	if (paycheck = document.getElementById('manPmtCheck'))
	{	params[params.length] = 'paid=' + (paycheck.checked ? '1' : '0');
	}
	if (cancelcheck = document.getElementById('cancelPmtCheck'))
	{	params[params.length] = 'cancel=' + (cancelcheck.checked ? '1' : '0');
	}
	
	action_function = function(ajax_response)
	{	var orderPmtContainer;
		if (orderPmtContainer = document.getElementById('orderPmtContainer'))
		{	orderPmtContainer.innerHTML = ajax_response;
		}
	};
	AK_Ajax_Post(url, params.join('&'), action_function);
} // end of fn SavePayment

function SavePaymentChanged(){	
	document.getElementById('paySaveSubmit').style.display = 'block';
} // end of fn SavePaymentChanged
