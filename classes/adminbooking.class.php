<?php
class AdminBooking extends Booking
{		
	function __construct($id = 0)
	{	parent::__construct($id);
	} // fn __construct
	
	function AssignCourse($id = 0)
	{	return new AdminCourse($id);
	} // end of fn AssignUser
	
	function AssignUser($id = 0)
	{	return new AdminStudent($id);
	} // end of fn AssignUser
	
	function BookingListPaidUpContents($can_edit = true)
	{	ob_start();
		$topay = $this->Price();
		if ($topay = $this->Price())
		{	$paid = $this->AmountPaid();
			
			if (($balance = $topay - $paid) > 0)
			{	echo "<a onclick='AdminBookPaid(", $this->id, "); CourseHeaderRefresh(", $this->course->id, ");'>", $paid ? "pay balance" : "pay in full", " (", number_format($balance, 2), ")</a>";
			} else
			{	echo "paid in full";
			}
		} else
		{	echo "no charge";
		}
		return ob_get_clean();
	} // end of fn BookingListPaidUpContents
	
	function BookingListAttendContents($dates = array())
	{	ob_start();
		if ($this->course->CanAttend())
		{	foreach ($dates as $date)
			{	echo "<div id='dateatt_", $this->id, "_", $date, "'>", $this->AttendanceDateLink($date), "</div>";
			}
		}
		return ob_get_clean();
	} // end of fn BookingListAttendContents
	
	function BookingInfo()
	{	$cats = new StaffCategories();
		$catoptions = $cats->SelectOptions();
		$adminuser = $this->GetAdminUser();
		$can_members = $adminuser->CanUserAccess("members");
		
		echo "<p>", $this->DisplayHistoryLink("bookings", $this->id), "</p><table class='adminDetailsHeader'>\n<tr><td class='label'>Booking made by</td><td>", $can_members ? "<a href='member.php?id={$this->user->id}'>" : "", $this->InputSafeString($this->user->details["firstname"] . " " . $this->user->details["surname"]), $can_members ? "</a>" : "", " (email: <a href='mailto:", $this->user->details["username"], "'>", $this->user->details["username"], "</a>)</td></tr>\n<tr><td class='label'>Booked on</td><td>", date("d/m/y @ H:i", strtotime($this->details["enroldate"]));
		if ($this->details["oldbookid"])
		{	echo " (imported booking, id:", $this->details["oldbookid"], ")";
		} else
		{	if ($this->details["admincreated"])
			{	echo " (booking created by AlKauthar admin)";
			}
		}
		echo "</td></tr>\n<tr><td class='label'>Course</td><td>", $this->InputSafeString($this->course->content->details["ctitle"]),"&nbsp;<span class='prodItemCode'>Code: ", $this->course->ProductID(), "</span><br />", $this->CityString($this->course->details["city"]), "<br />", date("d/m/y @ H:i", strtotime($this->course->details["starttime"])), " to ", date("d/m/y @ H:i", strtotime($this->course->details["endtime"])), "</td></tr>\n<tr><td class='label'>Price to Pay</td><td>", $this->Currency("cursymbol"), $topay = $this->Price();
		
		if ($topay != $this->details["price"])
		{	
			echo " with discount (full price: ", $this->Currency("cursymbol"), number_format($this->details["price"], 2), ")";
		}
		echo "</td></tr>\n<tr><td class='label'>Amount paid</td><td>";
		if ($paid = $this->AmountPaid())
		{	echo $this->Currency("cursymbol"), $paid;
			if ($paid >= $topay)
			{	echo " - paid in full";
			} else
			{	echo " - balance due ", $this->Currency("cursymbol"), $topay - $paid;
			}
		} else
		{	echo "----";
		}
		echo "</td></tr>\n<tr><td class='label'>Booking type</td><td>", $this->details["staffcat"] ? $this->InputSafeString($catoptions[$this->details["staffcat"]]) : "---", "</td></tr>\n";
		if ($this->details["scholarship"])
		{	echo "<tr><td class='label'>Scholarship used</td><td><a href='userscholarship.php?id=", $this->details["scholarship"], "'>view scholarship</a></td></tr>\n";
		}
		echo "</table>\n";
	} // end of fn BookingInfo
	
