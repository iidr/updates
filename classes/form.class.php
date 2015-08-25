<?php
class Form
{	var $action = "";
	var $css_class = "";
	var $formLines = array();
	var $method = "post";
	var $extraText = "";
	var $formHeader = "";
	var $fileUploads = false;


	function __construct($action = "", $css_class = "") // constructor
	{	if ($action)
		{	$this->action = $action;
		} else
		{	$this->action = $_SERVER["PHP_SELF"];
		}
		$this->css_class = $css_class;
	} // end of fn __construct (constructor)
	
	function SetMethod($method = "POST")
	{	$this->method = $method;
	} // end of fn SetMethod

	function AddExtraText($text = "")
	{	$this->extraText .= $text;
	} // end of fn AddExtraText

	function AddFormHeader($text = "")
	{	$this->formHeader .= $text;
	} // end of fn AddExtraText

	function AddTextInput($label = "", $name = "", $def_value = "", $css_class = "", $maxlength = 0, $reqfield = 0, $js = "")
	{	$this->formLines[] = new FormLine($label, $name, $def_value, $css_class, $maxlength, $reqfield, $js);
	} // end of fn AddTextInput

	function AddRawText($rawtext = "")
	{	$this->formLines[] = new FormRaw($rawtext);
	} // end of fn AddRawText

	function AddTextArea($label = "", $name = "", $def_value = "", $css_class = "", $maxlength = 0, $reqfield = 0, $rows = 0, $cols = 0, $js = "")
	{	$this->formLines[] = new TextArea($label, $name, $def_value, $css_class, $maxlength, $reqfield, $rows, $cols, $js);
	} // end of fn AddTextArea

	function AddFileUpload($label = "", $name = "", $css_class = "", $reqfield = 0)
	{	$this->formLines[] = new FileUpload($label, $name, $css_class, $reqfield);
		$this->fileUploads = true;
	} // end of fn AddFileUpload

	function AddLabelLine($label = "", $css_class = "")
	{	$this->formLines[] = new FormLabel($label, $css_class);
	} // end of fn AddTextInput

	function AddPasswordInput($label = "", $name = "", $css_class = "", $maxlength = 0, $reqfield = 0, $js = "")
	{	$this->formLines[] = new PasswordInput($label, $name, $css_class, $maxlength, $reqfield, $js);
	} // end of fn AddTextInput

	function AddHiddenInput($name = "", $value = "")
	{	$this->formLines[] = new HiddenInput($name, $value);
	} // end of fn AddHiddenInput

	function AddRadioGroup($name = "", $options = array(), $value = "", $css_class = "")
	{	$this->formLines[] = new RadioGroup($name, $options, $value, $css_class);
	} // end of fn AddRadioGroup

	function AddCheckBox($label = "", $name = "", $value = "", $checked = 0, $css_class = "", $js = "")
	{	$this->formLines[] = new CheckBoxLine($label, $name, $value, $checked, $css_class, $js);
	} // end of fn AddCheckBox

	function AddSubmitButton($name = "", $text = "", $css_class = "", $js = "")
	{	$this->formLines[] = new SubmitLine($name, $text, $css_class, $js);
	} // end of fn AddSubmitButton

	function AddMultiInput($label = "", $fields = array(), $reqfield = 0)
	{	$this->formLines[] = new MultiInput($label, $fields, $reqfield);
		foreach ($fields as $field)
		{	if ($field["type"] = "FILE")
			{	$this->fileUploads = true;
			}
		}
	} // end of fn AddMultiInput

	function AddDateInput($label = "", $name = "", $defdate = "", $years = array(), $months = array(), 
							$days = array(), $allowblank = false, $reqfield = 0, $defpickyear = 0, $js = "")
	{	$this->formLines[] = new FormLineDate($label, $name , $defdate, $years, $months, 
										$days, $allowblank, $reqfield, $defpickyear, $js);
	} // end of fn AddDateInput
	
