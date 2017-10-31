<?php
//form
class Form {

	static function dropdown($content_r, $query_r = '', $trunc = '', $fields = 2) {
		$field_name = '';
		$get_name = true;
		$content = '';
		$query_r = dev_value_to_array($query_r);
		$output = '';
		$select_r = array();
		$select_r['-------'] = '';
		foreach ($content_r as $row) {
			$row = dev_value_to_array($row);
			$value_r = array();
			$i = 0;
			foreach ($row as $a=>$b) {
				if ($get_name) {
					$field_name = $a;
					$get_name = dev_flip_bool($get_name);
				}
				if ($i > $fields) break;
				if ($i > 0) $value_r[] = dev_format_data($b, '', $trunc);
				$i++;
			}
			
			$select_r[implode(' - ', $value_r)] = $row[$field_name];
		}
		foreach ($query_r as $a=>$b) $output .= dev_draw_form_field('hidden', $a, '', $b);
		$output .= dev_draw_form_field('select', $field_name, '', $select_r) .
		dev_draw_form_field('submit', 'submit', 'Submit', 'Submit');
		
		return $output;
	}

	static function field($type = '', $name = '', $label = '', $value = '', $required = false, $id = '', $readonly = false, $properties = '') 
	{
	     $output = '';
	     $label_text = '';
	     
	     $varname = $name;
	     $properties = dev_value_to_array($properties);
	     if (!isset($properties['class'])) $properties['class'] = $type ? $type : 'formdata';
	     foreach ($properties as $a=>$b) $attribs[] = "$a=\"$b\"";
	     $attrib = implode(' ', $attribs);
	     
	     $id = 'id="' . ( ($id != '') ? $id : $name ) . '"';
	     $disabled = ($readonly === false) ? '' : 'readonly';
	     if ($readonly == 3) $type = 'static';

		if ($label != '' && $type != 'static') $label_text = '<label for="'.$name.'">' . (($required) ? '*' : '' ) . $label . ': </label>';
		
	     if ($type != 'date' && $type != 'select') {
			$name .= (is_array($value)) ? '[]' : '';
		}


		
	     $value = dev_value_to_array($value, true);
	     
	     switch ($type) {
	     default:
	     case 'text':
	     	foreach ($value as $a) {
	          	$output .= $label_text . ' <br />' . "\n\t" . '<input type="text" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" ' . $attrib . ' /><br />';
	          }
	          break;
	     case 'prompt':
	     	foreach ($value as $a) {
	          	$output .= $label_text . ' <br />' . "\n\t" . '<input type="text" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" ' . $attrib . ' onFocus="this.value=\'\'" /><br />';
	          }
	          break;
	     case 'password':
	     	foreach ($value as $a) {
	          	$output .= $label_text . ' <br />' . "\n\t" . '<input type="password" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" ' . $attrib . ' /><br />';
	          }
	          break;
	     case 'textarea':
	     	foreach ($value as $a) {
	          	$output .= $label_text . ' <br />' . "\n\t" . '<textarea ' . $disabled . ' name="' . $name . '" ' . $id . ' ' . $attrib . '>' . $a . '</textarea><br />';
	          }
	          break;
	     case 'select':
	          $output .= $label_text . ' <br />' . "\n";
			$output .= '<select name="' . $name . '" ' . $disabled . ' ' . $id . ' '.$attrib.'>' . "\n";
	          $value = dev_value_to_array($value);
	          global $$varname; 
	          
	          foreach ($value as $a=>$b) {
	          	if (is_array($b)) {
					$output .= "\t" . '<optgroup label="' . $a . '">' . "\n";
					foreach ($b as $c=>$d) {
						$output .= "\t\t" . '<option value="' . $d . '"';
		                    //if the select option being drawn is the same as the 
		                    //variable by the same name of this element, mark it as selected
		                    if ($$varname == $d) $output .= ' selected="selected"';
		                    $output .= '>' . $c . '</option>' . "\n";
					}
					$output .= "\t" . '</optgroup>' . "\n";
				} else {
	                    $output .= "\t" . '<option value="' . $b . '"';
	                    //if the select option being drawn is the same as the 
	                    //variable by the same name of this element, mark it as selected
	                    if ($$varname == $b) $output .= ' selected="selected"';
	                    $output .= '>' . $a . '</option>' . "\n";
	               }
	          }
	          $output .= '</select><br />';
	          break;
		case 'multiple':
	          $output .= $label_text . ' <br />' . "\n";
	          $output .= '<select name="' . $name . '" ' . $disabled . ' ' . $id . ' '.$attrib.' size="6" multiple="multiple">' . "\n";
	          $value = dev_value_to_array($value);
	          global $$varname; 
	          foreach ($value as $a=>$b) {
	          	if (is_array($b)) {
					$output .= "\t" . '<optgroup label="' . $a . '">' . "\n";
					foreach ($b as $c=>$d) {
						$output .= "\t\t" . '<option value="' . $d . '"';
		                    //if the select option being drawn is the same as the 
		                    //variable by the same name of this element, mark it as selected
		                    if (is_array($$varname)) if (in_array($d, $$varname)) $output .= ' selected="selected"';
		                    elseif ($$varname == $d) $output .= ' selected="selected"';
		                    $output .= '>' . $c . '</option>' . "\n";
					}
					$output .= "\t" . '</optgroup>' . "\n";
				} else {
	                    $output .= "\t" . '<option value="' . $b . '"';
	                    //if the select option being drawn is the same as the 
	                    //variable by the same name of this element, mark it as selected
	                    if (is_array($$varname)) if (in_array($b, $$varname)) $output .= ' selected="selected"';
	                    elseif ($$varname == $b) $output .= ' selected="selected"';
	                    $output .= '>' . $a . '</option>' . "\n";
	               }
	          }
	          $output .= '</select><br />';
	          break;
		case 'hidden':
			foreach ($value as $a) {
		          $output .= '<input type="hidden" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" />';
		     }
	          break;
	     case 'checkbox':
			if (dev_is_assoc($value)) {
		     	$output .= (($required) ? '*' : '' ) . $label.'<br />';
				$i = 1;
		     	foreach ($value as $a=>$b) {
			          $output .= '<input '.$attrib.'  type="checkbox" ' . $disabled . ' name="' . $name . '" ' . substr_replace($id, $i.'"', -1, strlen($id));
			          global $$varname;
			          if ($$varname == $b) $output .= ' checked="checked"';
			          $output .= ' value="' . $b . '" />';
			          $output .= '<label for="'.$varname.$i.'">' . substr($a, 0, (strlen($a))) . " </label><br /> \n";
						$i++;
			     }
			} else {
					foreach ($value as $a) {
				          $output .= '<input type="checkbox" ' . $disabled . ' name="' . $name . '" ' . $id;
				          global $$varname;
				          if ($$varname == $a) $output .= ' checked="checked"';
				          $output .= ' value="' . $a . '" /> ';
				          $output .= '<label for="'.$varname.'">' . substr($label, 0, (strlen($label))) . " </label><br /> \n";;
			     	}
		  	}		
	          break;
	     case 'radio':
			if (dev_is_assoc($value)) {
		     	$output .= (($required) ? '*' : '' ) . $label.'<br />';
				$i = 1;
		     	foreach ($value as $a=>$b) {
			          $output .= '<input '.$attrib.'  type="radio" ' . $disabled . ' name="' . $name . '" ' . substr_replace($id, $i.'"', -1, strlen($id));
			          global $$varname;
			          if ($$varname == $b) $output .= ' checked="checked"';
			          $output .= ' value="' . $b . '" />';
			          $output .= '<label for="'.$varname.$i.'">' . substr($a, 0, (strlen($a))) . " </label><br /> \n";
						$i++;
			     }
			} else {
					foreach ($value as $a) {
				          $output .= '<input type="radio" ' . $disabled . ' name="' . $name . '" ' . $id;
				          global $$varname;
				          if ($$varname == $a) $output .= ' checked="checked"';
				          $output .= ' value="' . $a . '" /> ';
				          $output .= '<label for="'.$varname.'">' . substr($label, 0, (strlen($label))) . " </label><br /> \n";;
			     	}
		  	}
	     	break;
	     case 'date':
	          $output .= dev_date_select($name, $label, $value[0], $readonly);
	          break;
	      case 'calendar':
	     			$output .= $label_text . "\n\t" . '<SCRIPT LANGUAGE="JavaScript" ID="js'.$name.'">
		var cal'.$name.' = new CalendarPopup("datediv1");
		cal'.$name.'.setCssPrefix("DATE");
	</SCRIPT>
	<SCRIPT LANGUAGE="JavaScript">writeSource("js'.$name.'");</SCRIPT>
	<input type="text" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $value[0] . '" '.$attrib.' />
	<A HREF="#" onclick="cal'.$name.'.select(document.getElementById(\''.$name.'\'),\'anchor'.$name.'\',\'yyyy-MM-dd\'); return false;" TITLE="cal'.$name.'.select(document.getElementById(\''.$id.'\'),\'anchor1x\',\'yyyy-MM-dd\'); return false;" NAME="anchor'.$name.'" ID="anchor'.$name.'"><img src="'.$image_path.'calendar.gif" alt="select" title="select" border="0" /></A><br />';
	     	break;
	     case 'time':
	     	$output .= dev_time_select($name, $label);
	     	break;
	     case 'file':
			foreach ($value as $a) {
				if (!$readonly && $a == '') $output .= $label_text . ' - ' . $a . ' <br />' . "\n\t" . '<input type="file" ' . $disabled . ' name="' . $name . '" ' . $id . ' /><br />';
				else $output = dev_form_field('checkbox', $name, $label . ' (' . $a . ')', $a, $required, $name, $readonly, $properties);
		     }
	          break;
	     case 'richtext':
	     	foreach ($value as $a) {
	     	//Code provided by Kevin Roth at www.kevinroth.com/rte/demo.htm
	     	//Requires following form header:
	     	/*
			<script language="JavaScript" type="text/javascript">
			<!--
			function submitForm() {
				//make sure hidden and iframe values are in sync before submitting form
				//to sync only 1 rte, use updateRTE(rte)
				//to sync all rtes, use updateRTEs
				//updateRTE(\'rte1\');
				updateRTEs();
				
				//change the following line to true to submit form
				return true;
			}
			
			//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
			initRTE("images/", "", "", true);
			//-->
			</script>
			     	*/
			     	
			     	//and included JS file and 'onsubmit="return submitForm();"' in form.
			     	$output .= '
			<noscript>';
			$output .= dev_form_field('textarea', $name, $label, $a, '', $id, $readonly);
			$output .= '
			</noscript>
			
			<script language="JavaScript" type="text/javascript">
			<!--
			var ' . $name . '_val = "";
			';
			//$output .= "\n";
			$a = html_entity_decode($a);
			$val_search = array('"', '</script>', '</textarea>', '</noscript>');
			$val_replace = array('\"', '&lt;/script&gt;', '&lt;/textarea&gt;', '&lt;/noscript&gt;');
			$a = str_replace($val_search, $val_replace, $a);
			$val_array = explode("\n", $a);
			//foreach ($val_array as $a) $output .= $name . '_val += "' . html_entity_decode(rtrim($a)) . '\n";' . "\n";
			foreach ($val_array as $var) $output .= $name . '_val += "' . rtrim($var) . '\n";' . "\n";
			$output .= '
			//-->
			</script>
			';
			//$output .= dev_form_field('hidden', $name . '_hidden', '', $a);
			if ($id == 'id="big"') {
				$w = '100%';
				$h = 400;
			} else {
				$w = 450;
				$h = 200;
			}
			$output .= '
			<script language="JavaScript" type="text/javascript">
			<!--
			' . $name  . '_val = ' . $name  . '_val.replace(/&lt;\/script&gt;/gi, "<" + "/script>");
			' . $name  . '_val = ' . $name  . '_val.replace(/&lt;\/textarea&gt;/gi, "<" + "/textarea>");
			' . $name  . '_val = ' . $name  . '_val.replace(/&lt;\/noscript&gt;/gi, "<" + "/noscript>");
			writeRichText(\'' . $name . '\', ' . $name . '_val, \'' . $w . '\', \'' . $h . '\', true, ' . (($readonly) ? 'true' : 'false') . ');
			//-->
			</script>
					';
			}
	     	break;
	     case 'static':
	     	foreach ($value as $a) {
	     		$output .= $label . '<br /><span ' . implode(' ', $attribs) . ' name="' . $name . '" ' . $id . '>' . $a . '</span>';
	     		$output .= dev_form_field('hidden', $name, '', $a);
	     	}
			break;
		case 'submit':
			foreach ($value as $a) {
	          	$output .= '<input type="submit" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" onclick="this.disabled=false;this.value=' . dev_prep_input((($label != '') ? $label : 'Submitting...')) . ';" ' . $attrib . ' /><br />';
	          }
	          break;
		case 'reset':
			foreach ($value as $a) {
	          	$output .= '<input type="reset" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" ' . $attrib . ' /><br />';
	          }
	          break;
		case 'button':
			foreach ($value as $a) {
	          	$output .= '<input type="button" ' . $disabled . ' name="' . $name . '" ' . $id . ' value="' . $a . '" ' . $attrib . ' /><br />';
	          }
	          break;
	     }
	     
		if ($label != '' && $type != 'hidden') {
			$output .= "<br />\n";
		}
	     return $output;
	}

	static function date($name = 'date', $label = 'Date', $value = '', $readonly = false) 
	{
		if ($value == '') 
		{
			$value = date("Y-m-d");
		}
		$disabled = ($readonly === false) ? '' : 'readonly';
	     
	     $date_day = dev_split_date($value, 'day');
	     $date_month = dev_split_date($value, 'month');
	     $date_year = dev_split_date($value, 'year');
	     
	     $output = '';
	     if ($label != '') $label .= ': <br />';
		$output .= $label . '
	     <select name="' . $name . '_month" ' . $disabled . '>';
	     for ($count=1; $count<=12; $count++) {
			$output .= '<option value="' . $count . '"';
			if ($date_month == $count) $output .= ' selected="selected"';
			$output .= '>' . $count . '</option>';
	     }
	     $output .= '</select> /	
	     <select name="' . $name . '_day" ' . $disabled . '>';
	     for ($count=1; $count<=31; $count++) {
			$output .= '<option value="' . $count . '"';
			if ($date_day == $count) $output .= ' selected="selected"';
			$output .= '>' . $count . '</option>';
	     }
	     $output .= '</select> /
	     <select name="' . $name . '_year" ' . $disabled . '>';
	     for ($count=1983; $count<=2016; $count++) {
			$output .= '<option value="' . $count . '"';
			if ($date_year == $count) $output .= ' selected="selected"';
			$output .= '>' . $count . '</option>';
	     }
	     $output .= '</select><br />';

	     return $output;
	}
}