	function AmendForm()
	{	
		$form = new Form($_SERVER["SCRIPT_NAME"] . "?id=" . $this->id, "");
		$this->AddBackLinkHiddenField($form);
		if (!($this->details["paypalid"] || (int)$this->details["manualpaid"]))
		{	$form->AddSelect("Change payment method", "pmtmethod", $this->details["pmtmethod"], "", $this->PmtOptions());
			$form->AddTextInput("Price to pay", "price_topay", number_format($this->details["price_topay"], 2), "short", 10);
		}
		$cats = new StaffCategories();
		$form->AddSelect("Booking type (if not student)", "staffcat", $this->details["staffcat"], "", $cats->SelectOptions(), 1, 0);
		$form->AddTextArea("Notes", "adminnotes", $this->InputSafeString($this->details["adminnotes"]), "", 0, 0, $rows = 3, $cols = 40);
		
		$form->AddSubmitButton("", "Save Changes", "submit");
		echo "<p>", $this->DisplayHistoryLink("bookings", $this->id), "</p>";
		if ($this->CanDelete())
		{	
			echo "<p><a href='", $_SERVER["SCRIPT_NAME"], "?id=", $this->id, "&delete=1", $_GET["delete"] ? "&confirm=1" : "", "'>", $_GET["delete"] ? "please confirm you really want to " : "", "delete this booking</a></p>\n";
		}
		$form->Output();
	} // end of fn AmendForm
	
	function AttendanceBlock()
	{	ob_start();
		if ($this->course->CanAttend())
		{	echo "<div id='booking_attendance'><h4>Attendance at course ...</h4><ul>";
			foreach ($this->course->dates as $stamp=>$date)
			{	echo "<li id='dateatt_", $this->id, "_", $date, "'>", $this->AttendanceDateLink($date), "</li>";
			}
			echo "</ul>\n</div>\n";
		}
		return ob_get_clean();
	} // end of fn AttendanceBlock
	
	function AttendanceDateLink($date  = "")
	{	ob_start();
		echo "<p class='booking_attendance_date'><span class='bad_date'><span class='bad_date_date'>", date("d/m/Y", strtotime($date)), "</span> <span class='bad_date_day'>", date("D", strtotime($date)), "</span></span><span class='bad_click'><input type='checkbox' onclick='AdminBookAttendDate(", $this->id, ", \"", $date, "\", ", $this->attendance[$date] ? "0" : "1", ");CourseHeaderRefresh(", $this->course->id, ");' ", $this->attendance[$date] ? "checked='checked' " : "", "/></span><br class='clear' /></p>";
		return ob_get_clean();
	} // end of fn AttendanceDateLink

	function RecordAttendance($date = "", $attend = 0)
	{	
		if ($date == $this->datefn->SQLDate(strtotime($date)))
		{	
			if ($attend)
			{	if (!$this->attendance[$date])
				{	$sql = "INSERT INTO attendance SET bookid={$this->id}, attdate='$date'";
				}
			} else
			{	if ($this->attendance[$date])
				{	$sql = "DELETE FROM attendance WHERE bookid={$this->id} AND attdate='$date'";
				}
			}
			
			if ($sql)
			{	if ($result = $this->db->Query($sql))
				{	if ($this->db->AffectedRows())
					{	$this->GetAttendance();
						if ($attend)
						{	$this->RecordAdminAction(array("tablename"=>"bookings", "tableid"=>$this->id, "area"=>"bookings", "action"=>"attendance added", "actionto"=>$date, "actiontype"=>"date"));
						} else
						{	$this->RecordAdminAction(array("tablename"=>"bookings", "tableid"=>$this->id, "area"=>"bookings", "action"=>"attendance removed", "actionfrom"=>$date, "actiontype"=>"date"));
						}
						$this->SetExpectedFlag();
						return true;
					}
				}
			}
		}
		
	} // end of fn RecordAttendance
	