	function AddDateInputNoPicker($label = "", $name = "", $defdate = "", $years = array(), $months = array(), 
							$days = array(), $allowblank = false, $reqfield = 0, $defpickyear = 0, $js = "")
	{	$this->formLines[] = new DOBFormLineDate($label, $name , $defdate, $years, $months, 
										$days, $allowblank, $reqfield, $defpickyear, $js);
	} // end of fn AddDateInput

	function AddSelectPlusOther($label = "", $name = "", $def_value = "", $css_class = "", $options = array(), $reqfield = 0)
	{	$this->formLines[] = new SelectPlusOther($label, $name, $def_value, $css_class, $options, $reqfield);
	} // end of fn AddSelectPlusOther

	function AddSelect($label = "", $name = "", $def_value = "", $css_class = "", $options = array(), 
								$allowblank = false, $reqfield = 0, $js = "")
	{	$this->formLines[] = new FormLineSelect($label, $name, $def_value, $css_class, $options, $allowblank, $reqfield, $js);
	} // end of fn AddSelect

	function AddSelectWithGroups($label = "", $name = "", $def_value = "", $css_class = "", $options = array(), 
								$allowblank = false, $reqfield = 0, $js = "")
	{	$this->formLines[] = new FormSelectWithGroups($label, $name, $def_value, $css_class, $options, $allowblank, 
										$reqfield, $js);
	} // end of fn AddSelectWithGroups

	function FormDef()
	{	echo "<form action='", $this->action, "'", 
			$this->css_class ? " class='$this->css_class'" : "", 
			$this->fileUploads ? " enctype='multipart/form-data'" : "", 
			" method='", $this->method, "'>";
	} // end of fn FormDef

	function FormEnd()
	{	echo "</form>\n";
	} // end of fn FormEnd

	function RequiredFieldsWarning($req_count = 0)
	{	if ($req_count)
		{	echo "<label><span class='req'>*</span>", $req_count > 1 ? "are required fields" : "is a required field", 
						"</label><br />\n";
		}
	} // end of fn RequiredFieldsWarning
	
	function DisplayFormHeader()
	{	if ($this->formHeader)
		{	echo "<p class='formHeader'>", $this->formHeader, "</p>\n";
		}
	} // end of fn DisplayFormHeader
	
	function DisplayExtraText()
	{	if ($this->extraText)
		{	echo "<p class='formFooter'>", $this->extraText, "</p><br />\n";
		}
	} // end of fn DisplayExtraText
	
	function DisplayFormLines()
	{	foreach($this->formLines as $line)
		{	$line->Output();
			if ($line->reqfield)
			{	$reqfields++;
			}
		}
	} // end of fn DisplayFormLines
	
	function Output()
	{	$this->FormDef();
		$this->DisplayFormHeader();
		$this->DisplayFormLines();
		$this->RequiredFieldsWarning($reqfields);
		$this->DisplayExtraText();
		$this->FormEnd();
	} // end of fn Output

} // end of class defn Form

class FormLine
{	var $label = "&nbsp;";
	var $name = "";
	var $css_class = "";
	var $def_value = "";
	var $maxlength = 0;
	var $reqfield = 0;
	var $js = "";

	function __construct($label = "", $name = "", $def_value = "", $css_class = "", $maxlength = 0, $reqfield = 0, $js = "") // constructor
	{	if ($label) $this->label = $label;
		$this->name = $name;
		$this->def_value = $def_value;
		$this->css_class = $css_class;
		$this->js = $js;
		$this->reqfield = (int)$reqfield;
		$this->maxlength = (int)$maxlength;
	} // end of fn __construct (constructor)

	function OutputLabel()
	{	echo "<label>", $this->label, "<span class='req'>", $this->reqfield ? "*" : "&nbsp;","</span></label>";
	} // end of fn OutputLabel

	function OutputField()
	{	echo "<input type='text' name='", $this->name, "' id='", $this->name, "' value='", $this->def_value, "' ", 
				$this->css_class ? "class='$this->css_class' " : "", 
				$this->js ? "$this->js " : "", 
				$this->maxlength ? "maxlength='$this->maxlength' " : "", 
				"/>";
	} // end of fn OutputField

