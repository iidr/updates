function SearchOnFocus()
{	if ($input = document.getElementById("searchinput"))
	{	if ($input.value == jsDefSearchText)
		{	$input.value = "";
		}
	}
} // end of fn SearchOnFocus

function SearchLostFocus()
{	if ($input = document.getElementById("searchinput"))
	{	if ($input.value == "")
		{	$input.value = jsDefSearchText;
		}
	}
} // end of fn SearchOnFocus

function SearchSubmit()
{	if ($input = document.getElementById("searchinput"))
	{	return $input.value.length > 0;
	}
	return false;
} // end of fn SearchSubmit

function clearField(element, value)
{
	if($(element).val() == value)
	{
		$(element).val('');	
	}
} // end of fn clearField

function fillField(element, value)
{
	if($(element) && ($(element).val() == ''))
	{
		$(element).val(value);	
	}
} // end of fn fillField

function AK_Ajax(url, external_url)
{	var xmlhttp, url;
	if (window.XMLHttpRequest) // code for IE7+, Firefox, Chrome, Opera, Safari
	{	xmlhttp = new XMLHttpRequest();
	} else // code for IE6, IE5
	{	xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}
	if (external_url == undefined)
	{	url = jsSiteRoot + url;
	}
	xmlhttp.open("GET", url, false);
	xmlhttp.send(null);
	return xmlhttp.responseText;
} // end of fn BH_Ajax

function AK_Ajax_Post(url, params, action_function, external_url)
{	
	var xmlhttp;
	if (external_url == undefined)
	{	url = jsSiteRoot + url;
	}

	if (window.XMLHttpRequest) // code for IE7+, Firefox, Chrome, Opera, Safari
	{	xmlhttp = new XMLHttpRequest();
		//xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
	} else // code for IE6, IE5
	{	xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.open("POST", url, true);
	//xmlhttp.setRequestHeader("Cache", "false");
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.setRequestHeader("Connection", "close");

	xmlhttp.onreadystatechange = function()
	{	if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{	action_function(xmlhttp.responseText);
		}
	}
	xmlhttp.send(params);
	
} // end of fn AK_Ajax_Post

function AjaxPagination(containerid, ajaxpage, parameters, pageno, pagename)
{	var container;
	if (pagename == undefined)
	{	pagename = 'page';
	}
	if (container = document.getElementById(containerid))
	{	url = ajaxpage + '?' + pagename + '=' + String(pageno);
		if (parameters.length)
		{	url += '&' + parameters;
		}
		$(container).hide();
		container.innerHTML = AK_Ajax(url);
		$(container).fadeIn(400);
		window.scrollTo(0, 0);
		
	/*	var doc_offset = (document.documentElement && document.documentElement.scrollTop  || document.body && document.body.scrollTop  || 0);
		var container_offset = getOffset(container);
		if (container_offset['top'] < doc_offset)
		{	if (document.documentElement && document.documentElement.scrollTop)
			{	document.documentElement.scrollTop = container_offset['top'];
			} else
			{	if (document.body && document.body.scrollTop)
				{	document.body.scrollTop = container_offset['top'];
				}
			}
		}*/
	}
} // end of fn AjaxPagination

function getOffset( el )
{	var _x = 0;
	var _y = 0;
	while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) )
	{	_x += el.offsetLeft - el.scrollLeft;
		_y += el.offsetTop - el.scrollTop;
		el = el.offsetParent;
	}
	return { top: _y, left: _x };
} // end of fn getOffset

function SubPageHighlight(slugname)
{	var h3;
	if (h3 = document.getElementById('sub_' + slugname))
	{	SubPageUnsetHighlight();
		$('#sub_' + slugname).addClass('inPageHighlight');
	}
} // end of fn SubPageHighlight

function SubPageUnsetHighlight()
{	$('div.the-content h3').removeClass('inPageHighlight');
} // end of fn SubPageHighlight

function SubPagePreHighlight()
{	var subpos;
	if (subpos = String(window.location).indexOf('#sub_'))
	{	SubPageHighlight(String(window.location).slice(subpos + 5));
	}
} // end of fn SubPagePreHighlight

function OpenReviewForm()
{	$('#review_modal_popup').jqmShow();
} // end of fn OpenReviewForm

function SubPageContainerToggle(menuid)
{	var menuheader, menuheader_name, menucontainer, menucontainer_name;
	if ((menuheader = document.getElementById(menuheader_name = 'subpage_menu_header_' + menuid)) && (menucontainer = document.getElementById(menucontainer_name = 'subpage_menu_container_' + menuid)))
	{	if ($('#' + menucontainer_name).hasClass('subpage_menu_container_open'))
		{	$('#' + menucontainer_name).removeClass('subpage_menu_container_open');
			$('#' + menucontainer_name).addClass('subpage_menu_container_closed');
			$('#' + menuheader_name).removeClass('subpage_menu_header_open');
			$('#' + menuheader_name).addClass('subpage_menu_header_closed');
		} else
		{	$('#' + menucontainer_name).addClass('subpage_menu_container_open');
			$('#' + menucontainer_name).removeClass('subpage_menu_container_closed');
			$('#' + menuheader_name).addClass('subpage_menu_header_open');
			$('#' + menuheader_name).removeClass('subpage_menu_header_closed');
		}
	}
} // end of fn SubPageContainerToggle
/*
function goToByScroll(id, damount)
{	//$('html,body').delay(damount).animate({scrollTop: $("#"+id).offset().top},'slow', 'easeInOutSine' );
	$('html,body').animate({scrollTop: $("#"+id).offset().top},'slow', 'easeInOutSine' );
	return false;
} // end of fn SubPageContainerToggle;
*/
function goToByScroll(elementid)
{	$('html,body').animate({scrollTop: $(elementid).offset().top},'slow');
}

function TickBoxToggle(boxname)
{	var boxid, boxvalue;
	boxvalue = document.getElementById('TickBoxValue' + String(boxname));
	if ($(boxid = '#TickBox' + String(boxname)).hasClass('TickBox0'))
	{	$(boxid).addClass('TickBox1');
		$(boxid).removeClass('TickBox0');
		boxvalue.value = 1;
	} else
	{	$(boxid).addClass('TickBox0');
		$(boxid).removeClass('TickBox1');
		boxvalue.value = 0;
	}
} // end of fn mtfTickBoxToggle