	function Amend($data = array())
	{	$fail = array();
		$success = array();
		$fields = array();
		$admin_actions = array();
		
		if (isset($data["attended"]))
		{	$fields[] = "attended=" . ($data["attended"] ? "1" : "0");
		}
		
		$pmtmethod = $this->details["pmtmethod"];
		if (isset($data["pmtmethod"]))
		{	if ($pmtoptions = $this->PmtOptions())
			{	if ($pmtoptions[$data["pmtmethod"]])
				{	$fields[] = "pmtmethod='{$data["pmtmethod"]}'";
					$pmtmethod = $data["pmtmethod"];
					if ($this->id && ($data["pmtmethod"] != $this->details["pmtmethod"]))
					{	$admin_actions[] = array("action"=>"Payment method", "actionfrom"=>$this->details["pmtmethod"], "actionto"=>$pmtmethod, "actiontype"=>"link", "linkmask"=>"paymentoption.php?id={linkid}");
					}
				} else
				{	$fail[] = "invalid payment method";
				}
			}
		}
		
		if (isset($data["discount_code"]))
		{	$discount_id = 0;
			if ($data["discount_code"])
			{	$discount = new Discount();
				$discount->GetFromCode($data["discount_code"]);
				if ($discount_id = $discount->id)
				{	if ($discount_id != $this->details["discount"])
					{	// only check validity if changed or added
						if (!$discount->StillValid())
						{	$fail[] = "discount code \"" . $this->InputSafeString($data["discount_code"]) . "\" no longer valid";
							$discount_id = $this->details["discount"];
						}
						if (!$discount->AppliesToCourse($this->details["course"]))
						{	$fail[] = "discount code \"" . $this->InputSafeString($data["discount_code"]) . "\" does not apply to this course";
							$discount_id = $this->details["discount"];
						}
					}
				} else
				{	$fail[] = "invalid discount code \"" . $this->InputSafeString($data["discount_code"]) . "\" applied";
					$discount_id = $this->details["discount"];
				}
			}
			
			$fields[] = "discount=$discount_id";
			if ($this->id && ($discount_id != $this->details["discount"]))
			{	$admin_actions[] = array("action"=>"Discount", "actionfrom"=>$this->details["discount"], "actionto"=>$discount_id, "actiontype"=>"link", "linkmask"=>"discountedit.php?id={linkid}");
			}
		}
		
		$fields[] = "adminnotes='" . $this->SQLSafe($data["adminnotes"]) . "'";
		if ($this->id && ($data["adminnotes"] != $this->details["adminnotes"]))
		{	$admin_actions[] = array("action"=>"Description text", "actionfrom"=>$this->details["adminnotes"], "actionto"=>$data["adminnotes"]);
		}
		
		$price_topay = round($data["price_topay"], 2);
		$fields[] = "price_topay=" . $price_topay;
		if ($this->id && ($price_topay != $this->details["price_topay"]))
		{	$admin_actions[] = array("action"=>"Price to pay", "actionfrom"=>$this->details["price_topay"], "actionto"=>$price_topay);
		}
		
		if ($price_topay > $this->details["price"])
		{	$fields[] = "price=" . $price_topay;
			if ($this->id && ($price_topay != $this->details["price"]))
			{	$admin_actions[] = array("action"=>"Price", "actionfrom"=>$this->details["price"], "actionto"=>$price_topay);
			}
		}
		
		if (isset($data["staffcat"]))
		{	// i.e. only if admin form
			$staffcat = (int)$data["staffcat"];
			$fields[] = "staffcat=" . $staffcat;
			if ($this->id && ($staffcat != $this->details["staffcat"]))
			{	$admin_actions[] = array("action"=>"Staff category", "actionfrom"=>$this->details["staffcat"], "actionto"=>$staffcat, "actiontype"=>"link", "linkmask"=>"staffcatedit.php?id={linkid}");
			}
		}
		
		if ($set = implode(", ", $fields))
		{	$sql = "UPDATE bookings SET $set WHERE bookid=" . (int)$this->id;
			if ($this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$base_parameters = array("tablename"=>"bookings", "tableid"=>$this->id, "area"=>"bookings");
					if ($admin_actions)
					{	foreach ($admin_actions as $admin_action)
						{	$this->RecordAdminAction(array_merge($base_parameters, $admin_action));
						}
					}
					$success[] = "changes saved";
					$this->Get($this->id);
					$this->SetExpectedFlag();
				}
			}
		}
		