	function Output()
	{	$this->OutputLabel();
		$this->OutputField();
		echo "<br />\n";
	} // end of fn Output

} // end of class defn FormLine

class TextArea extends FormLine
{	var $rows = 4;
	var $cols = 30;

	function __construct($label = "", $name = "", $def_value = "", $css_class = "", $maxlength = 0, $reqfield = 0, 
							$rows = 0, $cols = 0, $js = "")
	{	parent::__construct($label, $name, $def_value, $css_class, $maxlength, $reqfield, $js);
		if ($rows = (int)$rows)
		{	$this->rows = $rows;
		}
		if ($cols = (int)$cols)
		{	$this->cols = $cols;
		}
	} // end of fn __construct

	function OutputField()
	{	echo "<textarea id='", $this->name, "' name='", $this->name, "'", $this->css_class ? " class='$this->css_class'" : "", 
				"", $this->js ? " $this->js" : "", 
				" rows='", $this->rows, "' cols='", $this->cols, "'>", $this->def_value, "</textarea>";
	} // end of fn OutputField

} // end of class TextArea

class PasswordInput extends FormLine
{	
	function __construct($label = "", $name = "", $css_class = "", $maxlength = 0, $reqfield = 0, $js = "") // constructor
	{	parent::__construct($label, $name, "", $css_class, $maxlength, $reqfield, $js);
	} // end of fn __construct

	function OutputField()
	{	echo "<input type='password' name='", $this->name, "' ", 
				$this->css_class ? "class='$this->css_class' " : "", 
				$this->js ? "$this->js " : "", 
				$this->maxlength ? "maxlength='$this->maxlength' " : "", 
				"/>",
				$this->maxlength ? "<br /><span>Maximum Password Length: ".$this->maxlength." characters</span>": "";
	} // end of fn OutputField

} // end of class PasswordInput

class FileUpload extends FormLine
{	
	function __construct($label = "", $name = "", $css_class = "", $reqfield = 0) // constructor
	{	parent::__construct($label, $name, "", $css_class, 0, $reqfield);
	} // end of fn __construct

	function OutputField()
	{	echo "<input type='file' name='", $this->name, "' ", 
				$this->css_class ? "class='$this->css_class' " : "", 
				"/>";
	} // end of fn OutputField

} // end of class FileUpload

class CheckBoxLine extends FormLine
{	var $checked = 0;

	function __construct($label = "", $name = "", $value = "", $checked = 0, $css_class = "", $js = "") // constructor
	{	parent::__construct($label, $name, $value, $css_class, 0, 0, $js);
		$this->checked = (int)$checked;
	} // end of fn __construct

	function OutputField()
	{	echo "<input type='checkbox' name='", $this->name, "' value='", 
				$this->def_value ? $this->def_value : "1", "' ", 
				$this->css_class ? "class='$this->css_class' " : "", 
				$this->js ? "$this->js " : "", 
				$this->checked ? "checked='checked' " : "", 
				"/>";
	} // end of fn OutputField

} // end of class CheckBoxLine

class HiddenInput
{	var $name = "";
	var $value = "";

	function __construct($name = "", $value = "") // constructor
	{	$this->name = $name;
		$this->value = $value;
	} // end of fn __construct (constructor)

	function Output()
	{	echo "<input type='hidden' class='formHidden' name='", $this->name, "' value='", $this->value, "' />";
	} // end of fn Output

	function OutputField()
	{	$this->Output();
	} // end of fn Output

} // end of class defn HiddenInput

class RadioGroup
{	var $buttons = array();

	function RadioGroup($name = "", $options = array(), $value = "", $css_class = "") // constructor
	{	if (is_array($options))
		{	foreach ($options as $opvalue=>$label)
			{	if (!$value)
				{	$value = $opvalue;
				}
				$this->buttons[] = new RadioButton($label, $name, $opvalue, $value, $css_class);
			}
		}
	} // end of fn RadioGroup (constructor)

	function Output()
	{	foreach ($this->buttons as $button)
		{	$button->Output();
		}
	} // end of fn Output

} // end of class defn RadioGroup

