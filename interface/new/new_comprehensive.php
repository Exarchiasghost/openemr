<?php
// Copyright (C) 2009-2010 Rod Roark <rod@sunsetsystems.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

require_once("../globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/erx_javascript.inc.php");

// Check authorization.
$thisauth = acl_check('patients', 'demo');
if ($thisauth != 'write' && $thisauth != 'addonly')
  die("Adding demographics is not authorized.");

$CPR = 4; // cells per row

$searchcolor = empty($GLOBALS['layout_search_color']) ?
  '#ffff55' : $GLOBALS['layout_search_color'];

$WITH_SEARCH = ($GLOBALS['full_new_patient_form'] == '1' || $GLOBALS['full_new_patient_form'] == '2');
$SHORT_FORM  = ($GLOBALS['full_new_patient_form'] == '2' || $GLOBALS['full_new_patient_form'] == '3');

function getLayoutRes() {
  global $SHORT_FORM;
  return sqlStatement("SELECT * FROM layout_options " .
    "WHERE form_id = 'DEM' AND uor > 0 AND field_id != '' " .
    ($SHORT_FORM ? "AND ( uor > 1 OR edit_options LIKE '%N%' ) " : "") .
    "ORDER BY group_name, seq");
}

// Determine layout field search treatment from its data type:
// 1 = text field
// 2 = select list
// 0 = not searchable
//
function getSearchClass($data_type) {
  switch($data_type) {
    case  1: // single-selection list
    case 10: // local provider list
    case 11: // provider list
    case 12: // pharmacy list
    case 13: // squads
    case 14: // address book list
    case 26: // single-selection list with add
    case 35: // facilities
      return 2;
    case  2: // text field
    case  3: // textarea
    case  4: // date
      return 1;
  }
  return 0;
}

$fres = getLayoutRes();
?>
<html>
<head>
<?php html_header_show(); ?>

<link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">

<style>
body, td, input, select, textarea {
 font-family: Arial, Helvetica, sans-serif;
 font-size: 10pt;
}

body {
 padding: 5pt 5pt 5pt 5pt;
}

div.section {
 border: solid;
 border-width: 1px;
 border-color: #0000ff;
 margin: 0 0 0 10pt;
 padding: 5pt;
}

</style>

<style type="text/css">@import url(../../library/dynarch_calendar.css);</style>

<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dynarch_calendar.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
<script type="text/javascript" src="../../library/dynarch_calendar_setup.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot']; ?>/library/js/jquery.js"></script>

<SCRIPT LANGUAGE="JavaScript"><!--
//Visolve - sync the radio buttons - Start
if((top.window.parent) && (parent.window)){
        var wname = top.window.parent.left_nav;
        fname = (parent.window.name)?parent.window.name:window.name;
        wname.syncRadios();
        wname.setRadio(fname, "new");
}//Visolve - sync the radio buttons - End

var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

// This may be changed to true by the AJAX search script.
var force_submit = false;

//code used from http://tech.irt.org/articles/js037/
function replace(string,text,by) {
 // Replaces text with by in string
 var strLength = string.length, txtLength = text.length;
 if ((strLength == 0) || (txtLength == 0)) return string;

 var i = string.indexOf(text);
 if ((!i) && (text != string.substring(0,txtLength))) return string;
 if (i == -1) return string;

 var newstr = string.substring(0,i) + by;

 if (i+txtLength < strLength)
  newstr += replace(string.substring(i+txtLength,strLength),text,by);

 return newstr;
}