		return array("failmessage"=>implode(", ", $fail), "successmessage"=>implode(", ", $success));
		
	} // end of fn Amend
	
	function PaymentsList()
	{	ob_start();
		if ($this->id)
		{
			echo "<h4>Payments received</h4>\n<table>\n<tr class='newlink'><th colspan='4'><a href='bookingpmt.php?bookid=", $this->id, "'>Add new payment</a></th></tr>\n<tr><th>Date / time</th><th>Pmt type</th><th>Amount</th><th>Actions</th></tr>\n";
			foreach ($this->payments as $pmtrow)
			{	$pmt = new AdminBookingPmt($pmtrow);
				echo "<tr>\n<td>", date("d/m/y @H:i", strtotime($pmt->details["paydate"])), "</td>\n<td>", $pmt->details["pptransid"] ? "PayPal (Tx ID: {$pmt->details["pptransid"]})" : "Manual", "</td>\n<td>", $this->Currency("cursymbol"), number_format($pmt->details["amount"], 2), "</td>\n<td><a href='bookingpmt.php?id=", $pmt->id, "'>edit</a>";
				if ($pmt->CanDelete())
				{	echo "&nbsp;|&nbsp;<a href='bookingpmt.php?id=", $pmt->id, "&delete=1'>delete</a>";
				}
				echo "</td>\n</tr>\n";
			}
			echo "</table>\n";
		}
		return ob_get_clean();
	} // end of fn PaymentsList
	
	function CanDelete()
	{	return  $this->CanAdminUserDelete() && !$this->attendance && (!$this->payments || $this->CanAdminUser("full-booking-deletions"));
	} // end of fn CanDelete
	
	function Delete()
	{	if ($this->CanDelete())
		{	$sql = "DELETE FROM bookings WHERE bookid=" . $this->id;
			if ($result = $this->db->Query($sql))
			{	if ($this->db->AffectedRows())
				{	$linkmask = "<a href='member.php?id={$this->user->id}' target='_blank'>" . $this->InputSafeString($this->user->details["firstname"] . " " . $this->user->details["surname"]) . "</a>";
					$this->RecordAdminAction(array("tablename"=>"bookings", "tableid"=>$this->id, "area"=>"bookings", "action"=>"deleted", "actiontype"=>"deleted", "deleteparentid"=>$this->details["course"], "deleteparenttable"=>"courses", "linkmask"=>$linkmask));
					// delete payments if any
					foreach ($this->payments as $pmtrow)
					{	$pmt = new AdminBookingPmt($pmtrow);
						$pmt->Delete();
					}
					// attendance if any
					foreach ($this->attendance as $attdate)
					{	$this->RecordAttendance($attdate, 0);
					}
					// free scholarship if any
					$sql = "UPDATE scholarships SET bookid=0 WHERE bookid={$this->id}";
					$this->db->Query($sql);
					$this->Reset();
					return true;
				}
			}
		}
	} // end of fn Delete

/*	function Delete()
	{	$bookid = $this->id;
		$courseid = $this->details["course"];
		$linkmask = "<a href='member.php?id={$this->user->id}' target='_blank'>" . $this->InputSafeString($this->user->details["firstname"] . " " . $this->user->details["surname"]) . "</a>";
		if (parent::Delete())
		{	$this->RecordAdminAction(array("tablename"=>"bookings", "tableid"=>$bookid, "area"=>"bookings", "action"=>"deleted", "actiontype"=>"deleted", "deleteparentid"=>$courseid, "deleteparenttable"=>"courses", "linkmask"=>$linkmask));
			return true;
		}
	} // end of fn Delete
	*/
} // end of defn AdminBooking
?>