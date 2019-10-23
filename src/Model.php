<?php

namespace Pair;

abstract class Model {
	
	/**
	 * Application object.
	 * @var Application
	 */
	protected $app;
	
	/**
	 * Pagination object, started from the View.
	 * @var Pagination
	 */
	private $pagination;

	/**
	 * Database handler object.
	 * @var Database
	 */
	private $db;

	/**
	 * List of all errors tracked.
	 * @var array
	 */
	private $errors = [];
	
	/**
	 * Constructor, connects to db.
	*/
	final public function __construct() {
		
		// singleton objects
		$this->app	= Application::getInstance();
		$this->db	= Database::getInstance();

		$this->init();

	}
	
	public function __get(string $name) {
		
		return $this->$name;
		
	}

	public function __set(string $name, $value) {
	
		$this->$name = $value;
	
	}

	/**
	 * Management of unknown model's function.
	 * 
	 * @param	string	$name
	 * @param	array	$arguments
	 */
	public function __call(string $name, array $arguments) {
		
		if (Application::isDevelopmentHost()) {
	
			$backtrace = debug_backtrace();
			$this->app->logError('Method '. get_called_class() . $backtrace[0]['type'] . $name .'(), which doesn’t exist, has been called by '. $backtrace[0]['file'] .' on line '. $backtrace[0]['line']);
		
		}		

	}
	 
	/**
	 * Start function, being executed before each method. Optional.
	 */
	protected function init() {}
	 
	/**
	 * Adds an error to error list.
	 * 
	 * @param	string	Error message’s text.
	 */
	public function addError(string $message) {
		
		$this->errors[] = $message;
		
	}
	
	/**
	 * Returns text of latest error. In case of no errors, returns FALSE.
	 * 
	 * @return mixed
	 */
	public function getLastError() {
		
		return end($this->errors);
		
	}
	
	/**
	 * Returns an array with text of all errors.
	 *
	 * @return array
	 */
	public function getErrors(): array {
	
		return $this->errors;
	
	}
	
	/**
	 * Adds an event to framework’s logger, storing its chrono time.
	 * 
	 * @param	string	Event description.
	 * @param	string	Event type notice or error (default notice).
	 * @param	string	Optional additional text.
	 */
	public function logEvent(string $description, string $type='notice', string $subtext=NULL) {
		
		$logger = Logger::getInstance();
		$logger->addEvent($description, $type, $subtext);
		
	}
	
	/**
	 * AddEvent’s proxy for warning event creations.
	 *
	 * @param	string	Event description.
	 */
	public function logWarning(string $description) {
	
		$logger = Logger::getInstance();
		$logger->addWarning($description);
	
	}
	
	/**
	 * AddEvent’s proxy for error event creations.
	 *
	 * @param	string	Event description.
	 */
	public function logError(string $description) {
	
		$logger = Logger::getInstance();
		$logger->addError($description);
	
	}
	
	/**
	 * Returns list of all object specified in param, within pagination limit and sets
	 * pagination count.
	 *
	 * @param	string	Name of desired class.
	 * @param	string	Ordering db field.
	 * @param	bool	Sorting direction ASC or DESC (optional)
	 * @return	mixed[]
	 */
	public function getActiveRecordObjects(string $class, string $orderBy=NULL, bool $descOrder=FALSE): array {

		if (!class_exists($class) or !is_subclass_of($class, 'Pair\ActiveRecord')) {
			return array();
		}
		
		// set pagination count
		$this->pagination->count = $class::countAllObjects();
	
		$orderDir = $descOrder ? 'DESC' : 'ASC';
		
		$query =
			'SELECT *' .
			' FROM `' . $class::TABLE_NAME . '`' .
			($orderBy ? ' ORDER BY `' . $orderBy . '` ' . $orderDir : NULL) .
			' LIMIT ' . $this->pagination->start . ', ' . $this->pagination->limit;
	
		return $class::getObjectsByQuery($query);
	
	}
	
	/**
	 * Return empty array as default in case isn’t overloaded by children class.
	 * 
	 * @return	array
	 */
	protected function getOrderOptions() {
		
		return array();
		
	}
	
	/**
	 * Create SQL code about ORDER and LIMIT.
	 * 
	 * @return string
	 */
	protected function getOrderLimitSql() {

		$router = Router::getInstance();
		
		$ret = '';
		
		if ($router->order) {
			$orderOptions = $this->getOrderOptions();
			if (isset($orderOptions[$router->order])) { 
				$ret = ' ORDER BY ' . $orderOptions[$router->order];
			}
		}

		$ret .= ' LIMIT ' . $this->pagination->start . ', ' . $this->pagination->limit;
		
		return $ret;
		
	}
	
}