<?php for ($i=1;$i<=3;$i++) { ?>
function auto_populate_employer_address<?php echo $i ?>(){
 var f = document.demographics_form;
 if (f.form_i<?php echo $i?>subscriber_relationship.options[f.form_i<?php echo $i?>subscriber_relationship.selectedIndex].value == "self") {
  f.i<?php echo $i?>subscriber_fname.value=f.form_fname.value;
  f.i<?php echo $i?>subscriber_mname.value=f.form_mname.value;
  f.i<?php echo $i?>subscriber_lname.value=f.form_lname.value;
  f.i<?php echo $i?>subscriber_street.value=f.form_street.value;
  f.i<?php echo $i?>subscriber_city.value=f.form_city.value;
  f.form_i<?php echo $i?>subscriber_state.value=f.form_state.value;
  f.i<?php echo $i?>subscriber_postal_code.value=f.form_postal_code.value;
  if (f.form_country_code)
    f.form_i<?php echo $i?>subscriber_country.value=f.form_country_code.value;
  f.i<?php echo $i?>subscriber_phone.value=f.form_phone_home.value;
  f.i<?php echo $i?>subscriber_DOB.value=f.form_DOB.value;
  f.i<?php echo $i?>subscriber_ss.value=f.form_ss.value;
  f.form_i<?php echo $i?>subscriber_sex.value = f.form_sex.value;
  f.i<?php echo $i?>subscriber_employer.value=f.form_em_name.value;
  f.i<?php echo $i?>subscriber_employer_street.value=f.form_em_street.value;
  f.i<?php echo $i?>subscriber_employer_city.value=f.form_em_city.value;
  f.form_i<?php echo $i?>subscriber_employer_state.value=f.form_em_state.value;
  f.i<?php echo $i?>subscriber_employer_postal_code.value=f.form_em_postal_code.value;
  if (f.form_em_country)
    f.form_i<?php echo $i?>subscriber_employer_country.value=f.form_em_country.value;
 }
}

<?php } ?>

function upperFirst(string,text) {
 return replace(string,text,text.charAt(0).toUpperCase() + text.substring(1,text.length));
}

// The ins_search.php window calls this to set the selected insurance.
function set_insurance(ins_id, ins_name) {
 var thesel = document.forms[0]['i' + insurance_index + 'provider'];
 var theopts = thesel.options; // the array of Option objects
 var i = 0;
 for (; i < theopts.length; ++i) {
  if (theopts[i].value == ins_id) {
   theopts[i].selected = true;
   return;
  }
 }
 // no matching option was found so create one, append it to the
 // end of the list, and select it.
 theopts[i] = new Option(ins_name, ins_id, false, true);
}

// Indicates which insurance slot is being updated.
var insurance_index = 0;

// The OnClick handler for searching/adding the insurance company.
function ins_search(ins) {
 insurance_index = ins;
 dlgopen('../../interface/practice/ins_search.php', '_blank', 550, 400);
 return false;
}

function checkNum () {
 var re= new RegExp();
 re = /^\d*\.?\d*$/;
 str=document.forms[0].monthly_income.value;
 if(re.exec(str))
 {
 }else{
  alert("Please enter a dollar amount using only numbers and a decimal point.");
 }
}

// This capitalizes the first letter of each word in the passed input
// element.  It also strips out extraneous spaces.
function capitalizeMe(elem) {
 var a = elem.value.split(' ');
 var s = '';
 for(var i = 0; i < a.length; ++i) {
  if (a[i].length > 0) {
   if (s.length > 0) s += ' ';
   s += a[i].charAt(0).toUpperCase() + a[i].substring(1);
  }
 }
 elem.value = s;
}

// Onkeyup handler for policy number.  Allows only A-Z and 0-9.
function policykeyup(e) {
 var v = e.value.toUpperCase();
 for (var i = 0; i < v.length; ++i) {
  var c = v.charAt(i);
  if (c >= '0' && c <= '9') continue;
  if (c >= 'A' && c <= 'Z') continue;
  if (c == '*') continue;
  if (c == '-') continue;
  if (c == '_') continue;
  if (c == '(') continue;
  if (c == ')') continue;
  if (c == '#') continue;
  v = v.substring(0, i) + v.substring(i + i);
  --i;
 }
 e.value = v;
 return;
}

function divclick(cb, divid) {
 var divstyle = document.getElementById(divid).style;
 if (cb.checked) {
  divstyle.display = 'block';
 } else {
  divstyle.display = 'none';
 }
 return true;
}

// Compute the length of a string without leading and trailing spaces.
function trimlen(s) {
 var i = 0;
 var j = s.length - 1;
 for (; i <= j && s.charAt(i) == ' '; ++i);
 for (; i <= j && s.charAt(j) == ' '; --j);
 if (i > j) return 0;
 return j + 1 - i;
}

