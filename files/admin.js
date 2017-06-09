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