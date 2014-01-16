function sisat_setarchive() {
   var check = document.getElementById('noind').checked;
   if( check == true ) {
      for (i = 1; i < 5; i++) {
         document.getElementById('noind'+i).checked = true;
      }
   }
}
function sisat_countdesc(id) {
   var desc = document.getElementById(id).value;
   desc = desc.trim();
   document.getElementById(id+'-ctr').value=desc.length;
}