<?php
require_once INCLUDE_ROOT."Model/RunUnit.php";

class TimeBranch extends RunUnit {
	public $errors = array();
	public $id = null;
	public $session = null;
	public $unit = null;
	private $if_true = null;
	private $if_false = null;
	private $relative_to = null;
	private $wait_minutes = null;
	private $wait_until_time = null;
	private $wait_until_date = null;
	public $ended = false;
	
	public function __construct($fdb, $session = null, $unit = null) 
	{
		parent::__construct($fdb,$session,$unit);

		if($this->id):
			$data = $this->dbh->prepare("SELECT * FROM `survey_time_branches` WHERE id = :id LIMIT 1");
			$data->bindParam(":id",$this->id);
			$data->execute() or die(print_r($data->errorInfo(), true));
			$vars = $data->fetch(PDO::FETCH_ASSOC);
			
			if($vars):
				array_walk($vars,"emptyNull");
				$this->if_true = $vars['if_true'];
				$this->if_false = $vars['if_false'];
				$this->wait_until_time = $vars['wait_until_time'];
				$this->wait_until_date = $vars['wait_until_date'];
				$this->wait_minutes = $vars['wait_minutes'];
				$this->relative_to = $vars['relative_to'];
		
				$this->valid = true;
			endif;
		endif;
	}
	public function create($options)
	{
		$this->dbh->beginTransaction();
		if(!$this->id)
			$this->id = parent::create('TimeBranch');
		else
			$this->modify($this->id);
		
		if(isset($options['if_true']))
		{
			array_walk($options,"emptyNull");
			$this->if_true = $options['if_true'];
			$this->if_false = $options['if_false'];
			$this->wait_until_time = $options['wait_until_time'];
			$this->wait_until_date = $options['wait_until_date'];
			$this->wait_minutes = $options['wait_minutes'];
			$this->relative_to = $options['relative_to'];
		}
		
		$create = $this->dbh->prepare("INSERT INTO `survey_time_branches` 
			(`id`, if_true, if_false, `wait_until_time`, `wait_until_date` , `wait_minutes`, `relative_to`)
			VALUES (:id, :if_true, :if_false, :wait_until_time, :wait_until_date, :wait_minutes, :relative_to)
		ON DUPLICATE KEY UPDATE
			`if_true` = :if_true2, 
			`if_false` = :if_false2,
			`wait_until_time` = :wait_until_time2, 
			`wait_until_date` = :wait_until_date2, 
			`wait_minutes` = :wait_minutes2, 
			`relative_to` = :relative_to2
			
		;");
		$create->bindParam(':id',$this->id);
		$create->bindParam(':if_true',$this->if_true);
		$create->bindParam(':if_false',$this->if_false);
		$create->bindParam(':wait_until_time',$this->wait_until_time);
		$create->bindParam(':wait_until_date',$this->wait_until_date);
		$create->bindParam(':wait_minutes',$this->wait_minutes);
		$create->bindParam(':relative_to',$this->relative_to);
		
		$create->bindParam(':if_true2',$this->if_true);
		$create->bindParam(':if_false2',$this->if_false);
		$create->bindParam(':wait_until_time2',$this->wait_until_time);
		$create->bindParam(':wait_until_date2',$this->wait_until_date);
		$create->bindParam(':wait_minutes2',$this->wait_minutes);
		$create->bindParam(':relative_to2',$this->relative_to);
		
		$create->execute() or die(print_r($create->errorInfo(), true));
		$this->dbh->commit();
		$this->valid = true;
		
		return true;
	}
	public function displayForRun($prepend = '')
	{
		$dialog = '<p>
				<label class="inline hastooltip" title="Leave empty so that this does not apply">until time: 
				<input type="time" placeholder="daybreak" name="wait_until_time" value="'.$this->wait_until_time.'">
				</label> <strong>and</strong>
				
				</p>
				<p>
				<label class="inline hastooltip" title="Leave empty so that this does not apply">until date: 
				<input type="date" placeholder="the next day" name="wait_until_date" value="'.$this->wait_until_date.'">
				</label> <strong>and</strong>
				
				</p>
				<p>
				<span class="input-append">
				<input type="number" class="span2" placeholder="" name="wait_minutes" value="'.$this->wait_minutes.'"><button class="btn from_days hastooltip" title="Enter a number of days and press this button to convert them to minutes (*60*24)"><small>convert days</small></button>
				</span>
				 minutes <label class="inline">relative to 
					<input type="text" class="span2" placeholder="Survey.DateField" name="relative_to" value="'.$this->relative_to.'">
					</label
				</p> 
			';
		$dialog .= '
			<div class="row">
				<p class="span2"><label>…if there <strong>still is time</strong> <br><i class="icon-hand-right"></i> <input type="number" class="span1" name="if_false" max="32000" min="-32000" step="1" value="'.$this->if_false.'"></p>
				<p class="span1"><i class="icon-fast-forward icon-flip-vertical icon-3x icon-muted"></i></p>
				<p class="span2"><label>…if the time is <strong>up</strong> <br><i class="icon-hand-right"></i> <input type="number" class="span1" name="if_true" max="32000" min="-32000" step="1" value="'.$this->if_true.'"></p>
			</div>
			';
			$dialog .= '<p class="btn-group"><a class="btn unit_save" href="ajax_save_run_unit?type=TimeBranch">Save.</a>
			<a class="btn unit_test" href="ajax_test_unit?type=TimeBranch">Test</a></p>';

		$dialog = $prepend . $dialog;
		
		return parent::runDialog($dialog,'icon-fast-forward');
	}
	public function removeFromRun($run_id)
	{
		return $this->delete();
	}
	public function test()
	{
		if($this->relative_to=== null OR trim($this->relative_to)=='')
		{
			$this->relative_to = 'survey_unit_sessions$created';
		}
		
		
		$q = "SELECT `survey_run_sessions`.session,`survey_run_sessions`.id,`survey_run_sessions`.position FROM `survey_run_sessions`

		WHERE 
			`survey_run_sessions`.run_id = :run_id

		ORDER BY `survey_run_sessions`.position DESC,RAND()

		LIMIT 20";
		$get_sessions = $this->dbh->prepare($q); // should use readonly
		$get_sessions->bindParam(':run_id',$this->run_id);

		$get_sessions->execute() or die(print_r($get_sessions->errorInfo(), true));
		if($get_sessions->rowCount()>=1):
			$results = array();
			while($temp = $get_sessions->fetch())
				$results[] = $temp;
		else:
			echo 'No data to compare to yet.';
			return false;
		endif;
		
		$openCPU = $this->makeOpenCPU();
		$this->run_session_id = current($results)['id'];

		$openCPU->addUserData($this->getUserDataInRun(
			$this->dataNeeded($this->dbh,$this->relative_to)
		));

		echo $openCPU->evaluateAdmin($this->relative_to);
		
		echo '<table class="table table-striped">
				<thead><tr>
					<th>Code</th>
					<th>Relative to</th>
					<th>Test</th>
				</tr></thead>
				<tbody>"';
				
		foreach($results AS $row):
			$openCPU = $this->makeOpenCPU();
			$this->run_session_id = $row['id'];

			$openCPU->addUserData($this->getUserDataInRun(
				$this->dataNeeded($this->dbh,$this->relative_to)
			));
			
			$relative_to = $openCPU->evaluate($this->relative_to);
	if($relative_to !== null):

			$conditions = array();
			if($this->wait_minutes AND $this->wait_minutes!='')
				$conditions['minute'] = "DATE_ADD(:relative_to, INTERVAL :wait_minutes MINUTE) <= NOW()";
			if($this->wait_until_date AND $this->wait_until_date != '0000-00-00') 
				$conditions['date'] = "CURDATE() >= :wait_date";
			if($this->wait_until_time AND $this->wait_until_time != '00:00:00')
				$conditions['time'] = "CURTIME() >= :wait_time";

			if(isset($conditions['time']) AND !isset($conditions['date']) AND !isset($conditions['minute']))
				$conditions['date'] = "DATE_ADD(:relative_to, INTERVAL 1 DAY) >= CURDATE()";
		
			if(!empty($conditions)):
				$condition = implode($conditions," AND ");
		
				$order = str_replace(array(':wait_minutes',':wait_date',':wait_time',':relative_to'),array(':wait_minutes2',':wait_date2',':wait_time2',':relative_to2'),$condition);
		
	$q = "SELECT DISTINCT ( {$condition} ) AS test,`survey_run_sessions`.session FROM `survey_run_sessions`

		left join `survey_unit_sessions`
			on `survey_run_sessions`.id = `survey_unit_sessions`.run_session_id

	WHERE 
		`survey_run_sessions`.id = :run_session_id AND
		:relative_to3 IS NOT NULL

	ORDER BY IF(ISNULL($order),1,0), RAND()

	LIMIT 1";
		
				$evaluate = $this->dbh->prepare($q); // should use readonly
				if(isset($conditions['minute'])):
					$evaluate->bindParam(':wait_minutes',$this->wait_minutes);
					$evaluate->bindParam(':wait_minutes2',$this->wait_minutes);
				endif;
				if(isset($conditions['date'])): 
					$evaluate->bindParam(':wait_date',$this->wait_until_date);
					$evaluate->bindParam(':wait_date2',$this->wait_until_date);
				endif;
				if(isset($conditions['time'])): 
					$evaluate->bindParam(':wait_time',$this->wait_until_time);
					$evaluate->bindParam(':wait_time2',$this->wait_until_time);
				endif;
				$evaluate->bindParam(':relative_to',$relative_to);
				$evaluate->bindParam(':relative_to2',$relative_to);
				$evaluate->bindParam(':relative_to3',$relative_to);
				$evaluate->bindParam(':run_session_id',$this->run_session_id);

				$evaluate->execute() or die(print_r($evaluate->errorInfo(), true));
				if($evaluate->rowCount()===1):
					$temp = $evaluate->fetch();
					$result = $temp['test'];
				endif;
			else:
				$result = true;
			endif;
	else:
		$result = null;
	endif;
			
			echo "<tr>
					<td style='word-wrap:break-word;max-width:150px'><small>".$row['session']." ({$row['position']})</small></td>
					<td><small>".stringBool($relative_to )."</small></td>
					<td>".stringBool($result )."</td>
				</tr>";

		endforeach;
		echo '</tbody></table>';
	}
	public function exec()
	{
		if($this->relative_to=== null OR trim($this->relative_to)=='')
		{
			$this->relative_to = 'survey_unit_sessions$created';
		}
		$openCPU = $this->makeOpenCPU();

		$openCPU->addUserData($this->getUserDataInRun(
			$this->dataNeeded($this->dbh,$this->relative_to)
		));
		
		$relative_to = $openCPU->evaluate($this->relative_to);

		$conditions = array();
		if($this->wait_minutes AND $this->wait_minutes!='')
			$conditions['minute'] = "DATE_ADD(:relative_to, INTERVAL :wait_minutes MINUTE) <= NOW()";
		if($this->wait_until_date AND $this->wait_until_date != '0000-00-00') 
			$conditions['date'] = "CURDATE() >= :wait_date";
		if($this->wait_until_time AND $this->wait_until_time != '00:00:00')
			$conditions['time'] = "CURTIME() >= :wait_time";

		if(isset($conditions['time']) AND !isset($conditions['date']) AND !isset($conditions['minute']))
			$conditions['date'] = "DATE_ADD(:relative_to, INTERVAL 1 DAY) >= CURDATE()";
		
		if(!empty($conditions)):
			$condition = implode($conditions," AND ");

		$order = str_replace(array(':wait_minutes',':wait_date',':wait_time',':relative_to'),array(':wait_minutes2',':wait_date2',':wait_time2',':relative_to2'),$condition);

	$q = "SELECT ( {$condition} ) AS test FROM `survey_run_sessions`
		left join `survey_unit_sessions`
			on `survey_run_sessions`.id = `survey_unit_sessions`.run_session_id
	
	WHERE 
	`survey_run_sessions`.`id` = :run_session_id

	ORDER BY IF(ISNULL( ( {$order} ) ),1,0), `survey_unit_sessions`.id DESC
	
	LIMIT 1";
			$evaluate = $this->dbh->prepare($q); // should use readonly
			if(isset($conditions['minute'])):
				$evaluate->bindParam(':wait_minutes',$this->wait_minutes);
				$evaluate->bindParam(':wait_minutes2',$this->wait_minutes);
			endif;
			if(isset($conditions['date'])): 
				$evaluate->bindParam(':wait_date',$this->wait_until_date);
				$evaluate->bindParam(':wait_date2',$this->wait_until_date);
			endif;
			if(isset($conditions['time'])): 
				$evaluate->bindParam(':wait_time',$this->wait_until_time);
				$evaluate->bindParam(':wait_time2',$this->wait_until_time);
			endif;
			$evaluate->bindParam(':relative_to',$relative_to);
			$evaluate->bindParam(':relative_to2',$relative_to);			
			$evaluate->bindParam(":run_session_id", $this->run_session_id);
		

			$evaluate->execute() or die(print_r($evaluate->errorInfo(), true));
			if($evaluate->rowCount()===1):
				$temp = $evaluate->fetch();
				$result = (bool)$temp['test'];
			else:
				$result = false;
			endif;
		else:
			$result = true;
		endif;
		
		$position = $result ? $this->if_true : $this->if_false;
		
		
		if($result OR !$this->called_by_cron):
			global $run_session;
			if($run_session->session):
				$this->end();
				$run_session->runTo($position);
			endif;
		else:
			return true; // if the cron job is knocking and the wait time is not over yet, stop. we're waiting for the real user.
		endif;
	}
}