function validate(f) {
<?php generate_layout_validation('DEM'); ?>
  <?php if($GLOBALS['erx_enable']){ ?>
  alertMsg='';
  for(i=0;i<f.length;i++){
    if(f[i].type=='text' && f[i].value)
    {
      if(f[i].name == 'form_fname' || f[i].name == 'form_mname' || f[i].name == 'form_lname')
      {
        alertMsg += checkLength(f[i].name,f[i].value,35);
        alertMsg += checkUsername(f[i].name,f[i].value);
      }
      else if(f[i].name == 'form_street' || f[i].name == 'form_city')
      {
        alertMsg += checkLength(f[i].name,f[i].value,35);
        alertMsg += checkAlphaNumericExtended(f[i].name,f[i].value);
      }
      else if(f[i].name == 'form_phone_home')
      {
       alertMsg += checkPhone(f[i].name,f[i].value);
      }
    }
  }
  if(alertMsg)
  {
    alert(alertMsg);
    return false;
  }
  <?php } ?>
 return true;
}

function toggleSearch(elem) {
 var f = document.forms[0];
<?php if ($WITH_SEARCH) { ?>
 // Toggle background color.
 if (elem.style.backgroundColor == '')
  elem.style.backgroundColor = '<?php echo $searchcolor; ?>';
 else
  elem.style.backgroundColor = '';
<?php } ?>
 if (force_submit) {
  force_submit = false;
  f.create.value = '<?php xl('Create New Patient','e'); ?>';
 }
 return true;
}

// If a <select> list is dropped down, this is its name.
var open_sel_name = '';

function selClick(elem) {
 if (open_sel_name == elem.name) {
  open_sel_name = '';
 }
 else {
  open_sel_name = elem.name;
  toggleSearch(elem);
 }
 return true;
}

function selBlur(elem) {
 if (open_sel_name == elem.name) {
  open_sel_name = '';
 }
 return true;
}

// This invokes the patient search dialog.
function searchme() {
 var f = document.forms[0];
 var url = '../main/finder/patient_select.php?popup=1';

<?php
$lres = getLayoutRes();

while ($lrow = sqlFetchArray($lres)) {
  $field_id  = $lrow['field_id'];
  if (strpos($field_id, 'em_') === 0) continue;
  $data_type = $lrow['data_type'];
  $fldname = "form_$field_id";
  switch(getSearchClass($data_type)) {
    case  1:
      echo
      " if (f.$fldname.style.backgroundColor != '' && trimlen(f.$fldname.value) > 0) {\n" .
      "  url += '&$field_id=' + encodeURIComponent(f.$fldname.value);\n" .
      " }\n";
      break;
    case 2:
      echo
      " if (f.$fldname.style.backgroundColor != '' && f.$fldname.selectedIndex > 0) {\n" .
      "  url += '&$field_id=' + encodeURIComponent(f.$fldname.options[f.$fldname.selectedIndex].value);\n" .
      " }\n";
      break;
  }
}
?>

 dlgopen(url, '_blank', 700, 500);
}

//-->

</script>
</head>

<body class="body_top">

<form action='new_comprehensive_save.php' name='demographics_form' method='post' onsubmit='return validate(this)'>

<span class='title'><?php xl('Search or Add Patient','e'); ?></span>

<table width='100%' cellpadding='0' cellspacing='8'>
 <tr>
  <td align='left' valign='top'>
<?php if ($SHORT_FORM) echo "  <center>\n"; ?>
<?php

function end_cell() {
  global $item_count, $cell_count;
  if ($item_count > 0) {
    echo "</td>";
    $item_count = 0;
  }
}

function end_row() {
  global $cell_count, $CPR;
  end_cell();
  if ($cell_count > 0) {
    for (; $cell_count < $CPR; ++$cell_count) echo "<td></td>";
    echo "</tr>\n";
    $cell_count = 0;
  }
}

