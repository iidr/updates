<?php 
require_once('init.php');

class AskTheExpertFrontPage extends AskTheImamPage
{	
	function __construct()
	{	parent::__construct();
		$this->css[] = 'page.css';
	//	$this->css[] = 'course.css';
		$this->css[] = 'jqModal.css';
		$this->js[] = 'jqModal.js';
		$this->js[] = 'studentcomments.js';
		$this->css[] = 'studentreviews.css';
		$this->css[] = 'asktheimam.css';
		$this->js[] = 'asktheimam.js';
		
 	} // end of fn __construct
	
	function MainBodyContent()
	{	
		echo $this->OutputBanner(), '<div id="sidebar" class="col courselist_sidebar"><div id="askTopicArchive">', $this->DisplayArchiveList($this->archive_perpage), '</div>', $this->SidebarCategories(), $this->DisplayInstructorsList(), 
			//$this->DisplayComingUpList(), 
			$this->SidebarSubmitQuestion(), '</div><div class="col3-wrapper-with-sidebar courselist_main">', $this->HeaderTextBlock(), '<div id="courses_container">', ($this->instructor->id || $this->category->id) ? $this->FilteredQuestions() : $this->DisplayTopicDetails(), '</div><div class="clear"></div></div><div class="clear"></div>';

	} // end of fn MainBodyContent
	
	public function HeaderTextBlock()
	{	if (!$this->category->id && !$this->instructor->id && !$_GET['topic'] && $this->page->details['pagetext'])
		{	ob_start();
			echo '<div id="askExpertHeader">', $this->page->details['socialbar'] ? $this->GetSocialLinks(4) : '', stripslashes($this->page->details['pagetext']), '</div>';
			return ob_get_clean();
		}
	} // end of fn HeaderTextBlock
	
	public function SidebarSubmitQuestion()
	{	ob_start();
		echo '<div id="sidebar-submitq-wrapper"><h3>Submit Your Question</h3><div id="sidebar-submitq-content"><p>',$this->GetParameter("ask_expert_txt"),'</p>';
		if ($comingup = $this->GetComingUp())
		{	foreach ($comingup as $topic_row)
			{	$topic = new AskImamTopic($topic_row);
				echo '<p>Our next scheduled theme is "', $this->InputSafeString($topic->details['title']), '"';
				if ($topic->instructors)
				{	$inst_links = array();
					foreach ($topic->instructors as $inst_row)
					{	$inst = new Instructor($inst_row);
						$instnames[] = $this->InputSafeString($inst->GetFullName());
						$inst_links[] = '<a href="' . $this->InstructorLink($inst_row) . '">' . $this->InputSafeString($inst->GetFullName()) . '</a>';
					}
					echo ' with ', implode(', ', $inst_links);
				}
			//	if ($inst = $topic->InstructorsListDisplay())
			//	{	echo ' with ', $inst;
			//	}
				echo '</p>';
				break;
			}
		}
		echo '<a id="submitqButton" onclick="OpenQuestionSubmitter();">Ask now</a><div class="clear"></div></div></div><script type="text/javascript">$().ready(function(){$("body").append($(".jqmWindow")); $("#ask_question_modal_popup").jqm();})</script><div id="ask_question_modal_popup" class="jqmWindow"><a href="#" class="jqmClose submit">X</a><div id="ask_question_modal_inner"></div></div>';
		return ob_get_clean();
	} // end of fn SidebarSubmitQuestion
	
	public function SidebarCategories()
	{	ob_start();
		if ($cats = $this->GetCategoriesList())
		{	echo $this->SubMenuToggle('Topics', $this->DisplayCatList($cats, $cats, 0), 'cat', $this->category->id); // changed from Categories to Topics, Tim 11/10/13
		}
		return ob_get_clean();
	} // end of fn SidebarCategories
	
	private function DisplayTopicDetails()
	{	ob_start();
		if ($this->topic->id)
		{	echo '<div id="topicHeader">';/*<h3>Topic: ', $this->InputSafeString($this->topic->details['title']), '</h3>';
			if ($this->topic->cats)
			{	echo '<p>Categor', count($this->topic->cats) > 1 ? 'ies' : 'y', ': ', $this->DisplayCatsLinks($this->topic->cats), '</p>';
			}*/
			echo '<h3>Latest Questions Answered</h3>';
		//	if ($this->topic->instructors)
		//	{	echo '<p>Questions answered by: ', $this->DisplayInstLinks($this->topic->instructors), '</p>';
		//	}
			echo '</div>', $this->ListQuestionsContainer($this->topic->questions, 0, 0);
			//$this->VarDump($this->topic->details);
		}
		return ob_get_clean();
	} // end of fn DisplayTopicDetails
	
	private function FilteredQuestions()
	{	ob_start();
		echo '<div id="filterHeader"><h3>Showing questions for ... ';
		if ($this->instructor->id)
		{	echo $this->InputSafeString($this->instructor->GetFullName());
		} else
		{	if ($this->category->id)
			{	echo $this->InputSafeString($this->category->details['ctitle']);
			}
		}
		echo '</h3>';
		if ($questions = $this->GetFilteredQuestions())
		{	echo $this->ListQuestionsContainer($questions, $_GET['page'], $this->questions_perpage);
		} else
		{	echo '<h4>no questions found<h4>';
		}
		echo '</div>';
		return ob_get_clean();
	} // end of fn FilteredQuestions
	
	public function GetFilteredQuestions()
	{	$questions = array();
		if ($this->instructor->id)
		{	
			$sql = 'SELECT askimamquestions.qid FROM askimamquestions, askimamtopics, askimaminstructors WHERE askimamquestions.askid=askimamtopics.askid AND askimaminstructors.askid=askimamtopics.askid AND askimamquestions.live=1 AND askimamtopics.live=1 AND askimamtopics.startdate<="' . $this->datefn->SQLDate() . '" AND askimaminstructors.inid=' . $this->instructor->id . ' ORDER BY askimamtopics.startdate DESC, askimamquestions.listorder';
		}
		if ($this->category->id)
		{	
			$sql = 'SELECT askimamquestions.qid FROM askimamquestions, askimamtopics, askimamtocats WHERE askimamquestions.askid=askimamtopics.askid AND askimamtocats.askid=askimamtopics.askid AND askimamquestions.live=1 AND askimamtopics.live=1 AND askimamtopics.startdate<="' . $this->datefn->SQLDate() . '" AND askimamtocats.catid=' . $this->category->id . ' ORDER BY askimamtopics.startdate DESC, askimamquestions.listorder';
		}
		if ($sql)
		{	if ($result = $this->db->Query($sql))
			{	while ($row = $this->db->FetchArray($result))
				{	$questions[$row['qid']] = $row['qid'];
				}
			}
		}
		
		return $questions;
	} // end of fn GetFilteredQuestions
		
} // end of defn AskTheExpertFrontPage

$page = new AskTheExpertFrontPage();
$page->Page();
?>