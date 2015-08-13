function ChangedPType(discid){	
	PTypeProductSelect(discid, 0);
	DisplaySelectPTypePopUp(discid);
} // end of fn ChangedPType

function DisplaySelectPTypePopUp(discid,ticketid){	
	var ptype_select, prodidinput, popup;
	if ((ptype_select = document.getElementById('prodtype')) && (prodidinput = document.getElementById('prodid')) && (popup = document.getElementById('rlpModalInner'))){	
		if (ptype_select.value.length){	
			ticketidinput = document.getElementById('ticketid');
			url  = 'ajax_discountedit.php?action=list&id=' + String(discid) + '&ptype=' + ptype_select.value + '&prodid=' + String(prodidinput.value);
			url += (ticketid>0)?'&ticketid=' + String(ticketid):((ticketidinput.value!='undefined')?'&ticketid=' + String(ticketidinput.value):'&ticketid=0');
			popup.innerHTML = AK_Ajax(url);
			$("#rlp_modal_popup").jqmShow();
		}
	}
} // end of fn DisplaySelectPTypePopUp

function PTypeProductSelect(discid, prodid,ticketid){	
	if (ptype_select = document.getElementById('prodtype')){	
		url = 'ajax_discountedit.php?action=select&id=' + String(discid) + '&ptype=' + ptype_select.value + '&prodid=' + String(prodid);
		url += (ticketid>0)?'&ticketid=' + String(ticketid):'&ticketid=0';
		document.getElementById('ptypeDetails').innerHTML = AK_Ajax(url);
		$("#rlp_modal_popup").jqmHide();
	}
} // end of fn PTypeProductSelect