function end_group() {
  global $last_group, $SHORT_FORM;
  if (strlen($last_group) > 0) {
    end_row();
    echo " </table>\n";
    if (!$SHORT_FORM) echo "</div>\n";
  }
}

$last_group    = '';
$cell_count    = 0;
$item_count    = 0;
$display_style = 'block';
$group_seq     = 0; // this gives the DIV blocks unique IDs

while ($frow = sqlFetchArray($fres)) {
  $this_group = $frow['group_name'];
  $titlecols  = $frow['titlecols'];
  $datacols   = $frow['datacols'];
  $data_type  = $frow['data_type'];
  $field_id   = $frow['field_id'];
  $list_id    = $frow['list_id'];
  $currvalue  = '';

  if (strpos($field_id, 'em_') === 0) {
    $tmp = substr($field_id, 3);
    if (isset($result2[$tmp])) $currvalue = $result2[$tmp];
  }
  else {
    if (isset($result[$field_id])) $currvalue = $result[$field_id];
  }

  // Handle a data category (group) change.
  if (strcmp($this_group, $last_group) != 0) {
    if (!$SHORT_FORM) {
      end_group();
      $group_seq++;    // ID for DIV tags
      $group_name = substr($this_group, 1);
      if (strlen($last_group) > 0) echo "<br />";
      echo "<span class='bold'><input type='checkbox' name='form_cb_$group_seq' id='form_cb_$group_seq' value='1' " .
        "onclick='return divclick(this,\"div_$group_seq\");'";
      if ($display_style == 'block') echo " checked";
        
      // Modified 6-09 by BM - Translate if applicable  
      echo " /><b>" . xl_layout_label($group_name) . "</b></span>\n";
        
      echo "<div id='div_$group_seq' class='section' style='display:$display_style;'>\n";
      echo " <table border='0' cellpadding='0'>\n";
      $display_style = 'none';
    }
    else if (strlen($last_group) == 0) {
      echo " <table border='0' cellpadding='0'>\n";
    }
    $last_group = $this_group;
  }

  // Handle starting of a new row.
  if (($titlecols > 0 && $cell_count >= $CPR) || $cell_count == 0) {
    end_row();
    echo "  <tr>";
  }

  if ($item_count == 0 && $titlecols == 0) $titlecols = 1;

  // Handle starting of a new label cell.
  if ($titlecols > 0) {
    end_cell();
    echo "<td colspan='$titlecols'";
    echo ($frow['uor'] == 2) ? " class='required'" : " class='bold'";
    if ($cell_count == 2) echo " style='padding-left:10pt'";
    echo ">";
    $cell_count += $titlecols;
  }
  ++$item_count;

  echo "<b>";
    
  // Modified 6-09 by BM - Translate if applicable  
  if ($frow['title']) echo (xl_layout_label($frow['title']).":"); else echo "&nbsp;";
    
  echo "</b>";

  // Handle starting of a new data cell.
  if ($datacols > 0) {
    end_cell();
    echo "<td colspan='$datacols' class='text'";
    if ($cell_count > 0) echo " style='padding-left:5pt'";
    echo ">";
    $cell_count += $datacols;
  }

  ++$item_count;
  generate_form_field($frow, $currvalue);
}

end_group();
?>

<?php
if (! $GLOBALS['simplified_demographics']) {
  $insurancei = getInsuranceProviders();
  $pid = 0;
  $insurance_headings = array(xl("Primary Insurance Provider"), xl("Secondary Insurance Provider"), xl("Tertiary Insurance provider"));
  $insurance_info = array();
  $insurance_info[1] = getInsuranceData($pid,"primary");
  $insurance_info[2] = getInsuranceData($pid,"secondary");
  $insurance_info[3] = getInsuranceData($pid,"tertiary");

  echo "<br /><span class='bold'><input type='checkbox' name='form_cb_ins' value='1' " .
    "onclick='return divclick(this,\"div_ins\");'";
  if ($display_style == 'block') echo " checked";
 
  echo "<div id='div_ins' class='section' style='display:$display_style;'>\n";

  for($i=1;$i<=3;$i++) {
   $result3 = $insurance_info[$i];
?>


  

<?php
  }
  echo "</div>\n";
 } // end of "if not simplified_demographics"
