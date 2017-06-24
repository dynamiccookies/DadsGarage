$('#page span').click(function () {
	$(this).siblings().css({"fontWeight":"normal","cursor":"pointer"});
	$(this).css({"fontWeight":"bold","cursor":"pointer"});
});
$('#category span').click(function () {
	$(this).siblings().css({"fontWeight":"normal","cursor":"pointer"});
	$(this).css({"fontWeight":"bold","cursor":"pointer"});
});

function viewSold() {
	document.getElementById('hidden').style.display = 'block';document.getElementById('sale').style.display = 'none';
	document.getElementById('hidden1').style.display = 'block';document.getElementById('sale1').style.display = 'none';
	document.getElementById('lblSold').style.display = 'block';document.getElementById('lblSale').style.display = 'none';
	$.ajax({type: "POST", url: 'ajax.php', data: {view: "sold"}}).done(function() {});
}
function viewSale() {
	document.getElementById('sale').style.display = 'block';document.getElementById('hidden').style.display = 'none';
	document.getElementById('sale1').style.display = 'block';document.getElementById('hidden1').style.display = 'none';
	document.getElementById('lblSale').style.display = 'block';document.getElementById('lblSold').style.display = 'none';
	$.ajax({type: "POST", url: 'ajax.php', data: {view: "forsale"}}).done(function() {});
}
function myFunction(x) {
	x.classList.toggle("change");
	document.getElementById("adminSidenav").classList.toggle("change");
	document.getElementById("adminMain").classList.toggle("change");
}
function openTab(tabName,elmnt,position) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablink");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].style.backgroundColor = "#daecee";
		tablinks[i].style.fontWeight = "normal";
		tablinks[i].style.borderBottom = '1px solid';
		tablinks[i].style.borderTop = 'none';
		tablinks[i].style.borderRight = 'none';
		tablinks[i].style.borderLeft = 'none';
    }
    document.getElementById(tabName).style.display = "block";
    elmnt.style.backgroundColor = 'silver';
	elmnt.style.fontWeight = 'bold';
	elmnt.style.borderBottom = 'none';
	elmnt.style.borderTop = '3px solid blue';
	if (position == 'left' || position == 'middle') {elmnt.style.borderRight = '1px solid';}
	if (position == 'right' || position == 'middle') {elmnt.style.borderLeft = '1px solid';}
/* 	if (tabName == 'Database' || tabName == 'Owners' || tabName == 'Description') {elmnt.style.borderRight = '1px solid';}
	if (tabName == 'Users' || tabName == 'Owners') {elmnt.style.borderLeft = '1px solid';}
 */}