class RadioButton extends FormLine
{	var $value = "";

	function __construct($label = "", $name = "", $value = "", $def_value = "", $css_class = "") // constructor
	{	parent::__construct($label, $name, $def_value, $css_class);
		$this->value = $value;
	} // end of fn __construct (constructor)

	function OutputField()
	{	echo "<input type='radio' class='formRadio' value='", $this->value, "'", 
				$this->css_class ? " class='$this->css_class'" : "", 
				" name='", $this->name, "' ", 
				$this->def_value == $this->value ? "checked='checked' " : "", "/>";
	} // end of fn OutputField

} // end of class defn RadioButton

class MultiInput extends FormLine
{	var $fields = array();

	function __construct($label = "", $fields = array(), $reqfield = 0) // constructor
	{	parent::__construct($label, "", "", "", 0, $reqfield);
		if (is_array($fields))
		{	foreach ($fields as $field)
			{	switch ($field["type"])
				{	case "SELECT": $this->fields[] = new FormLineSelect("", $field["name"], $field["value"], 
												$field["css"], $field["options"], $field["allowblanks"], 0, $field["js"]);
							break;
					case "SELECTGROUPS": $this->fields[] = new FormSelectWithGroups("", $field["name"], $field["value"], 
												$field["css"], $field["options"], $field["allowblanks"], 0, $field["js"]);
							break;
					case "SELECTOTHER": $this->fields[] = new SelectPlusOther("", $field["name"], $field["value"], 
												$field["css"], $field["options"], 0, $field["js"]);
							break;
					case "TEXT": $this->fields[] = new FormLine("", $field["name"], $field["value"], $field["css"], 
									$field["maxlength"], 0, $field["js"]);
							break;
					case "FILE": $this->fields[] = new FileUpload("", $field["name"], $field["css"]);
							break;
					case "HIDDEN": $this->fields[] = new HiddenInput($field["name"], $field["value"]);
							break;
					case "DATE": $this->fields[] = new FormLineDate("", $field["name"], $field["value"], $field["years"], 
									$field["months"], $field["days"], $field["allowblanks"], 0, $field["defyear"]);
							break;
					case "TEXTAREA": $this->fields[] = new TextArea("", $field["name"], $field["value"], $field["css"]);
							break;
					case "LABEL": $this->fields[] = new FormLabel($field["label"], $field["css"]);
							break;
					case "CHECKBOX": $this->fields[] = new CheckBoxLine("", $field["name"], $field["value"], 
												$field["checked"], $field["css"], $field["js"]);
							break;
					case "SUBMIT": $this->fields[] = new SubmitLine($field["name"], $field["text"], $field["css"], $field["js"]);
							break;
					case "PASSWORD": $this->fields[] = new PasswordInput("", $field["name"], $field["css"], $field["maxlength"], 0, $field["js"]);
							break;
					case "RAW": $this->fields[] = new FormRaw($field["text"]);
							break;
				}
			}
		}
	} // end of fn __construct (constructor)

	function OutputField()
	{	foreach ($this->fields as $field)
		{	$field->OutputField();
		}
	} // end of fn OutputField

} // end of class defn MultiInput

class FormLineSelect extends FormLine
{	var $options = array();
	var $allowblank = false;

	function __construct($label = "", $name = "", $def_value = "", $css_class = "", $options = array(), 
							$allowblanks = false, $reqfield = 0, $js = "")
	{	parent::__construct($label, $name, $def_value, $css_class, 0, $reqfield, $js);
		if (is_array($options)) $this->options = $options;
		$this->allowblanks = $allowblanks;
	} // end of fn __construct (constructor)