?>

<?php if (!$SHORT_FORM) echo "  <center>\n"; ?>
<br />
<?php if ($WITH_SEARCH) { ?>
<input type="button" id="search" value=<?php xl('Search','e','\'','\''); ?>
 style='background-color:<?php echo $searchcolor; ?>' />
&nbsp;&nbsp;
<?php } ?>
<input type="button" name='create' id="create" value=<?php xl('Create New Patient','e','\'','\''); ?> />

</center>

  </td>
  <td align='right' valign='top' width='1%' nowrap>
   <!-- Image upload stuff was here but got moved. -->
  </td>
 </tr>
</table>

</form>

<!-- include support for the list-add selectbox feature -->
<?php include($GLOBALS['fileroot']."/library/options_listadd.inc"); ?>

</body>

<script language="JavaScript">

// fix inconsistently formatted phone numbers from the database
var f = document.forms[0];
if (f.form_phone_contact) phonekeyup(f.form_phone_contact,mypcc);
if (f.form_phone_home   ) phonekeyup(f.form_phone_home   ,mypcc);
if (f.form_phone_biz    ) phonekeyup(f.form_phone_biz    ,mypcc);
if (f.form_phone_cell   ) phonekeyup(f.form_phone_cell   ,mypcc);

<?php echo $date_init; ?>

// -=- jQuery makes life easier -=-

// var matches = 0; // number of patients that match the demographic information being entered
// var override = false; // flag that overrides the duplication warning

$(document).ready(function() {

    // added to integrate insurance stuff
    <?php for ($i=1;$i<=3;$i++) { ?>
    $("#form_i<?php echo $i?>subscriber_relationship").change(function() { auto_populate_employer_address<?php echo $i?>(); });
    <?php } ?>
	
    $('#search').click(function() { searchme(); });
    $('#create').click(function() { submitme(); });

    var submitme = function() {
      top.restoreSession();
      var f = document.forms[0];

      if (validate(f)) {
        if (force_submit) {
          // In this case dups were shown already and Save should just save.
          f.submit();
          return;
        }
<?php
// D in edit_options indicates the field is used in duplication checking.
// This constructs a list of the names of those fields.
$mflist = "";
$mfres = sqlStatement("SELECT * FROM layout_options " .
  "WHERE form_id = 'DEM' AND uor > 0 AND field_id != '' AND " .
  "edit_options LIKE '%D%' " .
  "ORDER BY group_name, seq");
while ($mfrow = sqlFetchArray($mfres)) {
  $field_id  = $mfrow['field_id'];
  if (strpos($field_id, 'em_') === 0) continue;
  if (!empty($mflist)) $mflist .= ",";
  $mflist .= "'" . htmlentities($field_id) . "'";
}
?>
        // Build and invoke the URL to create the dup-checker dialog.
        var url = 'new_search_popup.php';
        var flds = new Array(<?php echo $mflist; ?>);
        var separator = '?';
        for (var i = 0; i < flds.length; ++i) {
          var fval = $('#form_' + flds[i]).val();
          if (fval && fval != '') {
            url += separator;
            separator = '&';
            url += 'mf_' + flds[i] + '=' + encodeURIComponent(fval);
          }
        }
        dlgopen(url, '_blank', 700, 500);

      } // end if validate
    } // end function

// Set onclick/onfocus handlers for toggling background color.
<?php
$lres = getLayoutRes();
while ($lrow = sqlFetchArray($lres)) {
  $field_id  = $lrow['field_id'];
  if (strpos($field_id, 'em_') === 0) continue;
  switch(getSearchClass($lrow['data_type'])) {
    case 1:
      echo "    \$('#form_$field_id').click(function() { toggleSearch(this); });\n";
      break;
    case 2:
      echo "    \$('#form_$field_id').click(function() { selClick(this); });\n";
      echo "    \$('#form_$field_id').blur(function() { selBlur(this); });\n";
      break;
  }
}
?>

}); // end document.ready

</script>

</html>

