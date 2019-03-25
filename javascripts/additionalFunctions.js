function update1(ids) {
	var inputIds = ids.split(";");

	var total = 0;
	inputIds.forEach(function(currId, index){

		currAmount = parseInt(document.getElementById(currId).value);
		total += currAmount;
	})
	document.getElementById("Amount").value = total
	document.getElementById("ttl").value = total
}

function AddAmount(elem, id) {
	if (elem.checked) {
		document.getElementById(id).value = elem.value
	}else{
		document.getElementById(id).value = 0;
	}
}