	function OutputField()
	{	echo "<select name='", $this->name, "' id='", $this->name, "'", 
					$this->css_class ? " class='$this->css_class'" : "", 
					$this->js ? " $this->js" : "", 
					">\n";
		if ($this->allowblanks)
		{	echo "<option value=''", !$this->def_value ? " selected='selected'" : "", "></option>\n";
		}
		foreach ($this->options as $option=>$label)
		{	echo "<option value='", 
						htmlentities(stripslashes($option), ENT_QUOTES, "utf-8", false), 
					//	htmlentities(stripslashes($option), ENT_QUOTES, "ISO-8859-15", false), 
						"'", $option == $this->def_value ? " selected='selected'" : "", ">", 
						htmlentities(stripslashes($label), ENT_QUOTES, "utf-8", false), 
					//	htmlentities(stripslashes($label), ENT_QUOTES, "ISO-8859-15", false), 
						"</option>\n";
		}
		echo "</select>\n";
	} // end of fn OutputField

} // end of class defn FormLineSelect

class FormSelectWithGroups extends FormLineSelect
{	
	function __construct($label = "", $name = "", $def_value = "", $css_class = "", $options = array(), 
							$allowblanks = false, $reqfield = 0, $js = "")
	{	parent::__construct($label, $name, $def_value, $css_class, $options, $allowblanks, $reqfield, $js);
	} // end of fn __construct (constructor)

	function OutputField()
	{	echo "<select name='", $this->name, "'", 
					$this->css_class ? " class='$this->css_class'" : "", 
					$this->js ? " $this->js" : "", 
					">\n";
		if ($this->allowblanks)
		{	echo "<option value=''", !$this->def_value ? " selected='selected'" : "", "></option>\n";
		}
		foreach ($this->options as $defval=>$option)
		{	if (is_array($option))
			{	if ($option["group"])
				{	if ($optgrp)
					{	echo "</optgroup>\n";
					}
					$optgrp = true;
					echo "<optgroup label='", htmlentities($option["desc"]), "'>\n";
				} else
				{	echo "<option value='", htmlentities(stripslashes($option["value"]), ENT_QUOTES, "ISO-8859-15", false), "'", 
							$option["value"] == $this->def_value ? " selected='selected'" : "", ">", 
							htmlentities(stripslashes($option["desc"]), ENT_QUOTES, "ISO-8859-15", false), "</option>\n";
				}
			} else
			{	// assume not a group
				echo "<option value='", $defval, "'", $defval == $this->def_value ? " selected='selected'" : "", 
					">", $option, "</option>\n";
			}
		}
		if ($optgrp)
		{	echo "</optgroup>\n";
		}
		echo "</select>\n";
	} // end of fn OutputField

} // end of class defn FormSelectWithGroups

class SelectPlusOther extends FormLineSelect
{	
	function __construct($label = "", $name = "", $def_value = "", $css_class = "", $options = array(), $reqfield = 0, $js = "")
	{	parent::__construct($label, $name, $def_value, $css_class, $options, true, $reqfield, $js);
		$this->options["**"] = "other ...";
	} // end of fn __construct (constructor)

	function OutputField()
	{	if ($this->def_value)
		{	if (!$this->options[$this->def_value])
			{	$othervalue = $this->def_value;
				$this->def_value = "**";
			}
		}
		parent::OutputField();
		if ($brpos = strpos($this->name, "["))
		{	$othername = substr($this->name, 0, $brpos) . "other" . substr($this->name, $brpos);
		} else
		{	$othername = $this->name . "other";
		}
		echo "<label class='orLabel'>or</label>\n<input type='text' value='", $othervalue, "' name='", $othername, "'", 
					$this->css_class ? " class='{$this->css_class}other'" : "", " />\n";
	} // end of fn OutputField
	
} // end of defn SelectPlusOther

class SubmitLine extends FormLine
{	var $text = "Save";

	function __construct($name = "", $text = "", $css_class = "", $js = "") // constructor
	{	parent::__construct("", $name, $text, $css_class, 0, 0, $js);
		if ($text) $this->text = $text;
	} // end of fn __construct (constructor)

	function OutputField()
	{	
		echo "<input type='submit' value='", $this->text, "'", 
				$this->css_class ? " class='$this->css_class'" : "", 
				$this->js ? " $this->js" : "", 
				$this->name ? " name='$this->name'" : "", " />";
	} // end of fn OutputField

} // end of class defn SubmitLine

