function ShowItems(key){

	var value = document.getElementById("keywords_"+key).value;

 	$("#keywords_"+ key).autocomplete({
        source: 'includes/ItemShowSearch.php'
    });
	
}


// function ShowItems(key)
// {
//   var Description=document.getElementById('keywords_'+key).value;
//  // alert(desc);

//  var SearchOrSelect='Search';

// if (window.XMLHttpRequest)
//   {// code for IE7+, Firefox, Chrome, Opera, Safari
//   xmlhttp=new XMLHttpRequest();
//   }
// else
//   {// code for IE6, IE5
//   xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
//   }
// xmlhttp.onreadystatechange=function()
//   {

//   if (xmlhttp.readyState==4 && xmlhttp.status==200)
//     {
//       alert(xmlhttp.responseText);
   
//     document.getElementById("keywords_"+key).innerHTML=xmlhttp.responseText;
//     }
//   }
//   if (SearchOrSelect=='Search') {
  

// 		xmlhttp.open("GET","includes/ItemShowSearch.php?Description="+Description,true);
// 	} else {
// 		xmlhttp.open("GET","includes/ItemShowSelect.php?Category="+Category+"&Code="+Code+"&Description="+Description+"&MaxItems="+MaxItems+"&identifier="+identifier,true);
// 	}
// xmlhttp.send();
// }
