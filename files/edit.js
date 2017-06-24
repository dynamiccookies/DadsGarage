$(document).ready(function () {
	$('#oEdit').click(function (e) {
		e.preventDefault();
		var dad = $(this).parent().parent().parent();
		dad.find('.show').hide();
		dad.find('.noscreen').show();
		document.getElementById("oPhone").value = document.getElementById("phonenum").innerHTML;
		document.getElementById("oEmail").value = document.getElementById("emailadd").innerHTML;
		document.getElementById("SubmitAll").disabled = true;
		document.getElementById("ownerdd").disabled = true;
	});
	$('#oSave').click(function (e) {
		e.preventDefault();
		var e = document.getElementById("ownerdd");
		var owner = e.options[e.selectedIndex].value;
		var dad = $(this).parent().parent().parent();
		dad.find('.show').show();
		dad.find('.noscreen').hide();
		document.getElementById("phonenum").innerHTML = valPhone(document.getElementById("oPhone").value);
		document.getElementById("emailadd").innerHTML = document.getElementById("oEmail").value;
		document.getElementById("SubmitAll").disabled = false;
		document.getElementById("ownerdd").disabled = false;
		if (document.getElementById("phonenum").innerHTML != ownersArray[owner-1]['phone'] || document.getElementById("emailadd").innerHTML != ownersArray[owner-1]['email']) {
			window.location.href = thissite + '&ophone=' + valPhone(document.getElementById("phonenum").innerHTML) + '&oemail=' + document.getElementById("emailadd").innerHTML;
		}
	});
	$('.fedit').click(function (e) {
		e.preventDefault();
		var dad = $(this).parent();
		dad.find('.show').hide();
		dad.parent().parent().parent().find('.hide').hide();
		dad.find('.noscreen').show();
	});
});
function oSubmit() {
	var dad = $(this).parent().parent();
	dad.find('.show').show();
	dad.find('.noscreen').hide();
	document.getElementById("phonenum").innerHTML = document.getElementById("oPhone").value;
	document.getElementById("emailadd").innerHTML = document.getElementById("oEmail").value;
	document.getElementByName('SubmitAll').disabled = false;
}

function updateOwner() {
	var e = document.getElementById("ownerdd");
	var owner = e.options[e.selectedIndex].value;
	if (owner !=0) {
		document.getElementById("phonenum").innerHTML = ownersArray[owner-1]['phone'];
		document.getElementById("emailadd").innerHTML = ownersArray[owner-1]['email'];
		document.getElementById("oPhone").value = ownersArray[owner-1]['phone'];
		document.getElementById("oEmail").value = ownersArray[owner-1]['email'];
	} else {
		document.getElementById("phonenum").innerHTML = '';
		document.getElementById("emailadd").innerHTML = '';
		document.getElementById("oPhone").value = '';
		document.getElementById("oEmail").value = '';
	}
	statusChange();
}
function statusChange() {
	var txt = '';
	var e = document.getElementById("status");
	var stat = e.options[e.selectedIndex].value;
	switch(stat) {
		case 'Draft':
			txt = 'Vehicle is in DRAFT mode.';
			break;
		case 'Sold':
			txt = 'Vehicle has been SOLD.';
			break;
		case 'Delete':
			txt = 'Vehicle has been marked for DELETION.';
			break;
		default:
			txt = '';
	}
	document.getElementById("notice").innerHTML = txt;
	if (stat == 'Sold') {
		document.getElementById("payment").className = "";
		document.getElementById("lblinsured").className = "noscreen";
		document.getElementById("insured").className = "noscreen";
		document.getElementById("buyer").className = "";
		document.getElementById("fname").className = "";
		document.getElementById("lname").className = "";
		document.getElementById("lblsaledate").className = "";
		document.getElementById("saledate").className = "";
	}else{
		document.getElementById("lblsaledate").className = "noscreen";
		document.getElementById("saledate").className = "noscreen";
		document.getElementById("buyer").className = "noscreen";
		document.getElementById("fname").className = "noscreen";
		document.getElementById("lname").className = "noscreen";
		document.getElementById("lblinsured").className = "";
		document.getElementById("insured").className = "";
		document.getElementById("payment").className = "noscreen";
	}

}
function loading1() {
	document.getElementById("loading1").style.display = 'block';
}
function loading2() {
	document.getElementById("loading2").style.display = 'block';
}
function valPhone(num) {
	var arr = num.match(/\d+/g);
	var str = '';
	for (var i = 0, len = arr.length; i < len; i++) {str += arr[i];}
	str = substr_replace(str,'(',0,0);
	str = substr_replace(str,') ',4,0);
	str = substr_replace(str,'-',9,0);
	if (str.length != 14) {str = ''}
	return str;
}

function substr_replace(str, replace, start, length) {		//php substr_replace in js
  // discuss at: http://phpjs.org/functions/substr_replace/ // original by: Brett Zamir (http://brett-zamir.me)
  if (start < 0) {start = start + str.length;}
  length = length !== undefined ? length : str.length;
  if (length < 0) {length = length + str.length - start;}
  return str.slice(0, start) + replace.substr(0, length) + replace.slice(length) + str.slice(start + length);
}
//Testing Textarea Char Limit ( onkeyup="countChar(this)")
/* function countChar(val) {
	var len = val.value.length;
	if (len >= 700) {
		val.value = val.value.substring(0, 700);
	} else {
		$('#charNum').text(700 - len);
	}
} */