class FormLineDate extends FormLine
{	var $defday = "";
	var $defmonth = "";
	var $defyear = "";
	var $allmonths = array(1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"May", 6=>"Jun", 7=>"Jul", 8=>"Aug", 9=>"Sep", 10=>"Oct", 11=>"Nov", 12=>"Dec");
	var $years = array();
	var $months = array(1,2,3,4,5,6,7,8,9,10,11,12);
	var $days = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31);
	var $allowblanks = false;
	var $pickdefyear = 0;
	
	
	function __construct($label = "", $name = "", $defdate = "", $years = array(), $months = array(), 
				$days = array(), $allowblanks = false, $reqfield = 0, $pickdefyear = 0, $js = "") // constructor
	{	if ((int)$defdate)
		{	$this->defday = substr($defdate, 8, 2);
			$this->defmonth = substr($defdate, 5, 2);
			$this->defyear = substr($defdate, 0, 4);
		} else
		{	$defdate = "";
		}
		
		parent::__construct($label, $name, $defdate, "", 0, $reqfield, $js);

		if (count($months) && is_array($months))
		{	$this->months = $months;
		}
		
		$this->pickdefyear = (int)$pickdefyear;
		if (!$pickdefyear)
		{	$this->pickdefyear = date("Y");
		}
		if (count($days) && is_array($days))
		{	$this->days = $days;
		}
		if (count($years) && is_array($years))
		{	$this->years = $years;
		} else // default to 5 years around this year
		{	$ystart = date("Y") + (2000-date("Y"));
			//$yend = $ystart - (2000-date("Y"));
			$yend = 2025;
			for ($y = $ystart; $y <= $yend; $y++)
			{	$this->years[] = $y;
			}
		}
		
		if ($allowblanks || !$defdate)
		{	$this->allowblanks = true;
		}
		
	} // end of fn __construct (constructor)

	function OutputField()
	{	if ($this->days)
		{	echo "<select name='d", $this->name, "'", 
					$this->js ? " $this->js" : "", 

			$this->reqfield ? " required='required'" : "",
					">\n";
					
			if ($this->allowblanks)
			{	echo "<option value=''></option>\n";
			}
			foreach ($this->days as $day)
			{	echo "<option value='", $day, "'", 
						$day == $this->defday ? " selected='selected'" : "", 
						">", $day, "</option>\n";
			}
			echo "</select>\n";
		} else
		{	echo $this->defday ? $this->defday : "", 
				"<input type='hidden' name='d", $this->name, "' value='",  
				$this->defday ? $this->defday : "0", "' />";
		}
		if ($this->months)
		{	echo "<select name='m", $this->name, "'", 
					$this->js ? " $this->js" : "", 
					$this->reqfield ? "required='required'" : "",
					">\n";
			if ($this->allowblanks)
			{	echo "<option value=''></option>\n";
			}
			foreach ($this->months as $month)
			{	echo "<option value='", $month, "'", 
						$month == $this->defmonth ? " selected='selected'" : "", 
						">", $this->allmonths[$month], "</option>\n";
			}
			echo "</select>\n";
		} else
		{	echo "-", $this->defmonth ? $this->defmonth : "", 
				"<input type='hidden' name='m", $this->name, "' value='",  
				$this->defmonth ? $this->defmonth : 0, "' />";
		}
		if ($this->years)
		{	echo "<select name='y", $this->name, "'", 
					$this->js ? " $this->js" : "",
					$this->reqfield ? "required='required'" : "", 
					">\n";
			if ($this->allowblanks)
			{	echo "<option value=''></option>\n";
			}
			foreach ($this->years as $year)
			{	echo "<option value='", $year, "'", 
						$year == $this->defyear ? " selected='selected'" : "", 
						">", $year, "</option>\n";
			}
			echo "</select>\n";
		} else
		{	echo "-", $this->defyear ? $this->defyear : "", 
					"<input type='hidden' name='y", $this->name, "' value='",  
					$this->defyear ? $this->defyear : 0, "' />\n";
		}
		$this->DatePickerLink();
	} // end of fn OutputField

	function DatePickerLink()
	{	echo "<img src='datep2.gif' onclick='displayDatePicker(\"", $this->name, "\", ", (int)$this->pickdefyear, 
					");' alt='' />\n";
	} // end of fn DatePickerLink
	
} // end of class defn FormLineDate

