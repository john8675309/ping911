<html>
<head>
<script src="js/jquery-2.2.4.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.buttons.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#deviceinfo').DataTable({
      "pageLength": 1000,
    });
    new $.fn.dataTable.Buttons( table, {
        buttons: [
            {
                text: 'Add Selected',
                action: function ( e, dt, node, conf ) {
                  var interfaces = [];
                 	var address = $( "#address" ).val();
                	var snmpCommunity = $( "#snmpCommunity" ).val();
                	var snmpVersion = $('#snmp').find(":selected").val();
                  for(i = 0;i<table.rows('.selected').data().length;i++) {
                    var snmp_id = table.rows('.selected').data()[i][0];
                    var interface_name = table.rows('.selected').data()[i][1];
                    var interface_port = table.rows('.selected').data()[i][2];
                    var interface_status = table.rows('.selected').data()[i][3];
                    var interface = {"snmp_id":snmp_id,"interface_name":interface_name,"interface_port":interface_port};
                    interfaces.push(interface);
                  }
                  var address = {"ip_address":address,"snmpCommunity":snmpCommunity,"snmpVersion":snmpVersion,"interfaces":interfaces};
                  console.log(JSON.stringify(interfaces));
                  $.ajax({
                    type: 'POST',
                    url: 'addSNMPInterfaces.php',
                    data: JSON.stringify(address),
                    success: function(data) { alert('data: ' + data); },
                    contentType: "application/json",
                    dataType: 'json'
                  });
                  /*$.get( "addSNMPInterfaces.php?address="+address+"&snmpVersion="+snmpVersion+"&snmpCommunity="+snmpCommunity+"&snmp_id="+snmp_id+"&interface_name="+interface_name+"&interface_port="+interface_port, function( data ) {
                  });*/
                  alert("Your Device Has been Added!");
                  window.location.assign("added.php")
                }
            },
            {
                text: 'Unselect All',
                action: function ( e, dt, node, conf ) {
                  $('#deviceinfo tbody tr').removeClass('selected');
                }
            },
            {
                text: 'Select All',
                action: function ( e, dt, node, conf ) {
                  $('#deviceinfo tbody tr').removeClass('selected');
                  for (var i = 0;i<table.rows().count() +1;i++) {
                    var $row = $("#deviceinfo tr").eq(i);
                    var $cell = $row.find("td").eq(3);
                    if (typeof $cell.html() != 'undefined') {
                      $cell.trigger('click');
                    }
                  }
                }
            },
            {
                text: 'Select Active',
                action: function ( e, dt, node, conf ) {
                  $('#deviceinfo tbody tr').removeClass('selected');
                  for (var i = 0;i<table.rows().count() +1;i++) {
                    var $row = $("#deviceinfo tr").eq(i);
                    var $cell = $row.find("td").eq(3);
                    if (typeof $cell.html() != 'undefined') {
                      if ($cell.html().includes("online")) {
                        $cell.trigger('click');
                      }
                    }
                  }
               }
            }
        ]
    });
    table.buttons( 0, null ).container().appendTo(table.table().container());

    $('#deviceinfo tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
    });
    $('#button').click( function () {
        alert( table.rows('.selected').data().length +' row(s) selected' );
    });
});
function addDevice() {
	var address = $( "#address" ).val();
	var snmpCommunity = $( "#snmpCommunity" ).val();
	var snmpVersion = $('#snmp').find(":selected").val();
	$('#working').html('Please Wait<br /><img src="img/loading.gif"></img>');
	$.get( "snmpAddDevice.php?address="+address+"&snmpVersion="+snmpVersion+"&snmpCommunity="+snmpCommunity, function( data ) {
    console.log(data);
    data = jQuery.parseJSON(data);
    $('#working').html('<img src="'+data.image+'" width="128"/>');
    var t = $('#deviceinfo').DataTable();
    t.clear().draw();
    for (var i = 0; i < data.devices.length; i++) {
      var obj = data.devices[i];
      if (!obj.interfaceName) {
        obj.interfaceName = "";
      }
      if (obj.interfaceStatus == 0) {
        obj.interfaceStatus='<img src="img/offline.png" height=16 width=16></img>';
      } else if (obj.interfaceStatus == 1) {
        obj.interfaceStatus='<img src="img/online.png" height=16 width=16></img>';
      }
      t.row.add([
        obj.interface,
        obj.interfaceName,
        obj.interfacePort,
        obj.interfaceStatus
      ]).draw(false);
   		//$('#deviceinfo').html(data);
    }
	});
}
</script>
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="css/buttons.dataTables.min.css"/>
<style>
#container {
  display: table;
}
#row  {
  display: table-row;
}

#left, #right, #middle {
  display: table-cell;
}
div.dt-buttons {
  clear: both;
}
</style>
<title>Add A Device</title>
</head>
<body>
<div align="center">
  <div id="container">
    <div id="row">
  	  <div id="left">
	  	Device Address
  	  </div>

  	  <div id="middle">
	  	<input type="text" name="address" id="address">
  	  </div>
   </div>
    <div id="row">
  	  <div id="left">
	  	SNMP Community
  	  </div>

  	  <div id="middle">
	  	<input type="text" name="snmpCommunity" id="snmpCommunity" value="public">
  	  </div>
   </div>

    <div id="row">
  	  <div id="left">
	  	SNMP Version
  	  </div>
  	  <div id="middle" align="left">
            <select name="snmp" id="snmp">
              <option value="1">1</option>
              <option value="2c">2c</option>
              <option value="ping">Ping Only</option>
            </select>
  	  </div>
    </div>
  </div>
  <input type="Submit" value="Add Device" onClick="addDevice()"></input>
  <br />
  <br />
  <div id="working"></div>
</div>
<div style="border:1px solid black;">
  <table id="deviceinfo" class="display" cellspacing="0" width="100%">
    <thead align="left">
      <tr>
        <th>SNMP ID</th>
        <th>Interface Name</th>
        <th>Interface Port</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
</div>


</body>
</html>