class DOBFormLineDate extends FormLineDate
{	
	function __construct($label = "", $name = "", $defdate = "", $years = array(), $months = array(), 
				$days = array(), $allowblanks = false, $reqfield = 0, $pickdefyear = 0, $js = "") // constructor
	{	parent::__construct($label, $name, $defdate, $years, $months, $days, $allowblanks, $reqfield, $pickdefyear, $js);
	} // end of __construct

	function DatePickerLink()
	{	
	} // end of fn DatePickerLink
	
} // end of class DOBFormLineDate

class MonthYearSelect extends FormLineDate
{	
	function __construct($label = "", $name = "", $defdate = "", $years = array(), $months = array(), 
				$allowblanks = false, $reqfield = 0, $pickdefyear = 0)
	{	parent::__construct($label, $name, $defdate, $years, $months, $days, $allowblanks, $reqfield, $pickdefyear);
		$this->days = array();
	} // end of fn __construct

	function OutputField()
	{	if ($this->months)
		{	echo "<select name='m", $this->name, "'"; 
			if ($this->reqfield) {
				
				echo "required='required'";
				
			}
			echo ">\n";
			if ($this->allowblanks)
			{	echo "<option value=''></option>\n";
			}
			foreach ($this->months as $month)
			{	echo "<option value='", $month, "'", 
						$month == $this->defmonth ? " selected='selected'" : "", 
						">", $this->allmonths[$month], "</option>\n";
			}
			echo "</select>\n";
		} else
		{	echo "-", $this->defmonth ? $this->defmonth : "", 
				"<input type='hidden' name='m", $this->name, "' value='",  
				$this->defmonth ? $this->defmonth : 0, "' />";
		}
		if ($this->years)
		{	echo "<select name='y", $this->name, "'"; 
			if ($this->reqfield) {
				
				echo "required='required'";
				
			}
			echo ">\n";
			if ($this->allowblanks)
			{	echo "<option value=''></option>\n";
			}
			foreach ($this->years as $year)
			{	echo "<option value='", $year, "'", 
						$year == $this->defyear ? " selected='selected'" : "", 
						">", $year, "</option>\n";
			}
			echo "</select>\n";
		} else
		{	echo "-", $this->defyear ? $this->defyear : "", 
					"<input type='hidden' name='y", $this->name, "' value='",  
					$this->defyear ? $this->defyear : 0, "' />\n";
		}
	} // end of fn OutputField
	
} // end of defn MonthYearSelect

class FormLabel
{	var $css_class = "";
	var $label = "&nbsp;";
	var $reqfield = 0;

	function __construct($label = "", $css_class = "", $reqfield = 0) // constructor
	{	$this->css_class = $css_class;
		$this->reqfield = (int)$reqfield;
		if ($label)
		{	$this->label = $label;
		}
	} // end of fn __construct (constructor)

	function OutputField()
	{	echo "<label", $this->css_class ? " class='$this->css_class'" : "", 
				">", $this->label, $this->reqfield ? "<span class='reqlabel'>*</span>" : "", "</label>";
	} // end of fn OutputField

	function Output()
	{	echo "<label", $this->css_class ? " class='$this->css_class'" : "", 
				">", $this->label, $this->reqfield ? "<span class='reqlabel'>*</span>" : "", "</label><br />\n";
	} // end of fn OutputField

} // end of class defn FormLabel

class FormRaw
{	var $rawtext = "";

	function __construct($rawtext = "") // constructor
	{	$this->rawtext = $rawtext;
	} // end of fn __construct (constructor)

	function OutputField()
	{	echo $this->rawtext;
	} // end of fn OutputField

	function Output()
	{	echo $this->rawtext;
	} // end of fn OutputField

} // end of class defn FormRaw
?>